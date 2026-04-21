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
        Schema::create('api_field_configs', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint'); // listinstansi, listpegawai, listgaji
            $table->string('field_key'); // output key in JSON
            $table->string('field_label'); // Display name in UI
            $table->string('source_table')->nullable(); // info for user
            $table->boolean('is_enabled')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['endpoint', 'field_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_field_configs');
    }
};
