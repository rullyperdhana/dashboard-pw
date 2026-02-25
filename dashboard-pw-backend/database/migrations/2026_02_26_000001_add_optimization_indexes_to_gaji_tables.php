<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Add composite indexes to speed up dashboard, annual reports, and upload operations.
     */
    public function up(): void
    {
        // ── gaji_pns ──
        Schema::table('gaji_pns', function (Blueprint $table) {
            // Composite index for period + salary type (used by dashboard, upload deletions)
            $table->index(['tahun', 'bulan', 'jenis_gaji'], 'gaji_pns_period_jenis_idx');

            // Index on jenis_gaji alone (used by filtered queries)
            $table->index('jenis_gaji', 'gaji_pns_jenis_gaji_idx');

            // Index on kdskpd for institutional filtering
            $table->index('kdskpd', 'gaji_pns_kdskpd_idx');

            // Index on kdpangkat for rank-based queries
            $table->index('kdpangkat', 'gaji_pns_kdpangkat_idx');

            // Composite for annual report: year + skpd
            $table->index(['tahun', 'skpd'], 'gaji_pns_tahun_skpd_idx');
        });

        // ── gaji_pppk ──
        Schema::table('gaji_pppk', function (Blueprint $table) {
            $table->index(['tahun', 'bulan', 'jenis_gaji'], 'gaji_pppk_period_jenis_idx');
            $table->index('jenis_gaji', 'gaji_pppk_jenis_gaji_idx');
            $table->index('kdskpd', 'gaji_pppk_kdskpd_idx');
            $table->index('kdpangkat', 'gaji_pppk_kdpangkat_idx');
            $table->index(['tahun', 'skpd'], 'gaji_pppk_tahun_skpd_idx');
        });

        // ── tpg_data ──
        if (Schema::hasTable('tpg_data')) {
            Schema::table('tpg_data', function (Blueprint $table) {
                if (!Schema::hasIndex('tpg_data', 'tpg_data_tahun_triwulan_jenis_idx')) {
                    $table->index(['tahun', 'triwulan', 'jenis'], 'tpg_data_tahun_triwulan_jenis_idx');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gaji_pns', function (Blueprint $table) {
            $table->dropIndex('gaji_pns_period_jenis_idx');
            $table->dropIndex('gaji_pns_jenis_gaji_idx');
            $table->dropIndex('gaji_pns_kdskpd_idx');
            $table->dropIndex('gaji_pns_kdpangkat_idx');
            $table->dropIndex('gaji_pns_tahun_skpd_idx');
        });

        Schema::table('gaji_pppk', function (Blueprint $table) {
            $table->dropIndex('gaji_pppk_period_jenis_idx');
            $table->dropIndex('gaji_pppk_jenis_gaji_idx');
            $table->dropIndex('gaji_pppk_kdskpd_idx');
            $table->dropIndex('gaji_pppk_kdpangkat_idx');
            $table->dropIndex('gaji_pppk_tahun_skpd_idx');
        });

        if (Schema::hasTable('tpg_data')) {
            Schema::table('tpg_data', function (Blueprint $table) {
                $table->dropIndex('tpg_data_tahun_triwulan_jenis_idx');
            });
        }
    }
};
