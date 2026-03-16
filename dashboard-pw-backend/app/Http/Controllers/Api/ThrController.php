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
    /**
     * Calculate THR for PPPK Paruh Waktu
     * Formula: gapok * (n/12) where n is months worked since Jan 1, 2026
     */
    public function pppkPwThr(Request $request)
    {
        $year = $request->year ?? 2026;
        $thrMonth = $request->month ?? 4;
        $user = auth()->user();

        // Check if data already exists in tb_thr_pppk_pw
        $query = ThrPppkPw::where('year', $year)
            ->where('month', $thrMonth);

        // Filter by SKPD if user is operator
        if ($user && $user->role === 'operator' && !empty($user->institution)) {
            $skpdName = DB::table('skpd')->where('id_skpd', $user->institution)->value('nama_skpd');
            $query->where('skpd_name', $skpdName);
        }

        $records = $query->orderBy('skpd_name')->orderBy('nama')->get();

        if ($records->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'meta' => [
                    'year' => (int) $year,
                    'thr_month' => (int) $thrMonth,
                    'total_employees' => 0,
                    'total_thr_amount' => 0,
                    'is_generated' => false
                ]
            ]);
        }

        // Group the persistent records for the frontend
        $grouped = $records->groupBy('skpd_name')->map(function ($skpdItems, $skpdName) {
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

        // Find n_months and calculation_basis from the first record
        $first = $records->first();

        return response()->json([
            'success' => true,
            'data' => $grouped,
            'meta' => [
                'year' => (int) $year,
                'thr_month' => (int) $thrMonth,
                'n_months' => $first->n_months,
                'calculation_basis' => "Data Tersimpan (Database)",
                'total_employees' => $records->count(),
                'total_thr_amount' => $records->sum('thr_amount'),
                'is_generated' => true
            ]
        ]);
    }

    public function generateThr(Request $request)
    {
        if (auth()->user()->role !== 'superadmin') {
            return response()->json(['success' => false, 'message' => 'Hanya Superadmin yang dapat melakukan sinkronisasi data THR.'], 403);
        }

        $year = $request->year ?? 2026;
        $thrMonth = $request->month ?? 4;
        $nMonths = min((int) $thrMonth, 2);

        $thrMethod = \App\Models\Setting::where('key', 'thr_pppk_pw_method')->value('value') ?? 'proporsional';
        $thrFixedAmount = (float) (\App\Models\Setting::where('key', 'thr_pppk_pw_amount')->value('value') ?? 600000);

        // Fetch basis from payment month 2
        $employees = DB::table('tb_payment_detail as pd')
            ->join('tb_payment as p', 'pd.payment_id', '=', 'p.id')
            ->join('rka_settings as rs', 'p.rka_id', '=', 'rs.id')
            ->join('pegawai_pw as e', 'pd.employee_id', '=', 'e.id')
            ->leftJoin('skpd as s', 'e.idskpd', '=', 's.id_skpd')
            ->where('p.month', 2)
            ->where('p.year', $year)
            ->select(
                'e.id as employee_id',
                'e.nip',
                'e.nama',
                'e.jabatan',
                'pd.gaji_pokok as gapok_basis',
                DB::raw('COALESCE(s.nama_skpd, e.skpd) as skpd_name'),
                'rs.kode_sub_giat',
                'rs.nama_sub_giat'
            )
            ->get();

        DB::beginTransaction();
        try {
            // Clear existing for this specific month/year before regenerate
            ThrPppkPw::where('year', $year)->where('month', $thrMonth)->delete();

            foreach ($employees as $emp) {
                $gapok = (float) $emp->gapok_basis;
                $thrAmount = ($thrMethod === 'tetap') ? $thrFixedAmount : round($gapok * ($nMonths / 12));

                ThrPppkPw::create([
                    'employee_id' => $emp->employee_id,
                    'year' => $year,
                    'month' => $thrMonth,
                    'nip' => $emp->nip,
                    'nama' => $emp->nama,
                    'jabatan' => $emp->jabatan,
                    'skpd_name' => $emp->skpd_name,
                    'kode_sub_giat' => $emp->kode_sub_giat,
                    'nama_sub_giat' => '[' . $emp->kode_sub_giat . '] ' . $emp->nama_sub_giat,
                    'gapok_basis' => $gapok,
                    'n_months' => $nMonths,
                    'thr_amount' => $thrAmount,
                    'status' => 'generated'
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Data THR berhasil di-generate.']);
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
        if (auth()->user()->role !== 'superadmin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $record = ThrPppkPw::findOrFail($id);
        $record->update($request->only(['nama', 'nip', 'jabatan', 'thr_amount', 'notes', 'n_months']));

        return response()->json(['success' => true, 'message' => 'Data berhasil diupdate', 'data' => $record]);
    }

    public function storeThrRow(Request $request)
    {
        if (auth()->user()->role !== 'superadmin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $data = $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer',
            'nama' => 'required|string',
            'skpd_name' => 'required|string',
            'nama_sub_giat' => 'required|string',
            'thr_amount' => 'required|numeric',
        ]);

        $record = ThrPppkPw::create($request->all());
        return response()->json(['success' => true, 'message' => 'Data berhasil ditambah', 'data' => $record]);
    }

    public function deleteThrRow($id)
    {
        if (auth()->user()->role !== 'superadmin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $record = ThrPppkPw::findOrFail($id);
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

        $response = $this->pppkPwThr($request);
        $data = $response->getData()->data;
        $calculationBasis = $response->getData()->meta->calculation_basis ?? "Gaji Pokok Pebruari ({$nMonths}/12)";
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

        $response = $this->pppkPwThr($request);
        $data = $response->getData()->data;
        $dataArray = json_decode(json_encode($data), true);

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
            foreach ($skpd['sub_giat_groups'] as $gIndex => $subGiat) {
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
                    $dataArray[$sIndex]['sub_giat_groups'][$gIndex]['qr_code'] = $qrCache[$cacheKey];
                    continue;
                }

                // Use a larger size (200x200) for better scanability with long URLs
                $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($verifyUrl);
                $qrRequests["{$sIndex}_{$gIndex}"] = $qrUrl;
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
                        [$sIndex, $gIndex] = explode('_', $key);
                        $base64 = 'data:image/png;base64,' . base64_encode($res->body());
                        $dataArray[$sIndex]['sub_giat_groups'][$gIndex]['qr_code'] = $base64;

                        $subGiat = $dataArray[$sIndex]['sub_giat_groups'][$gIndex];
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
                'totalAmount' => $response->getData()->meta->total_thr_amount,
                'calculationBasis' => $response->getData()->meta->calculation_basis,
                'thrMethod' => $response->getData()->meta->thr_method,
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
