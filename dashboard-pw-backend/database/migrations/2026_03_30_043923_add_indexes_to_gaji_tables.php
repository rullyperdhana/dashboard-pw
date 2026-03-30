<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Perbaiki gaji_pns
        if (Schema::hasTable('gaji_pns')) {
            $this->addIndexIfNotExists('gaji_pns', 'nip', 'gaji_pns_nip_idx');
            $this->addIndexIfNotExists('gaji_pns', 'nama', 'gaji_pns_nama_idx');
        }

        // 2. Perbaiki gaji_pppk (Gunakan nama tabel tunggal)
        if (Schema::hasTable('gaji_pppk')) {
            $this->addIndexIfNotExists('gaji_pppk', 'nip', 'gaji_pppk_nip_idx');
            $this->addIndexIfNotExists('gaji_pppk', 'nama', 'gaji_pppk_nama_idx');
        }
    }

    /**
     * Helper to add index safely
     */
    private function addIndexIfNotExists($tableName, $column, $indexName)
    {
        $indexes = DB::select("SHOW INDEX FROM {$tableName} WHERE Key_name = '{$indexName}'");
        if (empty($indexes)) {
            Schema::table($tableName, function (Blueprint $table) use ($column, $indexName) {
                $table->index($column, $indexName);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gaji_pns', function (Blueprint $table) {
            $table->dropIndex('gaji_pns_nip_idx');
            $table->dropIndex('gaji_pns_nama_idx');
        });

        if (Schema::hasTable('gaji_pppk')) {
            Schema::table('gaji_pppk', function (Blueprint $table) {
                $table->dropIndex('gaji_pppk_nip_idx');
                $table->dropIndex('gaji_pppk_nama_idx');
            });
        }
    }
};
