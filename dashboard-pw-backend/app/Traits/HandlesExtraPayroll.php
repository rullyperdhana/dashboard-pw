<?php

namespace App\Traits;

use App\Models\ExtraPayrollPppkPw;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;
use App\Exports\MissingPayrollExport;
use Maatwebsite\Excel\Facades\Excel;

trait HandlesExtraPayroll
{
    /**
     * Get the payroll type for the controller (e.g., 'thr' or 'gaji13')
     */
    abstract protected function getPayrollType(): string;

    /**
     * Get the session label for responses (e.g., 'THR' or 'Gaji 13')
     */
    abstract protected function getPayrollLabel(): string;

    /**
     * Get the basis month setting key (e.g., 'thr_pppk_pw_basis_month')
     */
    abstract protected function getBasisMonthSettingKey(): string;

    protected function getBasicQuery(Request $request)
    {
        $year = $request->year ?? 2026;
        $month = $request->month ?? ($this->getPayrollType() === 'thr' ? 4 : 6);
        $search = $request->search;
        $user = auth()->user();

        $query = ExtraPayrollPppkPw::where('type', $this->getPayrollType())
            ->where('year', $year)
            ->where('month', $month);

        // Filter by SKPD if user is operator (Prefix Match for UPT support)
        if ($user && $user->role === 'operator' && !empty($user->institution)) {
            $skpdName = DB::table('skpd')->where('id_skpd', $user->institution)->value('nama_skpd');
            $query->where('skpd_name', 'like', $skpdName . '%');
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

    public function index(Request $request)
    {
        $year = $request->year ?? 2026;
        $month = $request->month ?? ($this->getPayrollType() === 'thr' ? 4 : 6);
        $perPage = $request->per_page ?? 50;

        $query = $this->getBasicQuery($request);
        $totalAmount = (float) $query->sum('payroll_amount');
        $records = $query->orderBy('skpd_name')->orderBy('nama')->paginate($perPage);

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
                'payroll_amount' => (float)$record->payroll_amount,
                'pptk_nama' => $record->pptk_nama,
                'pptk_nip' => $record->pptk_nip,
                'pptk_jabatan' => $record->pptk_jabatan,
                'notes' => $record->notes,
                'status' => $record->status
            ];
        });

        $sample = ExtraPayrollPppkPw::where('type', $this->getPayrollType())
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
                'per_page' => $records->perPage(),
                'total' => $records->total(),
                'total_employees' => $records->total(),
                'total_amount' => $totalAmount,
                'year' => (int) $year,
                'month' => (int) $month,
                'n_months' => $sample ? $sample->n_months : 2,
                'is_generated' => $sample ? true : false,
                'calculation_basis' => "Data Tersimpan (Database)",
                'method' => Setting::where('key', $this->getPayrollType() . '_pppk_pw_method')->value('value') ?? 'proporsional'
            ]
        ]);
    }

    public function summary(Request $request)
    {
        $year = $request->year ?? 2026;
        $month = $request->month ?? ($this->getPayrollType() === 'thr' ? 4 : 6);
        $user = auth()->user();

        $query = ExtraPayrollPppkPw::where('type', $this->getPayrollType())
            ->where('year', $year)
            ->where('month', $month)
            ->select(
                'skpd_name',
                DB::raw('count(*) as total_employees_skpd'),
                DB::raw('sum(payroll_amount) as total_amount_skpd')
            )
            ->groupBy('skpd_name');

        if ($user && $user->role === 'operator' && !empty($user->institution)) {
            $skpdName = DB::table('skpd')->where('id_skpd', $user->institution)->value('nama_skpd');
            $query->where('skpd_name', 'like', $skpdName . '%');
        }

        $summary = $query->orderBy('skpd_name')->get();

        return response()->json([
            'success' => true,
            'data' => $summary,
            'meta' => [
                'total_employees' => $summary->sum('total_employees_skpd'),
                'total_amount' => $summary->sum('total_amount_skpd')
            ]
        ]);
    }

    public function generate(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'superadmin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak. Fitur ini hanya untuk Superadmin.'], 403);
        }

        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '1024M');

        $year = $request->year ?? 2026;
        $payMonth = $request->month ?? ($this->getPayrollType() === 'thr' ? 4 : 6);
        
        // Configuration
        $basisMonth = (int) (Setting::where('key', $this->getBasisMonthSettingKey())->value('value') ?? 2);
        $nMonths = min((int) $payMonth, 2); // Policy specific n/12? Might need to generalize further
        
        $method = Setting::where('key', $this->getPayrollType() . '_pppk_pw_method')->value('value') ?? 'proporsional';
        $fixedAmount = (float) (Setting::where('key', $this->getPayrollType() . '_pppk_pw_amount')->value('value') ?? 600000);

        $employees = DB::table('tb_payment_detail as pd')
            ->join('tb_payment as p', 'pd.payment_id', '=', 'p.id')
            ->join('rka_settings as rs', 'p.rka_id', '=', 'rs.id')
            ->join('pegawai_pw as e', 'pd.employee_id', '=', 'e.id')
            ->leftJoin('skpd as s', 'e.idskpd', '=', 's.id_skpd')
            ->leftJoin('pptk_settings as ps', 'rs.pptk_id', '=', 'ps.id')
            ->where('p.month', $basisMonth)
            ->where('p.year', $year)
            ->select(
                'e.id as employee_id',
                'e.nip',
                'e.nama',
                'e.jabatan',
                DB::raw('MAX(pd.gaji_pokok) as gapok_basis'),
                DB::raw("CASE WHEN e.upt IS NOT NULL THEN CONCAT(COALESCE(s.nama_skpd, e.skpd), ' - ', e.upt) ELSE COALESCE(s.nama_skpd, e.skpd) END as skpd_name"),
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
            ExtraPayrollPppkPw::where('type', $this->getPayrollType())
                ->where('year', $year)
                ->where('month', $payMonth)
                ->delete();

            $insertData = [];
            foreach ($employees as $emp) {
                $gapok = (float) $emp->gapok_basis;
                $amount = ($method === 'tetap') ? $fixedAmount : round($gapok * ($nMonths / 12));

                $insertData[] = [
                    'type' => $this->getPayrollType(),
                    'employee_id' => $emp->employee_id,
                    'year' => $year,
                    'month' => $payMonth,
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
                    'payroll_amount' => $amount,
                    'status' => 'generated',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($insertData) >= 500) {
                    ExtraPayrollPppkPw::insert($insertData);
                    $insertData = [];
                }
            }

            if (!empty($insertData)) {
                ExtraPayrollPppkPw::insert($insertData);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => "Data {$this->getPayrollLabel()} berhasil di-generate. Total: " . $employees->count() . ' pegawai.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Gagal generate data: ' . $e->getMessage()], 500);
        }
    }

    public function storeRow(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'superadmin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak. Fitur ini hanya untuk Superadmin.'], 403);
        }

        $data = $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer',
            'nama' => 'required|string',
            'skpd_name' => 'required|string',
            'nama_sub_giat' => 'required|string',
            'payroll_amount' => 'required|numeric',
        ]);

        $data['type'] = $this->getPayrollType();
        $record = ExtraPayrollPppkPw::create($data);
        return response()->json(['success' => true, 'message' => 'Data berhasil ditambah', 'data' => $record]);
    }

    public function updateRow(Request $request, $id)
    {
        $user = auth()->user();
        if ($user->role !== 'superadmin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak. Fitur ini hanya untuk Superadmin.'], 403);
        }

        $record = ExtraPayrollPppkPw::where('type', $this->getPayrollType())->findOrFail($id);
        $record->update($request->only(['nama', 'nip', 'jabatan', 'payroll_amount', 'notes', 'n_months', 'pptk_nama', 'pptk_nip', 'pptk_jabatan']));

        return response()->json(['success' => true, 'message' => 'Data berhasil diupdate', 'data' => $record]);
    }

    public function deleteRow($id)
    {
        $user = auth()->user();
        if ($user->role !== 'superadmin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak. Fitur ini hanya untuk Superadmin.'], 403);
        }

        $record = ExtraPayrollPppkPw::where('type', $this->getPayrollType())->findOrFail($id);
        $record->delete();
        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    public function missing(Request $request)
    {
        $year = $request->year ?? 2026;
        $payMonth = $request->month ?? ($this->getPayrollType() === 'thr' ? 4 : 6);
        $basisMonth = (int) (Setting::where('key', $this->getBasisMonthSettingKey())->value('value') ?? 2);
        
        $perPage = $request->per_page ?? 50;
        $search = $request->search;
        $user = auth()->user();

        // 1. Get IDs of employees who ALREADY HAVE payroll data for this period
        $existingIds = ExtraPayrollPppkPw::where('type', $this->getPayrollType())
            ->where('year', $year)
            ->where('month', $payMonth)
            ->whereNotNull('employee_id')
            ->pluck('employee_id');

        // 2. Query ALL employees from pegawai_pw who are NOT in $existingIds
        $query = DB::table('pegawai_pw as e')
            ->leftJoin('skpd as s', 'e.idskpd', '=', 's.id_skpd')
            // Join with payments to see if they HAVE basis salary
            ->leftJoin(DB::raw("(
                SELECT pd.employee_id, MAX(pd.gaji_pokok) as gapok_basis
                FROM tb_payment_detail pd
                JOIN tb_payment p ON pd.payment_id = p.id
                WHERE p.month = $basisMonth AND p.year = $year
                GROUP BY pd.employee_id
            ) as basis_salary"), 'e.id', '=', 'basis_salary.employee_id')
            ->whereNotIn('e.id', $existingIds)
            ->select(
                'e.id',
                'e.nip',
                'e.nama',
                'e.jabatan',
                DB::raw("CASE WHEN e.upt IS NOT NULL THEN CONCAT(COALESCE(s.nama_skpd, e.skpd), ' - ', e.upt) ELSE COALESCE(s.nama_skpd, e.skpd) END as skpd_name"),
                'basis_salary.gapok_basis',
                DB::raw("CASE WHEN basis_salary.gapok_basis IS NULL THEN 'Tidak ada gaji basis (" . $basisMonth . "/$year)' ELSE 'Belum ter-generate (Teknis)' END as reason")
            );

        // Filter by SKPD if user is operator
        if ($user && $user->role === 'operator' && !empty($user->institution)) {
            $skpdName = DB::table('skpd')->where('id_skpd', $user->institution)->value('nama_skpd');
            $query->where(DB::raw("CASE WHEN e.upt IS NOT NULL THEN CONCAT(COALESCE(s.nama_skpd, e.skpd), ' - ', e.upt) ELSE COALESCE(s.nama_skpd, e.skpd) END"), 'like', $skpdName . '%');
        }

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('e.nama', 'like', "%{$search}%")
                  ->orWhere('e.nip', 'like', "%{$search}%");
            });
        }

        $missing = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $missing->items(),
            'meta' => [
                'current_page' => $missing->currentPage(),
                'last_page' => $missing->lastPage(),
                'per_page' => $missing->perPage(),
                'total' => $missing->total(),
                'basis_month' => $basisMonth,
                'pay_month' => $payMonth
            ]
        ]);
    }

    public function exportMissing(Request $request)
    {
        $year = $request->year ?? 2026;
        $payMonth = $request->month ?? ($this->getPayrollType() === 'thr' ? 4 : 6);
        $basisMonth = (int) (Setting::where('key', $this->getBasisMonthSettingKey())->value('value') ?? 2);
        
        $user = auth()->user();

        $existingIds = ExtraPayrollPppkPw::where('type', $this->getPayrollType())
            ->where('year', $year)
            ->where('month', $payMonth)
            ->whereNotNull('employee_id')
            ->pluck('employee_id');

        $query = DB::table('pegawai_pw as e')
            ->leftJoin('skpd as s', 'e.idskpd', '=', 's.id_skpd')
            ->leftJoin(DB::raw("(
                SELECT pd.employee_id, MAX(pd.gaji_pokok) as gapok_basis
                FROM tb_payment_detail pd
                JOIN tb_payment p ON pd.payment_id = p.id
                WHERE p.month = $basisMonth AND p.year = $year
                GROUP BY pd.employee_id
            ) as basis_salary"), 'e.id', '=', 'basis_salary.employee_id')
            ->whereNotIn('e.id', $existingIds)
            ->select(
                'e.nip',
                'e.nama',
                'e.jabatan',
                DB::raw("CASE WHEN e.upt IS NOT NULL THEN CONCAT(COALESCE(s.nama_skpd, e.skpd), ' - ', e.upt) ELSE COALESCE(s.nama_skpd, e.skpd) END as skpd_name"),
                'basis_salary.gapok_basis',
                DB::raw("CASE WHEN basis_salary.gapok_basis IS NULL THEN 'Gaji basis ($basisMonth/$year) tidak ditemukan' ELSE 'Belum ter-generate (Sistem)' END as reason")
            );

        if ($user && $user->role === 'operator' && !empty($user->institution)) {
            $skpdName = DB::table('skpd')->where('id_skpd', $user->institution)->value('nama_skpd');
            $query->where(DB::raw("CASE WHEN e.upt IS NOT NULL THEN CONCAT(COALESCE(s.nama_skpd, e.skpd), ' - ', e.upt) ELSE COALESCE(s.nama_skpd, e.skpd) END"), 'like', $skpdName . '%');
        }

        $data = $query->get();

        $dataArray = json_decode(json_encode($data), true);
        $title = "DAFTAR PEGAWAI TERDAMPAR (TIDAK MASUK LAPORAN) - " . strtoupper($this->getPayrollLabel());

        return Excel::download(
            new MissingPayrollExport($dataArray, $title),
            "DAFTAR_TIDAK_TERBENTUK_" . strtoupper($this->getPayrollType()) . "_{$year}_{$payMonth}.xlsx"
        );
    }

    protected function getFormattedGroupedData(Request $request)
    {
        $records = $this->getBasicQuery($request)
            ->orderBy('skpd_name')
            ->orderBy('nama')
            ->get();
            
        // Group data for the export format: SKPD -> PPTK -> Sub Kegiatan -> Employees
        return $records->groupBy('skpd_name')->map(function ($skpdItems, $skpdName) {
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
                                'subtotal_thr' => $subGiatItems->sum('payroll_amount'),
                                'employee_count' => $subGiatItems->count()
                            ];
                        })->values(),
                        'total_pptk_thr' => $pptkItems->sum('payroll_amount'),
                        'total_pptk_employees' => $pptkItems->count()
                    ];
                })->values(),
                'total_employees_skpd' => $skpdItems->count(),
                'total_thr_skpd' => $skpdItems->sum('payroll_amount')
            ];
        })->values();
    }
}
