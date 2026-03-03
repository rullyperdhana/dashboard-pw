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
        Schema::table('skpd_mapping', function (Blueprint $table) {
            $table->string('source_code')->nullable()->after('source_name')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skpd_mapping', function (Blueprint $table) {
            $table->dropColumn('source_code');
        });
    }
};
