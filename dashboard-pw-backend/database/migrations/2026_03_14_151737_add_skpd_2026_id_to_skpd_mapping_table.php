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
        Schema::table('skpd_mapping', function (Blueprint $table) {
            $table->unsignedBigInteger('skpd_2026_id')->nullable()->after('skpd_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skpd_mapping', function (Blueprint $table) {
            $table->dropColumn('skpd_2026_id');
        });
    }
};
