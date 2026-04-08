<?php

namespace App\Http\Controllers\Api;

use App\Exports\UnpaidDataExport;
use App\Exports\PaidSkpdExport;
use App\Exports\PaidEmployeesExport;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payment;
use App\Models\Skpd;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CombinedAllowanceExport;


use App\Traits\HasExportLogs;

class ReportController extends Controller
{
    use HasExportLogs;
    /**
     * Analytics Hub data
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // 1. Budget Summary (Annual)
        $currentYear = date('Y');
        $annualBudgetQuery = DB::table('tb_payment_detail')
            ->join('pegawai_pw', 'tb_payment_detail.employee_id', '=', 'pegawai_pw.id')
            ->join('tb_payment', 'tb_payment_detail.payment_id', '=', 'tb_payment.id')
            ->where('tb_payment.year', $currentYear);

        if ($user->role !== 'superadmin') {
            $annualBudgetQuery->whereIn('pegawai_pw.idskpd', $user->getAccessibleSkpds());
        }

        $totalAnnual = $annualBudgetQuery->sum('tb_payment_detail.total_amoun');

        // 2. Average per Employee (Month-to-Date Aggregate)
        // We look for the most recent month that has a significant amount of data (e.g., > 100 records)
        // to avoid partial months like February 2026 which only has 15 records.
        $latestFullMonth = DB::table('tb_payment')
            ->join('tb_payment_detail', 'tb_payment.id', '=', 'tb_payment_detail.payment_id')
            ->select('month', 'year', DB::raw('COUNT(tb_payment_detail.id) as record_count'))
            ->groupBy('month', 'year')
            ->having('record_count', '>', 50) // Threshold to consider a month "complete enough"
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();

        // Fallback to absolute latest if no month meets threshold
        if (!$latestFullMonth) {
            $latestFullMonth = DB::table('tb_payment')
                ->join('tb_payment_detail', 'tb_payment.id', '=', 'tb_payment_detail.payment_id')
                ->select('month', 'year')
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->first();
        }

        $avgPerEmployee = 0;
        if ($latestFullMonth) {
            $monthlyDetailQuery = DB::table('tb_payment_detail')
                ->join('tb_payment', 'tb_payment_detail.payment_id', '=', 'tb_payment.id')
                ->join('pegawai_pw', 'tb_payment_detail.employee_id', '=', 'pegawai_pw.id')
                ->where('tb_payment.month', $latestFullMonth->month)
                ->where('tb_payment.year', $latestFullMonth->year);

            if ($user->role !== 'superadmin') {
                $monthlyDetailQuery->whereIn('pegawai_pw.idskpd', $user->getAccessibleSkpds());
            }

            $count = (clone $monthlyDetailQuery)->count();
            $sum = $monthlyDetailQuery->sum('tb_payment_detail.total_amoun');
            $avgPerEmployee = $count > 0 ? $sum / $count : 0;
        }

        // 3. Institutional Budget Performance (Aggregate of all payments in selected month)
        $skpdPerformanceQuery = DB::table('pegawai_pw')
            ->join('skpd', 'pegawai_pw.idskpd', '=', 'skpd.id_skpd')
            ->join('tb_payment_detail', 'pegawai_pw.id', '=', 'tb_payment_detail.employee_id')
            ->join('tb_payment', 'tb_payment_detail.payment_id', '=', 'tb_payment.id')
            ->where('tb_payment.month', $latestFullMonth ? $latestFullMonth->month : date('n'))
            ->where('tb_payment.year', $latestFullMonth ? $latestFullMonth->year : date('Y'))
            ->select(
                'skpd.nama_skpd',
                'skpd.kode_skpd',
                DB::raw('COUNT(DISTINCT pegawai_pw.id) as staff_count'),
                DB::raw('SUM(tb_payment_detail.total_amoun) as total_budget')
            )
            ->groupBy('skpd.id_skpd', 'skpd.nama_skpd', 'skpd.kode_skpd')
            ->orderByDesc('total_budget');

        if ($user->role !== 'superadmin') {
            $skpdPerformanceQuery->whereIn('skpd.id_skpd', $user->getAccessibleSkpds());
        }

        $skpdPerformance = $skpdPerformanceQuery->get();

        // 4. Growth Trend (12 Months) — PPPK-PW only
        $growthTrendQuery = DB::table('tb_payment_detail')
            ->join('pegawai_pw', 'tb_payment_detail.employee_id', '=', 'pegawai_pw.id')
            ->join('tb_payment', 'tb_payment_detail.payment_id', '=', 'tb_payment.id')
            ->select(
                'tb_payment.month',
                'tb_payment.year',
                DB::raw('SUM(tb_payment_detail.total_amoun) as total'),
                DB::raw('COUNT(DISTINCT tb_payment_detail.employee_id) as employees')
            )
            ->groupBy('tb_payment.year', 'tb_payment.month')
            ->orderBy('tb_payment.year', 'desc')
            ->orderBy('tb_payment.month', 'desc')
            ->limit(12);

        if ($user->role !== 'superadmin') {
            $growthTrendQuery->whereIn('pegawai_pw.idskpd', $user->getAccessibleSkpds());
        }

        $growthTrend = $growthTrendQuery->get()
            ->reverse()
            ->values()
            ->map(function ($item) {
                $months = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];
                return [
                    'label' => ($months[$item->month] ?? $item->month) . ' ' . $item->year,
                    'value' => (float) $item->total,
                    'month' => $months[$item->month] ?? $item->month,
                    'total' => (float) $item->total,
                    'employees' => (int) $item->employees,
                ];
            });

        // 5. Top Earners (Historical Highest)
        $topEarnersQuery = DB::table('tb_payment_detail')
            ->join('pegawai_pw', 'tb_payment_detail.employee_id', '=', 'pegawai_pw.id')
            ->join('skpd', 'pegawai_pw.idskpd', '=', 'skpd.id_skpd')
            ->select(
                'pegawai_pw.nama',
                'pegawai_pw.nip',
                'pegawai_pw.jabatan',
                'skpd.nama_skpd',
                DB::raw('MAX(tb_payment_detail.total_amoun) as total_amoun')
            )
            ->groupBy('pegawai_pw.id', 'pegawai_pw.nama', 'pegawai_pw.nip', 'pegawai_pw.jabatan', 'skpd.nama_skpd')
            ->orderByDesc('total_amoun')
            ->limit(10);

        if ($user->role !== 'superadmin') {
            $topEarnersQuery->whereIn('pegawai_pw.idskpd', $user->getAccessibleSkpds());
        }

        $topEarners = $topEarnersQuery->get();

        $retirementMonitor = $this->getRetirementMonitor($user);

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'annual_budget' => (float) $totalAnnual,
                    'avg_per_employee' => (float) $avgPerEmployee,
                    'active_units' => count($skpdPerformance),
                ],
                'funding' => DB::table('pegawai_pw')
                    ->select('sumber_dana', DB::raw('COUNT(*) as total'))
                    ->groupBy('sumber_dana')
                    ->get(),
                'performance' => $skpdPerformance,
                'growth' => $growthTrend,
                'trend' => $growthTrend, // Payroll Expenditure Trend
                'top_earners' => $topEarners,
                'retirement_monitor' => $retirementMonitor,
            ]
        ]);
    }

    /**
     * List SKPD that haven't created payroll for current month
     */
    public function unpaidSkpds(Request $request)
    {
        $month = $request->month ?? date('n');
        $year = $request->year ?? date('Y');

        // Only include SKPDs that have at least one employee in pegawai_pw
        $query = Skpd::where('is_skpd', 1)
            ->whereIn('id_skpd', function($q) {
                $q->select('idskpd')->from('pegawai_pw');
            });
        $user = auth()->user();
        if ($user->role !== 'superadmin') {
            $query->whereIn('id_skpd', $user->getAccessibleSkpds());
        }
        $allSkpds = $query->get();

        $paidSkpdIds = DB::table('tb_payment_detail')
            ->join('pegawai_pw', 'tb_payment_detail.employee_id', '=', 'pegawai_pw.id')
            ->join('tb_payment', 'tb_payment_detail.payment_id', '=', 'tb_payment.id')
            ->where('tb_payment.month', $month)
            ->where('tb_payment.year', $year)
            ->pluck('pegawai_pw.idskpd')
            ->unique()
            ->toArray();

        $unpaid = $allSkpds->filter(function ($skpd) use ($paidSkpdIds) {
            return !in_array($skpd->id_skpd, $paidSkpdIds);
        })->values();

        return response()->json([
            'success' => true,
            'data' => $unpaid,
            'meta' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'total_skpd' => $allSkpds->count(),
                'paid_count' => count($paidSkpdIds),
                'unpaid_count' => $unpaid->count()
            ]
        ]);
    }

    /**
     * List UPT that haven't created payroll for current month
     */
    public function unpaidUpts(Request $request)
    {
        $month = $request->month ?? date('n');
        $year = $request->year ?? date('Y');
        $user = $request->user();

        // 1. Get Master List of UPTs involved in payroll (have employees)
        $masterQuery = DB::table('pegawai_pw')
            ->join('skpd', 'pegawai_pw.idskpd', '=', 'skpd.id_skpd')
            ->whereNotNull('pegawai_pw.upt')
            ->where('pegawai_pw.upt', '!=', '')
            ->select('pegawai_pw.upt', 'skpd.nama_skpd', 'pegawai_pw.idskpd')
            ->distinct();

        if ($user->role !== 'superadmin') {
            $masterQuery->whereIn('pegawai_pw.idskpd', $user->getAccessibleSkpds());
        }

        $allUpts = $masterQuery->get();

        // 2. Get Paid UPTs for the month
        $paidQuery = DB::table('tb_payment_detail')
            ->join('pegawai_pw', 'tb_payment_detail.employee_id', '=', 'pegawai_pw.id')
            ->join('tb_payment', 'tb_payment_detail.payment_id', '=', 'tb_payment.id')
            ->where('tb_payment.month', $month)
            ->where('tb_payment.year', $year)
            ->whereNotNull('pegawai_pw.upt')
            ->select('pegawai_pw.upt')
            ->distinct();

        if ($user->role !== 'superadmin') {
            $paidQuery->whereIn('pegawai_pw.idskpd', $user->getAccessibleSkpds());
        }

        $paidUptNames = $paidQuery->pluck('upt')->toArray();

        // 3. Diff
        $unpaid = $allUpts->filter(function ($item) use ($paidUptNames) {
            return !in_array($item->upt, $paidUptNames);
        })->values();

        return response()->json([
            'success' => true,
            'data' => $unpaid,
            'meta' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'total_upt' => $allUpts->count(),
                'paid_count' => count($paidUptNames),
                'unpaid_count' => $unpaid->count()
            ]
        ]);
    }

    /**
     * List Pegawai-PW that haven't been included in payroll for current month
     */
    public function unpaidEmployees(Request $request)
    {
        $month = $request->month ?? date('n');
        $year = $request->year ?? date('Y');
        $user = $request->user();

        // Get all active Pegawai-PW
        $allEmployeesQuery = DB::table('pegawai_pw')
            ->join('skpd', 'pegawai_pw.idskpd', '=', 'skpd.id_skpd')
            ->select(
                'pegawai_pw.id',
                'pegawai_pw.nip',
                'pegawai_pw.nama',
                'pegawai_pw.jabatan',
                'pegawai_pw.upt',
                'skpd.nama_skpd',
                'skpd.kode_skpd',
                'pegawai_pw.idskpd'
            );

        if ($user->role !== 'superadmin') {
            $allEmployeesQuery->whereIn('pegawai_pw.idskpd', $user->getAccessibleSkpds());
        }

        $allEmployees = $allEmployeesQuery->get();

        // Get employee IDs that have payroll entries for the specified month/year
        $paidEmployeeIdsQuery = DB::table('tb_payment_detail')
            ->join('tb_payment', 'tb_payment_detail.payment_id', '=', 'tb_payment.id')
            ->join('pegawai_pw', 'tb_payment_detail.employee_id', '=', 'pegawai_pw.id')
            ->where('tb_payment.month', $month)
            ->where('tb_payment.year', $year);

        if ($user->role !== 'superadmin') {
            $paidEmployeeIdsQuery->whereIn('pegawai_pw.idskpd', $user->getAccessibleSkpds());
        }

        $paidEmployeeIds = $paidEmployeeIdsQuery
            ->pluck('pegawai_pw.id')
            ->unique()
            ->toArray();

        // Filter to get unpaid employees
        $unpaidEmployees = $allEmployees->filter(function ($employee) use ($paidEmployeeIds) {
            return !in_array($employee->id, $paidEmployeeIds);
        })->values();

        // Group by SKPD for better organization
        $groupedBySkpd = $unpaidEmployees->groupBy('nama_skpd')->map(function ($employees, $skpdName) {
            return [
                'skpd_name' => $skpdName,
                'kode_skpd' => $employees->first()->kode_skpd,
                'count' => $employees->count(),
                'employees' => $employees->map(function ($emp) {
                    return [
                        'id' => $emp->id,
                        'nip' => $emp->nip,
                        'nama' => $emp->nama,
                        'jabatan' => $emp->jabatan,
                        'upt' => $emp->upt
                    ];
                })
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $groupedBySkpd,
            'meta' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'total_employees' => $allEmployees->count(),
                'paid_count' => count($paidEmployeeIds),
                'unpaid_count' => $unpaidEmployees->count()
            ]
        ]);
    }

    /**
     * Calculated retirement data
     */
    private function getRetirementMonitor($user)
    {
        $retirementAge = 58;
        $query = DB::table('pegawai_pw')
            ->join('skpd', 'pegawai_pw.idskpd', '=', 'skpd.id_skpd')
            ->whereNotNull('tgl_lahir')
            ->select(
                'pegawai_pw.nama',
                'pegawai_pw.nip',
                'pegawai_pw.tgl_lahir',
                'pegawai_pw.jabatan',
                'pegawai_pw.status',
                'skpd.nama_skpd',
                DB::raw("TIMESTAMPDIFF(YEAR, tgl_lahir, CURDATE()) AS current_age")
            )
            ->having('current_age', '>=', 55) // Show from 55 to be safe and informative
            ->orderByDesc('current_age');

        if ($user->role !== 'superadmin') {
            $query->whereIn('pegawai_pw.idskpd', $user->getAccessibleSkpds());
        }

        return $query->get()->map(function ($emp) use ($retirementAge) {
            $birthDate = new \DateTime($emp->tgl_lahir);
            $retirementDate = (clone $birthDate)->modify("+$retirementAge years");

            return [
                'nama' => $emp->nama,
                'nip' => $emp->nip,
                'jabatan' => $emp->jabatan,
                'status_pegawai' => $emp->status,
                'nama_skpd' => $emp->nama_skpd,
                'age' => $emp->current_age,
                'retirement_date' => $retirementDate->format('Y-m-d'),
                'is_critical' => $emp->current_age >= $retirementAge - 1,
            ];
        });
    }

    /**
     * Export unpaid data to Excel or PDF
     */
    public function exportUnpaid(Request $request)
    {
        $month = $request->month ?? date('n');
        $year = $request->year ?? date('Y');
        $viewBy = $request->view_by ?? 'skpd';
        $format = $request->input('format') ?? 'excel';

        // Fetch the appropriate data based on viewBy
        switch ($viewBy) {
            case 'upt':
                $response = $this->unpaidUpts($request);
                break;
            case 'employees':
                $response = $this->unpaidEmployees($request);
                break;
            default:
                $response = $this->unpaidSkpds($request);
                break;
        }

        $responseData = json_decode($response->getContent(), true);
        $data = $responseData['data'] ?? [];

        $months = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December'
        ];
        $monthName = $months[(int) $month] ?? 'Unknown';

        $filename = "missing_payrolls_{$viewBy}_{$month}_{$year}";

        $this->logExport("Laporan Kekurangan Payroll ({$viewBy})", $format === 'pdf' ? 'Cetak PDF' : 'Ekspor Excel', "Periode: {$monthName} {$year}");

        if ($format === 'pdf') {
            $totalCount = $responseData['meta']['unpaid_count'] ?? count($data);

            $pdf = Pdf::loadView('exports.unpaid-pdf', [
                'data' => $data,
                'viewBy' => $viewBy,
                'month' => $month,
                'year' => $year,
                'monthName' => $monthName,
                'totalCount' => $totalCount,
            ]);

            return $pdf->download($filename . '.pdf');
        }

        // Default to Excel
        return Excel::download(
            new UnpaidDataExport($data, $viewBy, (int) $month, (int) $year),
            $filename . '.xlsx'
        );
    }

    /**
     * List SKPD that have created payroll for a specific month/year.
     * type: pns | pppk → detailed columns; pw | all → summary columns
     */
    public function paidSkpds(Request $request)
    {
        $month = $request->month ?? date('n');
        $year = $request->year ?? date('Y');
        $type = $request->type ?? 'all'; // pns | pppk | pw | all
        $jenisGaji = $request->jenis_gaji ?? 'Induk';

        $user = auth()->user();
        $isSuperAdmin = $user->role === 'superadmin';
        $accessibleIds = $user->getAccessibleSkpds();
        $accessibleCodes = $user->getAccessibleSkpdCodes();

        $idFilter = "";
        if (!$isSuperAdmin && $accessibleIds) {
            $idList = implode(',', array_map('intval', $accessibleIds));
            $idFilter = " AND pw.idskpd IN ($idList) ";
        }

        $codeFilter = "";
        if (!$isSuperAdmin && $accessibleCodes) {
            $codeList = "'" . implode("','", $accessibleCodes) . "'";
            $codeFilter = " AND g.kdskpd IN ($codeList) ";
        }

        $jenisGajiFilter = "";
        if ($jenisGaji !== 'Semua') {
            $jenisGajiFilter = " AND g.jenis_gaji = " . DB::getPdo()->quote($jenisGaji);
        }

        $stIdFilter = "";
        if (!$isSuperAdmin && $accessibleIds) {
            $idList = implode(',', array_map('intval', $accessibleIds));
            $stIdFilter = " AND st.skpd_id IN ($idList) ";
        }

        // ── Detailed mode for PNS / PPPK / Gabungan ──────────────────────────
        if (in_array($type, ['pns', 'pppk', 'all'])) {
            $parts = [];
            $params = [];

            $typesToInclude = $type === 'all' ? ['pns', 'pppk'] : [$type];

            foreach ($typesToInclude as $t) {
                $table = $t === 'pns' ? 'gaji_pns' : 'gaji_pppk';
                $mapType = $t === 'pns' ? "('pns','all')" : "('pppk','all')";

                // --- MAIN PAYROLL DATA ---
                $parts[] = "
                    SELECT
                        COALESCE(sm.kode_skpd, g.kdskpd) COLLATE utf8mb4_unicode_ci AS kode_skpd,
                        COALESCE(sm.nama_skpd, s2.nmskpd, g.skpd) COLLATE utf8mb4_unicode_ci AS nama_skpd,
                        COUNT(DISTINCT g.nip)               AS jumlah_pegawai,
                        SUM(g.gaji_pokok)                  AS gapok,
                        SUM(g.tunj_istri)                  AS tj_istri,
                        SUM(g.tunj_anak)                   AS tj_anak,
                        SUM(g.tunj_tpp)                    AS tj_tpp,
                        SUM(g.tunj_eselon)                 AS tj_eselon,
                        SUM(g.tunj_fungsional)             AS tj_fungsi,
                        SUM(g.tunj_beras)                  AS tj_beras,
                        SUM(g.tunj_pph)                    AS tj_pajak,
                        SUM(g.tunj_umum)                   AS tj_umum,
                        SUM(g.tunj_khusus)                 AS tj_khusus,
                        SUM(g.pembulatan)                  AS pembulatan,
                        SUM(g.kotor)                       AS kotor,
                        SUM(g.pot_iwp)                     AS pot_iwp,
                        SUM(CASE WHEN g.pot_iwp1 > 0 THEN g.pot_iwp1 ELSE (g.pot_iwp - g.pot_iwp8) END) AS pot_iwp2,
                        SUM(g.pot_iwp8)                    AS pot_iwp8,
                        SUM(g.pot_pph)                     AS pot_pajak,
                        SUM(g.total_potongan)              AS total_potongan,
                        SUM(g.bersih)                      AS bersih
                    FROM {$table} g
                    LEFT JOIN (SELECT DISTINCT kdskpd, nmskpd FROM satkers) s2 ON g.kdskpd = s2.kdskpd
                    LEFT JOIN (
                        SELECT mp.source_code, s2.kode_skpd, s2.nama_skpd
                        FROM skpd_mapping mp
                        JOIN skpd s2 ON mp.skpd_id = s2.id_skpd
                        WHERE mp.type IN {$mapType}
                    ) sm ON g.kdskpd = sm.source_code
                    WHERE g.bulan = ? AND g.tahun = ? {$codeFilter} {$jenisGajiFilter}
                    GROUP BY 1, 2";
                
                $params = array_merge($params, [$month, $year]);

                // --- STANDALONE TPP DATA ---
                // Only include if these NIPs are NOT already in the main payroll table for this month (avoid double counting)
                $parts[] = "
                    SELECT
                        s.kode_skpd COLLATE utf8mb4_unicode_ci AS kode_skpd,
                        s.nama_skpd COLLATE utf8mb4_unicode_ci AS nama_skpd,
                        COUNT(DISTINCT st.nip) AS jumlah_pegawai,
                        0 AS gapok, 0 AS tj_istri, 0 AS tj_anak,
                        SUM(st.nilai) AS tj_tpp,
                        0 AS tj_eselon, 0 AS tj_fungsi, 0 AS tj_beras, 0 AS tj_pajak, 0 AS tj_umum, 0 AS tj_khusus, 0 AS pembulatan,
                        SUM(st.nilai) AS kotor,
                        0 AS pot_iwp, 0 AS pot_iwp2, 0 AS pot_iwp8, 0 AS pot_pajak, 0 AS total_potongan,
                        SUM(st.nilai) AS bersih
                    FROM standalone_tpp st
                    JOIN skpd s ON st.skpd_id = s.id_skpd
                    WHERE st.month = ? AND st.year = ? {$stIdFilter}
                    AND st.employee_type = " . DB::getPdo()->quote($t) . "
                    AND st.jenis_gaji = " . DB::getPdo()->quote($jenisGaji === 'Semua' ? 'Induk' : $jenisGaji) . "
                    AND NOT EXISTS (
                        SELECT 1 FROM {$table} g2 
                        WHERE g2.nip = st.nip 
                        AND g2.bulan = st.month 
                        AND g2.tahun = st.year
                    )
                    GROUP BY 1, 2";
                
                $params = array_merge($params, [$month, $year]);
            }

            $unionSql = implode("\n UNION ALL \n", $parts);

            $rows = collect(DB::select("
                SELECT
                    kode_skpd, nama_skpd,
                    SUM(jumlah_pegawai)  AS jumlah_pegawai,
                    SUM(gapok)           AS gapok,
                    SUM(tj_istri)        AS tj_istri,
                    SUM(tj_anak)         AS tj_anak,
                    SUM(tj_tpp)          AS tj_tpp,
                    SUM(tj_eselon)       AS tj_eselon,
                    SUM(tj_fungsi)       AS tj_fungsi,
                    SUM(tj_beras)        AS tj_beras,
                    SUM(tj_pajak)        AS tj_pajak,
                    SUM(tj_umum)         AS tj_umum,
                    SUM(tj_khusus)       AS tj_khusus,
                    SUM(pembulatan)      AS pembulatan,
                    SUM(kotor)           AS kotor,
                    SUM(pot_iwp)         AS pot_iwp,
                    SUM(pot_iwp2)        AS pot_iwp2,
                    SUM(pot_iwp8)        AS pot_iwp8,
                    SUM(pot_pajak)       AS pot_pajak,
                    SUM(total_potongan)  AS total_potongan,
                    SUM(bersih)          AS bersih
                FROM ({$unionSql}) AS combined
                GROUP BY kode_skpd, nama_skpd
                ORDER BY nama_skpd
            ", $params));

            return response()->json([
                'success' => true,
                'data' => $rows,
                'mode' => 'detail',
                'meta' => [
                    'month' => (int) $month,
                    'year' => (int) $year,
                    'type' => $type,
                    'jenis_gaji' => $jenisGaji,
                    'total_skpd' => $rows->count(),
                    'total_employees' => $rows->sum('jumlah_pegawai'),
                    'grand_total' => $rows->sum('bersih'),
                ],
            ]);
        }

        // ── Summary mode for PW (PPPK Paruh Waktu) ──────────────────────────────────────────
        $parts = [];
        $params = [];

        // --- PPPK-PW (Paruh Waktu) --- from tb_payment_detail
        if ($type === 'pw') {
            $sumberDana = $request->sumber_dana;
            $sumberDanaFilter = "";
            if ($sumberDana && $sumberDana !== 'Semua') {
                $sumberDanaFilter = " AND pw.sumber_dana = " . DB::getPdo()->quote($sumberDana);
            }

            $parts[] = "
                SELECT
                    s.kode_skpd COLLATE utf8mb4_unicode_ci AS kode_skpd,
                    s.nama_skpd,
                    pw.sumber_dana,
                    COUNT(DISTINCT pw.id) AS employee_count,
                    SUM(pd.gaji_pokok)    AS total_gaji_pokok,
                    SUM(pd.tunjangan)     AS total_tunjangan,
                    SUM(pd.potongan)      AS total_potongan,
                    SUM(pd.total_amoun)   AS total_bersih
                FROM tb_payment_detail pd
                JOIN pegawai_pw pw ON pd.employee_id = pw.id
                JOIN skpd s        ON pw.idskpd = s.id_skpd
                JOIN tb_payment p  ON pd.payment_id = p.id
                WHERE p.month = ? AND p.year = ? {$idFilter} {$sumberDanaFilter}
                GROUP BY s.id_skpd, s.kode_skpd, s.nama_skpd, pw.sumber_dana";
            $params = array_merge($params, [$month, $year]);
        }

        $unionSql = implode("\n UNION ALL \n", $parts);

        $unionResults = DB::select("
            SELECT
                kode_skpd, nama_skpd, sumber_dana,
                SUM(employee_count)    as employee_count,
                SUM(total_gaji_pokok)  as total_gaji_pokok,
                SUM(total_tunjangan)   as total_tunjangan,
                SUM(total_potongan)    as total_potongan,
                SUM(total_bersih)      as total_bersih
            FROM ($unionSql) AS combined
            GROUP BY kode_skpd, nama_skpd, sumber_dana
            ORDER BY nama_skpd, sumber_dana
        ", $params);

        $paidSkpds = collect($unionResults);

        return response()->json([
            'success' => true,
            'data' => $paidSkpds,
            'meta' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'type' => $type,
                'jenis_gaji' => $jenisGaji,
                'sumber_dana' => $request->sumber_dana ?? 'Semua',
                'total_skpd' => $paidSkpds->count(),
                'total_employees' => $paidSkpds->sum('employee_count'),
                'grand_total' => $paidSkpds->sum('total_bersih'),
            ]
        ]);
    }

    /**
     * Export paid SKPD data to Excel or PDF
     */
    public function exportPaidSkpds(Request $request)
    {
        $month = $request->month ?? date('n');
        $year = $request->year ?? date('Y');
        $format = $request->input('format') ?? 'excel';
        $type = $request->type ?? 'all';
        $jenisGaji = $request->jenis_gaji ?? 'Induk';

        // Re-use paidSkpds logic (includes type-based mode selection)
        $response = $this->paidSkpds($request);
        $responseData = json_decode($response->getContent(), true);
        $data = $responseData['data'] ?? [];
        $mode = $responseData['mode'] ?? 'summary';  // 'detail' | 'summary'

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
            12 => 'Desember',
        ];
        $monthName = $monthNames[(int) $month] ?? 'Unknown';
        $typeSlug = $type !== 'all' ? "_{$type}" : '';
        $jgSlug = $jenisGaji !== 'Semua' ? "_" . strtolower(str_replace(' ', '_', $jenisGaji)) : '';
        $filename = "skpd_daftar_gaji{$typeSlug}{$jgSlug}_{$month}_{$year}";

        $this->logExport("Daftar Gaji SKPD ({$type})", $format === 'pdf' ? 'Cetak PDF' : 'Ekspor Excel', "Periode: {$monthName} {$year}, Jenis: {$jenisGaji}");

        if ($format === 'pdf') {
            if ($mode === 'detail') {
                // Detail totals (PNS / PPPK)
                $totalEmployees = array_sum(array_column($data, 'jumlah_pegawai'));
                $grandTotal = array_sum(array_column($data, 'bersih'));
                $sumGajiPokok = array_sum(array_column($data, 'gapok'));
            } else {
                // Summary totals (Gabungan / PW)
                $totalEmployees = array_sum(array_column($data, 'employee_count'));
                $grandTotal = array_sum(array_column($data, 'total_bersih'));
                $sumGajiPokok = array_sum(array_column($data, 'total_gaji_pokok'));
            }

            $paperSize = $request->input('paper_size') ?? 'a4';

            $pdf = Pdf::loadView('exports.paid-skpd-pdf', [
                'data' => $data,
                'month' => $month,
                'year' => $year,
                'monthName' => $monthName,
                'mode' => $mode,
                'type' => $type,
                'jenis_gaji' => $jenisGaji,
                'totalEmployees' => $totalEmployees,
                'grandTotal' => $grandTotal,
                'sumGajiPokok' => $sumGajiPokok,
                'sumTunjangan' => array_sum(array_column($data, $mode === 'detail' ? 'kotor' : 'total_tunjangan')),
                'sumPotongan' => array_sum(array_column($data, 'total_potongan')),
                'sumPajak' => array_sum(array_column($data, $mode === 'detail' ? 'pot_pajak' : 'total_potongan')), // Summary uses total_potongan for simplicity
                'sumIwp' => array_sum(array_column($data, $mode === 'detail' ? 'pot_iwp' : 'total_potongan')),
            ]);

            $pdf->setPaper($paperSize, 'landscape');
            return $pdf->download($filename . '.pdf');
        }

        // Excel
        return Excel::download(
            new PaidSkpdExport($data, (int) $month, (int) $year, $mode),
            $filename . '.xlsx'
        );
    }

    /**
     * List employees that have received payroll for a specific month/year
     */
    public function paidEmployees(Request $request)
    {
        $month = $request->month ?? date('n');
        $year = $request->year ?? date('Y');
        $user = $request->user();

        // Query to get employees with payment details for the period
        $query = DB::table('tb_payment_detail')
            ->join('pegawai_pw', 'tb_payment_detail.employee_id', '=', 'pegawai_pw.id')
            ->join('skpd', 'pegawai_pw.idskpd', '=', 'skpd.id_skpd')
            ->join('tb_payment', 'tb_payment_detail.payment_id', '=', 'tb_payment.id')
            ->where('tb_payment.month', $month)
            ->where('tb_payment.year', $year)
            ->select(
                'pegawai_pw.id',
                'pegawai_pw.nip',
                'pegawai_pw.nama',
                'pegawai_pw.jabatan',
                'pegawai_pw.upt',
                'pegawai_pw.idskpd as id_skpd',
                'skpd.nama_skpd',
                'skpd.kode_skpd',
                'tb_payment_detail.gaji_pokok',
                'tb_payment_detail.pajak',
                'tb_payment_detail.iwp',
                'tb_payment_detail.tunjangan',
                'tb_payment_detail.potongan',
                'tb_payment_detail.total_amoun as total_bersih'
            )
            ->orderBy('skpd.nama_skpd')
            ->orderBy('pegawai_pw.nama');

        if ($user->role !== 'superadmin') {
            $query->whereIn('skpd.id_skpd', $user->getAccessibleSkpds());
        }

        $paidEmployees = $query->get();

        return response()->json([
            'success' => true,
            'data' => $paidEmployees,
            'meta' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'total_employees' => $paidEmployees->count(),
                'grand_total' => $paidEmployees->sum('total_bersih')
            ]
        ]);
    }

    /**
     * Export paid employees data to Excel or PDF
     */
    public function exportPaidEmployees(Request $request)
    {
        $month = $request->month ?? date('n');
        $year = $request->year ?? date('Y');
        $format = $request->input('format') ?? 'excel';

        // Get the data
        $response = $this->paidEmployees($request);
        $responseData = json_decode($response->getContent(), true);
        $data = $responseData['data'] ?? [];

        $months = [
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
        $monthName = $months[(int) $month] ?? 'Unknown';

        $filename = "daftar_gaji_pegawai_{$month}_{$year}";

        $this->logExport("Daftar Gaji Pegawai (Detailed)", $format === 'pdf' ? 'Cetak PDF' : 'Ekspor Excel', "Periode: {$monthName} {$year}");

        if ($format === 'pdf') {
            // Calculate totals for PDF
            $sumGajiPokok = array_sum(array_column($data, 'gaji_pokok'));
            $sumPajak = array_sum(array_column($data, 'pajak'));
            $sumIwp = array_sum(array_column($data, 'iwp'));
            $sumTunjangan = array_sum(array_column($data, 'tunjangan'));
            $grandTotal = array_sum(array_column($data, 'total_bersih'));

            $pdf = Pdf::loadView('exports.paid-employees-pdf', [
                'data' => $data,
                'month' => $month,
                'year' => $year,
                'monthName' => $monthName,
                'sumGajiPokok' => $sumGajiPokok,
                'sumPajak' => $sumPajak,
                'sumIwp' => $sumIwp,
                'sumTunjangan' => $sumTunjangan,
                'grandTotal' => $grandTotal,
            ]);

            $pdf->setPaper('a4', 'landscape');

            return $pdf->download($filename . '.pdf');
        }

        // Default to Excel
        return Excel::download(
            new PaidEmployeesExport($data, (int) $month, (int) $year),
            $filename . '.xlsx'
        );
    }
    public function exportCombinedAllowance(Request $request)
    {
        $month = $request->month ?? date('n');
        $year = $request->year ?? date('Y');

        $pns = DB::table('gaji_pns')
            ->where('bulan', $month)
            ->where('tahun', $year)
            ->selectRaw('
                SUM(gaji_pokok) as total_gaji_pokok,
                SUM(tunj_istri) as total_tunj_istri,
                SUM(tunj_anak) as total_tunj_anak,
                SUM(tunj_fungsional) as total_tunj_fungsional,
                SUM(tunj_struktural) as total_tunj_struktural,
                SUM(tunj_umum) as total_tunj_umum,
                SUM(tunj_beras) as total_tunj_beras,
                SUM(tunj_pph) as total_tunj_pph,
                SUM(tunj_tpp) as total_tunj_tpp,
                SUM(tunj_eselon) as total_tunj_eselon,
                SUM(tunj_guru) as total_tunj_guru,
                SUM(tunj_langka) as total_tunj_langka,
                SUM(tunj_tkd) as total_tunj_tkd,
                SUM(tunj_terpencil) as total_tunj_terpencil,
                SUM(tunj_khusus) as total_tunj_khusus,
                SUM(tunj_askes) as total_tunj_askes,
                SUM(tunj_kk) as total_tunj_kk,
                SUM(tunj_km) as total_tunj_km,
                SUM(pembulatan) as total_pembulatan,
                SUM(kotor) as total_kotor,
                SUM(pot_iwp) as total_pot_iwp,
                SUM(CASE WHEN pot_iwp1 > 0 THEN pot_iwp1 ELSE (pot_iwp - pot_iwp8) END) as total_pot_iwp1,
                SUM(pot_iwp8) as total_pot_iwp8,
                SUM(pot_askes) as total_pot_askes,
                SUM(pot_pph) as total_pot_pph,
                SUM(pot_bulog) as total_pot_bulog,
                SUM(pot_taperum) as total_pot_taperum,
                SUM(pot_sewa) as total_pot_sewa,
                SUM(pot_hutang) as total_pot_hutang,
                SUM(pot_korpri) as total_pot_korpri,
                SUM(pot_irdhata) as total_pot_irdhata,
                SUM(pot_koperasi) as total_pot_koperasi,
                SUM(pot_jkk) as total_pot_jkk,
                SUM(pot_jkm) as total_pot_jkm,
                SUM(total_potongan) as total_potongan,
                SUM(bersih) as total_bersih
            ')->first();

        $pppk = DB::table('gaji_pppk')
            ->where('bulan', $month)
            ->where('tahun', $year)
            ->selectRaw('
                SUM(gaji_pokok) as total_gaji_pokok,
                SUM(tunj_istri) as total_tunj_istri,
                SUM(tunj_anak) as total_tunj_anak,
                SUM(tunj_fungsional) as total_tunj_fungsional,
                SUM(tunj_struktural) as total_tunj_struktural,
                SUM(tunj_umum) as total_tunj_umum,
                SUM(tunj_beras) as total_tunj_beras,
                SUM(tunj_pph) as total_tunj_pph,
                SUM(tunj_tpp) as total_tunj_tpp,
                SUM(tunj_eselon) as total_tunj_eselon,
                SUM(tunj_guru) as total_tunj_guru,
                SUM(tunj_langka) as total_tunj_langka,
                SUM(tunj_tkd) as total_tunj_tkd,
                SUM(tunj_terpencil) as total_tunj_terpencil,
                SUM(tunj_khusus) as total_tunj_khusus,
                SUM(tunj_askes) as total_tunj_askes,
                SUM(tunj_kk) as total_tunj_kk,
                SUM(tunj_km) as total_tunj_km,
                SUM(pembulatan) as total_pembulatan,
                SUM(kotor) as total_kotor,
                SUM(pot_iwp) as total_pot_iwp,
                SUM(CASE WHEN pot_iwp1 > 0 THEN pot_iwp1 ELSE (pot_iwp - pot_iwp8) END) as total_pot_iwp1,
                SUM(pot_iwp8) as total_pot_iwp8,
                SUM(pot_askes) as total_pot_askes,
                SUM(pot_pph) as total_pot_pph,
                SUM(pot_bulog) as total_pot_bulog,
                SUM(pot_taperum) as total_pot_taperum,
                SUM(pot_sewa) as total_pot_sewa,
                SUM(pot_hutang) as total_pot_hutang,
                SUM(pot_korpri) as total_pot_korpri,
                SUM(pot_irdhata) as total_pot_irdhata,
                SUM(pot_koperasi) as total_pot_koperasi,
                SUM(pot_jkk) as total_pot_jkk,
                SUM(pot_jkm) as total_pot_jkm,
                SUM(total_potongan) as total_potongan,
                SUM(bersih) as total_bersih
            ')->first();

        $combined = [];

        // --- SECTION: TUNJANGAN ---
        $combined[] = ['label' => 'A. GAJI POKOK & TUNJANGAN', 'pns' => null, 'pppk' => null, 'total' => null];

        // 1. Gaji Pokok
        $pGaji = $pns->total_gaji_pokok ?? 0;
        $ppGaji = $pppk->total_gaji_pokok ?? 0;
        $combined[] = ['label' => 'Gaji Pokok', 'pns' => (float) $pGaji, 'pppk' => (float) $ppGaji, 'total' => (float) ($pGaji + $ppGaji)];

        $tunjanganFields = [
            ['key' => 'total_tunj_istri', 'label' => 'Tunjangan Istri'],
            ['key' => 'total_tunj_anak', 'label' => 'Tunjangan Anak'],
            ['key' => 'total_tunj_fungsional', 'label' => 'Tunjangan Fungsional'],
            ['key' => 'total_tunj_struktural', 'label' => 'Tunjangan Struktural'],
            ['key' => 'total_tunj_umum', 'label' => 'Tunjangan Umum'],
            ['key' => 'total_tunj_beras', 'label' => 'Tunjangan Beras'],
            ['key' => 'total_tunj_pph', 'label' => 'Tunjangan PPh'],
            ['key' => 'total_tunj_tpp', 'label' => 'Tunjangan TPP'],
            ['key' => 'total_tunj_eselon', 'label' => 'Tunjangan Eselon'],
            ['key' => 'total_tunj_guru', 'label' => 'Tunjangan Guru'],
            ['key' => 'total_tunj_langka', 'label' => 'Tunjangan Langka'],
            ['key' => 'total_tunj_tkd', 'label' => 'Tunjangan TKD'],
            ['key' => 'total_tunj_terpencil', 'label' => 'Tunjangan Terpencil'],
            ['key' => 'total_tunj_khusus', 'label' => 'Tunjangan Khusus'],
            ['key' => 'total_tunj_askes', 'label' => 'Tunjangan Askes'],
            ['key' => 'total_tunj_kk', 'label' => 'Tunjangan JKK'],
            ['key' => 'total_tunj_km', 'label' => 'Tunjangan JKM'],
        ];

        foreach ($tunjanganFields as $f) {
            $pVal = $pns->{$f['key']} ?? 0;
            $ppVal = $pppk->{$f['key']} ?? 0;
            $combined[] = [
                'label' => $f['label'],
                'pns' => (float) $pVal,
                'pppk' => (float) $ppVal,
                'total' => (float) ($pVal + $ppVal)
            ];
        }

        // 2. Pembulatan
        $pBulat = $pns->total_pembulatan ?? 0;
        $ppBulat = $pppk->total_pembulatan ?? 0;
        $combined[] = ['label' => 'Pembulatan', 'pns' => (float) $pBulat, 'pppk' => (float) $ppBulat, 'total' => (float) ($pBulat + $ppBulat)];

        // Total Kotor Row
        $pKotor = $pns->total_kotor ?? 0;
        $ppKotor = $pppk->total_kotor ?? 0;
        $combined[] = ['label' => 'TOTAL GAJI KOTOR', 'pns' => (float) $pKotor, 'pppk' => (float) $ppKotor, 'total' => (float) ($pKotor + $ppKotor)];

        $combined[] = ['label' => '', 'pns' => null, 'pppk' => null, 'total' => null];

        // --- SECTION: POTONGAN ---
        $combined[] = ['label' => 'B. POTONGAN', 'pns' => null, 'pppk' => null, 'total' => null];

        $potonganFields = [
            ['key' => 'total_pot_iwp', 'label' => 'IWP (Total)'],
            ['key' => 'total_pot_iwp1', 'label' => 'IWP 1%'],
            ['key' => 'total_pot_iwp8', 'label' => 'IWP 8%'],
            ['key' => 'total_pot_askes', 'label' => 'Askes/BPJS'],
            ['key' => 'total_pot_pph', 'label' => 'PPh 21'],
            ['key' => 'total_pot_bulog', 'label' => 'Bulog'],
            ['key' => 'total_pot_taperum', 'label' => 'Taperum'],
            ['key' => 'total_pot_sewa', 'label' => 'Sewa Rumah'],
            ['key' => 'total_pot_hutang', 'label' => 'Hutang'],
            ['key' => 'total_pot_korpri', 'label' => 'Korpri'],
            ['key' => 'total_pot_irdhata', 'label' => 'Irdhata'],
            ['key' => 'total_pot_koperasi', 'label' => 'Koperasi'],
            ['key' => 'total_pot_jkk', 'label' => 'JKK'],
            ['key' => 'total_pot_jkm', 'label' => 'JKM'],
        ];

        foreach ($potonganFields as $f) {
            $pVal = $pns->{$f['key']} ?? 0;
            $ppVal = $pppk->{$f['key']} ?? 0;
            $combined[] = [
                'label' => $f['label'],
                'pns' => (float) $pVal,
                'pppk' => (float) $ppVal,
                'total' => (float) ($pVal + $ppVal)
            ];
        }

        // Total Potongan Row
        $pPot = $pns->total_potongan ?? 0;
        $ppPot = $pppk->total_potongan ?? 0;
        $combined[] = ['label' => 'TOTAL POTONGAN', 'pns' => (float) $pPot, 'pppk' => (float) $ppPot, 'total' => (float) ($pPot + $ppPot)];

        $combined[] = ['label' => '', 'pns' => null, 'pppk' => null, 'total' => null];

        // --- SECTION: SUMMARY ---
        $pBersih = $pns->total_bersih ?? 0;
        $ppBersih = $pppk->total_bersih ?? 0;
        $combined[] = ['label' => 'C. GAJI BERSIH (A - B)', 'pns' => (float) $pBersih, 'pppk' => (float) $ppBersih, 'total' => (float) ($pBersih + $ppBersih)];

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
        $monthName = $monthNames[(int) $month] ?? 'Unknown';

        $this->logExport("Rincian Tunjangan Gabungan", "Ekspor Excel", "Periode: {$monthName} {$year}");

        return Excel::download(
            new CombinedAllowanceExport($combined, $month, $year, $monthName),
            "rincian_tunjangan_gabungan_{$month}_{$year}.xlsx"
        );
    }

    /**
     * Periodic report: aggregate paid SKPD data across a range of months.
     * Supports Triwulan, Semesteran, Tahunan, and Custom period.
     */
    public function periodicSkpds(Request $request)
    {
        $monthFrom = (int) ($request->month_from ?? 1);
        $monthTo   = (int) ($request->month_to ?? 12);
        $year      = (int) ($request->year ?? date('Y'));
        $type      = $request->type ?? 'all'; // pns | pppk | pw | all
        $jenisGaji = $request->jenis_gaji ?? 'Semua';

        $user = auth()->user();
        $isSuperAdmin = $user->role === 'superadmin';
        $accessibleIds = $user->getAccessibleSkpds();
        $accessibleCodes = $user->getAccessibleSkpdCodes();

        $idFilter = "";
        if (!$isSuperAdmin && $accessibleIds) {
            $idList = implode(',', array_map('intval', $accessibleIds));
            $idFilter = " AND pw.idskpd IN ($idList) ";
        }

        $codeFilter = "";
        if (!$isSuperAdmin && $accessibleCodes) {
            $codeList = "'" . implode("','", $accessibleCodes) . "'";
            $codeFilter = " AND g.kdskpd IN ($codeList) ";
        }

        $jenisGajiFilter = "";
        if ($jenisGaji !== 'Semua') {
            $jenisGajiFilter = " AND g.jenis_gaji = " . DB::getPdo()->quote($jenisGaji);
        }

        $stIdFilter = "";
        if (!$isSuperAdmin && $accessibleIds) {
            $idList = implode(',', array_map('intval', $accessibleIds));
            $stIdFilter = " AND st.skpd_id IN ($idList) ";
        }

        // ── Detailed mode for PNS / PPPK / Gabungan ──────────────────────────
        if (in_array($type, ['pns', 'pppk', 'all'])) {
            $parts = [];
            $params = [];

            $typesToInclude = $type === 'all' ? ['pns', 'pppk'] : [$type];

            foreach ($typesToInclude as $t) {
                $table = $t === 'pns' ? 'gaji_pns' : 'gaji_pppk';
                $mapType = $t === 'pns' ? "('pns','all')" : "('pppk','all')";

                $parts[] = "
                    SELECT
                        COALESCE(sm.kode_skpd, g.kdskpd) COLLATE utf8mb4_unicode_ci AS kode_skpd,
                        COALESCE(sm.nama_skpd, s2.nmskpd, g.skpd) COLLATE utf8mb4_unicode_ci AS nama_skpd,
                        COUNT(DISTINCT g.nip)               AS jumlah_pegawai,
                        SUM(g.gaji_pokok)                  AS gapok,
                        SUM(g.tunj_istri)                  AS tj_istri,
                        SUM(g.tunj_anak)                   AS tj_anak,
                        SUM(g.tunj_tpp)                    AS tj_tpp,
                        SUM(g.tunj_eselon)                 AS tj_eselon,
                        SUM(g.tunj_fungsional)             AS tj_fungsi,
                        SUM(g.tunj_beras)                  AS tj_beras,
                        SUM(g.tunj_pph)                    AS tj_pajak,
                        SUM(g.tunj_umum)                   AS tj_umum,
                        SUM(g.tunj_khusus)                 AS tj_khusus,
                        SUM(g.pembulatan)                  AS pembulatan,
                        SUM(g.kotor)                       AS kotor,
                        SUM(g.pot_iwp)                     AS pot_iwp,
                        SUM(CASE WHEN g.pot_iwp1 > 0 THEN g.pot_iwp1 ELSE (g.pot_iwp - g.pot_iwp8) END) AS pot_iwp2,
                        SUM(g.pot_iwp8)                    AS pot_iwp8,
                        SUM(g.pot_pph)                     AS pot_pajak,
                        SUM(g.total_potongan)              AS total_potongan,
                        SUM(g.bersih)                      AS bersih
                    FROM {$table} g
                    LEFT JOIN (SELECT DISTINCT kdskpd, nmskpd FROM satkers) s2 ON g.kdskpd = s2.kdskpd
                    LEFT JOIN (
                        SELECT mp.source_code, s2.kode_skpd, s2.nama_skpd
                        FROM skpd_mapping mp
                        JOIN skpd s2 ON mp.skpd_id = s2.id_skpd
                        WHERE mp.type IN {$mapType}
                    ) sm ON g.kdskpd = sm.source_code
                    WHERE g.bulan BETWEEN ? AND ? AND g.tahun = ? {$codeFilter} {$jenisGajiFilter}
                    GROUP BY 1, 2";

                $params = array_merge($params, [$monthFrom, $monthTo, $year]);

                // --- STANDALONE TPP DATA ---
                $parts[] = "
                    SELECT
                        s.kode_skpd COLLATE utf8mb4_unicode_ci AS kode_skpd,
                        s.nama_skpd COLLATE utf8mb4_unicode_ci AS nama_skpd,
                        COUNT(DISTINCT st.nip) AS jumlah_pegawai,
                        0 AS gapok, 0 AS tj_istri, 0 AS tj_anak,
                        SUM(st.nilai) AS tj_tpp,
                        0 AS tj_eselon, 0 AS tj_fungsi, 0 AS tj_beras, 0 AS tj_pajak, 0 AS tj_umum, 0 AS tj_khusus, 0 AS pembulatan,
                        SUM(st.nilai) AS kotor,
                        0 AS pot_iwp, 0 AS pot_iwp2, 0 AS pot_iwp8, 0 AS pot_pajak, 0 AS total_potongan,
                        SUM(st.nilai) AS bersih
                    FROM standalone_tpp st
                    JOIN skpd s ON st.skpd_id = s.id_skpd
                    WHERE st.month BETWEEN ? AND ? AND st.year = ? {$stIdFilter}
                    AND st.employee_type = " . DB::getPdo()->quote($t) . "
                    AND st.jenis_gaji = " . DB::getPdo()->quote($jenisGaji === 'Semua' ? 'Induk' : $jenisGaji) . "
                    AND NOT EXISTS (
                        SELECT 1 FROM {$table} g2
                        WHERE g2.nip = st.nip
                        AND g2.bulan = st.month
                        AND g2.tahun = st.year
                    )
                    GROUP BY 1, 2";

                $params = array_merge($params, [$monthFrom, $monthTo, $year]);
            }

            $unionSql = implode("\n UNION ALL \n", $parts);

            $rows = collect(DB::select("
                SELECT
                    kode_skpd, nama_skpd,
                    SUM(jumlah_pegawai)  AS jumlah_pegawai,
                    SUM(gapok)           AS gapok,
                    SUM(tj_istri)        AS tj_istri,
                    SUM(tj_anak)         AS tj_anak,
                    SUM(tj_tpp)          AS tj_tpp,
                    SUM(tj_eselon)       AS tj_eselon,
                    SUM(tj_fungsi)       AS tj_fungsi,
                    SUM(tj_beras)        AS tj_beras,
                    SUM(tj_pajak)        AS tj_pajak,
                    SUM(tj_umum)         AS tj_umum,
                    SUM(tj_khusus)       AS tj_khusus,
                    SUM(pembulatan)      AS pembulatan,
                    SUM(kotor)           AS kotor,
                    SUM(pot_iwp)         AS pot_iwp,
                    SUM(pot_iwp2)        AS pot_iwp2,
                    SUM(pot_iwp8)        AS pot_iwp8,
                    SUM(pot_pajak)       AS pot_pajak,
                    SUM(total_potongan)  AS total_potongan,
                    SUM(bersih)          AS bersih
                FROM ({$unionSql}) AS combined
                GROUP BY kode_skpd, nama_skpd
                ORDER BY nama_skpd
            ", $params));

            return response()->json([
                'success' => true,
                'data' => $rows,
                'mode' => 'detail',
                'meta' => [
                    'month_from' => $monthFrom,
                    'month_to' => $monthTo,
                    'year' => $year,
                    'type' => $type,
                    'jenis_gaji' => $jenisGaji,
                    'total_skpd' => $rows->count(),
                    'total_employees' => $rows->sum('jumlah_pegawai'),
                    'grand_total' => $rows->sum('bersih'),
                ],
            ]);
        }

        // ── Summary mode for PW (PPPK Paruh Waktu) ──────────────────────────
        $parts = [];
        $params = [];

        if ($type === 'pw') {
            $sumberDana = $request->sumber_dana;
            $sumberDanaFilter = "";
            if ($sumberDana && $sumberDana !== 'Semua') {
                $sumberDanaFilter = " AND pw.sumber_dana = " . DB::getPdo()->quote($sumberDana);
            }

            $parts[] = "
                SELECT
                    s.kode_skpd COLLATE utf8mb4_unicode_ci AS kode_skpd,
                    s.nama_skpd,
                    pw.sumber_dana,
                    COUNT(DISTINCT pw.id) AS employee_count,
                    SUM(pd.gaji_pokok)    AS total_gaji_pokok,
                    SUM(pd.tunjangan)     AS total_tunjangan,
                    SUM(pd.potongan)      AS total_potongan,
                    SUM(pd.total_amoun)   AS total_bersih
                FROM tb_payment_detail pd
                JOIN pegawai_pw pw ON pd.employee_id = pw.id
                JOIN skpd s        ON pw.idskpd = s.id_skpd
                JOIN tb_payment p  ON pd.payment_id = p.id
                WHERE p.month BETWEEN ? AND ? AND p.year = ? {$idFilter} {$sumberDanaFilter}
                GROUP BY s.id_skpd, s.kode_skpd, s.nama_skpd, pw.sumber_dana";
            $params = array_merge($params, [$monthFrom, $monthTo, $year]);
        }

        $unionSql = implode("\n UNION ALL \n", $parts);

        $unionResults = DB::select("
            SELECT
                kode_skpd, nama_skpd, sumber_dana,
                SUM(employee_count)    as employee_count,
                SUM(total_gaji_pokok)  as total_gaji_pokok,
                SUM(total_tunjangan)   as total_tunjangan,
                SUM(total_potongan)    as total_potongan,
                SUM(total_bersih)      as total_bersih
            FROM ($unionSql) AS combined
            GROUP BY kode_skpd, nama_skpd, sumber_dana
            ORDER BY nama_skpd, sumber_dana
        ", $params);

        $paidSkpds = collect($unionResults);

        return response()->json([
            'success' => true,
            'data' => $paidSkpds,
            'meta' => [
                'month_from' => $monthFrom,
                'month_to' => $monthTo,
                'year' => $year,
                'type' => $type,
                'jenis_gaji' => $jenisGaji,
                'sumber_dana' => $request->sumber_dana ?? 'Semua',
                'total_skpd' => $paidSkpds->count(),
                'total_employees' => $paidSkpds->sum('employee_count'),
                'grand_total' => $paidSkpds->sum('total_bersih'),
            ]
        ]);
    }

    /**
     * Export periodic SKPD report to Excel or PDF
     */
    public function exportPeriodicSkpds(Request $request)
    {
        $monthFrom = (int) ($request->month_from ?? 1);
        $monthTo   = (int) ($request->month_to ?? 12);
        $year      = (int) ($request->year ?? date('Y'));
        $format    = $request->input('format') ?? 'excel';
        $type      = $request->type ?? 'all';
        $jenisGaji = $request->jenis_gaji ?? 'Semua';

        // Re-use periodicSkpds logic
        $response = $this->periodicSkpds($request);
        $responseData = json_decode($response->getContent(), true);
        $data = $responseData['data'] ?? [];
        $mode = $responseData['mode'] ?? 'summary';

        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $fromName = $monthNames[$monthFrom] ?? 'Unknown';
        $toName   = $monthNames[$monthTo] ?? 'Unknown';
        $periodLabel = $fromName === $toName ? $fromName : "{$fromName} s.d. {$toName}";

        $typeSlug = $type !== 'all' ? "_{$type}" : '';
        $jgSlug = $jenisGaji !== 'Semua' ? "_" . strtolower(str_replace(' ', '_', $jenisGaji)) : '';
        $filename = "laporan_periodik{$typeSlug}{$jgSlug}_{$monthFrom}-{$monthTo}_{$year}";

        $this->logExport("Laporan Periodik SKPD ({$type})", $format === 'pdf' ? 'Cetak PDF' : 'Ekspor Excel', "Periode: {$periodLabel} {$year}, Jenis: {$jenisGaji}");

        if ($format === 'pdf') {
            if ($mode === 'detail') {
                $totalEmployees = array_sum(array_column($data, 'jumlah_pegawai'));
                $grandTotal = array_sum(array_column($data, 'bersih'));
                $sumGajiPokok = array_sum(array_column($data, 'gapok'));
            } else {
                $totalEmployees = array_sum(array_column($data, 'employee_count'));
                $grandTotal = array_sum(array_column($data, 'total_bersih'));
                $sumGajiPokok = array_sum(array_column($data, 'total_gaji_pokok'));
            }

            $paperSize = $request->input('paper_size') ?? 'a4';

            $pdf = Pdf::loadView('exports.paid-skpd-pdf', [
                'data' => $data,
                'month' => $monthFrom,
                'year' => $year,
                'monthName' => $periodLabel,
                'mode' => $mode,
                'type' => $type,
                'jenis_gaji' => $jenisGaji,
                'totalEmployees' => $totalEmployees,
                'grandTotal' => $grandTotal,
                'sumGajiPokok' => $sumGajiPokok,
                'sumTunjangan' => array_sum(array_column($data, $mode === 'detail' ? 'kotor' : 'total_tunjangan')),
                'sumPotongan' => array_sum(array_column($data, 'total_potongan')),
                'sumPajak' => array_sum(array_column($data, $mode === 'detail' ? 'pot_pajak' : 'total_potongan')),
                'sumIwp' => array_sum(array_column($data, $mode === 'detail' ? 'pot_iwp' : 'total_potongan')),
            ]);

            $pdf->setPaper($paperSize, 'landscape');
            return $pdf->download($filename . '.pdf');
        }

        // Excel — reuse existing PaidSkpdExport with period info in title
        return Excel::download(
            new \App\Exports\PeriodicSkpdExport($data, $monthFrom, $monthTo, $year, $mode, $periodLabel),
            $filename . '.xlsx'
        );
    }
}

