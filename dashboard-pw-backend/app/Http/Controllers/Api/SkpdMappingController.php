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
        $this->syncMasterSkpd();
        $allSkpds = Skpd::select('id_skpd', 'nama_skpd')->get();

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
            ->map(function($item) use ($allSkpds) {
                $suggestionId = null;
                if ($item->suggestion) {
                    $normSug = $this->normalizeName($item->suggestion);
                    $match = $allSkpds->first(fn($s) => $this->normalizeName($s->nama_skpd) === $normSug);
                    if ($match) $suggestionId = $match->id_skpd;
                }
                return [
                    'source_code' => $item->source_code,
                    'source_name' => $item->source_name,
                    'suggestion' => $item->suggestion,
                    'suggestion_id' => $suggestionId,
                    'type' => 'pns'
                ];
            });

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
            ->map(function($item) use ($allSkpds) {
                $suggestionId = null;
                if ($item->suggestion) {
                    $normSug = $this->normalizeName($item->suggestion);
                    $match = $allSkpds->first(fn($s) => $this->normalizeName($s->nama_skpd) === $normSug);
                    if ($match) $suggestionId = $match->id_skpd;
                }
                return [
                    'source_code' => $item->source_code,
                    'source_name' => $item->source_name,
                    'suggestion' => $item->suggestion,
                    'suggestion_id' => $suggestionId,
                    'type' => 'pppk'
                ];
            });

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
            ->map(function($item) use ($allSkpds) {
                $suggestionId = null;
                if ($item->suggestion) {
                    $normSug = $this->normalizeName($item->suggestion);
                    $match = $allSkpds->first(fn($s) => $this->normalizeName($s->nama_skpd) === $normSug);
                    if ($match) $suggestionId = $match->id_skpd;
                }
                return [
                    'source_code' => (string) $item->source_code,
                    'source_name' => $item->source_name,
                    'suggestion' => $item->suggestion,
                    'suggestion_id' => $suggestionId,
                    'type' => 'pppk_pw'
                ];
            });

        // Unique SKPD names from sp2d_realizations not yet mapped
        $allSkpds = Skpd::select('id_skpd', 'nama_skpd')->get();
        
        $unmappedSp2d = DB::table('sp2d_realizations')
            ->whereNull('skpd_id')
            ->select('nama_skpd_sipd as source_name')
            ->distinct()
            ->get()
            ->map(function ($item) use ($allSkpds) {
                // Try to find a suggestion using the same normalization logic
                $suggestion = null;
                $suggestionId = null;
                $normalizedSource = $this->normalizeName($item->source_name);
                
                foreach ($allSkpds as $skpd) {
                    if ($this->normalizeName($skpd->nama_skpd) === $normalizedSource) {
                        $suggestion = $skpd->nama_skpd;
                        $suggestionId = $skpd->id_skpd;
                        break;
                    }
                }

                // If no exact match after normalization, try fuzzy (starts with)
                if (!$suggestion) {
                    $prefix = substr($item->source_name, 0, 15);
                    $fuzzy = $allSkpds->filter(fn($s) => str_starts_with($s->nama_skpd, $prefix))->first();
                    if ($fuzzy) {
                        $suggestion = $fuzzy->nama_skpd;
                        $suggestionId = $fuzzy->id_skpd;
                    }
                }

                return [
                    'source_code' => '',
                    'source_name' => $item->source_name,
                    'suggestion' => $suggestion,
                    'suggestion_id' => $suggestionId,
                    'type' => 'all'
                ];
            });

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
     * Normalize SKPD names for better matching
     */
    private function normalizeName($name)
    {
        $name = strtoupper(trim($name));
        $name = str_replace([
            'DINAS ', 'BADAN ', 'SEKRETARIAT ', 'DAERAH ', 'PROVINSI ', 
            'KALIMANTAN SELATAN', 'PROV. ', 'KALSEL',
            'UPTD ', 'UPPD ', 'BLUD ', 'UNIT ', 'PELAKSANA ', 'TEKNIS ',
            'UNIT PELAYANAN PENDAPATAN DAERAH ',
            'BALAI ', 'KANTOR '
        ], '', $name);
        
        return preg_replace('/[^A-Z0-9]/', '', $name);
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

        // Cari existing mapping dengan kriteria yang lebih ketat
        if ($request->source_code) {
            // Jika ada kode, cari berdasarkan kode + tipe
            $mapping = SkpdMapping::where('source_code', $request->source_code)
                ->where('type', $request->type)
                ->first();
        } else {
            // Jika tidak ada kode, cari berdasarkan nama + tipe (dimana kode juga null)
            $mapping = SkpdMapping::where('source_name', $request->source_name)
                ->where('type', $request->type)
                ->whereNull('source_code')
                ->first();
        }

        if ($mapping) {
            $mapping->update([
                'skpd_id' => $request->skpd_id,
                'skpd_2026_id' => $request->skpd_2026_id,
                'source_name' => $request->source_name,
                'source_code' => $request->source_code ?: $mapping->source_code
            ]);
        } else {
            $mapping = SkpdMapping::create([
                'source_name' => $request->source_name,
                'source_code' => $request->source_code,
                'skpd_id' => $request->skpd_id,
                'skpd_2026_id' => $request->skpd_2026_id,
                'type' => $request->type,
            ]);
        }

        // Update existing unmapped realizations with this name
        if ($request->type === 'all' || $request->type === 'sp2d') {
            \App\Models\Sp2dRealization::where('nama_skpd_sipd', $request->source_name)
                ->whereNull('skpd_id')
                ->update(['skpd_id' => $request->skpd_id]);
        }

        $mapping->load(['skpd', 'skpd2026']);

        return response()->json([
            'success' => true,
            'message' => 'Mapping berhasil disimpan',
            'data' => [
                'id' => $mapping->id,
                'source_name' => $mapping->source_name,
                'source_code' => $mapping->source_code,
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
     * Delete all mappings
     */
    public function destroyAll()
    {
        SkpdMapping::truncate();
        return response()->json(['success' => true, 'message' => 'Semua mapping berhasil dihapus']);
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
            $sourceCode = !empty($item['source_code']) ? $item['source_code'] : null;
            
            if ($sourceCode) {
                $mapping = SkpdMapping::where('source_code', $sourceCode)
                    ->where('type', $item['type'])
                    ->first();
            } else {
                $mapping = SkpdMapping::where('source_name', $item['source_name'])
                    ->where('type', $item['type'])
                    ->whereNull('source_code')
                    ->first();
            }

            if ($mapping) {
                $mapping->update([
                    'skpd_id' => $item['skpd_id'],
                    'skpd_2026_id' => $item['skpd_2026_id'] ?? null,
                    'source_name' => $item['source_name'],
                    'source_code' => $sourceCode ?: $mapping->source_code
                ]);
            } else {
                SkpdMapping::create([
                    'source_name' => $item['source_name'],
                    'source_code' => $sourceCode,
                    'skpd_id' => $item['skpd_id'],
                    'skpd_2026_id' => $item['skpd_2026_id'] ?? null,
                    'type' => $item['type'],
                ]);
            }
            $saved++;
        }

        return response()->json([
            'success' => true,
            'message' => "$saved mapping berhasil disimpan",
        ]);
    }
}
