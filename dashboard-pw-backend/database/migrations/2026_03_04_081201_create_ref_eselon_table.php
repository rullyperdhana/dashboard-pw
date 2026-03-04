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
        Schema::create('ref_eselon', function (Blueprint $table) {
            $table->string('kd_eselon', 2)->primary();
            $table->integer('rp_eselon')->default(0);
            $table->string('uraian', 50)->nullable();
            $table->integer('bup')->default(58);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ref_eselon');
    }
};
