<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Exports\EstimationExport;
use App\Models\GajiPppk;
use App\Models\GajiPns;
use App\Models\PegawaiPw;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

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

        // BPJS base: LEAST(gaji_pokok + tunj_keluarga + tunj_jabatan/umum + TPP, 12000000) per employee
        $bpjsCap = 12000000;
        $bpjsBaseExpression = "LEAST(IFNULL(gaji_pokok, 0) + IFNULL(tunj_istri, 0) + IFNULL(tunj_anak, 0) + IFNULL(tunj_fungsional, 0) + IFNULL(tunj_struktural, 0) + IFNULL(tunj_umum, 0) + IFNULL(tunj_tpp, 0), $bpjsCap)";
        $totalBpjsBase = (clone $query)->sum(DB::raw($bpjsBaseExpression));

        // 3. Calculate Estimation
        $estJkk = $totalGajiPokok * ($jkkPercent / 100);
        $estJkm = $totalGajiPokok * ($jkmPercent / 100);
        $estBpjs = $totalBpjsBase * ($bpjsPercent / 100);
        $totalEstimation = $totalGajiPokok + $totalTunjangan + $estJkk + $estJkm;

        // 4. Get Breakdown by SKPD (Aggregated by mapping)
        $details = GajiPppk::select(
            DB::raw('CASE WHEN skpd.id_skpd IS NOT NULL THEN CAST(skpd.id_skpd AS CHAR) ELSE gaji_pppk.kdskpd END as group_id'),
            DB::raw('MAX(CASE WHEN skpd.id_skpd IS NOT NULL THEN 1 ELSE 0 END) as is_mapped'),
            DB::raw('COALESCE(skpd.nama_skpd, sat.nmskpd, gaji_pppk.skpd) as mapped_nama_skpd'),
            DB::raw('COUNT(gaji_pppk.id) as employee_count'),
            DB::raw('SUM(gaji_pppk.gaji_pokok) as total_gaji_pokok'),
            DB::raw("SUM($tunjanganExpression) as total_tunjangan"),
            DB::raw("SUM($bpjsBaseExpression) as total_bpjs_base")
        )
            ->leftJoin('skpd_mapping', function ($join) {
                $join->on('gaji_pppk.kdskpd', '=', 'skpd_mapping.source_code')
                    ->whereIn('skpd_mapping.type', ['pppk', 'all']);
            })
            ->leftJoin('skpd', 'skpd_mapping.skpd_id', '=', 'skpd.id_skpd')
            ->leftJoin(DB::raw('(SELECT DISTINCT kdskpd, nmskpd FROM satkers) as sat'), 'gaji_pppk.kdskpd', '=', 'sat.kdskpd')
            ->where('gaji_pppk.bulan', $month)
            ->where('gaji_pppk.tahun', $year)
            ->groupBy('group_id', 'mapped_nama_skpd')
            ->orderBy('mapped_nama_skpd')
            ->get()
            ->map(function ($item) use ($jkkPercent, $jkmPercent, $bpjsPercent) {
                $gapok = (float) $item->total_gaji_pokok;
                $tunj = (float) $item->total_tunjangan;
                $bpjsBase = (float) $item->total_bpjs_base;
                $jkk = $gapok * ($jkkPercent / 100);
                $jkm = $gapok * ($jkmPercent / 100);
                $bpjs = $bpjsBase * ($bpjsPercent / 100);

                return [
                    'id_skpd' => $item->group_id,
                    'is_mapped' => (bool) $item->is_mapped,
                    'nama_skpd' => $item->mapped_nama_skpd,
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
            DB::raw('COALESCE(skpd.nama_skpd, pegawai_pw.skpd) as nama_skpd'),
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

        // BPJS base: LEAST(gaji_pokok + tunj_keluarga + tunj_jabatan/umum + TPP, 12000000) per employee
        $bpjsCap = 12000000;
        $bpjsBaseExpression = "LEAST(IFNULL(gaji_pokok, 0) + IFNULL(tunj_istri, 0) + IFNULL(tunj_anak, 0) + IFNULL(tunj_fungsional, 0) + IFNULL(tunj_struktural, 0) + IFNULL(tunj_umum, 0) + IFNULL(tunj_tpp, 0), $bpjsCap)";
        $totalBpjsBase = (clone $query)->sum(DB::raw($bpjsBaseExpression));

        // 4. Get Breakdown by SKPD (Aggregated by mapping)
        $details = GajiPns::select(
            DB::raw('CASE WHEN skpd.id_skpd IS NOT NULL THEN CAST(skpd.id_skpd AS CHAR) ELSE gaji_pns.kdskpd END as group_id'),
            DB::raw('MAX(CASE WHEN skpd.id_skpd IS NOT NULL THEN 1 ELSE 0 END) as is_mapped'),
            DB::raw('COALESCE(skpd.nama_skpd, sat.nmskpd, gaji_pns.skpd) as mapped_nama_skpd'),
            DB::raw('COUNT(gaji_pns.id) as employee_count'),
            DB::raw('SUM(gaji_pns.gaji_pokok) as total_gaji_pokok'),
            DB::raw("SUM($tunjanganExpression) as total_tunjangan"),
            DB::raw("SUM($bpjsBaseExpression) as total_bpjs_base")
        )
            ->leftJoin('skpd_mapping', function ($join) {
                $join->on('gaji_pns.kdskpd', '=', 'skpd_mapping.source_code')
                    ->whereIn('skpd_mapping.type', ['pns', 'all']);
            })
            ->leftJoin('skpd', 'skpd_mapping.skpd_id', '=', 'skpd.id_skpd')
            ->leftJoin(DB::raw('(SELECT DISTINCT kdskpd, nmskpd FROM satkers) as sat'), 'gaji_pns.kdskpd', '=', 'sat.kdskpd')
            ->where('gaji_pns.bulan', $month)
            ->where('gaji_pns.tahun', $year)
            ->groupBy('group_id', 'mapped_nama_skpd')
            ->orderBy('mapped_nama_skpd')
            ->get()
            ->map(function ($item) use ($jkkPercent, $jkmPercent, $bpjsPercent) {
                $gapok = (float) $item->total_gaji_pokok;
                $tunj = (float) $item->total_tunjangan;
                $bpjsBase = (float) $item->total_bpjs_base;
                $jkk = $gapok * ($jkkPercent / 100);
                $jkm = $gapok * ($jkmPercent / 100);
                $bpjs = $bpjsBase * ($bpjsPercent / 100);

                return [
                    'id_skpd' => $item->group_id,
                    'is_mapped' => (bool) $item->is_mapped,
                    'nama_skpd' => $item->mapped_nama_skpd,
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

    public function clearPayrollData(Request $request)
    {
        $validated = $request->validate([
            'target' => 'required|string|in:pns,pppk,both',
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer',
            'jenis_gaji' => 'nullable|string'
        ]);

        $target = $validated['target'];
        $month = $validated['month'] ?? null;
        $year = $validated['year'] ?? null;
        $jenisGaji = $validated['jenis_gaji'] ?? null;

        $results = [];

        if ($target === 'pns' || $target === 'both') {
            $query = GajiPns::query();
            if ($month)
                $query->where('bulan', $month);
            if ($year)
                $query->where('tahun', $year);
            if ($jenisGaji)
                $query->where('jenis_gaji', $jenisGaji);

            $count = $query->delete();
            $results['pns'] = $count;
        }

        if ($target === 'pppk' || $target === 'both') {
            $query = GajiPppk::query();
            if ($month)
                $query->where('bulan', $month);
            if ($year)
                $query->where('tahun', $year);
            if ($jenisGaji)
                $query->where('jenis_gaji', $jenisGaji);

            $count = $query->delete();
            $results['pppk'] = $count;
        }

        Log::info("User " . auth()->user()->username . " cleared payroll data", [
            'results' => $results,
            'params' => $validated
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus.',
            'details' => $results
        ]);
    }

    // ========== PER-EMPLOYEE DETAIL METHODS ==========

    private function getEmployeeDetailPnsOrPppk($request, $modelClass, $type)
    {
        $month = $request->month;
        $year = $request->year;
        $kdskpd = $request->kdskpd; // This could be Real SKPD ID or Raw Code
        $modelTable = (new $modelClass)->getTable();

        // Get percentages from settings
        $jkkPercent = (float) (\App\Models\Setting::where('key', 'pppk_jkk_percentage')->first()?->value ?? 0.24);
        $jkmPercent = (float) (\App\Models\Setting::where('key', 'pppk_jkm_percentage')->first()?->value ?? 0.72);
        $bpjsPercent = 4.0;
        $bpjsCap = 12000000;

        $query = $modelClass::where($modelTable . '.bulan', $month)->where($modelTable . '.tahun', $year);

        // Add joins for Name Resolution
        $query->leftJoin('skpd_mapping', function ($join) use ($modelTable, $type) {
            $join->on($modelTable . '.kdskpd', '=', 'skpd_mapping.source_code')
                ->whereIn('skpd_mapping.type', [$type, 'all']);
        })
            ->leftJoin('skpd', 'skpd_mapping.skpd_id', '=', 'skpd.id_skpd')
            ->leftJoin(DB::raw('(SELECT DISTINCT kdskpd, nmskpd FROM satkers) as sat'), $modelTable . '.kdskpd', '=', 'sat.kdskpd')
            ->select($modelTable . '.*', DB::raw('COALESCE(skpd.nama_skpd, sat.nmskpd, ' . $modelTable . '.skpd) as resolved_skpd_name'));

        // If kdskpd looks like a Real SKPD ID (numeric and exists in skpd table)
        // we should fetch all raw codes mapped to it.
        if (is_numeric($kdskpd)) {
            $mappedCodes = \App\Models\SkpdMapping::where('skpd_id', $kdskpd)
                ->whereIn('type', [$type, 'all'])
                ->pluck('source_code')
                ->toArray();

            if (!empty($mappedCodes)) {
                $query->whereIn($modelTable . '.kdskpd', $mappedCodes);
            } else {
                // Fallback: maybe it's actually a raw code that just happens to be numeric
                $query->where($modelTable . '.kdskpd', $kdskpd);
            }
        } elseif ($kdskpd) {
            $query->where($modelTable . '.kdskpd', $kdskpd);
        }

        $data = $query->get()->map(function ($item) use ($jkkPercent, $jkmPercent, $bpjsPercent, $bpjsCap) {
            $gapok = (float) $item->gaji_pokok;

            // Allowances for BPJS base
            $tunjKeluarga = (float) ($item->tunj_istri + $item->tunj_anak);
            $tunjJabatan = (float) ($item->tunj_fungsional + $item->tunj_struktural + $item->tunj_umum);
            $tpp = (float) $item->tunj_tpp;

            $bpjsBase = min($gapok + $tunjKeluarga + $tunjJabatan + $tpp, $bpjsCap);

            $jkk = $gapok * ($jkkPercent / 100);
            $jkm = $gapok * ($jkmPercent / 100);
            $bpjs = $bpjsBase * ($bpjsPercent / 100);

            return [
                'nip' => $item->nip,
                'nama' => $item->nama,
                'jabatan' => $item->jabatan,
                'skpd' => $item->resolved_skpd_name,
                'gaji_pokok' => $gapok,
                'tunj_keluarga' => $tunjKeluarga,
                'tunj_jabatan' => $tunjJabatan,
                'tunj_tpp' => $tpp,
                'bpjs_base' => $bpjsBase,
                'jkk' => round($jkk, 2),
                'jkm' => round($jkm, 2),
                'bpjs_kesehatan' => round($bpjs, 2),
                'total_estimation' => round($gapok + $jkk + $jkm, 2)
            ];
        });

        return $data;
    }

    public function pppkEstimationDetail(Request $request)
    {
        $data = $this->getEmployeeDetailPnsOrPppk($request, GajiPppk::class, 'pppk');
        // Get percentages from settings for the response
        $settings = [
            'jkk_percent' => (float) (\App\Models\Setting::where('key', 'pppk_jkk_percentage')->first()?->value ?? 0.24),
            'jkm_percent' => (float) (\App\Models\Setting::where('key', 'pppk_jkm_percentage')->first()?->value ?? 0.72),
            'bpjs_percent' => 4.0
        ];
        return response()->json(['success' => true, 'data' => $data, 'settings' => $settings]);
    }

    public function pnsEstimationDetail(Request $request)
    {
        $data = $this->getEmployeeDetailPnsOrPppk($request, GajiPns::class, 'pns');
        // Get percentages from settings for the response
        $settings = [
            'jkk_percent' => (float) (\App\Models\Setting::where('key', 'pppk_jkk_percentage')->first()?->value ?? 0.24),
            'jkm_percent' => (float) (\App\Models\Setting::where('key', 'pppk_jkm_percentage')->first()?->value ?? 0.72),
            'bpjs_percent' => 4.0
        ];
        return response()->json(['success' => true, 'data' => $data, 'settings' => $settings]);
    }

    public function pppkPwEstimationDetail(Request $request)
    {
        $jkkPercent = (float) Setting::where('key', 'pppk_jkk_percentage')->value('value') ?? 0.24;
        $jkmPercent = (float) Setting::where('key', 'pppk_jkm_percentage')->value('value') ?? 0.72;
        $bpjsPercent = 4.0;
        $bpjsCap = 12000000;

        $month = $request->month ?? date('n');
        $year = $request->year ?? date('Y');
        $idskpd = $request->kdskpd;

        $selectedDate = \Carbon\Carbon::createFromDate($year, $month, 1);
        $cutoffBirthDate = $selectedDate->copy()->subYears(58);

        $query = PegawaiPw::whereDate('tgl_lahir', '>=', $cutoffBirthDate->format('Y-m-d'));
        if ($idskpd) {
            $query->where('idskpd', $idskpd);
        }

        $employees = $query->leftJoin('skpd', 'pegawai_pw.idskpd', '=', 'skpd.id_skpd')
            ->select('pegawai_pw.*', DB::raw('COALESCE(skpd.nama_skpd, pegawai_pw.skpd) as resolved_skpd_name'))
            ->orderBy('resolved_skpd_name')
            ->orderBy('pegawai_pw.nama')
            ->get();

        $data = $employees->map(function ($emp) use ($jkkPercent, $jkmPercent, $bpjsPercent, $bpjsCap) {
            $gapok = (float) $emp->gapok;
            $tunj = (float) $emp->tunjangan;
            $bpjsBase = min($gapok + $tunj, $bpjsCap);
            $jkk = round($gapok * ($jkkPercent / 100), 2);
            $jkm = round($gapok * ($jkmPercent / 100), 2);
            $bpjs = round($bpjsBase * ($bpjsPercent / 100), 2);

            return [
                'nip' => $emp->nip,
                'nama' => $emp->nama,
                'jabatan' => $emp->jabatan,
                'skpd' => $emp->resolved_skpd_name ?? '',
                'gaji_pokok' => $gapok,
                'tunjangan' => $tunj,
                'bpjs_base' => $bpjsBase,
                'jkk' => $jkk,
                'jkm' => $jkm,
                'bpjs_kesehatan' => $bpjs,
                'total_estimation' => round($gapok + $tunj + $jkk + $jkm, 2)
            ];
        })->toArray();

        return response()->json([
            'success' => true,
            'data' => $data,
            'settings' => [
                'jkk_percent' => $jkkPercent,
                'jkm_percent' => $jkmPercent,
                'bpjs_percent' => $bpjsPercent,
            ]
        ]);
    }

    // ========== EXPORT METHODS ==========

    public function pppkEstimationExport(Request $request)
    {
        $data = $this->getEmployeeDetailPnsOrPppk($request, GajiPppk::class, 'pppk');
        $month = (int) $request->month;
        $year = (int) $request->year;
        $skpdName = $request->skpd_name ?? '';
        $filename = "estimasi_pppk_{$month}_{$year}.xlsx";

        return Excel::download(new EstimationExport($data->toArray(), $month, $year, 'pppk', $skpdName), $filename);
    }

    public function pnsEstimationExport(Request $request)
    {
        $data = $this->getEmployeeDetailPnsOrPppk($request, GajiPns::class, 'pns');
        $month = (int) $request->month;
        $year = (int) $request->year;
        $skpdName = $request->skpd_name ?? '';
        $filename = "estimasi_pns_{$month}_{$year}.xlsx";

        return Excel::download(new EstimationExport($data->toArray(), $month, $year, 'pns', $skpdName), $filename);
    }

    public function pppkPwEstimationExport(Request $request)
    {
        $jkkPercent = (float) Setting::where('key', 'pppk_jkk_percentage')->value('value') ?? 0.24;
        $jkmPercent = (float) Setting::where('key', 'pppk_jkm_percentage')->value('value') ?? 0.72;
        $bpjsPercent = 4.0;
        $bpjsCap = 12000000;

        $month = (int) ($request->month ?? date('n'));
        $year = (int) ($request->year ?? date('Y'));
        $idskpd = $request->kdskpd;
        $skpdName = $request->skpd_name ?? '';

        $selectedDate = \Carbon\Carbon::createFromDate($year, $month, 1);
        $cutoffBirthDate = $selectedDate->copy()->subYears(58);

        $query = PegawaiPw::whereDate('tgl_lahir', '>=', $cutoffBirthDate->format('Y-m-d'));
        if ($idskpd) {
            $query->where('idskpd', $idskpd);
        }

        $employees = $query->leftJoin('skpd', 'pegawai_pw.idskpd', '=', 'skpd.id_skpd')
            ->select('pegawai_pw.*', 'skpd.nama_skpd')
            ->orderBy('skpd.nama_skpd')
            ->orderBy('pegawai_pw.nama')
            ->get();

        $data = $employees->map(function ($emp) use ($jkkPercent, $jkmPercent, $bpjsPercent, $bpjsCap) {
            $gapok = (float) $emp->gapok;
            $tunj = (float) $emp->tunjangan;
            $bpjsBase = min($gapok + $tunj, $bpjsCap);
            $jkk = round($gapok * ($jkkPercent / 100), 2);
            $jkm = round($gapok * ($jkmPercent / 100), 2);
            $bpjs = round($bpjsBase * ($bpjsPercent / 100), 2);

            return [
                'nip' => $emp->nip,
                'nama' => $emp->nama,
                'jabatan' => $emp->jabatan,
                'skpd' => $emp->nama_skpd ?? $emp->skpd ?? '',
                'gaji_pokok' => $gapok,
                'tunjangan' => $tunj,
                'bpjs_base' => $bpjsBase,
                'jkk' => $jkk,
                'jkm' => $jkm,
                'bpjs_kesehatan' => $bpjs,
                'total_estimation' => round($gapok + $tunj + $jkk + $jkm, 2)
            ];
        })->toArray();

        $filename = "estimasi_pppk_pw_{$month}_{$year}.xlsx";
        return Excel::download(new EstimationExport($data, $month, $year, 'pppk_pw', $skpdName), $filename);
    }
}
