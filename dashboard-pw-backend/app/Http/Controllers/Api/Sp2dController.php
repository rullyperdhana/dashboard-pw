<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sp2dRealization;
use App\Models\Skpd;
use App\Imports\Sp2dImport;
use App\Imports\Sp2dPotonganImport;
use App\Exports\Sp2dReconExport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class Sp2dController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
            'bulan' => 'required|numeric',
            'tahun' => 'required|numeric',
            'target_type' => 'nullable|string',
            'preview' => 'nullable|boolean',
        ]);

        try {
            $isPreview = $request->boolean('preview');
            $targetType = $request->target_type;
            
            if ($targetType === 'POTONGAN') {
                $import = new Sp2dPotonganImport($isPreview);
            } else {
                $import = new Sp2dImport($targetType, $isPreview);
            }
            
            Excel::import($import, $request->file('file'));

            if ($isPreview) {
                $previewData = $import->previewData;
                return response()->json([
                    'status' => 'success',
                    'preview' => $previewData,
                    'summary' => [
                        'total_rows' => count($previewData),
                        'unmapped_rows' => $targetType !== 'POTONGAN' ? collect($previewData)->whereNull('skpd_id')->count() : 0,
                        'mapped_rows' => $targetType !== 'POTONGAN' ? collect($previewData)->whereNotNull('skpd_id')->count() : count($previewData),
                    ]
                ]);
            }

            return response()->json(['message' => 'Data SP2D berhasil diimpor/sinkronisasi.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal impor: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'nomor_sp2d' => 'required|string',
            'tanggal_sp2d' => 'required|date',
            'nama_skpd_sipd' => 'nullable|string',
            'skpd_id' => 'required|exists:skpds,id_skpd',
            'jenis_data' => 'required|string',
            'netto' => 'required|numeric',
            'bulan' => 'required|numeric',
            'tahun' => 'required|numeric',
            'keterangan' => 'nullable|string',
        ]);

        $realization = Sp2dRealization::create(array_merge($request->all(), [
            'is_manual' => 1,
            'brutto' => $request->netto, // Default brutto to netto for manual entries if not provided
            'potongan' => 0
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Data realisasi manual berhasil ditambahkan',
            'data' => $realization
        ]);
    }

    public function getStatus(Request $request)
    {
        $bulan = $request->query('bulan', date('n'));
        $tahun = $request->query('tahun', date('Y'));
        $tppReconMode = $request->query('tpp_recon_mode', 'bruto'); // default to bruto
        
        // 1. Get Internal Data with Individual Counts
        $pnsInternal = \DB::table('gaji_pns')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->select(
                'kdskpd', 
                'jenis_gaji', 
                \DB::raw('SUM(bersih) as netto'), 
                \DB::raw('SUM(tunj_tpp) as tpp'),
                \DB::raw('COUNT(DISTINCT nip) as emp_count')
            )
            ->groupBy('kdskpd', 'jenis_gaji')
            ->get();

        $pppkInternal = \DB::table('gaji_pppk')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->select(
                'kdskpd', 
                'jenis_gaji', 
                \DB::raw('SUM(bersih) as netto'), 
                \DB::raw('SUM(tunj_tpp) as tpp'),
                \DB::raw('COUNT(DISTINCT nip) as emp_count')
            )
            ->groupBy('kdskpd', 'jenis_gaji')
            ->get();

        $pwInternal = \DB::table('tb_payment_detail as pd')
            ->join('tb_payment as p', 'pd.payment_id', '=', 'p.id')
            ->join('pegawai_pw as e', 'pd.employee_id', '=', 'e.id')
            ->where('p.month', $bulan)
            ->where('p.year', $tahun)
            ->select(
                'e.idskpd', 
                \DB::raw('SUM(pd.total_amoun) as netto'),
                \DB::raw('COUNT(DISTINCT e.nip) as emp_count')
            )
            ->groupBy('e.idskpd')
            ->get();

        // 2. Get Realizations
        $realizations = Sp2dRealization::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get();

        // 3. Get SKPDs & Mapping
        $skpds = Skpd::where('is_skpd', 1)->get();
        $manualMappingsCount = \DB::table('skpd_mapping')->count();
        $mappings = \DB::table('skpd_mapping')->get()->groupBy('skpd_id');

        // 4. Build Detailed Rows
        $resultData = [];
        $types = ['Induk', 'Susulan', 'Kekurangan', 'Terusan', 'Gaji 13', 'Gaji 14 / THR'];

        foreach ($skpds as $skpd) {
            $kdskpds = [$skpd->kode_simgaji];
            if (isset($mappings[$skpd->id_skpd])) {
                foreach ($mappings[$skpd->id_skpd] as $m) if ($m->source_code) $kdskpds[] = $m->source_code;
            }
            $kdskpds = array_filter(array_unique($kdskpds));

            foreach ($types as $type) {
                // Check if there is ANY data for this (SKPD, Type)
                $hasPns = $pnsInternal->whereIn('kdskpd', $kdskpds)->where('jenis_gaji', $type)->isNotEmpty();
                $hasPppk = $pppkInternal->whereIn('kdskpd', $kdskpds)->where('jenis_gaji', $type)->isNotEmpty();
                
                $typeKey = strtoupper($type);
                if ($type === 'Gaji 14 / THR') $typeKey = 'THR';
                
                $reals = $realizations->where('skpd_id', $skpd->id_skpd)
                    ->filter(fn($r) => str_contains(strtoupper($r->jenis_data), $typeKey) || ($type === 'Induk' && !str_contains($r->jenis_data, 'SUSULAN') && !str_contains($r->jenis_data, 'KEKURANGAN') && !str_contains($r->jenis_data, 'TERUSAN') && !str_contains($r->jenis_data, '13') && !str_contains($r->jenis_data, 'THR')));

                if (!$hasPns && !$hasPppk && $reals->isEmpty() && !($type === 'Induk' && $pwInternal->where('idskpd', $skpd->id_skpd)->isNotEmpty())) {
                    continue; 
                }

                $pnsInt = $pnsInternal->whereIn('kdskpd', $kdskpds)->where('jenis_gaji', $type);
                $pppkInt = $pppkInternal->whereIn('kdskpd', $kdskpds)->where('jenis_gaji', $type);
                
                // Aggregate PW data across all sub-units for this main SKPD
                $pwInt = collect();
                if ($type === 'Induk') {
                    $prefix = substr($skpd->kode_skpd, 0, 18);
                    $allUnitIds = Skpd::where('kode_skpd', 'LIKE', $prefix . '%')->pluck('id_skpd')->toArray();
                    $pwInt = $pwInternal->whereIn('idskpd', $allUnitIds);
                }

                $resultData[] = [
                    'id_skpd' => $skpd->id_skpd,
                    'nama_skpd' => $skpd->nama_skpd,
                    'jenis_gaji' => $type,
                    'pns' => $this->formatStatus(
                        $reals->filter(fn($r) => str_contains($r->jenis_data, 'PNS') && !str_contains($r->jenis_data, 'TPP')),
                        $pnsInt->sum('netto'),
                        $pnsInt->sum('emp_count')
                    ),
                    'pppk' => $this->formatStatus(
                        $reals->filter(fn($r) => str_contains($r->jenis_data, 'PPPK') && !str_contains($r->jenis_data, 'TPP') && !str_contains($r->jenis_data, 'PPPK-PW')),
                        $pppkInt->sum('netto'),
                        $pppkInt->sum('emp_count')
                    ),
                    'pppk_pw' => $this->formatStatus(
                        $reals->filter(fn($r) => str_contains($r->jenis_data, 'PPPK-PW')),
                        $pwInt->sum('netto'),
                        $pwInt->sum('emp_count')
                    ),
                    'tpp' => $this->formatStatus(
                        $reals->filter(fn($r) => str_contains($r->jenis_data, 'TPP')),
                        ($pnsInt->sum('tpp') + $pppkInt->sum('tpp')),
                        ($pnsInt->where('tpp', '>', 0)->sum('emp_count') + $pppkInt->where('tpp', '>', 0)->sum('emp_count')),
                        $tppReconMode
                    ),
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $resultData,
            'meta' => [
                'bulan' => (int) $bulan,
                'tahun' => (int) $tahun
            ]
        ]);
    }

    private function formatStatus($collection, $internalAmount = 0, $empCount = 0, $mode = 'netto')
    {
        $realizedAmount = ($mode === 'bruto') ? $collection->sum('brutto') : $collection->sum('netto');
        return [
            'is_realized' => $collection->isNotEmpty(),
            'nomor_sp2d' => $collection->first()?->nomor_sp2d,
            'tanggal_sp2d' => $collection->first()?->tanggal_sp2d,
            'netto' => $realizedAmount, // Key remains 'netto' for frontend but contains mode-specific value
            'internal_amount' => (float)$internalAmount,
            'count' => (int)$empCount,
            'sp2d_count' => $collection->count()
        ];
    }

    public function getTransactions(Request $request)
    {
        $bulan = $request->query('bulan', date('n'));
        $tahun = $request->query('tahun', date('Y'));
        $idSkpd = $request->query('id_skpd');
        $jenisGaji = $request->query('jenis_gaji');

        $query = Sp2dRealization::where('bulan', $bulan)
            ->where('tahun', $tahun);

        if ($idSkpd) {
            $query->where('skpd_id', $idSkpd);
        }

        if ($jenisGaji) {
            $query->where('jenis_data', 'LIKE', '%' . strtoupper($jenisGaji) . '%');
        }

        $data = $query->orderBy('tanggal_sp2d', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'netto' => 'required|numeric',
            'nomor_sp2d' => 'required|string',
            'jenis_data' => 'required|string|in:PNS,PPPK,TPP',
        ]);

        $realization = Sp2dRealization::findOrFail($id);
        $realization->update($request->only(['netto', 'nomor_sp2d', 'jenis_data']));

        return response()->json([
            'status' => 'success',
            'message' => 'Data realisasi berhasil diperbarui',
            'data' => $realization
        ]);
    }

    public function destroy($id)
    {
        $realization = Sp2dRealization::findOrFail($id);
        $realization->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Data realisasi berhasil dihapus'
        ]);
    }

    public function getRecon(Request $request)
    {
        $bulan = $request->query('bulan', date('n'));
        $tahun = $request->query('tahun', date('Y'));
        $jenisGaji = $request->query('jenis_gaji');
        $tppReconMode = $request->query('tpp_recon_mode', 'bruto');

        // 1. Get all imported SP2D realizations
        $realQuery = Sp2dRealization::where('bulan', $bulan)
            ->where('tahun', $tahun);

        if ($jenisGaji) {
            $realQuery->where('jenis_data', 'LIKE', '%' . strtoupper($jenisGaji) . '%');
        }

        // EXCLUDE PPPK-PW from general reconciliation per user request
        if (!$jenisGaji) {
            $realQuery->where('jenis_data', 'NOT LIKE', 'PPPK-PW%');
        }

        $realizations = $realQuery->orderBy('tanggal_sp2d', 'asc')
            ->get();

        // 2. Get SKPDs to map internal data
        $skpds = Skpd::where('is_skpd', 1)->get();

        // 3. Get Internal Data (Gaji & TPP) grouped by SKPD and Type
        $pnsInternal = \DB::table('gaji_pns')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->select(
                'kdskpd',
                'jenis_gaji',
                \DB::raw('SUM(kotor) as brutto'),
                \DB::raw('SUM(total_potongan) as potongan'),
                \DB::raw('SUM(bersih) as netto'),
                \DB::raw('SUM(tunj_tpp) as tpp'),
                \DB::raw('COUNT(DISTINCT nip) as emp_count')
            )
            ->groupBy('kdskpd', 'jenis_gaji')
            ->get();

        $pppkInternal = \DB::table('gaji_pppk')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->select(
                'kdskpd',
                'jenis_gaji',
                \DB::raw('SUM(kotor) as brutto'),
                \DB::raw('SUM(total_potongan) as potongan'),
                \DB::raw('SUM(bersih) as netto'),
                \DB::raw('SUM(tunj_tpp) as tpp'),
                \DB::raw('COUNT(DISTINCT nip) as emp_count')
            )
            ->groupBy('kdskpd', 'jenis_gaji')
            ->get();

        $pwInternal = \DB::table('tb_payment_detail as pd')
            ->join('tb_payment as p', 'pd.payment_id', '=', 'p.id')
            ->join('pegawai_pw as e', 'pd.employee_id', '=', 'e.id')
            ->where('p.month', $bulan)
            ->where('p.year', $tahun)
            ->select(
                'e.idskpd',
                \DB::raw('SUM(pd.gaji_pokok + pd.tunjangan) as brutto'),
                \DB::raw('SUM(pd.pajak + pd.iwp + pd.potongan) as potongan'),
                \DB::raw('SUM(pd.total_amoun) as netto'),
                \DB::raw('COUNT(DISTINCT e.nip) as emp_count')
            )
            ->groupBy('e.idskpd')
            ->get();

        $manualMappings = \DB::table('skpd_mapping')
            ->get()
            ->groupBy('skpd_id');

        // 5. Build rows
        $data = $realizations->map(function ($real) use ($skpds, $pnsInternal, $pppkInternal, $pwInternal, $manualMappings, $tppReconMode) {
            $skpd = $skpds->where('id_skpd', $real->skpd_id)->first();

            $internalCtx = [
                'brutto' => 0,
                'potongan' => 0,
                'netto' => 0,
                'gaji_pns' => 0,
                'gaji_pppk' => 0,
                'tpp_pns' => 0,
                'tpp_pppk' => 0,
                'jenis_gaji' => null,
                'emp_count' => 0
            ];

            // Determine target jenis_gaji & data type flags based on SP2D type
            // This is moved here to ensure variables are always defined even if $skpd is null
            $targetJenisGajiDetected = 'Induk';
            $jenisDataRaw = $real->jenis_data ?? '';
            
            if (str_contains($jenisDataRaw, 'SUSULAN'))
                $targetJenisGajiDetected = 'Susulan';
            elseif (str_contains($jenisDataRaw, 'KEKURANGAN'))
                $targetJenisGajiDetected = 'Kekurangan';
            elseif (str_contains($jenisDataRaw, 'TERUSAN'))
                $targetJenisGajiDetected = 'Terusan';
            elseif (str_contains($jenisDataRaw, 'THR') || str_contains($jenisDataRaw, 'GAJI 14'))
                $targetJenisGajiDetected = 'Gaji 14 / THR';
            elseif (str_contains($jenisDataRaw, 'GAJI 13'))
                $targetJenisGajiDetected = 'Gaji 13';

            $isPnsRow = str_contains($jenisDataRaw, 'PNS');
            $isPppkRow = str_contains($jenisDataRaw, 'PPPK') && !str_contains($jenisDataRaw, 'PPPK-PW');
            $isTppRow = $jenisDataRaw === 'TPP' || str_contains($jenisDataRaw, 'TPP-');
            $isPwRow = str_contains($jenisDataRaw, 'PPPK-PW');

            if ($skpd) {
                // 1. Get kdskpds for Standard Payroll
                $kdskpds = [];
                if ($skpd->kode_simgaji) $kdskpds[] = $skpd->kode_simgaji;
                if (isset($manualMappings[$skpd->id_skpd])) {
                    foreach ($manualMappings[$skpd->id_skpd] as $m) {
                        if ($m->source_code) $kdskpds[] = $m->source_code;
                    }
                }
                $kdskpds = array_unique($kdskpds);

                // Standard Payroll aggregation
                foreach ($kdskpds as $kd) {
                    $ketRaw = strtoupper($real->keterangan ?? '');
                    $isPppkTpp = $isTppRow && (str_contains($ketRaw, 'PPPK') || str_contains($ketRaw, 'P3K') && !str_contains($ketRaw, 'PARUH'));
                    $isPnsTpp = $isTppRow && !$isPppkTpp && !str_contains($ketRaw, 'PARUH');

                    if ($isPnsRow || $isPnsTpp) {
                        $p = $pnsInternal->where('kdskpd', $kd)->where('jenis_gaji', $targetJenisGajiDetected)->first();
                        if ($p) {
                            if ($isPnsRow) {
                                $internalCtx['brutto'] += (float) ($p->brutto ?? 0);
                                $internalCtx['potongan'] += (float) ($p->potongan ?? 0);
                                $internalCtx['netto'] += (float) ($p->netto ?? 0);
                                $internalCtx['gaji_pns'] += (float) ($p->netto ?? 0);
                            } else {
                                $internalCtx['brutto'] += (float) ($p->tpp ?? 0);
                                $internalCtx['netto'] += (float) ($p->tpp ?? 0);
                                $internalCtx['tpp_pns'] += (float) ($p->tpp ?? 0);
                            }
                            $internalCtx['emp_count'] += (int) ($p->emp_count ?? 0);
                        }
                    }

                    if ($isPppkRow || $isPppkTpp) {
                        $pk = $pppkInternal->where('kdskpd', $kd)->where('jenis_gaji', $targetJenisGajiDetected)->first();
                        if ($pk) {
                            if ($isPppkRow) {
                                $internalCtx['brutto'] += (float) ($pk->brutto ?? 0);
                                $internalCtx['potongan'] += (float) ($pk->potongan ?? 0);
                                $internalCtx['netto'] += (float) ($pk->netto ?? 0);
                                $internalCtx['gaji_pppk'] += (float) ($pk->netto ?? 0);
                            } else {
                                $internalCtx['brutto'] += (float) ($pk->tpp ?? 0);
                                $internalCtx['netto'] += (float) ($pk->tpp ?? 0);
                                $internalCtx['tpp_pppk'] += (float) ($pk->tpp ?? 0);
                            }
                            $internalCtx['emp_count'] += (int) ($pk->emp_count ?? 0);
                        }
                    }
                }

                // PPPK-PW aggregation (Source 1)
                if ($isPwRow) {
                    $prefix = substr($skpd->kode_skpd, 0, 18);
                    $allUnitIds = Skpd::where('kode_skpd', 'LIKE', $prefix . '%')->pluck('id_skpd')->toArray();
                    foreach ($allUnitIds as $uid) {
                        $pw = $pwInternal->where('idskpd', $uid)->first(); // Note: PW doesn't use Induk/Susulan yet in DB schema
                        if ($pw) {
                            $internalCtx['brutto'] += (float) ($pw->brutto ?? 0);
                            $internalCtx['potongan'] += (float) ($pw->potongan ?? 0);
                            $internalCtx['netto'] += (float) ($pw->netto ?? 0);
                            $internalCtx['emp_count'] += (int) ($pw->emp_count ?? 0);
                        }
                    }
                }
                
                $internalCtx['jenis_gaji'] = $targetJenisGajiDetected;
            }

            return [
                'id' => $real->id,
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
                    'tanggal_sp2d' => $real->tanggal_sp2d ? $real->tanggal_sp2d->format('Y-m-d') : null,
                    'tanggal_cair' => $real->tanggal_cair ? $real->tanggal_cair->format('Y-m-d') : null,
                    'nomor_sp2d' => $real->nomor_sp2d,
                    'nama_skpd' => $real->nama_skpd_sipd,
                    'jenis_data' => $real->jenis_data,
                    'keterangan' => $real->keterangan,
                    'brutto' => $real->brutto,
                    'potongan' => $real->potongan,
                    'netto' => ($isTppRow && $tppReconMode === 'bruto') ? $real->brutto : $real->netto, 
                    'is_realized' => true,
                ]
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function getReconDetail(Request $request, $id)
    {
        $real = Sp2dRealization::findOrFail($id);
        $bulan = $real->bulan;
        $tahun = $real->tahun;
        $skpdId = $real->skpd_id;
        $skpd = Skpd::where('id_skpd', $skpdId)->first();

        if (!$skpd) {
            return response()->json(['message' => 'SKPD tidak ditemukan'], 404);
        }

        // Get kdskpds
        $kdskpds = [];
        if ($skpd->kode_simgaji) $kdskpds[] = $skpd->kode_simgaji;
        $manual = \DB::table('skpd_mapping')->where('skpd_id', $skpdId)->pluck('source_code')->toArray();
        $kdskpds = array_unique(array_merge($kdskpds, $manual));

        $jenisDataRaw = $real->jenis_data ?? '';
        $targetType = 'Induk';
        if (str_contains($jenisDataRaw, 'SUSULAN')) $targetType = 'Susulan';
        elseif (str_contains($jenisDataRaw, 'KEKURANGAN')) $targetType = 'Kekurangan';
        elseif (str_contains($jenisDataRaw, 'TERUSAN')) $targetType = 'Terusan';
        elseif (str_contains($jenisDataRaw, 'THR') || str_contains($jenisDataRaw, 'GAJI 14')) $targetType = 'Gaji 14 / THR';
        elseif (str_contains($jenisDataRaw, 'GAJI 13')) $targetType = 'Gaji 13';

        $isPns = str_contains($jenisDataRaw, 'PNS');
        $isPppk = str_contains($jenisDataRaw, 'PPPK') && !str_contains($jenisDataRaw, 'PPPK-PW');
        $isTpp = str_contains($jenisDataRaw, 'TPP');
        $isPw = str_contains($jenisDataRaw, 'PPPK-PW');

        $details = [];

        if ($isPns) {
            $details = \DB::table('gaji_pns')
                ->whereIn('kdskpd', $kdskpds)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->where('jenis_gaji', $targetType)
                ->select('nip', 'nama', 'bersih as nominal', \DB::raw("'PNS' as tipe"))
                ->get();
        } elseif ($isPppk) {
            $details = \DB::table('gaji_pppk')
                ->whereIn('kdskpd', $kdskpds)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->where('jenis_gaji', $targetType)
                ->select('nip', 'nama', 'bersih as nominal', \DB::raw("'PPPK' as tipe"))
                ->get();
        } elseif ($isTpp) {
            $ketRaw = strtoupper($real->keterangan ?? '');
            $table = (str_contains($ketRaw, 'PPPK') || str_contains($ketRaw, 'P3K')) ? 'gaji_pppk' : 'gaji_pns';
            $details = \DB::table($table)
                ->whereIn('kdskpd', $kdskpds)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->where('jenis_gaji', $targetType)
                ->where('tunj_tpp', '>', 0)
                ->select('nip', 'nama', 'tunj_tpp as nominal', \DB::raw("'TPP' as tipe"))
                ->get();
        } elseif ($isPw) {
            $prefix = substr($skpd->kode_skpd, 0, 18);
            $allUnitIds = Skpd::where('kode_skpd', 'LIKE', $prefix . '%')->pluck('id_skpd')->toArray();
            $details = \DB::table('tb_payment_detail as pd')
                ->join('tb_payment as p', 'pd.payment_id', '=', 'p.id')
                ->join('pegawai_pw as e', 'pd.employee_id', '=', 'e.id')
                ->whereIn('e.idskpd', $allUnitIds)
                ->where('p.month', $bulan)
                ->where('p.year', $tahun)
                ->select('e.nip', 'e.nama', 'pd.total_amoun as nominal', \DB::raw("'PPPK-PW' as tipe"))
                ->get();
        }

        return response()->json([
            'status' => 'success',
            'sp2d' => [
                'nomor' => $real->nomor_sp2d,
                'nominal' => $isTpp ? $real->brutto : $real->netto,
                'jenis' => $real->jenis_data
            ],
            'details' => $details
        ]);
    }

    public function exportRecon(Request $request)
    {
        $bulan = $request->query('bulan', date('n'));
        $tahun = $request->query('tahun', date('Y'));

        $response = $this->getRecon($request);
        $data = $response->getData(true)['data'];

        return Excel::download(new Sp2dReconExport($data, (int) $bulan, (int) $tahun), "rekon-sp2d-{$bulan}-{$tahun}.xlsx");
    }
}
