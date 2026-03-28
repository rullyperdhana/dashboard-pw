<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\GajiPns;
use App\Models\GajiPppk;

class EssAuthController extends Controller
{
    /**
     * Authenticate ESS user using NIK (noktp) and NIP.
     */
    public function login(Request $request)
    {
        $request->validate([
            'nik' => 'required|string',
            'nip' => 'required|string',
        ]);

        $nik = $request->nik;
        $nip = $request->nip;

        // Cari di Gaji PNS
        $pns = GajiPns::where('noktp', $nik)->where('nip', $nip)->orderBy('tahun', 'desc')->orderBy('bulan', 'desc')->first();
        if ($pns) {
            $user = [
                'type' => 'PNS',
                'nip' => $pns->nip,
                'nama' => $pns->nama,
                'skpd' => $pns->skpd,
                'jabatan' => $pns->jabatan,
                'golongan' => $pns->golongan
            ];
            return $this->generateEssToken('PNS', $user);
        }

        // Cari di Gaji PPPK
        $pppk = GajiPppk::where('noktp', $nik)->where('nip', $nip)->orderBy('tahun', 'desc')->orderBy('bulan', 'desc')->first();
        if ($pppk) {
            $user = [
                'type' => 'PPPK',
                'nip' => $pppk->nip,
                'nama' => $pppk->nama,
                'skpd' => $pppk->skpd,
                'jabatan' => $pppk->jabatan,
                'golongan' => $pppk->golongan
            ];
            return $this->generateEssToken('PPPK', $user);
        }

        // Cek juga di Master Pegawai jika belum ada penggajian
        $masterPns = DB::table('master_pegawai')->where('noktp', $nik)->where('nip', $nip)->first();
        if ($masterPns) {
            $user = [
                'type' => 'PNS',
                'nip' => $masterPns->nip,
                'nama' => $masterPns->nama,
                'skpd' => '-',
                'jabatan' => '-',
                'golongan' => $masterPns->blgolt ?? '-'
            ];
            return $this->generateEssToken('PNS', $user);
        }

        return response()->json([
            'success' => false,
            'message' => 'Detail NIK atau NIP tidak ditemukan atau tidak cocok.'
        ], 401);
    }

    private function generateEssToken($type, $user)
    {
        // Untuk ESS sederhana, kita issue pseudo-token atau custom claims.
        // Karena sistem ini tidak menggunakan tabel users untuk ESS, kita kembalikan raw data user 
        // dan hash sederhana / jwt sederhana (Untuk skenario ini, kita cukup kirim success dan biarkan frontend meyimpan user state).
        // Pada praktek nyatanya sebaiknya mendaftarkan Custom User Provider di auth.php untuk Sanctum.
        return response()->json([
            'success' => true,
            'message' => 'Login Berhasil',
            'data' => [
                'token' => 'ess_token_' . base64_encode($user['nip'] . time()), 
                'user' => $user
            ]
        ]);
    }

    /**
     * Get Employee basic slips
     */
    public function slips(Request $request)
    {
        $nip = $request->header('X-ESS-NIP'); // Ambil dari header request khusus ESS
        if (!$nip) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Mengambil histori maksimal 5 tahun terakhir (60 bulan) dan menyertakan rincian
        $slipsPns = GajiPns::where('nip', $nip)
            ->select('id', 'bulan', 'tahun', 'jenis_gaji', 'kotor', 'bersih', 'skpd', 'gaji_pokok', 'tunj_tpp')
            ->orderBy('tahun', 'desc')->orderBy('bulan', 'desc')->limit(60)->get()->map(function($i) { $i->tipe = 'PNS'; return $i; });
        
        $slipsPppk = GajiPppk::where('nip', $nip)
            ->select('id', 'bulan', 'tahun', 'jenis_gaji', 'kotor', 'bersih', 'skpd', 'gaji_pokok', 'tunj_tpp')
            ->orderBy('tahun', 'desc')->orderBy('bulan', 'desc')->limit(60)->get()->map(function($i) { $i->tipe = 'PPPK'; return $i; });

        $slips = $slipsPns->merge($slipsPppk)->sortByDesc('tahun')->sortByDesc('bulan')->values();

        return response()->json([
            'success' => true,
            'data' => $slips
        ]);
    }
}
