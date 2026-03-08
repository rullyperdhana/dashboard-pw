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
        if (!Schema::hasColumn('sp2d_realizations', 'tanggal_cair')) {
            Schema::table('sp2d_realizations', function (Blueprint $table) {
                $table->date('tanggal_cair')->nullable()->after('tanggal_sp2d')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sp2d_realizations', function (Blueprint $table) {
            $table->dropColumn('tanggal_cair');
        });
    }
};
