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
            ->select('master_pegawai.*', 'satkers.nmskpd', 'satkers.nmsatker');

        if ($request->has('search')) {
            $query->search($request->search);
        }

        if ($request->has('kdskpd')) {
            $query->where('master_pegawai.kdskpd', $request->kdskpd);
        }

        if ($request->has('kd_jns_peg')) {
            $query->where('master_pegawai.kd_jns_peg', $request->kd_jns_peg);
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
            ->select('master_pegawai.*', 'satkers.nmskpd', 'satkers.nmsatker')
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
            ->select('master_pegawai.*', 'satkers.nmskpd', 'satkers.nmsatker')
            ->with('keluarga')
            ->where('nip', $nip)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $pegawai
        ]);
    }
}
