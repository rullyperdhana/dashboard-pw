<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GajiPppk;
use App\Models\GajiPns;
use App\Models\PegawaiPw;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->keyBy('key');
        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required'
        ]);

        foreach ($validated['settings'] as $item) {
            Setting::updateOrCreate(
                ['key' => $item['key']],
                ['value' => $item['value']]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
    }

    public function pppkEstimation(Request $request)
    {
        // 1. Get Settings
        $jkkPercent = (float) Setting::where('key', 'pppk_jkk_percentage')->value('value') ?? 0.24;
        $jkmPercent = (float) Setting::where('key', 'pppk_jkm_percentage')->value('value') ?? 0.72;
        $bpjsPercent = 4.0; // BPJS Kesehatan employer share: 4%

        // 2. Determine Period
        if ($request->has('month') && $request->has('year')) {
            $month = $request->month;
            $year = $request->year;
        } else {
            // Find latest month with data
            $latestPeriod = GajiPppk::select('bulan', 'tahun')
                ->orderBy('tahun', 'desc')
                ->orderBy('bulan', 'desc')
                ->first();

            if (!$latestPeriod) {
                return response()->json([
                    'success' => false,
                    'message' => 'No PPPK data found'
                ]);
            }
            $month = $latestPeriod->bulan;
            $year = $latestPeriod->tahun;
        }

        $query = GajiPppk::where('bulan', $month)
            ->where('tahun', $year);

        $totalGajiPokok = (clone $query)->sum('gaji_pokok');
        $totalPegawai = (clone $query)->count();

        // Calculate Total Tunjangan
        $tunjanganColumns = [
            'tunj_istri',
            'tunj_anak',
            'tunj_fungsional',
            'tunj_struktural',
            'tunj_umum',
            'tunj_beras',
            'tunj_pph',
            'tunj_tpp',
            'tunj_eselon',
            'tunj_guru',
            'tunj_langka',
            'tunj_tkd',
            'tunj_terpencil',
            'tunj_khusus',
            'tunj_askes',
            'tunj_kk',
            'tunj_km',
            'pembulatan'
        ];

        $tunjanganExpression = implode(' + ', $tunjanganColumns);
        $totalTunjangan = (clone $query)->sum(DB::raw($tunjanganExpression));

        // BPJS base: LEAST(gaji_pokok + tunj_tpp, 12000000) per employee
        $bpjsCap = 12000000;
        $bpjsBaseExpression = "LEAST(IFNULL(gaji_pokok, 0) + IFNULL(tunj_tpp, 0), $bpjsCap)";
        $totalBpjsBase = (clone $query)->sum(DB::raw($bpjsBaseExpression));

        // 3. Calculate Estimation
        $estJkk = $totalGajiPokok * ($jkkPercent / 100);
        $estJkm = $totalGajiPokok * ($jkmPercent / 100);
        $estBpjs = $totalBpjsBase * ($bpjsPercent / 100);
        $totalEstimation = $totalGajiPokok + $totalTunjangan + $estJkk + $estJkm;

        // 4. Get Breakdown by SKPD
        $details = GajiPppk::select(
            'kdskpd as id_skpd',
            'skpd as nama_skpd',
            DB::raw('COUNT(id) as employee_count'),
            DB::raw('SUM(gaji_pokok) as total_gaji_pokok'),
            DB::raw("SUM($tunjanganExpression) as total_tunjangan"),
            DB::raw("SUM($bpjsBaseExpression) as total_bpjs_base")
        )
            ->where('bulan', $month)
            ->where('tahun', $year)
            ->groupBy('kdskpd', 'skpd')
            ->orderBy('skpd')
            ->get()
            ->map(function ($item) use ($jkkPercent, $jkmPercent, $bpjsPercent) {
                $gapok = (float) $item->total_gaji_pokok;
                $tunj = (float) $item->total_tunjangan;
                $bpjsBase = (float) $item->total_bpjs_base;
                $jkk = $gapok * ($jkkPercent / 100);
                $jkm = $gapok * ($jkmPercent / 100);
                $bpjs = $bpjsBase * ($bpjsPercent / 100);

                return [
                    'id_skpd' => $item->id_skpd,
                    'nama_skpd' => $item->nama_skpd,
                    'employee_count' => (int) $item->employee_count,
                    'total_gaji_pokok' => $gapok,
                    'total_tunjangan' => $tunj,
                    'tunjangan_jkk' => round($jkk, 2),
                    'tunjangan_jkm' => round($jkm, 2),
                    'bpjs_kesehatan' => round($bpjs, 2),
                    'total_estimation' => round($gapok + $jkk + $jkm, 2)
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'month' => (int) $month,
                    'year' => (int) $year
                ],
                'employees_count' => $totalPegawai,
                'total_gaji_pokok' => (float) $totalGajiPokok,
                'total_tunjangan' => (float) $totalTunjangan,
                'settings' => [
                    'jkk_percent' => $jkkPercent,
                    'jkm_percent' => $jkmPercent,
                    'bpjs_percent' => $bpjsPercent,
                ],
                'estimation' => [
                    'jkk_amount' => round($estJkk, 2),
                    'jkm_amount' => round($estJkm, 2),
                    'bpjs_kesehatan_amount' => round($estBpjs, 2),
                    'total_amount' => round($totalGajiPokok + $estJkk + $estJkm, 2)
                ],
                'details' => $details
            ]
        ]);
    }

    public function pppkPwEstimation(Request $request)
    {
        // 1. Get Params
        $month = $request->month ?? date('n');
        $year = $request->year ?? date('Y');
        $selectedDate = \Carbon\Carbon::createFromDate($year, $month, 1);

        // Retirement cutoff: Employees born BEFORE this date are considered retired
        // assuming retirement age is 58
        $retirementAge = 58;
        $cutoffBirthDate = $selectedDate->copy()->subYears($retirementAge);

        // 2. Get Settings
        $jkkPercent = (float) Setting::where('key', 'pppk_jkk_percentage')->value('value') ?? 0.24;
        $jkmPercent = (float) Setting::where('key', 'pppk_jkm_percentage')->value('value') ?? 0.72;
        $bpjsPercent = 4.0; // BPJS Kesehatan employer share: 4%

        // Base Query with Retirement Filter
        $query = PegawaiPw::whereDate('tgl_lahir', '>=', $cutoffBirthDate->format('Y-m-d'));

        // 3. Get Total from PegawaiPw (Master Table)
        $totalGajiPokok = (clone $query)->sum('gapok');
        $totalTunjangan = (clone $query)->sum('tunjangan');
        $totalPegawai = (clone $query)->count();
        // BPJS base: LEAST(gapok + tunjangan, 12000000) per employee
        $bpjsCap = 12000000;
        $totalBpjsBase = (clone $query)->sum(DB::raw("LEAST(IFNULL(gapok, 0) + IFNULL(tunjangan, 0), $bpjsCap)"));

        // 4. Get Breakdown by SKPD
        $details = PegawaiPw::select(
            'pegawai_pw.idskpd',
            'skpd.nama_skpd',
            DB::raw('COUNT(pegawai_pw.id) as employee_count'),
            DB::raw('SUM(pegawai_pw.gapok) as total_gapok'),
            DB::raw('SUM(pegawai_pw.tunjangan) as total_tunjangan'),
            DB::raw("SUM(LEAST(IFNULL(pegawai_pw.gapok, 0) + IFNULL(pegawai_pw.tunjangan, 0), $bpjsCap)) as total_bpjs_base")
        )
            ->whereDate('pegawai_pw.tgl_lahir', '>=', $cutoffBirthDate->format('Y-m-d'))
            ->leftJoin('skpd', 'pegawai_pw.idskpd', '=', 'skpd.id_skpd')
            ->groupBy('pegawai_pw.idskpd', 'skpd.nama_skpd')
            ->orderBy('skpd.nama_skpd')
            ->get()
            ->map(function ($item) use ($jkkPercent, $jkmPercent, $bpjsPercent) {
                $gapok = (float) $item->total_gapok;
                $tunj = (float) $item->total_tunjangan;
                $jkk = $gapok * ($jkkPercent / 100);
                $jkm = $gapok * ($jkmPercent / 100);
                $bpjsBase = (float) $item->total_bpjs_base;
                $bpjs = $bpjsBase * ($bpjsPercent / 100);

                return [
                    'id_skpd' => $item->idskpd,
                    'nama_skpd' => $item->nama_skpd ?? 'Unknown SKPD',
                    'employee_count' => (int) $item->employee_count,
                    'total_gaji_pokok' => $gapok,
                    'total_tunjangan' => $tunj,
                    'tunjangan_jkk' => round($jkk, 2),
                    'tunjangan_jkm' => round($jkm, 2),
                    'bpjs_kesehatan' => round($bpjs, 2),
                    'total_estimation' => round($gapok + $tunj + $jkk + $jkm, 2)
                ];
            });

        // 5. Calculate Global Estimation
        $estJkk = $totalGajiPokok * ($jkkPercent / 100);
        $estJkm = $totalGajiPokok * ($jkmPercent / 100);
        $estBpjs = $totalBpjsBase * ($bpjsPercent / 100);

        $totalEstimation = $totalGajiPokok + $totalTunjangan + $estJkk + $estJkm;

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'month' => (int) $month,
                    'year' => (int) $year
                ],
                'employees_count' => $totalPegawai,
                'total_gaji_pokok' => (float) $totalGajiPokok,
                'total_tunjangan' => (float) $totalTunjangan,
                'settings' => [
                    'jkk_percent' => $jkkPercent,
                    'jkm_percent' => $jkmPercent,
                    'bpjs_percent' => $bpjsPercent,
                ],
                'estimation' => [
                    'jkk_amount' => round($estJkk, 2),
                    'jkm_amount' => round($estJkm, 2),
                    'bpjs_kesehatan_amount' => round($estBpjs, 2),
                    'total_amount' => round($totalEstimation, 2)
                ],
                'details' => $details
            ]
        ]);
    }

    public function pnsEstimation(Request $request)
    {
        // 1. Get Settings
        $jkkPercent = (float) Setting::where('key', 'pppk_jkk_percentage')->value('value') ?? 0.24;
        $jkmPercent = (float) Setting::where('key', 'pppk_jkm_percentage')->value('value') ?? 0.72;
        $bpjsPercent = 4.0; // BPJS Kesehatan employer share: 4%

        // 2. Determine Period
        if ($request->has('month') && $request->has('year')) {
            $month = $request->month;
            $year = $request->year;
        } else {
            // Find latest month with data
            $latestPeriod = GajiPns::select('bulan', 'tahun')
                ->orderBy('tahun', 'desc')
                ->orderBy('bulan', 'desc')
                ->first();

            if (!$latestPeriod) {
                return response()->json([
                    'success' => false,
                    'message' => 'No PNS data found'
                ]);
            }
            $month = $latestPeriod->bulan;
            $year = $latestPeriod->tahun;
        }

        // 3. Get Totals
        // Ensure month/year set
        if (!$month || !$year) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid period'
            ]);
        }

        $query = GajiPns::where('bulan', $month)->where('tahun', $year);

        $totalGajiPokok = (clone $query)->sum('gaji_pokok');
        $totalPegawai = (clone $query)->count();

        // Calculate Total Tunjangan
        $tunjanganColumns = [
            'tunj_istri',
            'tunj_anak',
            'tunj_fungsional',
            'tunj_struktural',
            'tunj_umum',
            'tunj_beras',
            'tunj_pph',
            'tunj_tpp',
            'tunj_eselon',
            'tunj_guru',
            'tunj_langka',
            'tunj_tkd',
            'tunj_terpencil',
            'tunj_khusus',
            'tunj_askes',
            'tunj_kk',
            'tunj_km',
            'pembulatan'
        ];

        $tunjanganExpression = implode(' + ', $tunjanganColumns);
        $totalTunjangan = (clone $query)->sum(DB::raw($tunjanganExpression));

        // BPJS base: LEAST(gaji_pokok + tunj_tpp, 12000000) per employee
        $bpjsCap = 12000000;
        $bpjsBaseExpression = "LEAST(IFNULL(gaji_pokok, 0) + IFNULL(tunj_tpp, 0), $bpjsCap)";
        $totalBpjsBase = (clone $query)->sum(DB::raw($bpjsBaseExpression));

        // 4. Get Breakdown by SKPD
        $details = GajiPns::select(
            'kdskpd as id_skpd',
            'skpd as nama_skpd',
            DB::raw('COUNT(id) as employee_count'),
            DB::raw('SUM(gaji_pokok) as total_gaji_pokok'),
            DB::raw("SUM($tunjanganExpression) as total_tunjangan"),
            DB::raw("SUM($bpjsBaseExpression) as total_bpjs_base")
        )
            ->where('bulan', $month)
            ->where('tahun', $year)
            ->groupBy('kdskpd', 'skpd')
            ->orderBy('skpd')
            ->get()
            ->map(function ($item) use ($jkkPercent, $jkmPercent, $bpjsPercent) {
                $gapok = (float) $item->total_gaji_pokok;
                $tunj = (float) $item->total_tunjangan;
                $bpjsBase = (float) $item->total_bpjs_base;
                $jkk = $gapok * ($jkkPercent / 100);
                $jkm = $gapok * ($jkmPercent / 100);
                $bpjs = $bpjsBase * ($bpjsPercent / 100);

                return [
                    'id_skpd' => $item->id_skpd,
                    'nama_skpd' => $item->nama_skpd,
                    'employee_count' => (int) $item->employee_count,
                    'total_gaji_pokok' => $gapok,
                    'total_tunjangan' => $tunj,
                    'tunjangan_jkk' => round($jkk, 2),
                    'tunjangan_jkm' => round($jkm, 2),
                    'bpjs_kesehatan' => round($bpjs, 2),
                    'total_estimation' => round($gapok + $jkk + $jkm, 2)
                ];
            });

        // 5. Calculate Global Estimation
        $estJkk = $totalGajiPokok * ($jkkPercent / 100);
        $estJkm = $totalGajiPokok * ($jkmPercent / 100);
        $estBpjs = $totalBpjsBase * ($bpjsPercent / 100);
        // Exclude tunjangan from total estimation as per user request
        $totalEstimation = $totalGajiPokok + $estJkk + $estJkm;

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'month' => (int) $month,
                    'year' => (int) $year
                ],
                'employees_count' => $totalPegawai,
                'total_gaji_pokok' => (float) $totalGajiPokok,
                'total_tunjangan' => (float) $totalTunjangan,
                'settings' => [
                    'jkk_percent' => $jkkPercent,
                    'jkm_percent' => $jkmPercent,
                    'bpjs_percent' => $bpjsPercent,
                ],
                'estimation' => [
                    'jkk_amount' => round($estJkk, 2),
                    'jkm_amount' => round($estJkm, 2),
                    'bpjs_kesehatan_amount' => round($estBpjs, 2),
                    'total_amount' => round($totalEstimation, 2)
                ],
                'details' => $details
            ]
        ]);
    }
}
