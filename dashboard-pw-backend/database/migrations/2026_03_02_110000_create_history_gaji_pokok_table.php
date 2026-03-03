<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('history_gaji_pokok', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 18);
            $table->string('nama', 50)->nullable();
            $table->integer('gapok')->nullable();
            $table->string('tmt_berlaku', 10)->nullable(); // YYYY-MM-DD
            $table->string('no_sk', 100)->nullable();
            $table->string('tmt_sk', 10)->nullable();      // YYYY-MM-DD
            $table->integer('bulan')->nullable();
            $table->integer('tahun')->nullable();
            $table->string('upload_batch', 30)->nullable();
            $table->timestamps();

            $table->index('nip');
            $table->index(['bulan', 'tahun']);
            $table->index('upload_batch');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('history_gaji_pokok');
    }
};
