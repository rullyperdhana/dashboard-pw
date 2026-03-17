<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tpp_discrepancy_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('month');
            $table->integer('year');
            $table->string('employee_type'); // pns, pppk
            $table->string('nip', 50)->index();
            $table->string('nama')->nullable();
            $table->string('skpd')->nullable();
            $table->string('reason')->default('Missing in TPP Excel');
            $table->timestamps();

            $table->index(['month', 'year', 'employee_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tpp_discrepancy_logs');
    }
};
