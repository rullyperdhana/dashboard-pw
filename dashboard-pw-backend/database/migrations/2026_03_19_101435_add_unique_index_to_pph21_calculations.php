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
        // 1. Delete duplicates, keeping the latest one (Database Agnostic)
        DB::table('pph21_calculations')
            ->whereNotIn('id', function($query) {
                $query->selectRaw('MAX(id)')
                    ->from('pph21_calculations')
                    ->groupBy('nip', 'bulan', 'tahun', 'jenis_gaji');
            })
            ->delete();

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
