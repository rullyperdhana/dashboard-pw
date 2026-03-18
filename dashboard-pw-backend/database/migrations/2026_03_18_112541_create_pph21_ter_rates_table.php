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
        Schema::create('pph21_ter_rates', function (Blueprint $table) {
            $table->id();
            $table->string('category', 1); // A, B, C
            $table->decimal('min_gross', 15, 2);
            $table->decimal('max_gross', 15, 2)->nullable();
            $table->decimal('rate', 5, 2); // percentage
            $table->timestamps();
            
            $table->index(['category', 'min_gross', 'max_gross']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pph21_ter_rates');
    }
};
