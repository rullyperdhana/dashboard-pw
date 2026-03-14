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
        Schema::create('skpd_2026', function (Blueprint $table) {
            $table->id();
            $table->string('kode_skpd')->nullable();
            $table->string('nama_skpd');
            $table->boolean('is_skpd')->default(true);
            $table->string('kode_simgaji', 50)->nullable();
            $table->string('kode_sipd', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skpd_2026');
    }
};
