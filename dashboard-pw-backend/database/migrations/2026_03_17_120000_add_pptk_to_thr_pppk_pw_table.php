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
        Schema::table('tb_thr_pppk_pw', function (Blueprint $table) {
            $table->string('pptk_nama')->nullable()->after('nama_sub_giat');
            $table->string('pptk_nip')->nullable()->after('pptk_nama');
            $table->string('pptk_jabatan')->nullable()->after('pptk_nip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tb_thr_pppk_pw', function (Blueprint $table) {
            $table->dropColumn(['pptk_nama', 'pptk_nip', 'pptk_jabatan']);
        });
    }
};
