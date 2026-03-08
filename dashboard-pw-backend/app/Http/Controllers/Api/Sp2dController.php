<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sp2dRealization;
use App\Models\Skpd;
use App\Imports\Sp2dImport;
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
        ]);

        try {
            // Clear existing data for the period before importing new ones
            Sp2dRealization::where('bulan', $request->bulan)
                ->where('tahun', $request->tahun)
                ->delete();

            Excel::import(new Sp2dImport, $request->file('file'));
            return response()->json(['message' => 'Data SP2D berhasil diimpor (Data lama telah dibersihkan)']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal impor: ' . $e->getMessage()], 500);
        }
    }

    public function getStatus(Request $request)
    {
        $bulan = $request->query('bulan', date('n'));
        $tahun = $request->query('tahun', date('Y'));
        $jenisGaji = $request->query('jenis_gaji');

        // Get all main SKPDs
        $skpds = Skpd::where('is_skpd', 1)->orderBy('nama_skpd')->get();

        // Get all realizations for the period
        $realQuery = Sp2dRealization::where('bulan', $bulan)
            ->where('tahun', $tahun);

        if ($jenisGaji) {
            $realQuery->where('jenis_data', 'LIKE', '%' . strtoupper($jenisGaji) . '%');
        }

        $realizations = $realQuery->get();

        // Get Internal Stats
        $pnsQuery = \DB::table('gaji_pns')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun);

        if ($jenisGaji) {
            $pnsQuery->where('jenis_gaji', $jenisGaji);
        }

        $pnsInternal = $pnsQuery->select('kdskpd', \DB::raw('SUM(bersih) as total_gaji'), \DB::raw('SUM(tunj_tpp) as total_tpp'))
            ->groupBy('kdskpd')
            ->get()
            ->keyBy('kdskpd');

        $pppkQuery = \DB::table('gaji_pppk')
            ->where('bulan', $bulan)
            ->where('tahun', $tahun);

        if ($jenisGaji) {
            $pppkQuery->where('jenis_gaji', $jenisGaji);
        }

        $pppkInternal = $pppkQuery->select('kdskpd', \DB::raw('SUM(bersih) as total_gaji'), \DB::raw('SUM(tunj_tpp) as total_tpp'))
            ->groupBy('kdskpd')
            ->get()
            ->keyBy('kdskpd');

        $satkerMapping = \DB::table('satkers')
            ->select('nmskpd', 'kdskpd')
            ->distinct()
            ->get()
            ->map(function ($item) {
                $item->nmskpd = trim($item->nmskpd);
                return $item;
            })
            ->groupBy('nmskpd');

        $manualMappings = \DB::table('skpd_mapping')
            ->get()
            ->groupBy('skpd_id');

        $data = $skpds->map(function ($skpd) use ($realizations, $pnsInternal, $pppkInternal, $satkerMapping, $manualMappings) {
            $skpdRealizations = $realizations->where('skpd_id', $skpd->id_skpd);

            // Mapping: Find short codes (kdskpd) in satkers table
            // 1. Try official name
            $searchNames = [trim($skpd->nama_skpd)];

            // 2. Try manual mappings
            if (isset($manualMappings[$skpd->id_skpd])) {
                foreach ($manualMappings[$skpd->id_skpd] as $m) {
                    $searchNames[] = trim($m->source_name);
                }
            }

            $kdskpds = [];
            foreach ($searchNames as $name) {
                $shortCodes = $satkerMapping->get($name);
                if ($shortCodes) {
                    foreach ($shortCodes as $sc) {
                        $kdskpds[] = $sc->kdskpd;
                    }
                }
            }
            $kdskpds = array_unique($kdskpds);

            // Sum up internal data for all matching short codes
            $pnsGaji = 0;
            $pnsTpp = 0;
            $pppkGaji = 0;
            $pppkTpp = 0;

            foreach ($kdskpds as $kd) {
                $p = $pnsInternal->get($kd);
                if ($p) {
                    $pnsGaji += (float) $p->total_gaji;
                    $pnsTpp += (float) $p->total_tpp;
                }
                $pk = $pppkInternal->get($kd);
                if ($pk) {
                    $pppkGaji += (float) $pk->total_gaji;
                    $pppkTpp += (float) $pk->total_tpp;
                }
            }

            return [
                'id_skpd' => $skpd->id_skpd,
                'nama_skpd' => $skpd->nama_skpd,
                'pns' => $this->formatStatus(
                    $skpdRealizations->filter(fn($r) => str_contains($r->jenis_data, 'PNS') && !str_contains($r->jenis_data, 'TPP')),
                    $pnsGaji
                ),
                'pppk' => $this->formatStatus(
                    $skpdRealizations->filter(fn($r) => str_contains($r->jenis_data, 'PPPK') && !str_contains($r->jenis_data, 'TPP')),
                    $pppkGaji
                ),
                'tpp' => $this->formatStatus(
                    $skpdRealizations->filter(fn($r) => str_contains($r->jenis_data, 'TPP')),
                    ($pnsTpp + $pppkTpp)
                ),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'meta' => [
                'bulan' => (int) $bulan,
                'tahun' => (int) $tahun
            ]
        ]);
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

        // 1. Get all realizations for the period
        $realQuery = Sp2dRealization::where('bulan', $bulan)
            ->where('tahun', $tahun);

        if ($jenisGaji) {
            $realQuery->where('jenis_data', 'LIKE', '%' . strtoupper($jenisGaji) . '%');
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
                \DB::raw('SUM(tunj_tpp) as tpp')
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
                \DB::raw('SUM(tunj_tpp) as tpp')
            )
            ->groupBy('kdskpd', 'jenis_gaji')
            ->get();

        $satkerMapping = \DB::table('satkers')
            ->select('nmskpd', 'kdskpd')
            ->distinct()
            ->get()
            ->map(function ($item) {
                $item->nmskpd = trim($item->nmskpd);
                return $item;
            })
            ->groupBy('nmskpd');

        $manualMappings = \DB::table('skpd_mapping')
            ->get()
            ->groupBy('skpd_id');

        // 5. Build rows
        $data = $realizations->map(function ($real) use ($skpds, $pnsInternal, $pppkInternal, $satkerMapping, $manualMappings) {
            $skpd = $skpds->where('id_skpd', $real->skpd_id)->first();

            $internalCtx = [
                'brutto' => 0,
                'potongan' => 0,
                'netto' => 0,
                'gaji_pns' => 0,
                'gaji_pppk' => 0,
                'tpp_pns' => 0,
                'tpp_pppk' => 0,
                'jenis_gaji' => null
            ];

            if ($skpd) {
                $searchNames = [trim($skpd->nama_skpd)];
                if (isset($manualMappings[$skpd->id_skpd])) {
                    foreach ($manualMappings[$skpd->id_skpd] as $m) {
                        $searchNames[] = trim($m->source_name);
                    }
                }

                $kdskpds = [];
                foreach ($searchNames as $name) {
                    $shortCodes = $satkerMapping->get($name);
                    if ($shortCodes) {
                        foreach ($shortCodes as $sc) {
                            $kdskpds[] = $sc->kdskpd;
                        }
                    }
                }
                $kdskpds = array_unique($kdskpds);

                // Determine target jenis_gaji based on SP2D type (Common for all satkers of this SKPD)
                $targetJenisGajiDetected = 'Induk';
                if (str_contains($real->jenis_data, 'SUSULAN'))
                    $targetJenisGajiDetected = 'Susulan';
                elseif (str_contains($real->jenis_data, 'KEKURANGAN'))
                    $targetJenisGajiDetected = 'Kekurangan';
                elseif (str_contains($real->jenis_data, 'TERUSAN'))
                    $targetJenisGajiDetected = 'Terusan';

                foreach ($kdskpds as $kd) {
                    $isPnsRow = str_contains($real->jenis_data, 'PNS');
                    $isPppkRow = str_contains($real->jenis_data, 'PPPK');
                    $isTppRow = $real->jenis_data === 'TPP';

                    // Determine if TPP is for PNS or PPPK (Default to PNS if not specified)
                    $isPppkTpp = $isTppRow && (str_contains(strtoupper($real->keterangan), 'PPPK') || str_contains(strtoupper($real->keterangan), 'P3K'));
                    $isPnsTpp = $isTppRow && !$isPppkTpp;

                    if ($isPnsRow || $isPnsTpp) {
                        $p = $pnsInternal->where('kdskpd', $kd)->where('jenis_gaji', $targetJenisGajiDetected)->first();
                        if ($p) {
                            if ($isPnsRow) {
                                $internalCtx['brutto'] += (float) $p->brutto;
                                $internalCtx['potongan'] += (float) $p->potongan;
                                $internalCtx['netto'] += (float) $p->netto;
                                $internalCtx['gaji_pns'] += (float) $p->netto;
                            } else {
                                // For TPP rows, we show TPP as the primary value
                                $internalCtx['brutto'] += (float) $p->tpp;
                                $internalCtx['netto'] += (float) $p->tpp;
                                $internalCtx['tpp_pns'] += (float) $p->tpp;
                            }
                        }
                    }

                    if ($isPppkRow || $isPppkTpp) {
                        $pk = $pppkInternal->where('kdskpd', $kd)->where('jenis_gaji', $targetJenisGajiDetected)->first();
                        if ($pk) {
                            if ($isPppkRow) {
                                $internalCtx['brutto'] += (float) $pk->brutto;
                                $internalCtx['potongan'] += (float) $pk->potongan;
                                $internalCtx['netto'] += (float) $pk->netto;
                                $internalCtx['gaji_pppk'] += (float) $pk->netto;
                            } else {
                                // For TPP rows
                                $internalCtx['brutto'] += (float) $pk->tpp;
                                $internalCtx['netto'] += (float) $pk->tpp;
                                $internalCtx['tpp_pppk'] += (float) $pk->tpp;
                            }
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
                    'netto' => $real->netto,
                ]
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    private function formatStatus($collection, $internalAmount = 0)
    {
        if ($collection->isEmpty()) {
            return [
                'is_realized' => false,
                'nomor_sp2d' => null,
                'tanggal_sp2d' => null,
                'netto' => 0,
                'internal_amount' => $internalAmount
            ];
        }

        $allNumbers = $collection->pluck('nomor_sp2d')->unique()->implode(', ');
        $latestDate = $collection->max('tanggal_sp2d');

        return [
            'is_realized' => true,
            'nomor_sp2d' => $allNumbers,
            'tanggal_sp2d' => $latestDate ? $latestDate->format('Y-m-d') : null,
            'netto' => $collection->sum('netto'),
            'internal_amount' => $internalAmount,
            'count' => $collection->count()
        ];
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
