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
        // Unique SKPD names from gaji_pns not yet mapped
        $mappedPns = SkpdMapping::whereIn('type', ['pns', 'all'])->pluck('source_name')->toArray();
        $unmappedPns = DB::table('gaji_pns')
            ->whereNotNull('skpd')
            ->where('skpd', '!=', '')
            ->whereNotIn('skpd', $mappedPns)
            ->distinct()
            ->orderBy('skpd')
            ->pluck('skpd')
            ->map(fn($name) => ['source_name' => $name, 'type' => 'pns']);

        // Unique SKPD names from gaji_pppk not yet mapped
        $mappedPppk = SkpdMapping::whereIn('type', ['pppk', 'all'])->pluck('source_name')->toArray();
        $unmappedPppk = DB::table('gaji_pppk')
            ->whereNotNull('skpd')
            ->where('skpd', '!=', '')
            ->whereNotIn('skpd', $mappedPppk)
            ->distinct()
            ->orderBy('skpd')
            ->pluck('skpd')
            ->map(fn($name) => ['source_name' => $name, 'type' => 'pppk']);

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
            'skpd_id' => 'required|integer|exists:skpd,id_skpd',
            'type' => 'required|in:pns,pppk,all',
        ]);

        $mapping = SkpdMapping::updateOrCreate(
            ['source_name' => $request->source_name, 'type' => $request->type],
            ['skpd_id' => $request->skpd_id]
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
            'mappings.*.skpd_id' => 'required|integer|exists:skpd,id_skpd',
            'mappings.*.type' => 'required|in:pns,pppk,all',
        ]);

        $saved = 0;
        foreach ($request->mappings as $item) {
            SkpdMapping::updateOrCreate(
                ['source_name' => $item['source_name'], 'type' => $item['type']],
                ['skpd_id' => $item['skpd_id']]
            );
            $saved++;
        }

        return response()->json([
            'success' => true,
            'message' => "$saved mapping berhasil disimpan",
        ]);
    }
}
