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
        // 1. Rename the table
        Schema::rename('tb_thr_pppk_pw', 'tb_extra_payroll_pppk_pw');

        Schema::table('tb_extra_payroll_pppk_pw', function (Blueprint $table) {
            // 2. Add type column
            $table->string('type', 20)->default('thr')->after('month')->index();
            
            // 3. Rename thr_amount to payroll_amount
            $table->renameColumn('thr_amount', 'payroll_amount');
            
            // 4. Update unique index
            $table->dropUnique('thr_pppk_pw_unique');
            $table->unique(['year', 'month', 'type', 'nip', 'nama_sub_giat'], 'extra_payroll_pppk_pw_unique');
        });

        // 5. Update existing records to have type 'thr' (handled by default above, but explicit is better)
        DB::table('tb_extra_payroll_pppk_pw')->update(['type' => 'thr']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_extra_payroll_pppk_pw', function (Blueprint $table) {
            $table->dropUnique('extra_payroll_pppk_pw_unique');
            $table->renameColumn('payroll_amount', 'thr_amount');
            $table->dropColumn('type');
            $table->unique(['year', 'month', 'nip', 'nama_sub_giat'], 'thr_pppk_pw_unique');
        });

        Schema::rename('tb_extra_payroll_pppk_pw', 'tb_thr_pppk_pw');
    }
};
