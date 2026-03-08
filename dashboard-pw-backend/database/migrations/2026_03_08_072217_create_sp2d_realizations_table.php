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
        if (!Schema::hasTable('sp2d_realizations')) {
            Schema::create('sp2d_realizations', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_sp2d')->index();
                $table->date('tanggal_sp2d')->index();
                $table->string('nama_skpd_sipd')->index();
                $table->unsignedBigInteger('skpd_id')->nullable()->index();
                $table->text('keterangan')->nullable();
                $table->string('jenis_data')->index(); // PNS, PPPK, TPP
                $table->decimal('brutto', 20, 2)->default(0);
                $table->decimal('potongan', 20, 2)->default(0);
                $table->decimal('netto', 20, 2)->default(0);
                $table->integer('bulan');
                $table->integer('tahun');
                $table->timestamps();

                $table->foreign('skpd_id')->references('id_skpd')->on('skpd')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sp2d_realizations');
    }
};
