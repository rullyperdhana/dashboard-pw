<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skpd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SkpdController extends Controller
{
    public function index(Request $request)
    {
        // Pastikan Master SKPD sinkron dengan Satker (untuk kode-kode baru seperti sekolah)
        $this->syncMasterSkpd();

        // Paksa refresh cache jika ada parameter ?refresh=1
        if ($request->has('refresh')) {
            \Illuminate\Support\Facades\Cache::forget('ref_skpds');
        }

        $skpds = \Illuminate\Support\Facades\Cache::remember('ref_skpds', 3600, function () {
            $data = DB::table('skpd')
                ->orderBy('nama_skpd')
                ->get();

            // Jika hasil kosong, jangan cache dulu agar bisa dicoba lagi
            if ($data->isEmpty()) return null;
            return $data;
        });

        // Jika cache null (akibat kegagalan ambil data), coba ambil direct tanpa cache
        if (!$skpds) {
            $skpds = DB::table('skpd')->orderBy('nama_skpd')->get();
        }

        return response()->json([
            'success' => true,
            'data' => $skpds
        ]);
    }

    /**
     * Synchronize missing SKPDs from satkers to skpd table
     */
    private function syncMasterSkpd()
    {
        $missing = DB::table('satkers')
            ->whereNotIn('kdskpd', DB::table('skpd')->whereNotNull('kode_skpd')->pluck('kode_skpd'))
            ->select('kdskpd', 'nmskpd')
            ->distinct()
            ->get();

        foreach ($missing as $item) {
            $cleanName = trim($item->nmskpd);
            Skpd::updateOrCreate(
                ['kode_skpd' => $item->kdskpd],
                [
                    'nama_skpd' => $cleanName,
                    'is_skpd' => 1,
                ]
            );
        }
        
        if ($missing->isNotEmpty()) {
            \Illuminate\Support\Facades\Cache::forget('ref_skpds');
        }
    }
}
