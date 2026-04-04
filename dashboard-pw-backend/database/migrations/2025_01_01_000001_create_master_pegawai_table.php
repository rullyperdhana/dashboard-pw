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
        Schema::create('master_pegawai', function (Blueprint $table) {
            $table->string('nip', 20)->primary();
            $table->string('nmpeg', 100)->nullable();
            $table->string('kdskpd', 20)->nullable();
            $table->string('kdstapeg', 2)->nullable();
            $table->string('kdgol', 2)->nullable();
            $table->string('kdjab', 10)->nullable();
            $table->integer('gapok')->default(0);
            $table->timestamps();
            
            $table->index(['kdskpd', 'kdstapeg'], 'master_pegawai_skpd_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_pegawai');
    }
};
