<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skpd;
use App\Models\SkpdMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SkpdMappingController extends Controller
{
    /**
     * Get all existing mappings with their SKPD info
     */
    public function index()
    {
        $mappings = SkpdMapping::with(['skpd', 'skpd2026'])
            ->orderBy('type')
            ->orderBy('source_name')
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'source_name' => $m->source_name,
                'source_code' => $m->source_code,
                'skpd_id' => $m->skpd_id,
                'skpd_2026_id' => $m->skpd_2026_id,
                'type' => $m->type,
                'nama_skpd' => $m->skpd?->nama_skpd,
                'kode_skpd' => $m->skpd?->kode_skpd,
                'nama_skpd_2026' => $m->skpd2026?->nama_skpd,
                'kode_skpd_2026' => $m->skpd2026?->kode_skpd,
            ]);

        return response()->json(['success' => true, 'data' => $mappings]);
    }

    /**
     * Get unmapped SKPD names from gaji_pns and gaji_pppk
     */
    public function unmapped()
    {
        // Unique SKPD codes from gaji_pns not yet mapped
        $mappedPns = SkpdMapping::whereIn('type', ['pns', 'all'])->whereNotNull('source_code')->pluck('source_code')->toArray();
        $unmappedPns = DB::table('gaji_pns')
            ->leftJoin(DB::raw('(SELECT DISTINCT kdskpd, nmskpd FROM satkers) as s'), 'gaji_pns.kdskpd', '=', 's.kdskpd')
            ->whereNotNull('gaji_pns.kdskpd')
            ->whereNotIn('gaji_pns.kdskpd', $mappedPns)
            ->select('gaji_pns.kdskpd as source_code', DB::raw('MAX(gaji_pns.skpd) as source_name'), 's.nmskpd as suggestion')
            ->groupBy('gaji_pns.kdskpd', 's.nmskpd')
            ->orderBy('gaji_pns.kdskpd')
            ->get()
            ->map(fn($item) => [
                'source_code' => $item->source_code,
                'source_name' => $item->source_name,
                'suggestion' => $item->suggestion,
                'type' => 'pns'
            ]);

        // Unique SKPD codes from gaji_pppk not yet mapped
        $mappedPppk = SkpdMapping::whereIn('type', ['pppk', 'all'])->whereNotNull('source_code')->pluck('source_code')->toArray();
        $unmappedPppk = DB::table('gaji_pppk')
            ->leftJoin(DB::raw('(SELECT DISTINCT kdskpd, nmskpd FROM satkers) as s'), 'gaji_pppk.kdskpd', '=', 's.kdskpd')
            ->whereNotNull('gaji_pppk.kdskpd')
            ->whereNotIn('gaji_pppk.kdskpd', $mappedPppk)
            ->select('gaji_pppk.kdskpd as source_code', DB::raw('MAX(gaji_pppk.skpd) as source_name'), 's.nmskpd as suggestion')
            ->groupBy('gaji_pppk.kdskpd', 's.nmskpd')
            ->orderBy('gaji_pppk.kdskpd')
            ->get()
            ->map(fn($item) => [
                'source_code' => $item->source_code,
                'source_name' => $item->source_name,
                'suggestion' => $item->suggestion,
                'type' => 'pppk'
            ]);

        // Unique SKPD codes from pegawai_pw (PPPK Paruh Waktu) not yet mapped
        $mappedPppkPw = SkpdMapping::whereIn('type', ['pppk_pw', 'all'])->whereNotNull('source_code')->pluck('source_code')->toArray();
        $unmappedPppkPw = DB::table('pegawai_pw')
            ->leftJoin(DB::raw('(SELECT DISTINCT kdskpd, nmskpd FROM satkers) as s'), 'pegawai_pw.idskpd', '=', 's.kdskpd')
            ->whereNotNull('pegawai_pw.idskpd')
            ->whereNotIn('pegawai_pw.idskpd', $mappedPppkPw)
            ->select('pegawai_pw.idskpd as source_code', DB::raw('MAX(pegawai_pw.skpd) as source_name'), 's.nmskpd as suggestion')
            ->groupBy('pegawai_pw.idskpd', 's.nmskpd')
            ->orderBy('pegawai_pw.idskpd')
            ->get()
            ->map(fn($item) => [
                'source_code' => (string) $item->source_code,
                'source_name' => $item->source_name,
                'suggestion' => $item->suggestion,
                'type' => 'pppk_pw'
            ]);

        // Unique SKPD names from sp2d_realizations not yet mapped
        $unmappedSp2d = DB::table('sp2d_realizations')
            ->whereNull('skpd_id')
            ->select('nama_skpd_sipd as source_name')
            ->distinct()
            ->get()
            ->map(fn($item) => [
                'source_code' => '',
                'source_name' => $item->source_name,
                'suggestion' => null,
                'type' => 'all'
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'pns' => $unmappedPns,
                'pppk' => $unmappedPppk,
                'pppk_pw' => $unmappedPppkPw,
                'sp2d' => $unmappedSp2d,
            ]
        ]);
    }

    /**
     * Create or update a mapping
     */
    public function store(Request $request)
    {
        $request->validate([
            'source_name' => 'required|string|max:255',
            'source_code' => 'nullable|string|max:50',
            'skpd_id' => 'required|integer|exists:skpd,id_skpd',
            'skpd_2026_id' => 'nullable|integer|exists:skpd_2026,id',
            'type' => 'required|in:pns,pppk,pppk_pw,all',
        ]);

        if ($request->source_code) {
            $mapping = SkpdMapping::updateOrCreate(
                ['source_code' => $request->source_code, 'type' => $request->type],
                ['skpd_id' => $request->skpd_id, 'skpd_2026_id' => $request->skpd_2026_id, 'source_name' => $request->source_name]
            );
        } else {
            $mapping = SkpdMapping::updateOrCreate(
                ['source_name' => $request->source_name, 'type' => $request->type],
                ['skpd_id' => $request->skpd_id, 'skpd_2026_id' => $request->skpd_2026_id, 'source_code' => null]
            );

            // Update existing unmapped realizations with this name
            if ($request->type === 'all') {
                \App\Models\Sp2dRealization::where('nama_skpd_sipd', $request->source_name)
                    ->whereNull('skpd_id')
                    ->update(['skpd_id' => $request->skpd_id]);
            }
        }

        $mapping->load(['skpd', 'skpd2026']);

        return response()->json([
            'success' => true,
            'message' => 'Mapping berhasil disimpan',
            'data' => [
                'id' => $mapping->id,
                'source_name' => $mapping->source_name,
                'skpd_id' => $mapping->skpd_id,
                'skpd_2026_id' => $mapping->skpd_2026_id,
                'type' => $mapping->type,
                'nama_skpd' => $mapping->skpd?->nama_skpd,
                'kode_skpd' => $mapping->skpd?->kode_skpd,
                'nama_skpd_2026' => $mapping->skpd2026?->nama_skpd,
                'kode_skpd_2026' => $mapping->skpd2026?->kode_skpd,
            ],
        ]);
    }

    /**
     * Delete a mapping
     */
    public function destroy($id)
    {
        $mapping = SkpdMapping::findOrFail($id);
        $mapping->delete();

        return response()->json(['success' => true, 'message' => 'Mapping berhasil dihapus']);
    }

    /**
     * Bulk store mappings
     */
    public function bulkStore(Request $request)
    {
        $request->validate([
            'mappings' => 'required|array|min:1',
            'mappings.*.source_name' => 'required|string|max:255',
            'mappings.*.source_code' => 'nullable|string|max:50',
            'mappings.*.skpd_id' => 'required|integer|exists:skpd,id_skpd',
            'mappings.*.skpd_2026_id' => 'nullable|integer|exists:skpd_2026,id',
            'mappings.*.type' => 'required|in:pns,pppk,pppk_pw,all',
        ]);

        $saved = 0;
        foreach ($request->mappings as $item) {
            if (!empty($item['source_code'])) {
                SkpdMapping::updateOrCreate(
                    ['source_code' => $item['source_code'], 'type' => $item['type']],
                    [
                        'skpd_id' => $item['skpd_id'], 
                        'skpd_2026_id' => $item['skpd_2026_id'] ?? null, 
                        'source_name' => $item['source_name']
                    ]
                );
            } else {
                SkpdMapping::updateOrCreate(
                    ['source_name' => $item['source_name'], 'type' => $item['type']],
                    [
                        'skpd_id' => $item['skpd_id'], 
                        'skpd_2026_id' => $item['skpd_2026_id'] ?? null, 
                        'source_code' => null
                    ]
                );
            }
            $saved++;
        }

        return response()->json([
            'success' => true,
            'message' => "$saved mapping berhasil disimpan",
        ]);
    }
}
