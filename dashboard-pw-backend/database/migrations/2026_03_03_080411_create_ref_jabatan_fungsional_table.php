<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ref_jabatan_fungsional', function (Blueprint $table) {
            $table->id();
            $table->string('kdfungsi', 20)->index();
            $table->string('nama_jabatan', 200);
            $table->bigInteger('tunjangan')->default(0);
            $table->unsignedInteger('usia_pensiun')->nullable();
            $table->unsignedInteger('kelompok_fungsi')->nullable();
            $table->date('tmt_jabatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ref_jabatan_fungsional');
    }
};
