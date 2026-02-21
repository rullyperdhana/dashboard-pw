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
        Schema::table('gaji_pns', function (Blueprint $table) {
            $table->enum('jenis_gaji', ['Induk', 'Susulan', 'Kekurangan', 'Terusan'])->default('Induk')->after('tahun');
        });

        Schema::table('gaji_pppk', function (Blueprint $table) {
            $table->enum('jenis_gaji', ['Induk', 'Susulan', 'Kekurangan', 'Terusan'])->default('Induk')->after('tahun');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gaji_pns', function (Blueprint $table) {
            $table->dropColumn('jenis_gaji');
        });

        Schema::table('gaji_pppk', function (Blueprint $table) {
            $table->dropColumn('jenis_gaji');
        });
    }
};
