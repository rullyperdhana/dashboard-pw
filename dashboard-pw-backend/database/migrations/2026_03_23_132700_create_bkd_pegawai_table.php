<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bkd_pegawai', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 50)->index();
            $table->string('nama')->nullable();
            $table->string('nik', 30)->nullable();
            $table->string('jabatan')->nullable();
            $table->string('golongan', 10)->nullable();
            $table->date('tgl_lahir')->nullable();
            $table->string('jenis_kelamin', 20)->nullable();
            $table->string('upload_batch', 50)->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bkd_pegawai');
    }
};
