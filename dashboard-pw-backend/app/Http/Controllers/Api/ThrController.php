<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PegawaiPw;
use App\Exports\ThrExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ThrPppkPw;
use App\Models\ExportLog;

class ThrController extends Controller
{
    private function getPppkPwThrQuery(Request $request)
    {
        $year = $request->year ?? 2026;
        $thrMonth = $request->month ?? 4;
        $search = $request->search;
        $user = auth()->user();

        $query = ThrPppkPw::where('year', $year)
            ->where('month', $thrMonth);

        // Filter by SKPD if user is operator
        if ($user && $user->role === 'operator' && !empty($user->institution)) {
            $skpdName = DB::table('skpd')->where('id_skpd', $user->institution)->value('nama_skpd');
            $query->where('skpd_name', $skpdName);
        }

        // Server-side Searching
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('skpd_name', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Calculate THR for PPPK Paruh Waktu
     * Formula: gapok * (n/12) where n is months worked since Jan 1, 2026
     */
    public function pppkPwThr(Request $request)
    {
        $year = $request->year ?? 2026;
        $thrMonth = $request->month ?? 4;
        $perPage = $request->per_page ?? 50;

        $query = $this->getPppkPwThrQuery($request);
        $totalThrAmount = (float) $query->sum('thr_amount');
        $records = $query->orderBy('skpd_name')->orderBy('nama')->paginate($perPage);

        // Map data to match frontend expectations
        $items = collect($records->items())->map(function($record) {
            return [
                'id' => $record->id,
                'employee_id' => $record->employee_id,
                'nip' => $record->nip,
                'nama' => $record->nama,
                'jabatan' => $record->jabatan,
                'skpd' => $record->skpd_name,
                'sub_giat' => $record->nama_sub_giat,
                'gapok_basis' => (float)$record->gapok_basis,
                'n_months' => (int)$record->n_months,
                'thr_amount' => (float)$record->thr_amount,
                'pptk_nama' => $record->pptk_nama,
                'pptk_nip' => $record->pptk_nip,
                'pptk_jabatan' => $record->pptk_jabatan,
                'notes' => $record->notes,
                'status' => $record->status
            ];
        });

        // Get info about n_months and generation status from any record
        $sample = ThrPppkPw::where('year', $year)->where('month', $thrMonth)->first();

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
                'total_employees' => $records->total(),
                'total_thr_amount' => $totalThrAmount,
                'year' => (int) $year,
                'thr_month' => (int) $thrMonth,
                'n_months' => $sample ? $sample->n_months : 2,
                'is_generated' => $sample ? true : false,
                'calculation_basis' => "Data Tersimpan (Database)",
                'thr_method' => DB::table('settings')->where('key', 'thr_pppk_pw_method')->value('value') ?? 'proporsional'
            ]
        ]);
    }

    /**
     * Get Aggregated Summary for SKPD
     */
    public function pppkPwThrSummary(Request $request)
    {
        $year = $request->year ?? 2026;
        $thrMonth = $request->month ?? 4;
        $user = auth()->user();

        $query = ThrPppkPw::where('year', $year)
            ->where('month', $thrMonth)
            ->select(
                'skpd_name',
                DB::raw('count(*) as total_employees_skpd'),
                DB::raw('sum(thr_amount) as total_thr_skpd')
            )
            ->groupBy('skpd_name');

        if ($user && $user->role === 'operator' && !empty($user->institution)) {
            $skpdName = DB::table('skpd')->where('id_skpd', $user->institution)->value('nama_skpd');
            $query->where('skpd_name', $skpdName);
        }

        $summary = $query->orderBy('skpd_name')->get();

        return response()->json([
            'success' => true,
            'data' => $summary,
            'meta' => [
                'total_employees' => $summary->sum('total_employees_skpd'),
                'total_thr_amount' => $summary->sum('total_thr_skpd')
            ]
        ]);
    }

    public function generateThr(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'superadmin' && $user->role !== 'operator') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        // Drastically increase limits for massive datasets
        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '1024M');

        $year = $request->year ?? 2026;
        $thrMonth = $request->month ?? 4;
        $nMonths = min((int) $thrMonth, 2);

        $thrMethod = \App\Models\Setting::where('key', 'thr_pppk_pw_method')->value('value') ?? 'proporsional';
        $thrFixedAmount = (float) (\App\Models\Setting::where('key', 'thr_pppk_pw_amount')->value('value') ?? 600000);

        // Fetch basis from payment month 2, grouping by the unique constraint keys to avoid duplicates
        $employeesQuery = DB::table('tb_payment_detail as pd')
            ->join('tb_payment as p', 'pd.payment_id', '=', 'p.id')
            ->join('rka_settings as rs', 'p.rka_id', '=', 'rs.id')
            ->join('pegawai_pw as e', 'pd.employee_id', '=', 'e.id')
            ->leftJoin('skpd as s', 'e.idskpd', '=', 's.id_skpd')
            ->leftJoin('pptk_settings as ps', 'rs.pptk_id', '=', 'ps.id')
            ->where('p.month', 2)
            ->where('p.year', $year);

        if ($user->role === 'operator') {
            $employeesQuery->where('e.idskpd', $user->institution);
        }

        $employees = $employeesQuery->select(
            'e.id as employee_id',
            'e.nip',
            'e.nama',
            'e.jabatan',
            DB::raw('MAX(pd.gaji_pokok) as gapok_basis'),
            DB::raw('COALESCE(s.nama_skpd, e.skpd) as skpd_name'),
            'rs.kode_sub_giat',
            'rs.nama_sub_giat',
            'ps.nama_pptk',
            'ps.nip_pptk',
            'ps.pangkat_pptk'
        )
        ->groupBy('e.nip', 'rs.kode_sub_giat', 'rs.nama_sub_giat', 'e.id', 'e.nama', 'e.jabatan', 'skpd_name', 'ps.nama_pptk', 'ps.nip_pptk', 'ps.pangkat_pptk')
        ->get();

        DB::beginTransaction();
        try {
            // Clear existing for this specific month/year before regenerate
            $deleteQuery = ThrPppkPw::where('year', $year)->where('month', $thrMonth);
            
            if ($user->role === 'operator') {
                $skpdNameAttr = DB::table('skpd')->where('id_skpd', $user->institution)->value('nama_skpd');
                $deleteQuery->where('skpd_name', $skpdNameAttr);
            }
            
            $deleteQuery->delete();

            $insertData = [];
            foreach ($employees as $emp) {
                $gapok = (float) $emp->gapok_basis;
                $thrAmount = ($thrMethod === 'tetap') ? $thrFixedAmount : round($gapok * ($nMonths / 12));

                $insertData[] = [
                    'employee_id' => $emp->employee_id,
                    'year' => $year,
                    'month' => $thrMonth,
                    'nip' => $emp->nip,
                    'nama' => $emp->nama,
                    'jabatan' => $emp->jabatan,
                    'skpd_name' => $emp->skpd_name,
                    'kode_sub_giat' => $emp->kode_sub_giat,
                    'nama_sub_giat' => '[' . $emp->kode_sub_giat . '] ' . $emp->nama_sub_giat,
                    'pptk_nama' => $emp->nama_pptk,
                    'pptk_nip' => $emp->nip_pptk,
                    'pptk_jabatan' => $emp->pangkat_pptk,
                    'gapok_basis' => $gapok,
                    'n_months' => $nMonths,
                    'thr_amount' => $thrAmount,
                    'status' => 'generated',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Chunked insert to save memory and speed up
                if (count($insertData) >= 500) {
                    ThrPppkPw::insert($insertData);
                    $insertData = [];
                }
            }

            if (!empty($insertData)) {
                ThrPppkPw::insert($insertData);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data THR berhasil di-generate. Total: ' . $employees->count() . ' pegawai.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false, 
                'message' => 'Gagal generate data: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function updateThrRow(Request $request, $id)
    {
        $user = auth()->user();
        if ($user->role !== 'superadmin' && $user->role !== 'operator') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $record = ThrPppkPw::findOrFail($id);

        // If operator, check if record belongs to their SKPD
        if ($user->role === 'operator') {
            $skpdName = DB::table('skpd')->where('id_skpd', $user->institution)->value('nama_skpd');
            if ($record->skpd_name !== $skpdName) {
                return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses ke data SKPD lain.'], 403);
            }
        }

        $record->update($request->only(['nama', 'nip', 'jabatan', 'thr_amount', 'notes', 'n_months', 'pptk_nama', 'pptk_nip', 'pptk_jabatan']));

        return response()->json(['success' => true, 'message' => 'Data berhasil diupdate', 'data' => $record]);
    }

    public function storeThrRow(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'superadmin' && $user->role !== 'operator') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $data = $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer',
            'nama' => 'required|string',
            'skpd_name' => 'required|string',
            'nama_sub_giat' => 'required|string',
            'thr_amount' => 'required|numeric',
            'pptk_nama' => 'nullable|string',
            'pptk_nip' => 'nullable|string',
            'pptk_jabatan' => 'nullable|string',
        ]);

        // If operator, force SKPD match
        if ($user->role === 'operator') {
            $skpdName = DB::table('skpd')->where('id_skpd', $user->institution)->value('nama_skpd');
            $data['skpd_name'] = $skpdName;
        }

        $record = ThrPppkPw::create($data);
        return response()->json(['success' => true, 'message' => 'Data berhasil ditambah', 'data' => $record]);
    }

    public function deleteThrRow($id)
    {
        $user = auth()->user();
        if ($user->role !== 'superadmin' && $user->role !== 'operator') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $record = ThrPppkPw::findOrFail($id);

        // If operator, check if record belongs to their SKPD
        if ($user->role === 'operator') {
            $skpdName = DB::table('skpd')->where('id_skpd', $user->institution)->value('nama_skpd');
            if ($record->skpd_name !== $skpdName) {
                return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses ke data SKPD lain.'], 403);
            }
        }

        $record->delete();
        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    public function verifyThr(Request $request)
    {
        return view('verify_thr', [
            'total' => $request->total,
            'period' => $request->period,
            'date' => $request->date,
            'sub_giat' => $request->sub_giat
        ]);
    }

    public function exportExcel(Request $request)
    {
        $year = $request->year ?? 2026;
        $thrMonth = $request->month ?? 4;
        $nMonths = min((int) $thrMonth, 2);

        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];
        $thrMonthName = $monthNames[$thrMonth] ?? 'Unknown';

        // Get ALL data for export, not paginated
        $records = $this->getPppkPwThrQuery($request)
            ->orderBy('skpd_name')
            ->orderBy('nama')
            ->get();
            
        // Group data for the export format
        $data = $records->groupBy('skpd_name')->map(function ($skpdItems, $skpdName) {
            $subGiatGroups = $skpdItems->groupBy(function ($item) {
                return $item->nama_sub_giat;
            })->map(function ($subGiatItems, $subGiatName) {
                return [
                    'sub_giat_name' => $subGiatName,
                    'employees' => $subGiatItems,
                    'subtotal_thr' => $subGiatItems->sum('thr_amount'),
                    'employee_count' => $subGiatItems->count()
                ];
            })->values();

            return [
                'skpd_name' => $skpdName,
                'sub_giat_groups' => $subGiatGroups,
                'total_employees_skpd' => $skpdItems->count(),
                'total_thr_skpd' => $subGiatGroups->sum('subtotal_thr')
            ];
        })->values();

        $calculationBasis = "Data Tersimpan (Database)";
        $dataArray = json_decode(json_encode($data), true);

        // Record Export Log
        if (auth()->check()) {
            ExportLog::create([
                'user_id' => auth()->id(),
                'report_name' => 'THR PPPK Paruh Waktu',
                'action' => 'Ekspor Excel',
                'description' => "Periode: {$thrMonthName} {$year}",
                'ip_address' => request()->ip(),
            ]);
        }

        return Excel::download(
            new ThrExport($dataArray, $year, $nMonths, $thrMonthName, $calculationBasis),
            "THR_PPPK_PW_{$year}_{$thrMonth}.xlsx"
        );
    }

    public function exportPdf(Request $request)
    {
        // Drastically increase limits for massive datasets
        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '2048M');

        $year = $request->year ?? 2026;
        $thrMonth = $request->month ?? 4;
        $nMonths = min((int) $thrMonth, 2);

        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];
        $thrMonthName = $monthNames[$thrMonth] ?? 'Unknown';

        // Get ALL data for export, not paginated
        $records = $this->getPppkPwThrQuery($request)
            ->orderBy('skpd_name')
            ->orderBy('nama')
            ->get();
            
        // Group data for the export format: SKPD -> PPTK -> Sub Kegiatan -> Employees
        $groupedData = $records->groupBy('skpd_name')->map(function ($skpdItems, $skpdName) {
            return [
                'skpd_name' => $skpdName,
                'pptk_groups' => $skpdItems->groupBy(function ($item) {
                    return $item->pptk_nama ?: 'Tanpa PPTK';
                })->map(function ($pptkItems, $pptkName) {
                    $firstItem = $pptkItems->first();
                    return [
                        'pptk_nama' => $pptkName,
                        'pptk_nip' => $firstItem->pptk_nip,
                        'pptk_jabatan' => $firstItem->pptk_jabatan,
                        'sub_giat_groups' => $pptkItems->groupBy('nama_sub_giat')->map(function ($subGiatItems, $subGiatName) {
                            return [
                                'sub_giat_name' => $subGiatName,
                                'employees' => $subGiatItems,
                                'subtotal_thr' => $subGiatItems->sum('thr_amount'),
                                'employee_count' => $subGiatItems->count()
                            ];
                        })->values(),
                        'total_pptk_thr' => $pptkItems->sum('thr_amount'),
                        'total_pptk_employees' => $pptkItems->count()
                    ];
                })->values(),
                'total_employees_skpd' => $skpdItems->count(),
                'total_thr_skpd' => $skpdItems->sum('thr_amount')
            ];
        })->values();

        $dataArray = json_decode(json_encode($groupedData), true);
        $totalThrAmount = $records->sum('thr_amount');

        $printDate = now()->format('d/m/Y H:i');

        // Fetch Signature Data
        $user = auth()->user();
        $querySettings = DB::table('report_settings');
        if ($user && !empty($user->institution)) {
            $querySettings->where('skpdid', $user->institution);
        }
        $reportSettings = $querySettings->first() ?: DB::table('report_settings')->first();

        if (!$reportSettings) {
            $reportSettings = (object) [
                'nama_kepala' => null,
                'nip_kepala' => null,
                'jabatan_kepala' => null
            ];
        }

        // Prepare pool requests for parallel QR code fetching with Caching
        $qrRequests = [];
        $qrCache = [];

        foreach ($dataArray as $sIndex => $skpd) {
            foreach ($skpd['pptk_groups'] as $pIndex => $pptk) {
                foreach ($pptk['sub_giat_groups'] as $gIndex => $subGiat) {
                    $subTotalFormatted = number_format($subGiat['subtotal_thr'], 0, ',', '.');
                    $params = [
                        'total' => $subTotalFormatted,
                        'period' => $thrMonthName . ' ' . $year,
                        'date' => $printDate,
                        'sub_giat' => $subGiat['sub_giat_name']
                    ];
                    $verifyUrl = url('/api/verify-thr') . "?" . http_build_query($params);
                    $cacheKey = md5($verifyUrl);

                    if (isset($qrCache[$cacheKey])) {
                        $dataArray[$sIndex]['pptk_groups'][$pIndex]['sub_giat_groups'][$gIndex]['qr_code'] = $qrCache[$cacheKey];
                        continue;
                    }

                    // Use a larger size (200x200) for better scanability with long URLs
                    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($verifyUrl);
                    $qrRequests["{$sIndex}_{$pIndex}_{$gIndex}"] = $qrUrl;
                }
            }
        }

        // Execute parallel requests in CHUNKS to prevent connection exhaustion
        if (!empty($qrRequests)) {
            $chunks = array_chunk($qrRequests, 15, true); // Process 15 at a time

            foreach ($chunks as $chunk) {
                $responses = \Illuminate\Support\Facades\Http::pool(
                    fn($pool) =>
                    collect($chunk)->map(fn($url, $key) => $pool->as($key)->timeout(10)->get($url))
                );

                foreach ($responses as $key => $res) {
                    if ($res->successful()) {
                        [$sIndex, $pIndex, $gIndex] = explode('_', $key);
                        $base64 = 'data:image/png;base64,' . base64_encode($res->body());
                        $dataArray[$sIndex]['pptk_groups'][$pIndex]['sub_giat_groups'][$gIndex]['qr_code'] = $base64;

                        $subGiat = $dataArray[$sIndex]['pptk_groups'][$pIndex]['sub_giat_groups'][$gIndex];
                        $verifyUrl = "https://sipgaji.my.id/api/verify-thr?" . http_build_query([
                            'total' => number_format($subGiat['subtotal_thr'], 0, ',', '.'),
                            'period' => $thrMonthName . ' ' . $year,
                            'date' => $printDate,
                            'sub_giat' => $subGiat['sub_giat_name']
                        ]);
                        $qrCache[md5($verifyUrl)] = $base64;
                    }
                }
                usleep(100000); // Small 100ms delay between chunks to be nice to the API
            }
        }

        try {
            $pdfContent = Pdf::loadView('reports.thr_pppk_pw', [
                'data' => $dataArray,
                'year' => $year,
                'nMonths' => $nMonths,
                'thrMonthName' => $thrMonthName,
                'totalAmount' => $totalThrAmount,
                'calculationBasis' => "Data Tersimpan (Database)",
                'thrMethod' => 'proporsional',
                'printDate' => $printDate,
                'reportSettings' => $reportSettings
            ])->setPaper('a4', 'landscape')->setOption('isPhpEnabled', true)->output();

            // Record Export Log
            if (auth()->check()) {
                ExportLog::create([
                    'user_id' => auth()->id(),
                    'report_name' => 'THR PPPK Paruh Waktu',
                    'action' => 'Cetak PDF',
                    'description' => "Periode: {$thrMonthName} {$year}",
                    'ip_address' => request()->ip(),
                ]);
            }

            return response()->streamDownload(
                fn() => print ($pdfContent),
                "THR_PPPK_PW_{$year}_{$thrMonth}.pdf",
                ['Content-Type' => 'application/pdf']
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal merender PDF: ' . $e->getMessage()
            ], 500);
        }
    }
}
