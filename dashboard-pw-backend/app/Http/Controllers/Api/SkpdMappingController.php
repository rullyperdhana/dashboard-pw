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
        $mappings = SkpdMapping::with('skpd')
            ->orderBy('type')
            ->orderBy('source_name')
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'source_name' => $m->source_name,
                'source_code' => $m->source_code,
                'skpd_id' => $m->skpd_id,
                'type' => $m->type,
                'nama_skpd' => $m->skpd?->nama_skpd,
                'kode_skpd' => $m->skpd?->kode_skpd,
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

        return response()->json([
            'success' => true,
            'data' => [
                'pns' => $unmappedPns,
                'pppk' => $unmappedPppk,
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
            'source_code' => 'required|string|max:50',
            'skpd_id' => 'required|integer|exists:skpd,id_skpd',
            'type' => 'required|in:pns,pppk,all',
        ]);

        $mapping = SkpdMapping::updateOrCreate(
            ['source_code' => $request->source_code, 'type' => $request->type],
            ['skpd_id' => $request->skpd_id, 'source_name' => $request->source_name]
        );

        $mapping->load('skpd');

        return response()->json([
            'success' => true,
            'message' => 'Mapping berhasil disimpan',
            'data' => [
                'id' => $mapping->id,
                'source_name' => $mapping->source_name,
                'skpd_id' => $mapping->skpd_id,
                'type' => $mapping->type,
                'nama_skpd' => $mapping->skpd?->nama_skpd,
                'kode_skpd' => $mapping->skpd?->kode_skpd,
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
            'mappings.*.source_code' => 'required|string|max:50',
            'mappings.*.skpd_id' => 'required|integer|exists:skpd,id_skpd',
            'mappings.*.type' => 'required|in:pns,pppk,all',
        ]);

        $saved = 0;
        foreach ($request->mappings as $item) {
            SkpdMapping::updateOrCreate(
                ['source_code' => $item['source_code'], 'type' => $item['type']],
                ['skpd_id' => $item['skpd_id'], 'source_name' => $item['source_name']]
            );
            $saved++;
        }

        return response()->json([
            'success' => true,
            'message' => "$saved mapping berhasil disimpan",
        ]);
    }
}
