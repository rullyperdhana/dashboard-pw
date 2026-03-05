<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SimgajiController extends Controller
{
    public function listInstansi(Request $request)
    {
        $query = DB::table('skpd')->select('kode_skpd as kode_instansi', 'nama_skpd as nama_instansi');

        if ($request->has('kode_instansi')) {
            $query->where('kode_skpd', $request->kode_instansi);
        }

        $data = $query->get();

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => $data
        ]);
    }

    public function listPegawai(Request $request)
    {
        $query = DB::table('master_pegawai')->select(
            'noktp as nik',
            'nip',
            'nama',
            'npwp',
            'tgllhr as tanggal_lahir'
        );

        if ($request->has('nip')) {
            $query->where('nip', $request->nip);
        }

        if ($request->has('nik')) {
            $query->where('noktp', $request->nik);
        }

        $data = $query->get();

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => $data
        ]);
    }

    public function listGaji(Request $request)
    {
        // Parameter parsing
        $period = $request->period; // e.g., 2025-03
        $kode_instansi = $request->kode_instansi;
        $nip = $request->nip;
        $tahun = $request->tahun;

        $bulan = null;
        if ($period) {
            $parts = explode('-', $period);
            if (count($parts) == 2) {
                $tahun = $parts[0];
                $bulan = (int) $parts[1];
            }
        }

        // Build filter closure (shared for both PNS and PPPK queries)
        $applyFilters = function ($query) use ($tahun, $bulan, $nip, $kode_instansi) {
            if ($tahun) {
                $query->where('g.tahun', $tahun);
            }
            if ($bulan !== null) {
                $query->where('g.bulan', $bulan);
            }
            if ($nip) {
                $query->where('g.nip', $nip);
            }
            if ($kode_instansi) {
                $query->where('s.kode_skpd', $kode_instansi);
            }
            return $query;
        };

        $selectColumns = [
            'g.tahun',
            'g.bulan',
            'm.kdstawin',
            'm.janak',
            'm.kdjenkel',
            'g.nip',
            'g.tunj_struktural',
            'g.tunj_fungsional',
            'm.kdeselon',
            'g.golongan',
            'm.mkgolt',
            'm.alamat',
            'm.induk_bank',
            'm.norek',
            'm.jistri',
            DB::raw('CASE WHEN m.kdfungsi = "00000" THEN re.uraian ELSE rjf.nama_jabatan END as nama_jabatan'),
            'g.gaji_pokok',
            'g.tunj_istri as tk_tunjangan_istri',
            'g.tunj_anak as tk_tunjangan_anak',
            'g.tunj_eselon as tj_tunjangan_eselon',
            'g.tunj_fungsional as tj_tunjangan_fungsional',
            'g.tunj_struktural as tj_tunjangan_struktural',
            'g.tunj_umum as tunjangan_umum',
        ];

        // Query PNS - gaji_pns sebagai sumber utama, master_pegawai & skpd sebagai pelengkap
        $queryPns = DB::table('gaji_pns as g')
            ->leftJoin('master_pegawai as m', DB::raw('g.nip COLLATE utf8mb4_unicode_ci'), '=', DB::raw('m.nip COLLATE utf8mb4_unicode_ci'))
            ->leftJoin('skpd as s', DB::raw('m.kdskpd COLLATE utf8mb4_unicode_ci'), '=', DB::raw('s.kode_skpd COLLATE utf8mb4_unicode_ci'))
            ->leftJoin('ref_jabatan_fungsional as rjf', 'm.kdfungsi', '=', 'rjf.kdfungsi')
            ->leftJoin('ref_eselon as re', 'm.kdeselon', '=', 're.kd_eselon')
            ->select(array_merge($selectColumns, [DB::raw("'1' as status_asn")]));
        $applyFilters($queryPns);
        $pnsResults = $queryPns->get();

        // Query PPPK - gaji_pppk sebagai sumber utama, master_pegawai & skpd sebagai pelengkap
        $queryPpk = DB::table('gaji_pppk as g')
            ->leftJoin('master_pegawai as m', DB::raw('g.nip COLLATE utf8mb4_unicode_ci'), '=', DB::raw('m.nip COLLATE utf8mb4_unicode_ci'))
            ->leftJoin('skpd as s', DB::raw('m.kdskpd COLLATE utf8mb4_unicode_ci'), '=', DB::raw('s.kode_skpd COLLATE utf8mb4_unicode_ci'))
            ->leftJoin('ref_jabatan_fungsional as rjf', 'm.kdfungsi', '=', 'rjf.kdfungsi')
            ->leftJoin('ref_eselon as re', 'm.kdeselon', '=', 're.kd_eselon')
            ->select(array_merge($selectColumns, [DB::raw("'2' as status_asn")]));
        $applyFilters($queryPpk);
        $ppkResults = $queryPpk->get();

        // Merge results
        $results = $pnsResults->merge($ppkResults);

        $formattedData = [];
        foreach ($results as $row) {
            // Status Pajak Formatting
            // Wanita (kdjenkel=2) selalu TK/0
            if ($row->kdjenkel == 2) {
                $statusPajak = "TK/0";
            } else {
                $statusPajak = "TK/0";
                $anak = (int) $row->janak;
                if ($anak > 3)
                    $anak = 3; // Max 3 for tax

                if ($row->kdstawin == 1) {
                    // Belum kawin -> TK
                    $statusPajak = "TK/" . $anak;
                } else if ($row->kdstawin == 2) {
                    // Kawin -> K
                    $statusPajak = "K/" . $anak;
                } else {
                    // Janda/Duda -> TK
                    $statusPajak = "TK/" . $anak;
                }
            }

            // Tipe Jabatan (Based on tunjangan)
            // 1 = Struktural, 2 = Fungsional Tertentu, 3 = Fungsional Umum
            $tipeJabatan = "";
            if ((float) $row->tunj_struktural > 0) {
                $tipeJabatan = "1";
            } else if ((float) $row->tunj_fungsional > 0) {
                $tipeJabatan = "2";
            } else {
                $tipeJabatan = "3";
            }

            // Eselon
            $eselon = $row->kdeselon;
            if (empty($eselon) || $eselon == '99' || $eselon == '-' || $eselon == '00') {
                $eselon = "";
            }

            // Mkgol
            $mkgol = (int) $row->mkgolt;

            $formattedData[] = [
                "period" => $row->tahun . "-" . str_pad($row->bulan, 2, '0', STR_PAD_LEFT),
                "status_pajak" => $statusPajak,
                "sertifikat_fasilitas" => "N/A",
                "nip" => $row->nip,
                "tipe_jabatan" => $tipeJabatan,
                "nama_jabatan" => $row->nama_jabatan,
                "eselon" => $eselon,
                "status_asn" => $row->status_asn,
                "golongan" => $row->golongan,
                "masa_kerja_golongan" => (string) $mkgol,
                "alamat" => $row->alamat ?? "",
                "kode_bank" => $row->induk_bank ?? "",
                "nama_bank" => $row->induk_bank ?? "",
                "nomor_rekening" => $row->norek ?? "",
                "jumlah_istri" => (string) $row->jistri,
                "jumlah_anak" => (string) $row->janak,
                "gaji_pokok" => (float) $row->gaji_pokok,
                "tk_tunjangan_istri" => (float) $row->tk_tunjangan_istri,
                "tk_tunjangan_anak" => (float) $row->tk_tunjangan_anak,
                "tj_tunjangan_eselon" => (float) $row->tj_tunjangan_eselon,
                "tj_tunjangan_fungsional" => (float) $row->tj_tunjangan_fungsional,
                "tj_tunjangan_struktural" => (float) $row->tj_tunjangan_struktural,
                "tunjangan_umum" => (float) $row->tunjangan_umum
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => $formattedData
        ]);
    }
}
