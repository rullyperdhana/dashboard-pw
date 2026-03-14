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
        // Drop table if it partially exists from a failed migration attempt on production
        Schema::dropIfExists('export_logs');

        Schema::create('export_logs', function (Blueprint $table) {
            $table->id();
            // Using bigInteger without constrained foreign key to avoid type mismatch on older production databases
            $table->unsignedBigInteger('user_id')->index();
            $table->string('report_name');
            $table->string('action'); // e.g., 'Cetak PDF', 'Ekspor Excel'
            $table->string('description')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_logs');
    }
};
