<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tpg_data', function (Blueprint $table) {
            $table->string('jenis', 20)->default('INDUK')->after('tahun');

            // Drop old unique index and create new one that includes jenis
            $table->dropUnique('tpg_nip_tw_tahun_unique');
            $table->unique(['nip', 'triwulan', 'tahun', 'jenis'], 'tpg_nip_tw_tahun_jenis_unique');
        });
    }

    public function down(): void
    {
        Schema::table('tpg_data', function (Blueprint $table) {
            $table->dropUnique('tpg_nip_tw_tahun_jenis_unique');
            $table->unique(['nip', 'triwulan', 'tahun'], 'tpg_nip_tw_tahun_unique');
            $table->dropColumn('jenis');
        });
    }
};
