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
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_id');
            $table->string('type')->comment('SK_PENGANGKATAN, SK_PEMBERHENTIAN, etc');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('status_at_upload')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Handle relation to pegawai_pw (which has signed int id in local DB)
            $table->foreign('employee_id')->references('id')->on('pegawai_pw')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};
