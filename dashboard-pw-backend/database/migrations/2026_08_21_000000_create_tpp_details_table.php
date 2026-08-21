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
        Schema::create('tpp_details', function (Blueprint $table) {
            $table->id();
            $table->integer('month');
            $table->integer('year');
            $table->string('employee_type'); // pns, pppk
            $table->string('jenis_gaji')->default('Induk'); // Induk, Susulan, dll
            
            $table->string('nip')->nullable();
            $table->string('nama_lengkap')->nullable();
            $table->string('instansi_upt')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('status_pegawai')->nullable();
            
            $table->decimal('tpp_bruto', 15, 2)->default(0);
            $table->decimal('bruto_plus', 15, 2)->default(0);
            $table->decimal('tpp_netto', 15, 2)->default(0);
            $table->decimal('dpp_pajak', 15, 2)->default(0);
            $table->decimal('pph_21', 15, 2)->default(0);
            $table->decimal('potongan_tpp_lainnya', 15, 2)->default(0);
            $table->decimal('iuran_iwp', 15, 2)->default(0);
            $table->decimal('total_potongan', 15, 2)->default(0);
            $table->decimal('yang_dibayarkan_transfer', 15, 2)->default(0);

            $table->timestamps();

            $table->index(['month', 'year', 'employee_type', 'jenis_gaji']);
            $table->index('nip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tpp_details');
    }
};
