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
        Schema::create('standalone_tpp', function (Blueprint $table) {
            $table->id();
            $table->integer('month');
            $table->integer('year');
            $table->string('employee_type'); // pns, pppk
            $table->string('nip');
            $table->string('nama')->nullable();
            $table->decimal('nilai', 15, 2);
            $table->string('jenis_gaji')->default('Induk');
            $table->unsignedBigInteger('skpd_id')->nullable();
            $table->timestamps();

            $table->index(['month', 'year', 'employee_type']);
            $table->index('nip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standalone_tpp');
    }
};
