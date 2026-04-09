<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class PayrollEstimationService
{
    protected $tunjanganColumns = [
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

    /**
     * Get payroll settings
     */
    public function getSettings()
    {
        return [
            'jkk_percent' => (float) Setting::where('key', 'pppk_jkk_percentage')->value('value') ?? 0.24,
            'jkm_percent' => (float) Setting::where('key', 'pppk_jkm_percentage')->value('value') ?? 0.72,
            'bpjs_percent' => 4.0,
            'bpjs_cap' => 12000000,
            'ump_kalsel' => (float) Setting::where('key', 'ump_kalsel')->value('value') ?? 3725000,
        ];
    }

    /**
     * Get SQL expression for total tunjangan
     */
    public function getTunjanganExpression($prefix = '')
    {
        $cols = array_map(function($col) use ($prefix) {
            return "IFNULL(" . ($prefix ? $prefix . '.' : '') . $col . ", 0)";
        }, $this->tunjanganColumns);
        
        return implode(' + ', $cols);
    }

    /**
     * Get SQL expression for BPJS Base calculation
     * Formula: LEAST(GP + Tunj Keluarga + Tunj Jabatan/Umum + TPP, Cap)
     */
    public function getBpjsBaseExpression($cap, $prefix = '')
    {
        $p = $prefix ? $prefix . '.' : '';
        $formula = "IFNULL({$p}gaji_pokok, 0) + " .
                   "IFNULL({$p}tunj_istri, 0) + " .
                   "IFNULL({$p}tunj_anak, 0) + " .
                   "IFNULL({$p}tunj_fungsional, 0) + " .
                   "IFNULL({$p}tunj_struktural, 0) + " .
                   "IFNULL({$p}tunj_umum, 0) + " .
                   "IFNULL({$p}tunj_tpp, 0)";
                   
        return "LEAST($formula, $cap)";
    }

    /**
     * Apply standard filters to a payroll query
     */
    public function applyFilters($query, $month, $year, $filters = [], $table = '')
    {
        $p = $table ? $table . '.' : '';
        
        $query->where("{$p}bulan", $month)
              ->where("{$p}tahun", $year)
              ->whereNotIn("{$p}jenis_gaji", ['THR', 'Gaji 13', '13', 'Gaji ke-13']);

        if (!empty($filters['jenis_gaji']) && is_array($filters['jenis_gaji'])) {
            $query->whereIn("{$p}jenis_gaji", $filters['jenis_gaji']);
        }

        return $query;
    }

    /**
     * Getlatest period for a model
     */
    public function getLatestPeriod($modelClass)
    {
        return $modelClass::select('bulan', 'tahun')
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc')
            ->first();
    }

    /**
     * Get Estimation Summary for a payroll category
     */
    public function getEstimationSummary($modelClass, $month, $year, $filters = [])
    {
        $settings = $this->getSettings();
        $query = $modelClass::query();
        $this->applyFilters($query, $month, $year, $filters);

        $totalGajiPokok = (clone $query)->sum('gaji_pokok');
        $totalPegawai = (clone $query)->count();
        
        $tunjanganExpression = $this->getTunjanganExpression();
        $totalTunjangan = (clone $query)->sum(DB::raw($tunjanganExpression));

        $bpjsBaseExpression = $this->getBpjsBaseExpression($settings['bpjs_cap']);
        $totalBpjsBase = (clone $query)->sum(DB::raw($bpjsBaseExpression));

        $estJkk = $totalGajiPokok * ($settings['jkk_percent'] / 100);
        $estJkm = $totalGajiPokok * ($settings['jkm_percent'] / 100);
        $estBpjs = $totalBpjsBase * ($settings['bpjs_percent'] / 100);

        return [
            'period' => [
                'month' => (int) $month,
                'year' => (int) $year
            ],
            'employees_count' => $totalPegawai,
            'total_gaji_pokok' => (float) $totalGajiPokok,
            'total_tunjangan' => (float) $totalTunjangan,
            'settings' => $settings,
            'estimation' => [
                'jkk_amount' => round($estJkk, 2),
                'jkm_amount' => round($estJkm, 2),
                'bpjs_kesehatan_amount' => round($estBpjs, 2),
                'total_amount' => round($totalGajiPokok + $estJkk + $estJkm, 2)
            ]
        ];
    }

    /**
     * Get SKPD Breakdown for estimation
     */
    public function getEstimationDetails($modelClass, $month, $year, $filters = [], $type = 'pppk')
    {
        $settings = $this->getSettings();
        $tableName = (new $modelClass)->getTable();
        $tunjanganExpression = $this->getTunjanganExpression($tableName);
        $bpjsBaseExpression = $this->getBpjsBaseExpression($settings['bpjs_cap'], $tableName);

        $query = $modelClass::select(
            DB::raw("CASE WHEN skpd.id_skpd IS NOT NULL THEN CAST(skpd.id_skpd AS CHAR) ELSE $tableName.kdskpd END as group_id"),
            DB::raw('MAX(CASE WHEN skpd.id_skpd IS NOT NULL THEN 1 ELSE 0 END) as is_mapped'),
            DB::raw("COALESCE(skpd.nama_skpd, sat.nmskpd, $tableName.skpd) as mapped_nama_skpd"),
            DB::raw("COUNT($tableName.id) as employee_count"),
            DB::raw("SUM($tableName.gaji_pokok) as total_gaji_pokok"),
            DB::raw("SUM($tunjanganExpression) as total_tunjangan"),
            DB::raw("SUM($bpjsBaseExpression) as total_bpjs_base")
        )
            ->leftJoin('skpd_mapping', function ($join) use ($tableName, $type) {
                $join->on("$tableName.kdskpd", '=', 'skpd_mapping.source_code')
                    ->whereIn('skpd_mapping.type', [$type, 'all']);
            })
            ->leftJoin('skpd', 'skpd_mapping.skpd_id', '=', 'skpd.id_skpd')
            ->leftJoin(DB::raw("(SELECT DISTINCT kdskpd, nmskpd FROM satkers) as sat"), "$tableName.kdskpd", '=', 'sat.kdskpd');

        $this->applyFilters($query, $month, $year, $filters, $tableName);

        return $query->groupBy('group_id', 'mapped_nama_skpd')
            ->orderBy('mapped_nama_skpd')
            ->get()
            ->map(function ($item) use ($settings) {
                $gapok = (float) $item->total_gaji_pokok;
                $bpjsBase = (float) $item->total_bpjs_base;
                $jkk = $gapok * ($settings['jkk_percent'] / 100);
                $jkm = $gapok * ($settings['jkm_percent'] / 100);
                $bpjs = $bpjsBase * ($settings['bpjs_percent'] / 100);

                return [
                    'id_skpd' => $item->group_id,
                    'is_mapped' => (bool) $item->is_mapped,
                    'nama_skpd' => $item->mapped_nama_skpd,
                    'employee_count' => (int) $item->employee_count,
                    'total_gaji_pokok' => $gapok,
                    'total_tunjangan' => (float) $item->total_tunjangan,
                    'tunjangan_jkk' => round($jkk, 2),
                    'tunjangan_jkm' => round($jkm, 2),
                    'bpjs_kesehatan' => round($bpjs, 2),
                    'total_estimation' => round($gapok + $jkk + $jkm, 2)
                ];
            });
    }

    /**
     * Get Estimation Summary for PPPK-PW (static from master table)
     */
    public function getPppkPwEstimationSummary()
    {
        $settings = $this->getSettings();
        
        // Retirement cutoff logic
        $retirementAge = 58;
        $cutoffBirthDate = \Carbon\Carbon::now()->subYears($retirementAge);
        
        $query = \App\Models\PegawaiPw::whereDate('tgl_lahir', '>=', $cutoffBirthDate->format('Y-m-d'));
        
        $totalGajiPokok = (clone $query)->sum('gapok');
        $totalTunjangan = (clone $query)->sum('tunjangan');
        $totalPegawai = (clone $query)->count();
        
        $totalBpjsBase = (clone $query)->sum(DB::raw("LEAST(GREATEST(IFNULL(gapok, 0), {$settings['ump_kalsel']}), {$settings['bpjs_cap']})"));

        $estJkk = $totalGajiPokok * ($settings['jkk_percent'] / 100);
        $estJkm = $totalGajiPokok * ($settings['jkm_percent'] / 100);
        $estBpjs = $totalBpjsBase * ($settings['bpjs_percent'] / 100);

        return [
            'period' => [
                'month' => (int) date('n'),
                'year' => (int) date('Y')
            ],
            'employees_count' => $totalPegawai,
            'total_gaji_pokok' => (float) $totalGajiPokok,
            'total_tunjangan' => (float) $totalTunjangan,
            'settings' => $settings,
            'estimation' => [
                'jkk_amount' => round($estJkk, 2),
                'jkm_amount' => round($estJkm, 2),
                'bpjs_kesehatan_amount' => round($estBpjs, 2),
                'total_amount' => round($totalGajiPokok + $totalTunjangan + $estJkk + $estJkm, 2)
            ]
        ];
    }

    /**
     * Get Estimation Details for PPPK-PW
     */
    public function getPppkPwEstimationDetails($idskpd = null)
    {
        $settings = $this->getSettings();
        
        $retirementAge = 58;
        $cutoffBirthDate = \Carbon\Carbon::now()->subYears($retirementAge);
        
        $query = \App\Models\PegawaiPw::whereDate('tgl_lahir', '>=', $cutoffBirthDate->format('Y-m-d'));
        if ($idskpd) {
            $query->where('idskpd', $idskpd);
        }

        $employees = $query->leftJoin('skpd', 'pegawai_pw.idskpd', '=', 'skpd.id_skpd')
            ->leftJoin('master_pegawai as mp', 'pegawai_pw.nip', '=', 'mp.nip')
            ->leftJoin('ref_eselon as re', 'mp.kdeselon', '=', 're.kd_eselon')
            ->select(
                'pegawai_pw.*',
                DB::raw('COALESCE(skpd.nama_skpd, pegawai_pw.skpd) as resolved_skpd_name'),
                DB::raw('CASE WHEN mp.kdfungsi = "00000" THEN re.uraian ELSE pegawai_pw.jabatan END as resolved_jabatan_name')
            )
            ->orderBy('resolved_skpd_name')
            ->orderBy('pegawai_pw.nama')
            ->get();

        return $employees->map(function ($emp) use ($settings) {
            $gapok = (float) $emp->gapok;
            $tunj = (float) $emp->tunjangan;
            $bpjsBase = min(max($gapok, (float)$settings['ump_kalsel']), (float)$settings['bpjs_cap']);
            
            return [
                'nip' => $emp->nip,
                'nama' => $emp->nama,
                'jabatan' => $emp->resolved_jabatan_name ?? $emp->jabatan,
                'skpd' => $emp->resolved_skpd_name ?? '',
                'gaji_pokok' => $gapok,
                'tunjangan' => $tunj,
                'bpjs_base' => $bpjsBase,
                'jkk' => round($gapok * ($settings['jkk_percent'] / 100), 2),
                'jkm' => round($gapok * ($settings['jkm_percent'] / 100), 2),
                'bpjs_kesehatan' => round($bpjsBase * ($settings['bpjs_percent'] / 100), 2),
                'total_estimation' => round($gapok + $tunj + ($gapok * ($settings['jkk_percent'] / 100)) + ($gapok * ($settings['jkm_percent'] / 100)), 2)
            ];
        });
    }

    /**
     * Get employee detail for estimation context
     */
    public function getEmployeeDetailsByNip($modelClass, $nip, $month, $year, $filters = [])
    {
        $settings = $this->getSettings();
        $tunjanganExpression = $this->getTunjanganExpression();
        $bpjsBaseExpression = $this->getBpjsBaseExpression($settings['bpjs_cap']);

        $query = $modelClass::where('nip', $nip);
        $this->applyFilters($query, $month, $year, $filters);

        return $query->select(
            '*',
            DB::raw("($tunjanganExpression) as calc_total_tunjangan"),
            DB::raw("($bpjsBaseExpression) as calc_bpjs_base")
        )->get()->map(function($item) use ($settings) {
            $gapok = (float) $item->gaji_pokok;
            $bpjsBase = (float) $item->calc_bpjs_base;
            
            $item->est_jkk = round($gapok * ($settings['jkk_percent'] / 100), 2);
            $item->est_jkm = round($gapok * ($settings['jkm_percent'] / 100), 2);
            $item->est_bpjs = round($bpjsBase * ($settings['bpjs_percent'] / 100), 2);
            $item->total_estimation = round($gapok + $item->est_jkk + $item->est_jkm, 2);
            
            return $item;
        });
    }
}
