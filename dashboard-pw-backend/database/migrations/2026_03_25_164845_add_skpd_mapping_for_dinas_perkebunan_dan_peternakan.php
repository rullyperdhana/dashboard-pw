<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Find all \"old\" SKPD entries (missing kode_simgaji) that have a \"new\" 
        // match with a name that is identical and has a kode_simgaji.
        $mappings = \Illuminate\Support\Facades\DB::table('skpd as s1')
            ->join('skpd as s2', 's1.nama_skpd', '=', 's2.nama_skpd')
            ->whereNull('s1.kode_simgaji')
            ->whereNotNull('s2.kode_simgaji')
            ->where('s1.id_skpd', '!=', \Illuminate\Support\Facades\DB::raw('s2.id_skpd'))
            ->select('s1.id_skpd', 's2.kode_simgaji as source_code', 's1.nama_skpd as source_name')
            ->get();

        foreach ($mappings as $mapping) {
            \Illuminate\Support\Facades\DB::table('skpd_mapping')->updateOrInsert(
                [
                    'skpd_id' => $mapping->id_skpd,
                    'source_code' => $mapping->source_code,
                    'type' => 'all'
                ],
                [
                    'source_name' => $mapping->source_name,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optional: Clean up mappings created by this migration
        // But since we use updateOrInsert, we only delete the ones we are sure about
        // for safety, we'll only delete if it matches the specific pattern
        $mappings = \Illuminate\Support\Facades\DB::table('skpd as s1')
            ->join('skpd as s2', 's1.nama_skpd', '=', 's2.nama_skpd')
            ->whereNull('s1.kode_simgaji')
            ->whereNotNull('s2.kode_simgaji')
            ->where('s1.id_skpd', '!=', \Illuminate\Support\Facades\DB::raw('s2.id_skpd'))
            ->select('s1.id_skpd', 's2.kode_simgaji as source_code')
            ->get();

        foreach ($mappings as $mapping) {
            \Illuminate\Support\Facades\DB::table('skpd_mapping')
                ->where('skpd_id', $mapping->id_skpd)
                ->where('source_code', $mapping->source_code)
                ->delete();
        }
    }
};
