<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Creating sp2d_realizations table...\n";

try {
    if (!Schema::hasTable('sp2d_realizations')) {
        Schema::create('sp2d_realizations', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_sp2d')->index();
            $table->date('tanggal_sp2d')->index();
            $table->string('nama_skpd_sipd')->index();
            $table->unsignedBigInteger('skpd_id')->nullable()->index();
            $table->text('keterangan')->nullable();
            $table->string('jenis_data')->index(); // PNS, PPPK, TPP
            $table->decimal('brutto', 20, 2)->default(0);
            $table->decimal('potongan', 20, 2)->default(0);
            $table->decimal('netto', 20, 2)->default(0);
            $table->integer('bulan');
            $table->integer('tahun');
            $table->timestamps();

            $table->foreign('skpd_id')->references('id_skpd')->on('skpd')->onDelete('set null');
        });
        echo "✅ Table created successfully!\n";
    } else {
        echo "ℹ️ Table 'sp2d_realizations' already exists.\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
