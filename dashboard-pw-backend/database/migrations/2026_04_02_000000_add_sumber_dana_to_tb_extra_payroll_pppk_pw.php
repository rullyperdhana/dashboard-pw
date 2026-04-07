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
        if (!Schema::hasColumn('tb_extra_payroll_pppk_pw', 'sumber_dana')) {
            Schema::table('tb_extra_payroll_pppk_pw', function (Blueprint $table) {
                $table->string('sumber_dana')->nullable()->after('skpd_name');
            });
        }

        // Backfill existing data for 2026
        DB::statement("
            UPDATE tb_extra_payroll_pppk_pw t
            JOIN pegawai_pw p ON t.employee_id = p.id
            SET t.sumber_dana = p.sumber_dana
            WHERE t.year = 2026 AND t.sumber_dana IS NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_extra_payroll_pppk_pw', function (Blueprint $table) {
            $table->dropColumn('sumber_dana');
        });
    }
};
