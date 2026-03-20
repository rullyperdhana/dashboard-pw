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
        // 1. Delete duplicates, keeping the latest one
        DB::statement("
            DELETE c1 FROM pph21_calculations c1
            INNER JOIN pph21_calculations c2 
            WHERE c1.id < c2.id 
            AND c1.nip = c2.nip 
            AND c1.bulan = c2.bulan 
            AND c1.tahun = c2.tahun 
            AND c1.jenis_gaji = c2.jenis_gaji
        ");

        Schema::table('pph21_calculations', function (Blueprint $table) {
            // 2. Drop old index if exists (it was named automatically or manually in previous migration)
            // Based on previous migration it was: $table->index(['nip', 'bulan', 'tahun', 'jenis_gaji'])
            $table->dropIndex(['nip', 'bulan', 'tahun', 'jenis_gaji']);
            
            // 3. Add unique index
            $table->unique(['nip', 'bulan', 'tahun', 'jenis_gaji'], 'pph21_calc_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pph21_calculations', function (Blueprint $table) {
            $table->dropUnique('pph21_calc_unique');
            $table->index(['nip', 'bulan', 'tahun', 'jenis_gaji']);
        });
    }
};
