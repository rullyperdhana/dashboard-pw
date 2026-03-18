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
        Schema::create('pph21_calculations', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 20);
            $table->string('nama')->nullable();
            $table->integer('bulan');
            $table->integer('tahun');
            $table->string('jenis_gaji')->default('Induk');
            $table->string('status_ptkp', 10); // K/0, TK/1 etc
            $table->string('ter_category', 1); // A, B, C 
            $table->decimal('gross_base', 15, 2);
            $table->decimal('tax_amount', 15, 2);
            $table->json('calc_details')->nullable();
            $table->timestamps();

            $table->index(['nip', 'bulan', 'tahun', 'jenis_gaji']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pph21_calculations');
    }
};
