<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tpg_data', function (Blueprint $table) {
            $table->tinyInteger('bulan')->nullable()->after('salur_nett');
            
            // Drop old unique index and create new one that includes bulan
            $table->dropUnique('tpg_nip_tw_tahun_jenis_unique');
            $table->unique(['nip', 'bulan', 'tahun', 'jenis'], 'tpg_nip_bulan_tahun_jenis_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tpg_data', function (Blueprint $table) {
            $table->dropUnique('tpg_nip_bulan_tahun_jenis_unique');
            $table->unique(['nip', 'triwulan', 'tahun', 'jenis'], 'tpg_nip_tw_tahun_jenis_unique');
            $table->dropColumn('bulan');
        });
    }
};
