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
            // Kita ambil data skpd, di-group berdasarkan nama untuk menghindari duplikasi di dropdown
            // Kita ambil ID terkecil sebagai representatif jika ada nama yang identik
            $sub = DB::table('skpd')
                ->select(DB::raw('MIN(id_skpd) as id_skpd'), 'nama_skpd')
                ->groupBy('nama_skpd');

            return DB::table('skpd as s')
                ->joinSub($sub, 'sub', function ($join) {
                    $join->on('s.id_skpd', '=', 'sub.id_skpd');
                })
                ->orderBy('s.nama_skpd')
                ->get();
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
            $cleanName = trim($item->nmskpd);
            Skpd::updateOrCreate(
                ['kode_simgaji' => $item->kdskpd],
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
