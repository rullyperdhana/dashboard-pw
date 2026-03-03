<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['gaji_pns', 'gaji_pppk'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                if (!Schema::hasColumn($table->getTable(), 'kdsatker')) {
                    $table->string('kdsatker', 20)->nullable()->after('kdskpd');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['gaji_pns', 'gaji_pppk'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                if (Schema::hasColumn($table->getTable(), 'kdsatker')) {
                    $table->dropColumn('kdsatker');
                }
            });
        }
    }
};
