<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('skpd_mapping', function (Blueprint $table) {
            $table->id();
            $table->string('source_name')->comment('Nama SKPD dari file Excel (gaji_pns/gaji_pppk)');
            $table->unsignedInteger('skpd_id')->comment('FK ke skpd.id_skpd');
            $table->enum('type', ['pns', 'pppk', 'all'])->default('all')->comment('Jenis data: pns, pppk, atau all');
            $table->timestamps();

            $table->unique(['source_name', 'type']);
            $table->index('skpd_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skpd_mapping');
    }
};
