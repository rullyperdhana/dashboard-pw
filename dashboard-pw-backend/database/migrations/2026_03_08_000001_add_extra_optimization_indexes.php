<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Add missing indexes to master_pegawai and other tables to speed up complex joins.
     */
    public function up(): void
    {
        Schema::table('master_pegawai', function (Blueprint $table) {
            // Index for joins with ref_jabatan_fungsional
            if (!Schema::hasIndex('master_pegawai', 'master_pegawai_kdfungsi_idx')) {
                $table->index('kdfungsi', 'master_pegawai_kdfungsi_idx');
            }

            // Index for joins with ref_eselon
            if (!Schema::hasIndex('master_pegawai', 'master_pegawai_kdeselon_idx')) {
                $table->index('kdeselon', 'master_pegawai_kdeselon_idx');
            }

            // Index for bank-based filtering
            if (!Schema::hasIndex('master_pegawai', 'master_pegawai_induk_bank_idx')) {
                $table->index('induk_bank', 'master_pegawai_induk_bank_idx');
            }
        });

        // Ensure status_asn (kd_jns_peg) is always indexed
        Schema::table('master_pegawai', function (Blueprint $table) {
            if (!Schema::hasIndex('master_pegawai', 'master_pegawai_kd_jns_peg_idx')) {
                $table->index('kd_jns_peg', 'master_pegawai_kd_jns_peg_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_pegawai', function (Blueprint $table) {
            $table->dropIndex('master_pegawai_kdfungsi_idx');
            $table->dropIndex('master_pegawai_kdeselon_idx');
            $table->dropIndex('master_pegawai_induk_bank_idx');
            $table->dropIndex('master_pegawai_kd_jns_peg_idx');
        });
    }
};
