<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pppk_pw_jabatan_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kelompok');
            $table->string('kode_rekening');
            $table->string('keyword');
            $table->integer('order_weight')->default(0);
            $table->timestamps();
        });

        // Seed initial data
        $initialData = [
            ['nama_kelompok' => 'Guru', 'kode_rekening' => '5.1.02.03.01.0083', 'keyword' => 'GURU'],
            ['nama_kelompok' => 'Tenaga Kependidikan', 'kode_rekening' => '5.1.02.03.01.0084', 'keyword' => 'TENAGA KEPENDIDIKAN'],
            ['nama_kelompok' => 'Tenaga Kesehatan', 'kode_rekening' => '5.1.02.03.01.0085', 'keyword' => 'KESEHATAN'],
            ['nama_kelompok' => 'Teknis', 'kode_rekening' => '5.1.02.03.01.0086', 'keyword' => 'TEKNIS'],
            ['nama_kelompok' => 'Pengelola Umum Operasional', 'kode_rekening' => '5.1.02.03.01.0087', 'keyword' => 'PENGELOLA UMUM OPERASIONAL'],
            ['nama_kelompok' => 'Operator Layanan Operasional', 'kode_rekening' => '5.1.02.03.01.0088', 'keyword' => 'OPERATOR LAYANAN OPERASIONAL'],
            ['nama_kelompok' => 'Pengelola Layanan Operasional', 'kode_rekening' => '5.1.02.03.01.0089', 'keyword' => 'PENGELOLA LAYANAN OPERASIONAL'],
            ['nama_kelompok' => 'Penata Layanan Operasional', 'kode_rekening' => '5.1.02.03.01.0090', 'keyword' => 'PENATA LAYANAN OPERASIONAL'],
        ];

        foreach ($initialData as $data) {
            DB::table('pppk_pw_jabatan_mappings')->insert(array_merge($data, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pppk_pw_jabatan_mappings');
    }
};
