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
                // Fix confusing "Unknown" label
                $sourceName = $item->source_name;
                if ($item->suggestion && (empty($sourceName) || strtolower($sourceName) === 'unknown')) {
                    $sourceName = $item->suggestion;
                }

                $suggestionId = null;
                if ($item->suggestion) {
                    $normSug = $this->normalizeName($item->suggestion);
                    $match = $allSkpds->first(fn($s) => $this->normalizeName($s->nama_skpd) === $normSug);
                    if ($match) $suggestionId = $match->id_skpd;
                }
                return [
                    'source_code' => $item->source_code,
                    'source_name' => $sourceName,
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
                // Fix confusing "Unknown" label
                $sourceName = $item->source_name;
                if ($item->suggestion && (empty($sourceName) || strtolower($sourceName) === 'unknown')) {
                    $sourceName = $item->suggestion;
                }

                $suggestionId = null;
                if ($item->suggestion) {
                    $normSug = $this->normalizeName($item->suggestion);
                    $match = $allSkpds->first(fn($s) => $this->normalizeName($s->nama_skpd) === $normSug);
                    if ($match) $suggestionId = $match->id_skpd;
                }
                return [
                    'source_code' => $item->source_code,
                    'source_name' => $sourceName,
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
                // Fix confusing "Unknown" label
                $sourceName = $item->source_name;
                if ($item->suggestion && (empty($sourceName) || strtolower($sourceName) === 'unknown')) {
                    $sourceName = $item->suggestion;
                }

                $suggestionId = null;
                if ($item->suggestion) {
                    $normSug = $this->normalizeName($item->suggestion);
                    $match = $allSkpds->first(fn($s) => $this->normalizeName($s->nama_skpd) === $normSug);
                    if ($match) $suggestionId = $match->id_skpd;
                }
                return [
                    'source_code' => (string) $item->source_code,
                    'source_name' => $sourceName,
                    'suggestion' => $item->suggestion,
                    'suggestion_id' => $suggestionId,
                    'type' => 'pppk_pw'
                ];
            });

        // Unique SKPD names from sp2d_realizations not yet mapped
        // We consider it unmapped if: 
        // 1. skpd_id is null
        // 2. OR its assigned skpd_id does NOT have a record in skpd_mapping (Un-bridged)
        $allSkpds = Skpd::select('id_skpd', 'nama_skpd')->get();
        $bridgedSkpdIds = SkpdMapping::pluck('skpd_id')->unique()->toArray();
        
        $unmappedSp2d = DB::table('sp2d_realizations')
            ->where(function($query) use ($bridgedSkpdIds) {
                $query->whereNull('skpd_id')
                      ->orWhereNotIn('skpd_id', $bridgedSkpdIds);
            })
            ->select('nama_skpd_sipd as source_name', 'skpd_id')
            ->distinct()
            ->get()
            ->map(function ($item) use ($allSkpds) {
                // Try to find a suggestion using the same normalization logic
                $suggestion = null;
                $suggestionId = null;
                $normalizedSource = $this->normalizeName($item->source_name);
                
                // If it's already linked to an SKPD but un-bridged, use that as the suggestion
                if ($item->skpd_id) {
                    $existingMatch = $allSkpds->where('id_skpd', $item->skpd_id)->first();
                    if ($existingMatch) {
                        $suggestion = $existingMatch->nama_skpd;
                        $suggestionId = $existingMatch->id_skpd;
                    }
                }

                if (!$suggestion) {
                    foreach ($allSkpds as $skpd) {
                        if ($this->normalizeName($skpd->nama_skpd) === $normalizedSource) {
                            $suggestion = $skpd->nama_skpd;
                            $suggestionId = $skpd->id_skpd;
                            break;
                        }
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
                    'type' => 'all',
                    'is_bridged_issue' => $item->skpd_id ? true : false
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

    /**
     * Restore default mappings from seeder data
     */
    public function restoreDefaults()
    {
        $mappings = [
            ["source_name" => "Unknown", "source_code" => "001", "skpd_id" => 1, "skpd_2026_id" => 1, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "002", "skpd_id" => 6, "skpd_2026_id" => 6, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "006", "skpd_id" => 16, "skpd_2026_id" => 16, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "007", "skpd_id" => 96, "skpd_2026_id" => 96, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "008", "skpd_id" => 115, "skpd_2026_id" => 115, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "009", "skpd_id" => 42, "skpd_2026_id" => 42, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "010", "skpd_id" => 37, "skpd_2026_id" => 37, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "011", "skpd_id" => 79, "skpd_2026_id" => 79, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "012", "skpd_id" => 35, "skpd_2026_id" => 35, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "013", "skpd_id" => 59, "skpd_2026_id" => 59, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "014", "skpd_id" => 22, "skpd_2026_id" => 22, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "015", "skpd_id" => 45, "skpd_2026_id" => 45, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "016", "skpd_id" => 47, "skpd_2026_id" => 47, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "017", "skpd_id" => 58, "skpd_2026_id" => 58, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "018", "skpd_id" => 48, "skpd_2026_id" => 48, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "019", "skpd_id" => 119, "skpd_2026_id" => 119, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "020", "skpd_id" => 50, "skpd_2026_id" => 50, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "021", "skpd_id" => 62, "skpd_2026_id" => 62, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "022", "skpd_id" => 67, "skpd_2026_id" => 67, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "023", "skpd_id" => 21, "skpd_2026_id" => 21, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "024", "skpd_id" => 81, "skpd_2026_id" => 81, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "025", "skpd_id" => 97, "skpd_2026_id" => 97, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "026", "skpd_id" => 41, "skpd_2026_id" => 41, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "027", "skpd_id" => 85, "skpd_2026_id" => 85, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "029", "skpd_id" => 95, "source_code" => "029", "skpd_2026_id" => 95, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "030", "skpd_id" => 20, "skpd_2026_id" => 20, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "031", "skpd_id" => 118, "skpd_2026_id" => 118, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "032", "skpd_id" => 49, "skpd_2026_id" => 49, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "033", "skpd_id" => 98, "skpd_2026_id" => 98, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "034", "skpd_id" => 28, "skpd_2026_id" => 28, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "035", "skpd_id" => 114, "skpd_2026_id" => 114, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "036", "skpd_id" => 113, "skpd_2026_id" => 113, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "037", "skpd_id" => 117, "skpd_2026_id" => 117, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "100", "skpd_id" => 19, "skpd_2026_id" => 19, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "101", "skpd_id" => 40, "skpd_2026_id" => 40, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "102", "skpd_id" => 44, "skpd_2026_id" => 44, "type" => "all"],
            ["source_name" => "Unknown", "source_code" => "103", "skpd_id" => 83, "skpd_2026_id" => 83, "type" => "all"],
            ["source_name" => "DINAS PENDIDIKAN SMA/SMK TANAH LAUT", "source_code" => "070", "skpd_id" => 126, "skpd_2026_id" => 1, "type" => "all"],
            ["source_name" => "DINAS PENDIDIKAN SMA/SMK KOTABARU", "source_code" => "071", "skpd_id" => 127, "skpd_2026_id" => 1, "type" => "all"],
            ["source_name" => "DINAS PENDIDIKAN SMA/SMK BANJAR", "source_code" => "072", "skpd_id" => 128, "skpd_2026_id" => 1, "type" => "all"],
            ["source_name" => "DINAS PENDIDIKAN SMA/SMK BARITO KUALA", "source_code" => "073", "skpd_id" => 129, "skpd_2026_id" => 1, "type" => "all"],
            ["source_name" => "DINAS PENDIDIKAN SMA/SMK TAPIN", "source_code" => "074", "skpd_id" => 130, "skpd_2026_id" => 1, "type" => "all"],
            ["source_name" => "DINAS PENDIDIKAN SMA/SMK HULU SUNGAI SELATAN", "source_code" => "075", "skpd_id" => 131, "skpd_2026_id" => 1, "type" => "all"],
            ["source_name" => "DINAS PENDIDIKAN SMA/SMK HULU SUNGAI TENGAH", "source_code" => "076", "skpd_id" => 132, "skpd_2026_id" => 1, "type" => "all"],
            ["source_name" => "DINAS PENDIDIKAN SMA/SMK HULU SUNGAI UTARA", "source_code" => "077", "skpd_id" => 133, "skpd_2026_id" => 1, "type" => "all"],
            ["source_name" => "DINAS PENDIDIKAN SMA/SMK TABALONG", "source_code" => "078", "skpd_id" => 134, "skpd_2026_id" => 1, "type" => "all"],
            ["source_name" => "DINAS PENDIDIKAN SMA/SMK TANAH BUMBU", "source_code" => "079", "skpd_id" => 135, "skpd_2026_id" => 1, "type" => "all"],
            ["source_name" => "DINAS PENDIDIKAN SMA/SMK BALANGAN", "source_code" => "080", "skpd_id" => 136, "skpd_2026_id" => 1, "type" => "all"],
            ["source_name" => "DINAS PENDIDIKAN SMA/SMK BANJARMASIN", "source_code" => "081", "skpd_id" => 137, "skpd_2026_id" => 1, "type" => "all"],
            ["source_name" => "DINAS PENDIDIKAN SMA/SMK BANJARBARU", "source_code" => "082", "skpd_id" => 138, "skpd_2026_id" => 1, "type" => "all"],
        ];

        $restored = 0;
        $satkerNames = DB::table('satkers')->select('kdskpd', 'nmskpd')->distinct()->get()->pluck('nmskpd', 'kdskpd');

        foreach ($mappings as $mapping) {
            $sourceCode = $mapping['source_code'];
            
            // If name is unknown, try to find it in satkers
            if ($mapping['source_name'] === 'Unknown' && $sourceCode && isset($satkerNames[$sourceCode])) {
                $mapping['source_name'] = $satkerNames[$sourceCode];
            }

            $existing = SkpdMapping::where('source_code', $sourceCode)
                ->where('type', $mapping['type'])
                ->first();
            
            if (!$existing) {
                SkpdMapping::create($mapping);
                $restored++;
            } else if ($existing->source_name === 'Unknown' && $mapping['source_name'] !== 'Unknown') {
                // Also update existing if they were "Unknown" but we now have a better name
                $existing->update(['source_name' => $mapping['source_name']]);
            }
        }

        // Final cleanup for any other Unknowns that might be in the DB but not in the seeder list
        $unknowns = SkpdMapping::where('source_name', 'Unknown')->whereNotNull('source_code')->get();
        foreach ($unknowns as $item) {
            if (isset($satkerNames[$item->source_code])) {
                $item->update(['source_name' => $satkerNames[$item->source_code]]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Berhasil memulihkan $restored mapping standar dan memperbarui nama instansi.",
        ]);
    }
}
