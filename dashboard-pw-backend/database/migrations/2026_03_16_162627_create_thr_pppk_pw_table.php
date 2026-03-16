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
        Schema::create('tb_thr_pppk_pw', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->nullable()->index();
            $table->integer('year');
            $table->integer('month');
            $table->string('nip')->nullable()->index();
            $table->string('nama')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('skpd_name')->nullable();
            $table->string('kode_sub_giat')->nullable();
            $table->string('nama_sub_giat')->nullable();
            $table->decimal('gapok_basis', 15, 2)->default(0);
            $table->integer('n_months')->default(0);
            $table->decimal('thr_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();

            $table->unique(['year', 'month', 'nip', 'nama_sub_giat'], 'thr_pppk_pw_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tb_thr_pppk_pw');
    }
};
