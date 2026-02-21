<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TpgData;
use App\Imports\TpgImport;
use App\Exports\TpgExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TpgController extends Controller
{
    /**
     * Upload TPG XLSX file.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
            'triwulan' => 'required|integer|min:1|max:4',
            'tahun' => 'required|integer|min:2020|max:2030',
            'jenis' => 'required|in:INDUK,SUSULAN',
        ]);

        try {
            $file = $request->file('file');
            $triwulan = $request->input('triwulan');
            $tahun = $request->input('tahun');
            $jenis = $request->input('jenis');

            // For INDUK: delete existing INDUK data for this triwulan+tahun before re-importing
            if ($jenis === 'INDUK') {
                TpgData::where('triwulan', $triwulan)
                    ->where('tahun', $tahun)
                    ->where('jenis', 'INDUK')
                    ->delete();
            }

            Excel::import(new TpgImport($triwulan, $tahun, $jenis), $file);

            $count = TpgData::where('triwulan', $triwulan)
                ->where('tahun', $tahun)
                ->where('jenis', $jenis)
                ->count();

            $jenisLabel = $jenis === 'INDUK' ? 'Induk' : 'Susulan';

            return response()->json([
                'success' => true,
                'message' => "Data TPG {$jenisLabel} Triwulan {$triwulan} Tahun {$tahun} berhasil diimport. Total: {$count} data.",
                'total' => $count,
            ]);

        } catch (\Exception $e) {
            Log::error('TPG Upload Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal import data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Dashboard summary for TPG monitoring.
     */
    public function dashboard(Request $request)
    {
        $tahun = $request->input('tahun', date('Y'));
        $triwulan = $request->input('triwulan', null);

        // Per-triwulan summary
        $triwulanSummary = TpgData::where('tahun', $tahun)
            ->select(
                'triwulan',
                DB::raw('COUNT(*) as total_penerima'),
                DB::raw('SUM(salur_brut) as total_brut'),
                DB::raw('SUM(pph) as total_pph'),
                DB::raw('SUM(pot_jkn) as total_pot_jkn'),
                DB::raw('SUM(salur_nett) as total_nett')
            )
            ->groupBy('triwulan')
            ->orderBy('triwulan')
            ->get();

        // Yearly totals
        $yearlyTotals = TpgData::where('tahun', $tahun)
            ->select(
                DB::raw('COUNT(DISTINCT nip) as total_guru'),
                DB::raw('SUM(salur_brut) as total_brut'),
                DB::raw('SUM(pph) as total_pph'),
                DB::raw('SUM(pot_jkn) as total_pot_jkn'),
                DB::raw('SUM(salur_nett) as total_nett')
            )
            ->first();

        // Per-SATDIK breakdown (filtered by triwulan if specified)
        $satdikQuery = TpgData::where('tahun', $tahun);
        if ($triwulan) {
            $satdikQuery->where('triwulan', $triwulan);
        }
        $satdikBreakdown = $satdikQuery
            ->select(
                'satdik',
                DB::raw('COUNT(*) as jumlah_guru'),
                DB::raw('SUM(salur_brut) as total_brut'),
                DB::raw('SUM(pph) as total_pph'),
                DB::raw('SUM(pot_jkn) as total_pot_jkn'),
                DB::raw('SUM(salur_nett) as total_nett')
            )
            ->groupBy('satdik')
            ->orderByDesc('total_nett')
            ->get();

        // Available years
        $availableYears = TpgData::select('tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');

        return response()->json([
            'success' => true,
            'data' => [
                'yearly_totals' => $yearlyTotals,
                'triwulan_summary' => $triwulanSummary,
                'satdik_breakdown' => $satdikBreakdown,
                'available_years' => $availableYears,
            ]
        ]);
    }

    /**
     * Paginated TPG data list with filters.
     */
    public function data(Request $request)
    {
        $tahun = $request->input('tahun', date('Y'));
        $triwulan = $request->input('triwulan', null);
        $search = $request->input('search', '');
        $perPage = $request->input('per_page', 15);

        $query = TpgData::where('tahun', $tahun);

        if ($triwulan) {
            $query->where('triwulan', $triwulan);
        }

        // Exact SATDIK filter (from breakdown link)
        $satdik = $request->input('satdik', '');
        if ($satdik) {
            $query->where('satdik', $satdik);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nip', 'like', "%{$search}%")
                    ->orWhere('nama', 'like', "%{$search}%")
                    ->orWhere('satdik', 'like', "%{$search}%");
            });
        }

        $data = $query->orderBy('nama')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Export TPG data to Excel.
     */
    public function export(Request $request)
    {
        $tahun = $request->input('tahun', date('Y'));
        $triwulan = $request->input('triwulan', null);

        $filename = "data_tpg_{$tahun}";
        if ($triwulan) {
            $filename .= "_tw{$triwulan}";
        }
        $filename .= '.xlsx';

        return Excel::download(new TpgExport($tahun, $triwulan), $filename);
    }
}
