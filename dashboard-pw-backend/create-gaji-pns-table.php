<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

echo "Creating gaji_pns table manually...\n";

if (Schema::hasTable('gaji_pns')) {
    echo "Table gaji_pns already exists.\n";
    // Optional: Drop it if you want to recreate?
    // Schema::drop('gaji_pns');
} else {
    Schema::create('gaji_pns', function (Blueprint $table) {
        $table->id();
        $table->string('nip', 50)->index();
        $table->string('nama');
        $table->string('golongan', 10)->nullable();
        $table->string('jabatan')->nullable();
        $table->string('skpd')->nullable(); // Unit Kerja

        // Pendapatan
        $table->decimal('gaji_pokok', 15, 2)->default(0);
        $table->decimal('tunj_istri', 15, 2)->default(0);
        $table->decimal('tunj_anak', 15, 2)->default(0);
        $table->decimal('tunj_sh', 15, 2)->default(0); // Suami/Istri/Anak total
        $table->decimal('tunj_fungsional', 15, 2)->default(0);
        $table->decimal('tunj_struktural', 15, 2)->default(0);
        $table->decimal('tunj_umum', 15, 2)->default(0);
        $table->decimal('tunj_beras', 15, 2)->default(0);
        $table->decimal('tunj_pph', 15, 2)->default(0);
        $table->decimal('pembulatan', 15, 2)->default(0);
        $table->decimal('kotor', 15, 2)->default(0); // Gross Salary

        // Potongan
        $table->decimal('pot_iwp', 15, 2)->default(0); // 10%
        $table->decimal('pot_taspen', 15, 2)->default(0);
        $table->decimal('pot_bpjs', 15, 2)->default(0); // BPJS Kes
        $table->decimal('pot_pph', 15, 2)->default(0);
        $table->decimal('pot_lain', 15, 2)->default(0);
        $table->decimal('total_potongan', 15, 2)->default(0);

        // Net
        $table->decimal('bersih', 15, 2)->default(0);

        // Metadata
        $table->integer('bulan');
        $table->integer('tahun');
        $table->timestamps();

        // Index for faster queries
        $table->index(['bulan', 'tahun']);
        $table->index('skpd');
    });
    echo "Table gaji_pns created successfully.\n";
}
