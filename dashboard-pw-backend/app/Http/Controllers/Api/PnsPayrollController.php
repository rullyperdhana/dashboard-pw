<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GajiPns;
use App\Models\GajiPppk;
use App\Models\PayrollPosting;
use App\Imports\PnsImport;
use App\Imports\PppkImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class PnsPayrollController extends Controller
{
    /**
     * Upload XLS file
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000',
            'jenis_gaji' => 'required|in:Induk,Susulan,Kekurangan,Terusan'
        ]);

        try {
            ini_set('memory_limit', '512M');
            \Log::info('PNS Upload Started', [
                'month' => $request->month,
                'year' => $request->year,
                'jenis_gaji' => $request->jenis_gaji,
                'file_name' => $request->file('file')->getClientOriginalName()
            ]);

            // Check if posted
            if (PayrollPosting::isLocked((int) $request->year, (int) $request->month, 'PNS')) {
                return response()->json([
                    'success' => false,
                    'message' => "Data PNS periode {$request->month}/{$request->year} sudah di-POSTING (Dikunci) dan tidak dapat diubah."
                ], 403);
            }

            // Delete existing data for this period and salary type
            $deletedCount = GajiPns::where('bulan', $request->month)
                ->where('tahun', $request->year)
                ->where('jenis_gaji', $request->jenis_gaji)
                ->delete();

            \Log::info('PNS Upload - Deleted existing records', [
                'count' => $deletedCount
            ]);

            Excel::import(new PnsImport($request->month, $request->year, $request->jenis_gaji), $request->file('file'));

            $totalRecords = GajiPns::where('bulan', $request->month)->where('tahun', $request->year)->count();

            \Log::info('PNS Upload Completed', [
                'month' => $request->month,
                'year' => $request->year,
                'total_records' => $totalRecords
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PNS Payroll data imported successfully',
                'meta' => [
                    'month' => $request->month,
                    'year' => $request->year,
                    'jenis_gaji' => $request->jenis_gaji,
                    'total_records' => $totalRecords
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('PNS Upload Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Common selectRaw for dashboard summary
     */
    private function dashboardSelectRaw()
    {
        return '
            COUNT(DISTINCT nip) as total_employees,
            SUM(bersih) as total_net_salary,
            SUM(kotor) as total_gross_salary,
            SUM(total_potongan) as total_deductions,
            SUM(gaji_pokok) as total_basic_salary,
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
            SUM(pot_iwp) as total_pot_iwp,
            SUM(pot_iwp1) as total_pot_iwp1,
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
            SUM(pot_jkm) as total_pot_jkm
        ';
    }

    /**
     * Dashboard Summary
     */
    public function dashboard(Request $request)
    {
        if (!$request->has('year') || !$request->has('month')) {
            $latest = GajiPns::orderBy('tahun', 'desc')->orderBy('bulan', 'desc')->first();
            $year = $request->year ?? ($latest ? $latest->tahun : date('Y'));
            $month = $request->month ?? ($latest ? $latest->bulan : date('n'));
        } else {
            $year = $request->year;
            $month = $request->month;
        }

        \Log::info('PNS Dashboard Request', [
            'year' => $year,
            'month' => $month,
            'request_params' => $request->all()
        ]);

        $stats = GajiPns::where('tahun', $year)
            ->where('bulan', $month)
            ->selectRaw($this->dashboardSelectRaw())
            ->first();

        \Log::info('PNS Dashboard Stats', [
            'stats' => $stats->toArray()
        ]);

        // Budget Projection (12 months + THR + Gaji 13 = ~14 months)
        $projectedBudget = ($stats->total_gross_salary ?? 0) * 14;

        // Top 5 Earners
        $topEarners = GajiPns::where('tahun', $year)
            ->where('bulan', $month)
            ->orderByDesc('bersih')
            ->limit(5)
            ->get(['nama', 'nip', 'jabatan', 'bersih', 'skpd']);

        // SKPD Distribution
        $skpdStats = GajiPns::where('tahun', $year)
            ->where('bulan', $month)
            ->select('skpd', DB::raw('COUNT(*) as total'), DB::raw('SUM(kotor) as cost'))
            ->groupBy('skpd')
            ->orderByDesc('cost')
            ->limit(10)
            ->get();

        // Cost by Rank (Golongan)
        $byGolongan = GajiPns::where('tahun', $year)
            ->where('bulan', $month)
            ->whereNotNull('kdpangkat')
            ->select('kdpangkat as golongan', DB::raw('COUNT(*) as total'), DB::raw('SUM(kotor) as cost'))
            ->groupBy('kdpangkat')
            ->orderBy('kdpangkat')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => array_merge($stats->toArray(), [
                    'projected_annual_budget' => $projectedBudget,
                    'tpp_forecast_next_year' => ($stats->total_tunj_tpp ?? 0) * 12 // Simplified for now, or use average
                ]),
                'top_earners' => $topEarners,
                'skpd_breakdown' => $skpdStats,
                'golongan_breakdown' => $byGolongan,
                'period' => [
                    'month' => (int) $month,
                    'year' => (int) $year
                ]
            ]
        ]);
    }

    /**
     * List Data with Pagination
     */
    public function list(Request $request)
    {
        if (!$request->has('year') || !$request->has('month')) {
            $latest = GajiPns::orderBy('tahun', 'desc')->orderBy('bulan', 'desc')->first();
            $year = $request->year ?? ($latest ? $latest->tahun : date('Y'));
            $month = $request->month ?? ($latest ? $latest->bulan : date('n'));
        } else {
            $year = $request->year;
            $month = $request->month;
        }
        $search = $request->search;

        $query = GajiPns::where('tahun', $year)
            ->where('bulan', $month);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'LIKE', "%$search%")
                    ->orWhere('nip', 'LIKE', "%$search%")
                    ->orWhere('skpd', 'LIKE', "%$search%");
            });
        }

        $data = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Upload PPPK Payroll Data
     */
    public function uploadPppk(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000',
            'jenis_gaji' => 'required|in:Induk,Susulan,Kekurangan,Terusan'
        ]);

        try {
            ini_set('memory_limit', '512M');
            \Log::info('PPPK Upload Started', [
                'month' => $request->month,
                'year' => $request->year,
                'jenis_gaji' => $request->jenis_gaji,
                'file_name' => $request->file('file')->getClientOriginalName()
            ]);

            // Check if posted
            if (PayrollPosting::isLocked((int) $request->year, (int) $request->month, 'PPPK')) {
                return response()->json([
                    'success' => false,
                    'message' => "Data PPPK periode {$request->month}/{$request->year} sudah di-POSTING (Dikunci) dan tidak dapat diubah."
                ], 403);
            }

            // Delete existing data for this period and salary type
            $deletedCount = GajiPppk::where('bulan', $request->month)
                ->where('tahun', $request->year)
                ->where('jenis_gaji', $request->jenis_gaji)
                ->delete();

            \Log::info('PPPK Upload - Deleted existing records', [
                'count' => $deletedCount
            ]);

            Excel::import(new PppkImport($request->month, $request->year, $request->jenis_gaji), $request->file('file'));

            $totalRecords = GajiPppk::where('bulan', $request->month)->where('tahun', $request->year)->count();

            \Log::info('PPPK Upload Completed', [
                'month' => $request->month,
                'year' => $request->year,
                'total_records' => $totalRecords
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PPPK Payroll data imported successfully',
                'meta' => [
                    'month' => $request->month,
                    'year' => $request->year,
                    'jenis_gaji' => $request->jenis_gaji,
                    'total_records' => $totalRecords
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('PPPK Upload Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * PPPK Dashboard Summary
     */
    public function dashboardPppk(Request $request)
    {
        if (!$request->has('year') || !$request->has('month')) {
            $latest = GajiPppk::orderBy('tahun', 'desc')->orderBy('bulan', 'desc')->first();
            $year = $request->year ?? ($latest ? $latest->tahun : date('Y'));
            $month = $request->month ?? ($latest ? $latest->bulan : date('n'));
        } else {
            $year = $request->year;
            $month = $request->month;
        }

        \Log::info('PPPK Dashboard Request', [
            'year' => $year,
            'month' => $month,
            'request_params' => $request->all()
        ]);

        $stats = GajiPppk::where('tahun', $year)
            ->where('bulan', $month)
            ->selectRaw($this->dashboardSelectRaw())
            ->first();

        \Log::info('PPPK Dashboard Stats', [
            'stats' => $stats->toArray()
        ]);

        $projectedBudget = ($stats->total_gross_salary ?? 0) * 14;

        $topEarners = GajiPppk::where('tahun', $year)
            ->where('bulan', $month)
            ->orderByDesc('bersih')
            ->limit(5)
            ->get(['nama', 'nip', 'jabatan', 'bersih', 'skpd']);

        $skpdStats = GajiPppk::where('tahun', $year)
            ->where('bulan', $month)
            ->select('skpd', DB::raw('COUNT(*) as total'), DB::raw('SUM(kotor) as cost'))
            ->groupBy('skpd')
            ->orderByDesc('cost')
            ->limit(10)
            ->get();

        $byGolongan = GajiPppk::where('tahun', $year)
            ->where('bulan', $month)
            ->whereNotNull('kdpangkat')
            ->select('kdpangkat as golongan', DB::raw('COUNT(*) as total'), DB::raw('SUM(kotor) as cost'))
            ->groupBy('kdpangkat')
            ->orderBy('kdpangkat')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => array_merge($stats->toArray(), [
                    'projected_annual_budget' => $projectedBudget,
                    'tpp_forecast_next_year' => ($stats->total_tunj_tpp ?? 0) * 12
                ]),
                'top_earners' => $topEarners,
                'skpd_breakdown' => $skpdStats,
                'golongan_breakdown' => $byGolongan,
                'period' => [
                    'month' => (int) $month,
                    'year' => (int) $year
                ]
            ]
        ]);
    }

    /**
     * PNS Yearly Trend (for full year chart)
     */
    public function yearlyTrend(Request $request)
    {
        $year = $request->year ?? date('Y');

        $trend = GajiPns::where('tahun', $year)
            ->select('bulan', DB::raw('COUNT(DISTINCT nip) as total_employees'), DB::raw('SUM(kotor) as total_gross'), DB::raw('SUM(bersih) as total_net'), DB::raw('SUM(tunj_tpp) as total_tpp'))
            ->groupBy('bulan')
            ->having('total_employees', '>', 0)
            ->orderBy('bulan')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'year' => (int) $year,
                'trend' => $trend
            ]
        ]);
    }

    /**
     * PPPK Yearly Trend (for full year chart)
     */
    public function yearlyTrendPppk(Request $request)
    {
        $year = $request->year ?? date('Y');

        $trend = GajiPppk::where('tahun', $year)
            ->select('bulan', DB::raw('COUNT(DISTINCT nip) as total_employees'), DB::raw('SUM(kotor) as total_gross'), DB::raw('SUM(bersih) as total_net'), DB::raw('SUM(tunj_tpp) as total_tpp'))
            ->groupBy('bulan')
            ->having('total_employees', '>', 0)
            ->orderBy('bulan')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'year' => (int) $year,
                'trend' => $trend
            ]
        ]);
    }

    /**
     * Annual Report with Monthly Allowance Breakdown
     */
    public function annualReport(Request $request)
    {
        $year = $request->year ?? date('Y');
        $employeeType = $request->type ?? 'pns'; // 'pns' or 'pppk'
        $skpdFilter = $request->skpd ?? null;

        $model = $employeeType === 'pns' ? GajiPns::class : GajiPppk::class;

        $selectFields = '
            bulan,
            COUNT(DISTINCT nip) as total_employees,
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
            SUM(pot_iwp1) as total_pot_iwp1,
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
        ';

        $tunjanganFields = [
            'total_tunj_istri',
            'total_tunj_anak',
            'total_tunj_fungsional',
            'total_tunj_struktural',
            'total_tunj_umum',
            'total_tunj_beras',
            'total_tunj_pph',
            'total_tunj_tpp',
            'total_tunj_eselon',
            'total_tunj_guru',
            'total_tunj_langka',
            'total_tunj_tkd',
            'total_tunj_terpencil',
            'total_tunj_khusus',
            'total_tunj_askes',
            'total_tunj_kk',
            'total_tunj_km'
        ];

        // Single query with GROUP BY instead of 12 separate queries
        $query = $model::where('tahun', $year);
        if ($skpdFilter) {
            $query->where('skpd', $skpdFilter);
        }

        $results = $query->selectRaw($selectFields)
            ->groupBy('bulan')
            ->orderBy('bulan')
            ->get()
            ->keyBy('bulan');

        // Build monthly data for all 12 months (fill empty months with zeros)
        $numericKeys = [
            'total_employees',
            'total_gaji_pokok',
            'total_tunj_istri',
            'total_tunj_anak',
            'total_tunj_fungsional',
            'total_tunj_struktural',
            'total_tunj_umum',
            'total_tunj_beras',
            'total_tunj_pph',
            'total_tunj_tpp',
            'total_tunj_eselon',
            'total_tunj_guru',
            'total_tunj_langka',
            'total_tunj_tkd',
            'total_tunj_terpencil',
            'total_tunj_khusus',
            'total_tunj_askes',
            'total_tunj_kk',
            'total_tunj_km',
            'total_pembulatan',
            'total_kotor',
            'total_pot_iwp',
            'total_pot_iwp1',
            'total_pot_iwp8',
            'total_pot_askes',
            'total_pot_pph',
            'total_pot_bulog',
            'total_pot_taperum',
            'total_pot_sewa',
            'total_pot_hutang',
            'total_pot_korpri',
            'total_pot_irdhata',
            'total_pot_koperasi',
            'total_pot_jkk',
            'total_pot_jkm',
            'total_potongan',
            'total_bersih',
        ];

        $monthlyData = [];
        for ($month = 1; $month <= 12; $month++) {
            $data = $results->get($month);
            $row = [
                'month' => $month,
                'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
            ];

            foreach ($numericKeys as $key) {
                if ($key === 'total_employees') {
                    $row[$key] = (int) ($data->$key ?? 0);
                } else {
                    $row[$key] = (float) ($data->$key ?? 0);
                }
            }

            // Calculate total tunjangan
            $totalTunjangan = 0;
            foreach ($tunjanganFields as $field) {
                $totalTunjangan += $row[$field];
            }
            $row['total_tunjangan'] = (float) $totalTunjangan;

            $monthlyData[] = $row;
        }

        // Calculate yearly totals dynamically
        $yearlyTotal = [];
        foreach ($numericKeys as $field) {
            $yearlyTotal[$field] = array_sum(array_column($monthlyData, $field));
        }
        $yearlyTotal['total_tunjangan'] = array_sum(array_column($monthlyData, 'total_tunjangan'));

        // Calculate averages
        $monthsWithData = count(array_filter($monthlyData, fn($m) => $m['total_employees'] > 0));
        $avgEmployees = $monthsWithData > 0 ? $yearlyTotal['total_employees'] / $monthsWithData : 0;
        $avgSalaryPerEmployee = $yearlyTotal['total_employees'] > 0 ? $yearlyTotal['total_bersih'] / $yearlyTotal['total_employees'] : 0;

        // Optimized SKPD list — single indexed query
        $skpdList = $model::where('tahun', $year)
            ->whereNotNull('skpd')
            ->where('skpd', '!=', '')
            ->select('skpd')
            ->distinct()
            ->orderBy('skpd')
            ->pluck('skpd')
            ->map(function ($rawName) use ($employeeType) {
                $mapping = \App\Models\SkpdMapping::with('skpd')
                    ->where('source_name', $rawName)
                    ->whereIn('type', [$employeeType, 'all'])
                    ->first();
                return [
                    'source_name' => $rawName,
                    'display_name' => $mapping?->skpd?->nama_skpd ?? $rawName,
                    'is_mapped' => $mapping !== null,
                ];
            })
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'monthly' => $monthlyData,
                'yearly_total' => $yearlyTotal,
                'summary' => [
                    'avg_employees_per_month' => round($avgEmployees),
                    'avg_salary_per_employee' => round($avgSalaryPerEmployee),
                    'months_with_data' => $monthsWithData,
                ],
                'meta' => [
                    'year' => (int) $year,
                    'type' => $employeeType,
                    'skpd_filter' => $skpdFilter,
                    'skpd_list' => $skpdList,
                ]
            ]
        ]);
    }
}
