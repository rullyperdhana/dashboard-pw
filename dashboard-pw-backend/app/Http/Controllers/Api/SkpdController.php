<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skpd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SkpdController extends Controller
{
    public function index()
    {
        // Pastikan Master SKPD sinkron dengan Satker (untuk kode-kode baru seperti sekolah)
        $this->syncMasterSkpd();

        $skpds = \Illuminate\Support\Facades\Cache::remember('ref_skpds', 3600, function () {
            return \App\Models\Skpd::orderBy('nama_skpd')->get();
        });

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
            ->whereNotIn('kdskpd', DB::table('skpd')->whereNotNull('kode_simgaji')->pluck('kode_simgaji'))
            ->select('kdskpd', 'nmskpd')
            ->distinct()
            ->get();

        foreach ($missing as $item) {
            Skpd::updateOrCreate(
                ['kode_simgaji' => $item->kdskpd],
                [
                    'nama_skpd' => $item->nmskpd,
                    'is_skpd' => 1,
                ]
            );
        }
        
        if ($missing->isNotEmpty()) {
            \Illuminate\Support\Facades\Cache::forget('ref_skpds');
        }
    }
}
