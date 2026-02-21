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
        Schema::create('tpg_data', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 30);
            $table->string('nama', 255)->nullable();
            $table->string('no_rekening', 255)->nullable();
            $table->string('satdik', 255)->nullable();
            $table->decimal('salur_brut', 15, 2)->default(0);
            $table->decimal('pot_jkn', 15, 2)->default(0);
            $table->decimal('salur_nett', 15, 2)->default(0);
            $table->tinyInteger('triwulan'); // 1-4
            $table->smallInteger('tahun');
            $table->timestamps();

            $table->unique(['nip', 'triwulan', 'tahun'], 'tpg_nip_tw_tahun_unique');
            $table->index('satdik');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tpg_data');
    }
};
