<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pegawai_pw', function (Blueprint $table) {
            $table->string('sumber_dana', 20)->default('APBD')->after('keterangan');
            $table->index('sumber_dana');
        });
    }

    public function down(): void
    {
        Schema::table('pegawai_pw', function (Blueprint $table) {
            $table->dropIndex(['sumber_dana']);
            $table->dropColumn('sumber_dana');
        });
    }
};
