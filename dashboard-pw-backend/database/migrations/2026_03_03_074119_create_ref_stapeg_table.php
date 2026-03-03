<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ref_stapeg', function (Blueprint $table) {
            $table->unsignedInteger('kdstapeg')->primary();
            $table->string('nmstapeg', 100);
            $table->timestamps();
        });

        // Seed reference data
        DB::table('ref_stapeg')->insert([
            ['kdstapeg' => 1, 'nmstapeg' => 'PEJABAT NEGARA', 'created_at' => now(), 'updated_at' => now()],
            ['kdstapeg' => 2, 'nmstapeg' => 'PPBASN', 'created_at' => now(), 'updated_at' => now()],
            ['kdstapeg' => 3, 'nmstapeg' => 'CALON PEGAWAI (CPNS)', 'created_at' => now(), 'updated_at' => now()],
            ['kdstapeg' => 4, 'nmstapeg' => 'PEGAWAI TETAP (PNS)', 'created_at' => now(), 'updated_at' => now()],
            ['kdstapeg' => 5, 'nmstapeg' => 'PEGAWAI TUGAS BELAJAR', 'created_at' => now(), 'updated_at' => now()],
            ['kdstapeg' => 6, 'nmstapeg' => 'PEGAWAI CUTI DILUAR TANGGUNGAN NEGARA', 'created_at' => now(), 'updated_at' => now()],
            ['kdstapeg' => 7, 'nmstapeg' => 'PEGAWAI DIPERBANTUKAN', 'created_at' => now(), 'updated_at' => now()],
            ['kdstapeg' => 8, 'nmstapeg' => 'PEGAWAI DIPEKERJAKAN', 'created_at' => now(), 'updated_at' => now()],
            ['kdstapeg' => 9, 'nmstapeg' => 'PEGAWAI SKORSING', 'created_at' => now(), 'updated_at' => now()],
            ['kdstapeg' => 10, 'nmstapeg' => 'PEGAWAI PENERIMA UANG TUNGGU', 'created_at' => now(), 'updated_at' => now()],
            ['kdstapeg' => 11, 'nmstapeg' => 'ANGGOTA DPRD', 'created_at' => now(), 'updated_at' => now()],
            ['kdstapeg' => 12, 'nmstapeg' => 'P3K', 'created_at' => now(), 'updated_at' => now()],
            ['kdstapeg' => 22, 'nmstapeg' => 'GAJI DISETOP SEMENTARA', 'created_at' => now(), 'updated_at' => now()],
            ['kdstapeg' => 23, 'nmstapeg' => 'PEGAWAI PENSIUN', 'created_at' => now(), 'updated_at' => now()],
            ['kdstapeg' => 24, 'nmstapeg' => 'PEGAWAI KELUAR', 'created_at' => now(), 'updated_at' => now()],
            ['kdstapeg' => 27, 'nmstapeg' => 'PEGAWAI MENINGGAL', 'created_at' => now(), 'updated_at' => now()],
            ['kdstapeg' => 28, 'nmstapeg' => 'PEGAWAI PINDAH', 'created_at' => now(), 'updated_at' => now()],
            ['kdstapeg' => 30, 'nmstapeg' => 'PEGAWAI PENSIUN, MELANJUTKAN IURAN THT', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ref_stapeg');
    }
};
