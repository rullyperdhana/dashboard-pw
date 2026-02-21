<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gaji_pns', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 50)->index();
            $table->string('nama');
            $table->string('golongan', 10)->nullable();
            $table->string('kdpangkat', 10)->nullable();
            $table->string('jabatan')->nullable();
            $table->string('skpd')->nullable();
            $table->string('satker')->nullable();
            $table->string('kdskpd', 10)->nullable();
            $table->string('kdjenkel', 5)->nullable();
            $table->string('pendidikan')->nullable();
            $table->string('norek', 50)->nullable();
            $table->string('npwp', 50)->nullable();
            $table->string('noktp', 50)->nullable();
            // Tunjangan
            $table->decimal('gaji_pokok', 15, 2)->default(0);
            $table->decimal('tunj_istri', 15, 2)->default(0);
            $table->decimal('tunj_anak', 15, 2)->default(0);
            $table->decimal('tunj_fungsional', 15, 2)->default(0);
            $table->decimal('tunj_struktural', 15, 2)->default(0);
            $table->decimal('tunj_umum', 15, 2)->default(0);
            $table->decimal('tunj_beras', 15, 2)->default(0);
            $table->decimal('tunj_pph', 15, 2)->default(0);
            $table->decimal('tunj_tpp', 15, 2)->default(0);
            $table->decimal('tunj_eselon', 15, 2)->default(0);
            $table->decimal('tunj_guru', 15, 2)->default(0);
            $table->decimal('tunj_langka', 15, 2)->default(0);
            $table->decimal('tunj_tkd', 15, 2)->default(0);
            $table->decimal('tunj_terpencil', 15, 2)->default(0);
            $table->decimal('tunj_khusus', 15, 2)->default(0);
            $table->decimal('tunj_askes', 15, 2)->default(0);
            $table->decimal('tunj_kk', 15, 2)->default(0);
            $table->decimal('tunj_km', 15, 2)->default(0);
            $table->decimal('pembulatan', 15, 2)->default(0);
            $table->decimal('kotor', 15, 2)->default(0);
            // Potongan
            $table->decimal('pot_iwp', 15, 2)->default(0);
            $table->decimal('pot_iwp1', 15, 2)->default(0);
            $table->decimal('pot_iwp8', 15, 2)->default(0);
            $table->decimal('pot_askes', 15, 2)->default(0);
            $table->decimal('pot_pph', 15, 2)->default(0);
            $table->decimal('pot_bulog', 15, 2)->default(0);
            $table->decimal('pot_taperum', 15, 2)->default(0);
            $table->decimal('pot_sewa', 15, 2)->default(0);
            $table->decimal('pot_hutang', 15, 2)->default(0);
            $table->decimal('pot_korpri', 15, 2)->default(0);
            $table->decimal('pot_irdhata', 15, 2)->default(0);
            $table->decimal('pot_koperasi', 15, 2)->default(0);
            $table->decimal('pot_jkk', 15, 2)->default(0);
            $table->decimal('pot_jkm', 15, 2)->default(0);
            $table->decimal('total_potongan', 15, 2)->default(0);
            $table->decimal('bersih', 15, 2)->default(0);
            $table->integer('bulan');
            $table->integer('tahun');
            $table->timestamps();
            $table->index(['bulan', 'tahun']);
            $table->index('skpd');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gaji_pns');
    }
};
