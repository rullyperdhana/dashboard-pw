<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\GajiPns;
use App\Models\GajiPppk;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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
            'captcha_id' => 'required|string',
            'captcha_answer' => 'required|numeric',
        ]);

        // Validate Captcha
        $cachedAnswer = Cache::pull('ess_captcha_' . $request->captcha_id);
        if ($cachedAnswer === null || (int)$request->captcha_answer !== (int)$cachedAnswer) {
            return response()->json([
                'success' => false,
                'message' => 'Jawaban Keamanan (Captcha) salah. Silakan coba lagi.'
            ], 422);
        }

        $nik = $request->nik;
        $nip = $request->nip;
        
        // ... rest of the existing login logic ...
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

    /**
     * Generate simple math captcha
     */
    public function getCaptcha()
    {
        $n1 = rand(1, 10);
        $n2 = rand(1, 10);
        $id = Str::random(16);
        
        Cache::put('ess_captcha_' . $id, $n1 + $n2, now()->addMinutes(10));
        
        return response()->json([
            'success' => true,
            'captcha_id' => $id,
            'question' => "Berapa $n1 + $n2 ?"
        ]);
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
    /**
     * Get detailed breakdown of a specific slip.
     */
    public function slipDetail(Request $request, $id)
    {
        $nip = $request->header('X-ESS-NIP');
        $type = $request->query('type'); // PNS or PPPK

        if (!$nip) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $model = $type === 'PPPK' ? GajiPppk::class : GajiPns::class;
        $detail = $model::where('nip', $nip)->where('id', $id)->first();

        if (!$detail) {
            return response()->json([
                'success' => false, 
                'message' => 'Detail slip tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $detail
        ]);
    }

    /**
     * Download PDF slip.
     */
    public function downloadPdf(Request $request, $id)
    {
        $nip = $request->header('X-ESS-NIP') ?: $request->query('nip');
        $type = $request->query('type'); // PNS or PPPK

        if (!$nip) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $model = $type === 'PPPK' ? GajiPppk::class : GajiPns::class;
        $data = $model::where('nip', $nip)->where('id', $id)->first();

        if (!$data) {
            abort(404, 'Detail slip tidak ditemukan.');
        }

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $bulan_nama = $months[$data->bulan] ?? 'Unknown';

        // Generate QR Code for validation
        // Link to validation page (placeholder)
        $validationUrl = config('app.url') . "/verify/slip?" . http_build_query([
            'nip' => $data->nip,
            'id' => $data->id,
            'hash' => md5($data->nip . $data->id . $data->updated_at)
        ]);
        
        $qrcode = base64_encode(QrCode::format('png')->size(200)->margin(2)->generate($validationUrl));

        $pdf = Pdf::loadView('reports.ess_slip_pdf', [
            'data' => $data,
            'bulan_nama' => $bulan_nama,
            'qrcode' => $qrcode
        ]);

        return $pdf->download("Slip_Gaji_{$data->nip}_{$data->bulan}_{$data->tahun}.pdf");
    }
}
