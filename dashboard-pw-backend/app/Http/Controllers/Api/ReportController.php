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


class ReportController extends Controller
{
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

        if ($user->isAdminSkpd()) {
            $annualBudgetQuery->where('pegawai_pw.idskpd', $user->institution);
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

            if ($user->isAdminSkpd()) {
                $monthlyDetailQuery->where('pegawai_pw.idskpd', $user->institution);
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

        if ($user->isAdminSkpd()) {
            $skpdPerformanceQuery->where('skpd.id_skpd', $user->institution);
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

        if ($user->isAdminSkpd()) {
            $growthTrendQuery->where('pegawai_pw.idskpd', $user->institution);
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

        if ($user->isAdminSkpd()) {
            $topEarnersQuery->where('pegawai_pw.idskpd', $user->institution);
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

        $allSkpds = Skpd::where('is_skpd', 1)->get();

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

        if ($user->isAdminSkpd()) {
            $masterQuery->where('pegawai_pw.idskpd', $user->institution);
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

        if ($user->isAdminSkpd()) {
            $paidQuery->where('pegawai_pw.idskpd', $user->institution);
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

        if ($user->isAdminSkpd()) {
            $allEmployeesQuery->where('pegawai_pw.idskpd', $user->institution);
        }

        $allEmployees = $allEmployeesQuery->get();

        // Get employee IDs that have payroll entries for the specified month/year
        $paidEmployeeIdsQuery = DB::table('tb_payment_detail')
            ->join('tb_payment', 'tb_payment_detail.payment_id', '=', 'tb_payment.id')
            ->join('pegawai_pw', 'tb_payment_detail.employee_id', '=', 'pegawai_pw.id')
            ->where('tb_payment.month', $month)
            ->where('tb_payment.year', $year);

        if ($user->isAdminSkpd()) {
            $paidEmployeeIdsQuery->where('pegawai_pw.idskpd', $user->institution);
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
                'skpd.nama_skpd',
                DB::raw("TIMESTAMPDIFF(YEAR, tgl_lahir, CURDATE()) AS current_age")
            )
            ->having('current_age', '>=', 55) // Show from 55 to be safe and informative
            ->orderByDesc('current_age');

        if ($user->isAdminSkpd()) {
            $query->where('pegawai_pw.idskpd', $user->institution);
        }

        return $query->get()->map(function ($emp) use ($retirementAge) {
            $birthDate = new \DateTime($emp->tgl_lahir);
            $retirementDate = (clone $birthDate)->modify("+$retirementAge years");

            return [
                'nama' => $emp->nama,
                'nip' => $emp->nip,
                'jabatan' => $emp->jabatan,
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
        $format = $request->format ?? 'excel';

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

        // ── Detailed mode for PNS / PPPK Penuh Waktu ──────────────────────────
        if (in_array($type, ['pns', 'pppk'])) {
            $table = $type === 'pns' ? 'gaji_pns' : 'gaji_pppk';
            $mapType = $type === 'pns' ? "('pns','all')" : "('pppk','all')";

            $rows = collect(DB::select("
                SELECT
                    COALESCE(sm.kode_skpd, g.kdskpd)  AS kode_skpd,
                    COALESCE(sm.nama_skpd, g.skpd)    AS nama_skpd,
                    COUNT(*)                            AS jumlah_pegawai,
                    SUM(g.gaji_pokok)                  AS gapok,
                    SUM(g.tunj_istri)                  AS tj_istri,
                    SUM(g.tunj_anak)                   AS tj_anak,
                    SUM(g.tunj_tpp)                    AS tj_tpp,
                    SUM(g.tunj_eselon)                 AS tj_eselon,
                    SUM(g.tunj_fungsional)             AS tj_fungsi,
                    SUM(g.tunj_beras)                  AS tj_beras,
                    SUM(g.tunj_pph)                    AS tj_pajak,
                    SUM(g.tunj_umum)                   AS tj_umum,
                    SUM(g.tunj_tkd)                    AS tj_bilat,
                    SUM(g.kotor)                       AS kotor,
                    SUM(g.pot_iwp)                     AS pot_iwp,
                    SUM(g.pot_iwp1)                    AS pot_iwp2,
                    SUM(g.pot_iwp8)                    AS pot_iwp8,
                    SUM(g.pot_pph)                     AS pot_pajak,
                    SUM(g.total_potongan)              AS total_potongan,
                    SUM(g.bersih)                      AS bersih
                FROM {$table} g
                LEFT JOIN (
                    SELECT mp.source_name, s2.kode_skpd, s2.nama_skpd
                    FROM skpd_mapping mp
                    JOIN skpd s2 ON mp.skpd_id = s2.id_skpd
                    WHERE mp.type IN {$mapType}
                ) sm ON g.skpd = sm.source_name
                WHERE g.bulan = ? AND g.tahun = ?
                GROUP BY COALESCE(sm.kode_skpd, g.kdskpd), COALESCE(sm.nama_skpd, g.skpd)
                ORDER BY COALESCE(sm.nama_skpd, g.skpd)
            ", [$month, $year]));

            return response()->json([
                'success' => true,
                'data' => $rows,
                'mode' => 'detail',
                'meta' => [
                    'month' => (int) $month,
                    'year' => (int) $year,
                    'type' => $type,
                    'total_skpd' => $rows->count(),
                    'total_employees' => $rows->sum('jumlah_pegawai'),
                    'grand_total' => $rows->sum('bersih'),
                ],
            ]);
        }

        // ── Summary mode for PW / All ──────────────────────────────────────────
        $parts = [];
        $params = [];


        // --- PPPK-PW (Paruh Waktu) --- from tb_payment_detail
        if (in_array($type, ['pw', 'all'])) {
            $parts[] = "
                SELECT
                    s.kode_skpd COLLATE utf8mb4_unicode_ci AS kode_skpd,
                    s.nama_skpd,
                    COUNT(DISTINCT pw.id) AS employee_count,
                    SUM(pd.gaji_pokok)    AS total_gaji_pokok,
                    SUM(pd.tunjangan)     AS total_tunjangan,
                    SUM(pd.potongan)      AS total_potongan,
                    SUM(pd.total_amoun)   AS total_bersih
                FROM tb_payment_detail pd
                JOIN pegawai_pw pw ON pd.employee_id = pw.id
                JOIN skpd s        ON pw.idskpd = s.id_skpd
                JOIN tb_payment p  ON pd.payment_id = p.id
                WHERE p.month = ? AND p.year = ?
                GROUP BY s.id_skpd, s.kode_skpd, s.nama_skpd";
            $params = array_merge($params, [$month, $year]);
        }

        // --- PNS --- from gaji_pns, normalized via skpd_mapping
        if (in_array($type, ['pns', 'all'])) {
            $parts[] = "
                SELECT
                    COALESCE(sm.kode_skpd, g.kdskpd) AS kode_skpd,
                    COALESCE(sm.nama_skpd, g.skpd)   AS nama_skpd,
                    COUNT(*)                           AS employee_count,
                    SUM(g.gaji_pokok)                 AS total_gaji_pokok,
                    SUM(g.kotor - g.gaji_pokok)       AS total_tunjangan,
                    SUM(g.total_potongan)             AS total_potongan,
                    SUM(g.bersih)                     AS total_bersih
                FROM gaji_pns g
                LEFT JOIN (
                    SELECT mp.source_name, s2.kode_skpd, s2.nama_skpd
                    FROM skpd_mapping mp
                    JOIN skpd s2 ON mp.skpd_id = s2.id_skpd
                    WHERE mp.type IN ('pns', 'all')
                ) sm ON g.skpd = sm.source_name
                WHERE g.bulan = ? AND g.tahun = ?
                GROUP BY COALESCE(sm.kode_skpd, g.kdskpd), COALESCE(sm.nama_skpd, g.skpd)";
            $params = array_merge($params, [$month, $year]);
        }

        // --- PPPK Penuh Waktu --- from gaji_pppk, normalized via skpd_mapping
        if (in_array($type, ['pppk', 'all'])) {
            $parts[] = "
                SELECT
                    COALESCE(sm.kode_skpd, g.kdskpd) AS kode_skpd,
                    COALESCE(sm.nama_skpd, g.skpd)   AS nama_skpd,
                    COUNT(*)                           AS employee_count,
                    SUM(g.gaji_pokok)                 AS total_gaji_pokok,
                    SUM(g.kotor - g.gaji_pokok)       AS total_tunjangan,
                    SUM(g.total_potongan)             AS total_potongan,
                    SUM(g.bersih)                     AS total_bersih
                FROM gaji_pppk g
                LEFT JOIN (
                    SELECT mp.source_name, s2.kode_skpd, s2.nama_skpd
                    FROM skpd_mapping mp
                    JOIN skpd s2 ON mp.skpd_id = s2.id_skpd
                    WHERE mp.type IN ('pppk', 'all')
                ) sm ON g.skpd = sm.source_name
                WHERE g.bulan = ? AND g.tahun = ?
                GROUP BY COALESCE(sm.kode_skpd, g.kdskpd), COALESCE(sm.nama_skpd, g.skpd)";
            $params = array_merge($params, [$month, $year]);
        }

        $unionSql = implode("\n UNION ALL \n", $parts);

        $paidSkpds = collect(DB::select("
            SELECT
                kode_skpd, nama_skpd,
                SUM(employee_count)    as employee_count,
                SUM(total_gaji_pokok)  as total_gaji_pokok,
                SUM(total_tunjangan)   as total_tunjangan,
                SUM(total_potongan)    as total_potongan,
                SUM(total_bersih)      as total_bersih
            FROM ($unionSql) AS combined
            GROUP BY kode_skpd, nama_skpd
            ORDER BY nama_skpd
        ", $params));

        return response()->json([
            'success' => true,
            'data' => $paidSkpds,
            'meta' => [
                'month' => (int) $month,
                'year' => (int) $year,
                'type' => $type,
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
        $format = $request->format ?? 'excel';
        $type = $request->type ?? 'all';

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
        $filename = "skpd_daftar_gaji{$typeSlug}_{$month}_{$year}";

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

            $pdf = Pdf::loadView('exports.paid-skpd-pdf', [
                'data' => $data,
                'month' => $month,
                'year' => $year,
                'monthName' => $monthName,
                'mode' => $mode,
                'type' => $type,
                'totalEmployees' => $totalEmployees,
                'grandTotal' => $grandTotal,
                'sumGajiPokok' => $sumGajiPokok,
                'sumTunjangan' => array_sum(array_column($data, $mode === 'detail' ? 'kotor' : 'total_tunjangan')),
                'sumPotongan' => array_sum(array_column($data, 'total_potongan')),
                'sumPajak' => 0,
                'sumIwp' => 0,
            ]);

            $pdf->setPaper('a4', $mode === 'detail' ? 'landscape' : 'landscape');
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

        if ($user->isAdminSkpd()) {
            $query->where('skpd.id_skpd', $user->institution);
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
        $format = $request->format ?? 'excel';

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
}
