<?php

namespace App\Services;

use App\Models\Sp2dRealization;
use App\Models\Skpd;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class Sp2dReconciliationService
{
    /**
     * Calculate SP2D Reconciliation for a specific period
     */
    public function calculateReconciliation($bulan, $tahun, $tppReconMode = 'bruto', $jenisGaji = null)
    {
        // 1. Fetch SIPD Realizations
        $sp2dReals = Sp2dRealization::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->when($jenisGaji, function($q) use ($jenisGaji) {
                return $q->where('jenis_data', 'LIKE', '%' . $jenisGaji . '%');
            })
            ->get();

        if ($sp2dReals->isEmpty()) {
            return [];
        }

        // 2. Pre-fetch Internal Data (Optimization: One query per table)
        $skpds = Skpd::all();
        $manualMappings = DB::table('skpd_mapping')->get()->groupBy('skpd_id');

        $pnsInternal = DB::table('gaji_pns')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->select('kdskpd', 'jenis_gaji', 
                DB::raw('SUM(kotor) as brutto'), 
                DB::raw('SUM(total_potongan) as potongan'), 
                DB::raw('SUM(bersih - tunj_tpp) as netto'),
                DB::raw('SUM(tunj_tpp) as tpp'),
                DB::raw('COUNT(id) as emp_count')
            )
            ->groupBy('kdskpd', 'jenis_gaji')
            ->get();

        $pppkInternal = DB::table('gaji_pppk')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->select('kdskpd', 'jenis_gaji', 
                DB::raw('SUM(kotor) as brutto'), 
                DB::raw('SUM(total_potongan) as potongan'), 
                DB::raw('SUM(bersih - tunj_tpp) as netto'),
                DB::raw('SUM(tunj_tpp) as tpp'),
                DB::raw('COUNT(id) as emp_count')
            )
            ->groupBy('kdskpd', 'jenis_gaji')
            ->get();

        $pwInternal = DB::table('tb_payment as p')
            ->join('tb_payment_detail as pd', 'p.id', '=', 'pd.payment_id')
            ->join('pegawai_pw as e', 'pd.employee_id', '=', 'e.id')
            ->where('p.month', $bulan)
            ->where('p.year', $tahun)
            ->select('e.idskpd', 
                DB::raw('SUM(IFNULL(pd.gaji_pokok, 0) + IFNULL(pd.tunjangan, 0)) as brutto'),
                DB::raw('SUM(IFNULL(pd.pajak, 0) + IFNULL(pd.iwp, 0)) as potongan'), 
                DB::raw('SUM(pd.total_amoun) as netto'),
                DB::raw('COUNT(DISTINCT pd.employee_id) as emp_count')
            )
            ->groupBy('e.idskpd')
            ->get();

        $standaloneTpp = DB::table('standalone_tpp')
            ->where('month', $bulan)
            ->where('year', $tahun)
            ->get();

        // 3. Group and Process
        $groupedData = [];

        foreach ($sp2dReals as $real) {
            $jenisDataRaw = $real->jenis_data ?? '';
            $ketRaw = strtoupper($real->keterangan ?? '');
            
            // Logic to detect unified category and jenis_gaji
            $targetJenisGajiDetected = 'Induk';
            if (str_contains($jenisDataRaw, 'SUSULAN')) $targetJenisGajiDetected = 'Susulan';
            elseif (str_contains($jenisDataRaw, 'KEKURANGAN')) $targetJenisGajiDetected = 'Kekurangan';
            elseif (str_contains($jenisDataRaw, 'TERUSAN')) $targetJenisGajiDetected = 'Terusan';
            elseif (str_contains($jenisDataRaw, 'THR') || str_contains($jenisDataRaw, 'GAJI 14') || str_contains($ketRaw, 'THR')) $targetJenisGajiDetected = 'THR';
            elseif (str_contains($jenisDataRaw, 'GAJI 13')) $targetJenisGajiDetected = 'Gaji 13';

            $category = 'UNKNOWN';
            if (str_contains($jenisDataRaw, 'PPPK-PW')) $category = 'PPPK_PW';
            elseif (str_contains($jenisDataRaw, 'PNS') && !str_contains($jenisDataRaw, 'TPP')) $category = 'PNS';
            elseif (str_contains($jenisDataRaw, 'PPPK') && !str_contains($jenisDataRaw, 'TPP')) $category = 'PPPK';
            elseif (str_contains($jenisDataRaw, 'TPP')) {
                if (str_contains($jenisDataRaw, 'PPPK') || str_contains($ketRaw, 'PPPK') || str_contains($ketRaw, 'P3K')) $category = 'TPP_PPPK';
                else $category = 'TPP_PNS';
            }

            $key = $real->skpd_id . '|' . $targetJenisGajiDetected . '|' . $category;

            if (!isset($groupedData[$key])) {
                $groupedData[$key] = [
                    'skpd_id' => $real->skpd_id,
                    'jenis_gaji' => $targetJenisGajiDetected,
                    'category' => $category,
                    'reals' => collect(),
                    'ids' => []
                ];
            }
            $groupedData[$key]['reals']->push($real);
            $groupedData[$key]['ids'][] = $real->id;
        }

        $results = collect($groupedData)->map(function ($group) use ($skpds, $pnsInternal, $pppkInternal, $pwInternal, $manualMappings, $tppReconMode, $standaloneTpp) {
            $skpd = $skpds->where('id_skpd', $group['skpd_id'])->first();
            $category = $group['category'];
            $targetType = $group['jenis_gaji'];
            $reals = $group['reals'];

            $internalCtx = [
                'brutto' => 0, 'potongan' => 0, 'netto' => 0,
                'gaji_pns' => 0, 'gaji_pppk' => 0, 'tpp_pns' => 0, 'tpp_pppk' => 0,
                'jenis_gaji' => $targetType, 'emp_count' => 0
            ];

            if ($skpd) {
                $kdskpds = [];
                if ($skpd->kode_simgaji) $kdskpds[] = $skpd->kode_simgaji;
                if (isset($manualMappings[$skpd->id_skpd])) {
                    foreach ($manualMappings[$skpd->id_skpd] as $m) if ($m->source_code) $kdskpds[] = $m->source_code;
                }
                $kdskpds = array_unique($kdskpds);

                foreach ($kdskpds as $kd) {
                    if ($category === 'PNS') {
                        $p = $pnsInternal->where('kdskpd', $kd)->where('jenis_gaji', $targetType)->first();
                        if ($p) {
                            $internalCtx['brutto'] += (float)$p->brutto;
                            $internalCtx['potongan'] += (float)$p->potongan;
                            $internalCtx['netto'] += ($targetType !== 'Induk') ? (float)$p->brutto : (float)$p->netto;
                            $internalCtx['gaji_pns'] += (float)$p->brutto;
                            $internalCtx['emp_count'] += (int)$p->emp_count;
                        }
                    } elseif ($category === 'PPPK') {
                        $pk = $pppkInternal->where('kdskpd', $kd)->where('jenis_gaji', $targetType)->first();
                        if ($pk) {
                            $internalCtx['brutto'] += (float)$pk->brutto;
                            $internalCtx['potongan'] += (float)$pk->potongan;
                            $internalCtx['netto'] += ($targetType !== 'Induk') ? (float)$pk->brutto : (float)$pk->netto;
                            $internalCtx['gaji_pppk'] += (float)$pk->brutto;
                            $internalCtx['emp_count'] += (int)$pk->emp_count;
                        }
                    } elseif ($category === 'TPP' || $category === 'TPP_PNS') {
                        $p = $pnsInternal->where('kdskpd', $kd)->where('jenis_gaji', $targetType)->first();
                        if ($p) {
                            $internalCtx['brutto'] += (float)$p->tpp;
                            $internalCtx['netto'] += (float)$p->tpp;
                            $internalCtx['tpp_pns'] += (float)$p->tpp;
                            $internalCtx['emp_count'] += (int)$p->emp_count;
                        }
                    } elseif ($category === 'TPP_PPPK') {
                        $pk = $pppkInternal->where('kdskpd', $kd)->where('jenis_gaji', $targetType)->first();
                        if ($pk) {
                            $internalCtx['brutto'] += (float)$pk->tpp;
                            $internalCtx['netto'] += (float)$pk->tpp;
                            $internalCtx['tpp_pppk'] += (float)$pk->tpp;
                            $internalCtx['emp_count'] += (int)$pk->emp_count;
                        }
                    }
                }

                // PW aggregation
                if ($category === 'PPPK_PW') {
                    $prefix = substr($skpd->kode_skpd, 0, 18);
                    $allUnitIds = $skpds->filter(fn($s) => str_starts_with($s->kode_skpd, $prefix))->pluck('id_skpd')->toArray();
                    foreach ($allUnitIds as $uid) {
                        $pw = $pwInternal->where('idskpd', $uid)->first();
                        if ($pw) {
                            $internalCtx['brutto'] += (float)$pw->brutto;
                            $internalCtx['potongan'] += (float)$pw->potongan;
                            $internalCtx['netto'] += ($targetType !== 'Induk') ? (float)$pw->brutto : (float)$pw->netto;
                            $internalCtx['emp_count'] += (int)$pw->emp_count;
                        }
                    }
                }

                // Standalone TPP integration
                if (str_contains($category, 'TPP')) {
                    $st = $standaloneTpp->where('skpd_id', $skpd->id_skpd)->where('jenis_gaji', $targetType);
                    foreach ($st as $item) {
                        if ($category === 'TPP_PPPK' && $item->employee_type !== 'pppk') continue;
                        if ($category === 'TPP_PNS' && $item->employee_type !== 'pns') continue;
                        $internalCtx['brutto'] += (float)$item->nilai;
                        $internalCtx['netto'] += (float)$item->nilai;
                        if ($item->employee_type === 'pns') $internalCtx['tpp_pns'] += (float)$item->nilai;
                        else $internalCtx['tpp_pppk'] += (float)$item->nilai;
                        $internalCtx['emp_count'] += 1;
                    }
                }
            }

            return [
                'id' => $group['ids'][0],
                'all_ids' => implode(',', $group['ids']),
                'category' => $category,
                'simgaji' => [
                    'nama_skpd' => $skpd ? $skpd->nama_skpd : 'No SKPD Mapping',
                    'brutto' => $internalCtx['brutto'],
                    'potongan' => $internalCtx['potongan'],
                    'netto' => $internalCtx['netto'],
                    'gaji_pns' => $internalCtx['gaji_pns'],
                    'gaji_pppk' => $internalCtx['gaji_pppk'],
                    'tpp_pns' => $internalCtx['tpp_pns'],
                    'tpp_pppk' => $internalCtx['tpp_pppk'],
                    'jenis_gaji' => $internalCtx['jenis_gaji'],
                    'emp_count' => $internalCtx['emp_count'],
                ],
                'sipd' => [
                    'tanggal_sp2d' => $reals->first()->tanggal_sp2d ? $reals->first()->tanggal_sp2d->format('Y-m-d') : null,
                    'nomor_sp2d' => $reals->pluck('nomor_sp2d')->implode(', '),
                    'nama_skpd' => $reals->first()->nama_skpd_sipd,
                    'jenis_data' => $reals->first()->jenis_data . ($reals->count() > 1 ? ' (' . $reals->count() . ' items)' : ''),
                    'brutto' => $reals->sum('brutto'),
                    'potongan' => $reals->sum('potongan'),
                    'netto' => ((str_starts_with($category ?? '', 'TPP') && $tppReconMode === 'bruto') || $targetType !== 'Induk') ? $reals->sum('brutto') : $reals->sum('netto'),
                    'sp2d_count' => $reals->count()
                ]
            ];
        })->values();

        return $results->toArray();
    }

    /**
     * Cache key for reconciliation results
     */
    public function getCacheKey($bulan, $tahun, $tppReconMode)
    {
        return "sp2d_recon_{$bulan}_{$tahun}_{$tppReconMode}";
    }
}
