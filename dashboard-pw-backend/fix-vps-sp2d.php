<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

echo "--- SP2D Database Repair Script ---\n";

// 1. Periksa Tabel sp2d_realizations
if (!Schema::hasTable('sp2d_realizations')) {
    echo "Membuat tabel sp2d_realizations...\n";
    Schema::create('sp2d_realizations', function (Blueprint $table) {
        $table->id();
        $table->string('nomor_sp2d')->index();
        $table->date('tanggal_sp2d')->index();
        $table->string('nama_skpd_sipd')->index();
        $table->unsignedBigInteger('skpd_id')->nullable()->index();
        $table->text('keterangan')->nullable();
        $table->string('jenis_data')->index();
        $table->decimal('brutto', 20, 2)->default(0);
        $table->decimal('potongan', 20, 2)->default(0);
        $table->decimal('netto', 20, 2)->default(0);
        $table->integer('bulan');
        $table->integer('tahun');
        $table->timestamps();
        $table->foreign('skpd_id')->references('id_skpd')->on('skpd')->onDelete('set null');
    });
} else {
    echo "Tabel sp2d_realizations sudah ada.\n";
}

// 2. Periksa Kolom tanggal_cair
if (!Schema::hasColumn('sp2d_realizations', 'tanggal_cair')) {
    echo "Menambahkan kolom tanggal_cair...\n";
    Schema::table('sp2d_realizations', function (Blueprint $table) {
        $table->date('tanggal_cair')->nullable()->after('tanggal_sp2d')->index();
    });
} else {
    echo "Kolom tanggal_cair sudah ada.\n";
}

// 3. Daftarkan migrasi di tabel 'migrations' agar Laravel tidak mencoba create ulang
$migrations = [
    '2026_03_08_072217_create_sp2d_realizations_table',
    '2026_03_08_073000_add_tanggal_cair_to_sp2d_realizations_table'
];

foreach ($migrations as $m) {
    $exists = DB::table('migrations')->where('migration', $m)->exists();
    if (!$exists) {
        echo "Mendaftarkan migrasi: $m...\n";
        DB::table('migrations')->insert([
            'migration' => $m,
            'batch' => (DB::table('migrations')->max('batch') ?? 0) + 1
        ]);
    } else {
        echo "Migrasi $m sudah terdaftar.\n";
    }
}

echo "--- SELESAI: Database SIPD sudah siap digunakan ---\n";
