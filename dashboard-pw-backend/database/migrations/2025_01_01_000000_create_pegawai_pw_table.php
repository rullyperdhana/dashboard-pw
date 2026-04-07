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
        if (!Schema::hasTable('pegawai_pw')) {
            Schema::create('pegawai_pw', function (Blueprint $table) {
                $table->integer('id')->autoIncrement();
                $table->integer('idskpd')->nullable();
                $table->string('nip')->nullable();
                $table->string('nama')->nullable();
                $table->string('tempat_lahir')->nullable();
                $table->date('tgl_lahir')->nullable();
                $table->string('jk')->nullable();
                $table->string('status')->nullable();
                $table->string('agama')->nullable();
                $table->string('golru')->nullable();
                $table->date('tmt_golru')->nullable();
                $table->string('jabatan')->nullable();
                $table->string('eselon')->nullable();
                $table->string('jenis_jabatan')->nullable();
                $table->date('tmt_jabatan')->nullable();
                $table->string('skpd')->nullable();
                $table->string('upt')->nullable();
                $table->string('satker')->nullable();
                $table->integer('mk_thn')->nullable();
                $table->integer('mk_bln')->nullable();
                $table->string('tk_ijazah')->nullable();
                $table->string('nm_pendidikan')->nullable();
                $table->string('th_lulus')->nullable();
                $table->string('usia')->nullable();
                $table->string('usia_bup')->nullable();
                $table->text('keterangan')->nullable();
                $table->decimal('gapok', 15, 2)->nullable();
                $table->decimal('tunjangan', 15, 2)->nullable();
                $table->decimal('pajak', 15, 2)->nullable();
                $table->decimal('iwp', 15, 2)->nullable();
                $table->decimal('potongan', 15, 2)->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pegawai_pw');
    }
};
