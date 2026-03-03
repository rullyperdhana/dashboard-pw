<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['gaji_pns', 'gaji_pppk'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                if (!Schema::hasColumn($table->getTable(), 'jenis_gaji')) {
                    $table->string('jenis_gaji')->default('Induk')->after('tahun');
                }

                // Add any other potential missing fields from SIMDA DBF
                if (!Schema::hasColumn($table->getTable(), 'keterangan')) {
                    $table->text('keterangan')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['gaji_pns', 'gaji_pppk'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                if (Schema::hasColumn($table->getTable(), 'jenis_gaji')) {
                    $table->dropColumn('jenis_gaji');
                }
                if (Schema::hasColumn($table->getTable(), 'keterangan')) {
                    $table->dropColumn('keterangan');
                }
            });
        }
    }
};
