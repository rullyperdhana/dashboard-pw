<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sp2dRealization;
use App\Models\Skpd;
use App\Imports\Sp2dImport;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class Sp2dController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new Sp2dImport, $request->file('file'));
            return response()->json(['message' => 'Data SP2D berhasil diimpor']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal impor: ' . $e->getMessage()], 500);
        }
    }

    public function getStatus(Request $request)
    {
        $bulan = $request->query('bulan', date('n'));
        $tahun = $request->query('tahun', date('Y'));

        // Get all main SKPDs
        $skpds = Skpd::where('is_skpd', 1)->orderBy('nama_skpd')->get();

        // Get all realizations for the period
        $realizations = Sp2dRealization::where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get();

        $data = $skpds->map(function ($skpd) use ($realizations) {
            $skpdRealizations = $realizations->where('skpd_id', $skpd->id_skpd);

            return [
                'id_skpd' => $skpd->id_skpd,
                'nama_skpd' => $skpd->nama_skpd,
                'pns' => $this->formatStatus($skpdRealizations->where('jenis_data', 'PNS')),
                'pppk' => $this->formatStatus($skpdRealizations->where('jenis_data', 'PPPK')),
                'tpp' => $this->formatStatus($skpdRealizations->where('jenis_data', 'TPP')),
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

        $query = Sp2dRealization::where('bulan', $bulan)
            ->where('tahun', $tahun);

        if ($idSkpd) {
            $query->where('skpd_id', $idSkpd);
        }

        $data = $query->orderBy('tanggal_sp2d', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    private function formatStatus($collection)
    {
        if ($collection->isEmpty()) {
            return [
                'is_realized' => false,
                'nomor_sp2d' => null,
                'tanggal_sp2d' => null,
                'netto' => 0
            ];
        }

        $first = $collection->first();
        return [
            'is_realized' => true,
            'nomor_sp2d' => $first->nomor_sp2d,
            'tanggal_sp2d' => $first->tanggal_sp2d->format('Y-m-d'),
            'netto' => $collection->sum('netto')
        ];
    }
}
