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
        if (!Schema::hasTable('tb_payment_detail')) {
            Schema::create('tb_payment_detail', function (Blueprint $table) {
                $table->integer('id')->autoIncrement();
                $table->integer('payment_id');
                $table->integer('employee_id');
                $table->decimal('gapok', 15, 2)->nullable();
                $table->decimal('tunjangan', 15, 2)->nullable();
                $table->decimal('pajak', 15, 2)->nullable();
                $table->decimal('iwp', 15, 2)->nullable();
                $table->decimal('netto', 15, 2)->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tb_payment_detail');
    }
};
