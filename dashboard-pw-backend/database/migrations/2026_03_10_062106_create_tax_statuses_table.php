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
        Schema::create('tax_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('nip')->index();
            $table->string('nama')->nullable(); // Cached name for easier search/display
            $table->enum('employee_type', ['pns', 'pppk']);
            $table->string('tax_status', 10); // e.g. K/0, K/1, TK/0
            $table->year('year');
            $table->boolean('is_manual')->default(false);
            $table->timestamps();

            $table->unique(['nip', 'year'], 'nip_year_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_statuses');
    }
};
