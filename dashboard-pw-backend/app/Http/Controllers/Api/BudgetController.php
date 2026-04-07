<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Budget;
use App\Models\Skpd;
use App\Models\Sp2dRealization;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $tahun = $request->query('tahun', date('Y'));
        $skpdId = $request->query('skpd_id');
        $jenisAnggaran = $request->query('jenis_anggaran');

        $query = Budget::with('skpd')->where('tahun', $tahun);

        if ($skpdId) {
            $query->where('skpd_id', $skpdId);
        }
        
        if ($jenisAnggaran) {
            $query->where('jenis_anggaran', $jenisAnggaran);
        }

        $budgets = $query->orderBy('skpd_id')->orderBy('created_at', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $budgets
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'skpd_id' => 'required|exists:skpds,id_skpd',
            'tahun' => 'required|integer',
            'jenis_anggaran' => 'required|string|max:50',
            'tipe_anggaran' => 'required|string|max:50',
            'nominal' => 'required|numeric',
            'keterangan' => 'nullable|string'
        ]);

        $existing = Budget::where('skpd_id', $request->skpd_id)
            ->where('tahun', $request->tahun)
            ->where('jenis_anggaran', $request->jenis_anggaran)
            ->where('tipe_anggaran', $request->tipe_anggaran)
            ->first();

        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tipe anggaran ' . $request->tipe_anggaran . ' untuk jenis ' . $request->jenis_anggaran . ' pada SKPD tersebut sudah direkam sebelumnya.'
            ], 422);
        }

        $budget = Budget::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Anggaran berhasil direkam.',
            'data' => $budget
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nominal' => 'required|numeric',
            'jenis_anggaran' => 'required|string|max:50',
            'tipe_anggaran' => 'required|string|max:50',
            'keterangan' => 'nullable|string'
        ]);

        $budget = Budget::findOrFail($id);
        
        // Cek duplicate
        if ($request->tipe_anggaran !== $budget->tipe_anggaran || $request->jenis_anggaran !== $budget->jenis_anggaran) {
            $existing = Budget::where('skpd_id', $budget->skpd_id)
                ->where('tahun', $budget->tahun)
                ->where('jenis_anggaran', $request->jenis_anggaran)
                ->where('tipe_anggaran', $request->tipe_anggaran)
                ->first();
            if ($existing) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tipe anggaran tersebut sudah ada.'
                ], 422);
            }
        }

        $budget->update($request->only(['nominal', 'jenis_anggaran', 'tipe_anggaran', 'keterangan']));

        return response()->json([
            'status' => 'success',
            'message' => 'Anggaran berhasil diperbarui.',
            'data' => $budget
        ]);
    }

    public function destroy($id)
    {
        $budget = Budget::findOrFail($id);
        $budget->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Data anggaran berhasil dihapus.'
        ]);
    }

    public function comparisonReport(Request $request)
    {
        $tahun = $request->query('tahun', date('Y'));
        $tipeAnggaran = $request->query('tipe_anggaran', 'MURNI'); // MURNI, PERUBAHAN_1, dst, atau TERAKHIR
        $bulan = $request->query('bulan'); // Opsional

        $skpds = Skpd::where('is_skpd', 1)->get()->keyBy('id_skpd');
        
        // Dapatkan realisasi SP2D
        $realizationQuery = Sp2dRealization::where('tahun', $tahun);
        if ($bulan) {
            $realizationQuery->where('bulan', '<=', $bulan);
        }
        
        $sp2ds = $realizationQuery->get();
        
        // Parsing Kategori Realisasi (Sesuai Logic getRecon di Sp2dController)
        $parsedReals = [];
        foreach ($sp2ds as $real) {
            $jenisDataRaw = $real->jenis_data ?? '';
            $ketRaw = strtoupper($real->keterangan ?? '');
            
            $category = 'LAINNYA';
            if (str_contains($jenisDataRaw, 'PPPK-PW')) $category = 'PPPK_PW';
            elseif (str_contains($jenisDataRaw, 'TPP')) $category = 'TPP';
            elseif (str_contains($jenisDataRaw, 'PPPK') || str_contains($jenisDataRaw, 'P3K')) $category = 'PPPK';
            elseif (str_contains($jenisDataRaw, 'PNS')) $category = 'PNS';
            
            // Simpan agregrasi berdsarkan SKPD + Jenis
            $key = $real->skpd_id . '_' . $category;
            if (!isset($parsedReals[$key])) {
                $parsedReals[$key] = [
                    'skpd_id' => $real->skpd_id,
                    'kategori' => $category,
                    'brutto' => 0,
                    'netto' => 0,
                ];
            }
            $parsedReals[$key]['brutto'] += (float) $real->brutto;
            $parsedReals[$key]['netto'] += (float) $real->netto;
        }

        // Dapatkan anggaran
        $budgetsQuery = Budget::where('tahun', $tahun);
        if ($tipeAnggaran !== 'SEMUA' && $tipeAnggaran !== 'TERAKHIR') {
            $budgetsQuery->where('tipe_anggaran', $tipeAnggaran);
        }
        $budgets = $budgetsQuery->orderBy('created_at', 'desc')->get();
        
        $parsedBudgets = [];
        foreach ($budgets as $budget) {
            $kategori = $budget->jenis_anggaran ?: 'LAINNYA';
            $key = $budget->skpd_id . '_' . $kategori;
            
            // if we only want the LATEST version for a specific SKPD + Category
            if (isset($parsedBudgets[$key]) && ($tipeAnggaran === 'TERAKHIR' || $tipeAnggaran === 'SEMUA')) {
                continue; // already got the latest one because ordered by created_at desc
            }
            
            $parsedBudgets[$key] = $budget;
        }

        $report = [];
        // Extract all unique combinations of SKPD + Kategori from both sets
        $allKeys = array_unique(array_merge(array_keys($parsedReals), array_keys($parsedBudgets)));

        foreach ($allKeys as $key) {
            $parts = explode('_', $key, 2);
            $skpdId = $parts[0];
            $kategori = $parts[1];
            
            $skpd = $skpds->get($skpdId);
            if (!$skpd) continue; // safety check
            
            $budget = $parsedBudgets[$key] ?? null;
            $real = $parsedReals[$key] ?? null;
            
            $nominalAnggaran = $budget ? (float) $budget->nominal : 0;
            $tipe = $budget ? $budget->tipe_anggaran : 'Belum Ada';
            
            $totalRealisasiBrutto = $real ? $real['brutto'] : 0;
            $totalRealisasiNetto = $real ? $real['netto'] : 0;
            
            $sisaAnggaran = $nominalAnggaran - $totalRealisasiBrutto; // user minta brutto
            $persentase = $nominalAnggaran > 0 ? ($totalRealisasiBrutto / $nominalAnggaran) * 100 : 0;

            $report[] = [
                'skpd_id' => $skpdId,
                'nama_skpd' => $skpd->nama_skpd,
                'kategori' => $kategori,
                'tipe_anggaran' => $tipe,
                'nominal_anggaran' => $nominalAnggaran,
                'realisasi_brutto' => $totalRealisasiBrutto,
                'realisasi_netto' => $totalRealisasiNetto,
                'sisa_anggaran' => $sisaAnggaran,
                'persentase' => round($persentase, 2)
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => collect($report)->sortBy(['nama_skpd', 'kategori'])->values()->all()
        ]);
    }
}
