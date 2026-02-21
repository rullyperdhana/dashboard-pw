<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('skpd')) {
            Schema::create('skpd', function (Blueprint $table) {
                $table->id('id_skpd');
                $table->string('kode_skpd')->nullable();
                $table->string('nama_skpd');
                $table->boolean('is_skpd')->default(true);
            });

            // Seed default SKPD
            DB::table('skpd')->insert([
                'id_skpd' => 1,
                'kode_skpd' => '001',
                'nama_skpd' => 'SKPD Pusat',
                'is_skpd' => true,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('skpd');
    }
};
