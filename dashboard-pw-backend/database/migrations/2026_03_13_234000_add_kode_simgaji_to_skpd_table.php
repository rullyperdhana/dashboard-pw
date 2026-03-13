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
        Schema::table('skpd', function (Blueprint $row) {
            $row->string('kode_simgaji', 20)->nullable()->after('kode_skpd')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skpd', function (Blueprint $row) {
            $row->dropColumn('kode_simgaji');
        });
    }
};
