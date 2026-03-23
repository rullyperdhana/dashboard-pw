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
        Schema::table('pph21_calculations', function (Blueprint $table) {
            $table->index(['tahun', 'bulan', 'skpd_id'], 'pph21_period_skpd_idx');
        });

        Schema::table('pegawai_pw', function (Blueprint $table) {
            $table->index(['idskpd', 'status'], 'pegawai_pw_skpd_status_idx');
        });

        Schema::table('master_pegawai', function (Blueprint $table) {
            $table->index(['kdskpd', 'kdstapeg'], 'master_pegawai_skpd_status_idx');
        });

        Schema::table('tb_payment', function (Blueprint $table) {
            // Using existing columns year and month
            $table->index(['year', 'month'], 'tb_payment_period_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pph21_calculations', function (Blueprint $table) {
            $table->dropIndex('pph21_period_skpd_idx');
        });

        Schema::table('pegawai_pw', function (Blueprint $table) {
            $table->dropIndex('pegawai_pw_skpd_status_idx');
        });

        Schema::table('master_pegawai', function (Blueprint $table) {
            $table->dropIndex('master_pegawai_skpd_status_idx');
        });

        Schema::table('tb_payment', function (Blueprint $table) {
            $table->dropIndex('tb_payment_period_idx');
        });
    }
};
