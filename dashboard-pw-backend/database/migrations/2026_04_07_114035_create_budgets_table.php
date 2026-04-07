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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->string('skpd_id')->nullable();
            $table->integer('tahun');
            $table->string('tipe_anggaran', 50)->default('MURNI');
            $table->decimal('nominal', 20, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
            
            $table->index(['skpd_id', 'tahun']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
