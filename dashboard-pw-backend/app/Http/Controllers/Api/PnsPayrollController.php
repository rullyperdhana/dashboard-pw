<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GajiPns;
use App\Models\GajiPppk;
use App\Models\PayrollPosting;
// Removed PnsImport and PppkImport
use Illuminate\Http\Request;
// Removed Excel import
use Illuminate\Support\Facades\DB;

class PnsPayrollController extends Controller
{


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

        $jenisGaji = $request->jenis_gaji ?? 'Induk';

        \Log::info('PNS Dashboard Request', [
            'year' => $year,
            'month' => $month,
            'jenis_gaji' => $jenisGaji,
            'request_params' => $request->all()
        ]);

        $query = GajiPns::where('tahun', $year)
            ->where('bulan', $month)
            ->where('jenis_gaji', $jenisGaji);

        $stats = $query->selectRaw($this->dashboardSelectRaw())
            ->first();

        \Log::info('PNS Dashboard Stats', [
            'stats' => $stats->toArray()
        ]);

        // Budget Projection (12 months + THR + Gaji 13 = ~14 months)
        $projectedBudget = ($stats->total_gross_salary ?? 0) * 14;

        // Top 5 Earners
        $topEarners = GajiPns::where('gaji_pns.tahun', $year)
            ->where('gaji_pns.bulan', $month)
            ->leftJoin('satkers as s1', function ($join) {
                $join->on('gaji_pns.kdskpd', '=', 's1.kdskpd')
                    ->on('gaji_pns.kdsatker', '=', 's1.kdsatker');
            })
            ->leftJoin(DB::raw('(SELECT DISTINCT kdskpd, nmskpd FROM satkers) as s2'), 'gaji_pns.kdskpd', '=', 's2.kdskpd')
            ->leftJoin(DB::raw("(SELECT mp.source_code, s_ref.nama_skpd FROM skpd_mapping mp JOIN skpd s_ref ON mp.skpd_id = s_ref.id_skpd WHERE mp.type IN ('pns', 'all')) as sm"), 'gaji_pns.kdskpd', '=', 'sm.source_code')
            ->leftJoin('master_pegawai as mp', 'gaji_pns.nip', '=', 'mp.nip')
            ->leftJoin('ref_eselon as re', 'mp.kdeselon', '=', 're.kd_eselon')
            ->orderByDesc('gaji_pns.bersih')
            ->limit(5)
            ->get([
                'gaji_pns.nama',
                'gaji_pns.nip',
                DB::raw('CASE WHEN mp.kdfungsi = "00000" THEN re.uraian ELSE gaji_pns.jabatan END as jabatan'),
                'gaji_pns.bersih',
                DB::raw('COALESCE(sm.nama_skpd, s1.nmsatker, s2.nmskpd, gaji_pns.satker, gaji_pns.skpd) as skpd')
            ]);

        // SKPD Distribution
        $skpdStats = GajiPns::where('gaji_pns.tahun', $year)
            ->where('gaji_pns.bulan', $month)
            ->leftJoin('satkers as s1', function ($join) {
                $join->on('gaji_pns.kdskpd', '=', 's1.kdskpd')
                    ->on('gaji_pns.kdsatker', '=', 's1.kdsatker');
            })
            ->leftJoin(DB::raw('(SELECT DISTINCT kdskpd, nmskpd FROM satkers) as s2'), 'gaji_pns.kdskpd', '=', 's2.kdskpd')
            ->leftJoin(DB::raw("(SELECT mp.source_code, s_ref.nama_skpd FROM skpd_mapping mp JOIN skpd s_ref ON mp.skpd_id = s_ref.id_skpd WHERE mp.type IN ('pns', 'all')) as sm"), 'gaji_pns.kdskpd', '=', 'sm.source_code')
            ->select(
                DB::raw('COALESCE(sm.nama_skpd, s1.nmsatker, s2.nmskpd, gaji_pns.satker, gaji_pns.skpd) as skpd'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(gaji_pns.kotor) as cost')
            )
            ->groupBy(DB::raw('COALESCE(sm.nama_skpd, s1.nmsatker, s2.nmskpd, gaji_pns.satker, gaji_pns.skpd)'))
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
        $jenisGaji = $request->jenis_gaji ?? 'Induk';

        $query = GajiPns::where('gaji_pns.tahun', $year)
            ->where('gaji_pns.bulan', $month)
            ->where('gaji_pns.jenis_gaji', $jenisGaji)
            ->leftJoin('satkers as s1', function ($join) {
                $join->on('gaji_pns.kdskpd', '=', 's1.kdskpd')
                    ->on('gaji_pns.kdsatker', '=', 's1.kdsatker');
            })
            ->leftJoin(DB::raw('(SELECT DISTINCT kdskpd, nmskpd FROM satkers) as s2'), 'gaji_pns.kdskpd', '=', 's2.kdskpd')
            ->leftJoin(DB::raw("(SELECT mp.source_code, s_ref.nama_skpd FROM skpd_mapping mp JOIN skpd s_ref ON mp.skpd_id = s_ref.id_skpd WHERE mp.type IN ('pns', 'all')) as sm"), 'gaji_pns.kdskpd', '=', 'sm.source_code')
            ->leftJoin('master_pegawai as mp', 'gaji_pns.nip', '=', 'mp.nip')
            ->leftJoin('ref_eselon as re', 'mp.kdeselon', '=', 're.kd_eselon')
            ->select(
                'gaji_pns.*',
                DB::raw('COALESCE(sm.nama_skpd, s1.nmsatker, s2.nmskpd, gaji_pns.satker, gaji_pns.skpd) as skpd_display'),
                DB::raw('CASE WHEN mp.kdfungsi = "00000" THEN re.uraian ELSE gaji_pns.jabatan END as resolved_jabatan')
            );

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('gaji_pns.nama', 'LIKE', "%$search%")
                    ->orWhere('gaji_pns.nip', 'LIKE', "%$search%")
                    ->orWhere('gaji_pns.skpd', 'LIKE', "%$search%")
                    ->orWhere('s1.nmsatker', 'LIKE', "%$search%")
                    ->orWhere('s2.nmskpd', 'LIKE', "%$search%")
                    ->orWhere('sm.nama_skpd', 'LIKE', "%$search%")
                    ->orWhere('gaji_pns.kdskpd', 'LIKE', "%$search%");
            });
        }

        $data = $query->paginate(20);

        // Map names if ref exists
        $data->getCollection()->transform(function ($item) {
            $item->skpd = $item->skpd_display;
            $item->jabatan = $item->resolved_jabatan ?? $item->jabatan;
            return $item;
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
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

        $jenisGaji = $request->jenis_gaji ?? 'Induk';

        \Log::info('PPPK Dashboard Request', [
            'year' => $year,
            'month' => $month,
            'jenis_gaji' => $jenisGaji,
            'request_params' => $request->all()
        ]);

        $query = GajiPppk::where('tahun', $year)
            ->where('bulan', $month)
            ->where('jenis_gaji', $jenisGaji);

        $stats = $query->selectRaw($this->dashboardSelectRaw())
            ->first();

        \Log::info('PPPK Dashboard Stats', [
            'stats' => $stats->toArray()
        ]);

        $projectedBudget = ($stats->total_gross_salary ?? 0) * 14;

        // Top 5 Earners
        $topEarners = GajiPppk::where('gaji_pppk.tahun', $year)
            ->where('gaji_pppk.bulan', $month)
            ->leftJoin('satkers as s1', function ($join) {
                $join->on('gaji_pppk.kdskpd', '=', 's1.kdskpd')
                    ->on('gaji_pppk.kdsatker', '=', 's1.kdsatker');
            })
            ->leftJoin(DB::raw('(SELECT DISTINCT kdskpd, nmskpd FROM satkers) as s2'), 'gaji_pppk.kdskpd', '=', 's2.kdskpd')
            ->leftJoin(DB::raw("(SELECT mp.source_code, s_ref.nama_skpd FROM skpd_mapping mp JOIN skpd s_ref ON mp.skpd_id = s_ref.id_skpd WHERE mp.type IN ('pppk', 'all')) as sm"), 'gaji_pppk.kdskpd', '=', 'sm.source_code')
            ->leftJoin('master_pegawai as mp', 'gaji_pppk.nip', '=', 'mp.nip')
            ->leftJoin('ref_eselon as re', 'mp.kdeselon', '=', 're.kd_eselon')
            ->orderByDesc('gaji_pppk.bersih')
            ->limit(5)
            ->get([
                'gaji_pppk.nama',
                'gaji_pppk.nip',
                DB::raw('CASE WHEN mp.kdfungsi = "00000" THEN re.uraian ELSE gaji_pppk.jabatan END as jabatan'),
                'gaji_pppk.bersih',
                DB::raw('COALESCE(sm.nama_skpd, s1.nmsatker, s2.nmskpd, gaji_pppk.satker, gaji_pppk.skpd) as skpd')
            ]);

        // SKPD Distribution
        $skpdStats = GajiPppk::where('gaji_pppk.tahun', $year)
            ->where('gaji_pppk.bulan', $month)
            ->leftJoin('satkers as s1', function ($join) {
                $join->on('gaji_pppk.kdskpd', '=', 's1.kdskpd')
                    ->on('gaji_pppk.kdsatker', '=', 's1.kdsatker');
            })
            ->leftJoin(DB::raw('(SELECT DISTINCT kdskpd, nmskpd FROM satkers) as s2'), 'gaji_pppk.kdskpd', '=', 's2.kdskpd')
            ->leftJoin(DB::raw("(SELECT mp.source_code, s_ref.nama_skpd FROM skpd_mapping mp JOIN skpd s_ref ON mp.skpd_id = s_ref.id_skpd WHERE mp.type IN ('pppk', 'all')) as sm"), 'gaji_pppk.kdskpd', '=', 'sm.source_code')
            ->select(
                DB::raw('COALESCE(sm.nama_skpd, s1.nmsatker, s2.nmskpd, gaji_pppk.satker, gaji_pppk.skpd) as skpd'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(gaji_pppk.kotor) as cost')
            )
            ->groupBy(DB::raw('COALESCE(sm.nama_skpd, s1.nmsatker, s2.nmskpd, gaji_pppk.satker, gaji_pppk.skpd)'))
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
        $jenisGaji = $request->jenis_gaji ?? 'Induk';

        $query = GajiPns::where('tahun', $year)
            ->where('jenis_gaji', $jenisGaji);

        $trend = $query->select('bulan', DB::raw('COUNT(DISTINCT nip) as total_employees'), DB::raw('SUM(kotor) as total_gross'), DB::raw('SUM(bersih) as total_net'), DB::raw('SUM(tunj_tpp) as total_tpp'))
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
        $jenisGaji = $request->jenis_gaji ?? 'Induk';

        $query = GajiPppk::where('tahun', $year)
            ->where('jenis_gaji', $jenisGaji);

        $trend = $query->select('bulan', DB::raw('COUNT(DISTINCT nip) as total_employees'), DB::raw('SUM(kotor) as total_gross'), DB::raw('SUM(bersih) as total_net'), DB::raw('SUM(tunj_tpp) as total_tpp'))
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
        $jenisGajiFilter = $request->jenis_gaji ?? 'Induk';

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
        $tableName = (new $model)->getTable();
        $query = $model::where("$tableName.tahun", $year);
        if ($skpdFilter) {
            $query->where("$tableName.kdskpd", $skpdFilter);
        }
        if ($jenisGajiFilter) {
            $query->where("$tableName.jenis_gaji", $jenisGajiFilter);
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

        // Optimized SKPD list — join with satkers for better default names
        $tableName = (new $model)->getTable();
        $skpdList = $model::where("$tableName.tahun", $year)
            ->whereNotNull("$tableName.kdskpd")
            ->leftJoin('satkers as s1', function ($join) use ($tableName) {
                $join->on("$tableName.kdskpd", '=', 's1.kdskpd')
                    ->on("$tableName.kdsatker", '=', 's1.kdsatker');
            })
            ->leftJoin(DB::raw('(SELECT DISTINCT kdskpd, nmskpd FROM satkers) as s2'), "$tableName.kdskpd", '=', 's2.kdskpd')
            ->leftJoin(DB::raw("(SELECT mp.source_code, s_ref.nama_skpd FROM skpd_mapping mp JOIN skpd s_ref ON mp.skpd_id = s_ref.id_skpd WHERE mp.type IN ('$employeeType', 'all')) as sm"), "$tableName.kdskpd", '=', 'sm.source_code')
            ->select(
                DB::raw("$tableName.kdskpd as id_skpd"),
                DB::raw("COALESCE(MAX(sm.nama_skpd), MAX(s1.nmsatker), MAX(s2.nmskpd), MAX($tableName.skpd)) as nama_skpd")
            )
            ->groupBy("$tableName.kdskpd")
            ->orderBy('nama_skpd')
            ->get()
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
