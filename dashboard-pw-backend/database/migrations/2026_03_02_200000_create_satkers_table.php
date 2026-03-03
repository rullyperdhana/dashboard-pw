<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('satkers', function (Blueprint $table) {
            $table->id();
            $table->string('kdskpd')->index();
            $table->string('nmskpd')->nullable();
            $table->string('kdsatker')->index();
            $table->string('nmsatker')->nullable();
            $table->timestamps();

            // Unique constraint to prevent duplicate mappings
            $table->unique(['kdskpd', 'kdsatker']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('satkers');
    }
};
