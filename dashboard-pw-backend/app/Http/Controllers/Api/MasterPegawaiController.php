<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterPegawai;
use Illuminate\Http\Request;

class MasterPegawaiController extends Controller
{
    public function index(Request $request)
    {
        $query = MasterPegawai::query()
            ->leftJoin('satkers', function ($join) {
                $join->on('master_pegawai.kdskpd', '=', 'satkers.kdskpd')
                    ->on('master_pegawai.kdsatker', '=', 'satkers.kdsatker');
            })
            ->leftJoin('ref_stapeg', 'master_pegawai.kdstapeg', '=', 'ref_stapeg.kdstapeg')
            ->leftJoin('ref_jabatan_fungsional', 'master_pegawai.kdfungsi', '=', 'ref_jabatan_fungsional.kdfungsi')
            ->leftJoin('ref_eselon', 'master_pegawai.kdeselon', '=', 'ref_eselon.kd_eselon')
            ->select(
                'master_pegawai.*',
                'satkers.nmskpd',
                'satkers.nmsatker',
                'ref_stapeg.nmstapeg',
                \Illuminate\Support\Facades\DB::raw('CASE WHEN master_pegawai.kdfungsi = "00000" THEN ref_eselon.uraian ELSE ref_jabatan_fungsional.nama_jabatan END as nama_jabatan')
            );

        if ($request->has('search')) {
            $query->search($request->search);
        }

        if ($request->has('kdskpd')) {
            $query->where('master_pegawai.kdskpd', $request->kdskpd);
        }

        if ($request->has('kd_jns_peg')) {
            $query->where('master_pegawai.kd_jns_peg', $request->kd_jns_peg);
        }

        if ($request->has('kdstapeg')) {
            $query->where('master_pegawai.kdstapeg', $request->kdstapeg);
        }

        if ($request->has('status_group')) {
            switch ($request->status_group) {
                case 'aktif':
                    $query->whereIn('master_pegawai.kdstapeg', [1, 2, 3, 4, 5, 11, 12]);
                    break;
                case 'pensiun':
                    $query->whereIn('master_pegawai.kdstapeg', [23, 30]);
                    break;
                case 'meninggal':
                    $query->where('master_pegawai.kdstapeg', 27);
                    break;
                case 'mutasi':
                    $query->whereIn('master_pegawai.kdstapeg', [24, 28]);
                    break;
                case 'non_aktif_temp':
                    $query->whereIn('master_pegawai.kdstapeg', [6, 7, 8, 9, 10, 22]);
                    break;
            }
        }

        $data = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function show($id)
    {
        $pegawai = MasterPegawai::query()
            ->leftJoin('satkers', function ($join) {
                $join->on('master_pegawai.kdskpd', '=', 'satkers.kdskpd')
                    ->on('master_pegawai.kdsatker', '=', 'satkers.kdsatker');
            })
            ->leftJoin('ref_stapeg', 'master_pegawai.kdstapeg', '=', 'ref_stapeg.kdstapeg')
            ->leftJoin('ref_jabatan_fungsional', 'master_pegawai.kdfungsi', '=', 'ref_jabatan_fungsional.kdfungsi')
            ->leftJoin('ref_eselon', 'master_pegawai.kdeselon', '=', 'ref_eselon.kd_eselon')
            ->select(
                'master_pegawai.*',
                'satkers.nmskpd',
                'satkers.nmsatker',
                'ref_stapeg.nmstapeg',
                \Illuminate\Support\Facades\DB::raw('CASE WHEN master_pegawai.kdfungsi = "00000" THEN ref_eselon.uraian ELSE ref_jabatan_fungsional.nama_jabatan END as nama_jabatan')
            )
            ->with('keluarga')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $pegawai
        ]);
    }

    public function showByNip($nip)
    {
        $pegawai = MasterPegawai::query()
            ->leftJoin('satkers', function ($join) {
                $join->on('master_pegawai.kdskpd', '=', 'satkers.kdskpd')
                    ->on('master_pegawai.kdsatker', '=', 'satkers.kdsatker');
            })
            ->leftJoin('ref_stapeg', 'master_pegawai.kdstapeg', '=', 'ref_stapeg.kdstapeg')
            ->leftJoin('ref_jabatan_fungsional', 'master_pegawai.kdfungsi', '=', 'ref_jabatan_fungsional.kdfungsi')
            ->leftJoin('ref_eselon', 'master_pegawai.kdeselon', '=', 'ref_eselon.kd_eselon')
            ->select(
                'master_pegawai.*',
                'satkers.nmskpd',
                'satkers.nmsatker',
                'ref_stapeg.nmstapeg',
                \Illuminate\Support\Facades\DB::raw('CASE WHEN master_pegawai.kdfungsi = "00000" THEN ref_eselon.uraian ELSE ref_jabatan_fungsional.nama_jabatan END as nama_jabatan')
            )
            ->with('keluarga')
            ->where('nip', $nip)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $pegawai
        ]);
    }

    public function stats()
    {
        $stats = \Illuminate\Support\Facades\DB::table('master_pegawai')
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN kdstapeg IN (1, 2, 3, 4, 5, 11, 12) AND kd_jns_peg = 2 THEN 1 ELSE 0 END) as pns_aktif,
                SUM(CASE WHEN kdstapeg IN (1, 2, 3, 4, 5, 11, 12) AND kd_jns_peg = 4 THEN 1 ELSE 0 END) as pppk_aktif,
                SUM(CASE WHEN kdstapeg IN (1, 2, 3, 4, 5, 11, 12) THEN 1 ELSE 0 END) as aktif,
                SUM(CASE WHEN kdstapeg IN (23, 30) THEN 1 ELSE 0 END) as pensiun,
                SUM(CASE WHEN kdstapeg = 27 THEN 1 ELSE 0 END) as meninggal,
                SUM(CASE WHEN kdstapeg IN (24, 28) THEN 1 ELSE 0 END) as mutasi,
                SUM(CASE WHEN kdstapeg IN (6, 7, 8, 9, 10, 22) THEN 1 ELSE 0 END) as non_aktif_temp
            ')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
