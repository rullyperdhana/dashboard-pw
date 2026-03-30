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
        // Add indexes to gaji_pns
        Schema::table('gaji_pns', function (Blueprint $table) {
            $table->index('nip');
            $table->index('nama');
            $table->index('kdskpd');
            $table->index(['tahun', 'bulan']);
            $table->index('jenis_gaji');
        });

        // Add indexes to gaji_pppks (if exists)
        if (Schema::hasTable('gaji_pppks')) {
            Schema::table('gaji_pppks', function (Blueprint $table) {
                $table->index('nip');
                $table->index('nama');
                $table->index('kdskpd');
                $table->index(['tahun', 'bulan']);
                $table->index('jenis_gaji');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gaji_pns', function (Blueprint $table) {
            $table->dropIndex(['nip']);
            $table->dropIndex(['nama']);
            $table->dropIndex(['kdskpd']);
            $table->dropIndex(['tahun', 'bulan']);
            $table->dropIndex(['jenis_gaji']);
        });

        if (Schema::hasTable('gaji_pppks')) {
            Schema::table('gaji_pppks', function (Blueprint $table) {
                $table->dropIndex(['nip']);
                $table->dropIndex(['nama']);
                $table->dropIndex(['kdskpd']);
                $table->dropIndex(['tahun', 'bulan']);
                $table->dropIndex(['jenis_gaji']);
            });
        }
    }
};
