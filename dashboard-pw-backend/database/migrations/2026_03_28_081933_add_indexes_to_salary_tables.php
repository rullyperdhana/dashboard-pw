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
        Schema::table('gaji_pns', function (Blueprint $table) {
            $table->index(['tahun', 'bulan', 'jenis_gaji'], 'idx_gaji_pns_thn_bln_jns');
        });

        Schema::table('gaji_pppk', function (Blueprint $table) {
            $table->index(['tahun', 'bulan', 'jenis_gaji'], 'idx_gaji_pppk_thn_bln_jns');
        });

        Schema::table('tb_payment_detail', function (Blueprint $table) {
            $table->index('payment_id', 'idx_tb_payment_detail_payment_id');
            $table->index('employee_id', 'idx_tb_payment_detail_employee_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gaji_pns', function (Blueprint $table) {
            $table->dropIndex('idx_gaji_pns_thn_bln_jns');
        });

        Schema::table('gaji_pppk', function (Blueprint $table) {
            $table->dropIndex('idx_gaji_pppk_thn_bln_jns');
        });

        Schema::table('tb_payment_detail', function (Blueprint $table) {
            $table->dropIndex('idx_tb_payment_detail_payment_id');
            $table->dropIndex('idx_tb_payment_detail_employee_id');
        });
    }
};
