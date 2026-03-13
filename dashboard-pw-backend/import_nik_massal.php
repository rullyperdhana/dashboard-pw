<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$csvFile = 'data_nik.csv';

if (!file_exists($csvFile)) {
    die("Error: File '{$csvFile}' tidak ditemukan di folder backend.\nPastikan Anda sudah mengupload file CSV dengan nama tersebut.\n");
}

echo "Memulai proses import NIK massal...\n";

$file = fopen($csvFile, 'r');
$header = fgetcsv($file); // Baca header: nip,nik

$total = 0;
$updated = 0;
$notFound = 0;

while (($row = fgetcsv($file)) !== false) {
    $nip = ltrim(trim($row[0]), "'");
    $nik = ltrim(trim($row[1]), "'");
    
    if (empty($nip)) continue;
    
    $total++;
    
    $exists = DB::table('master_pegawai')->where('nip', $nip)->exists();
    
    if ($exists) {
        DB::table('master_pegawai')
            ->where('nip', $nip)
            ->update(['noktp' => $nik]);
        $updated++;
        echo "OK: NIP {$nip} -> NIK {$nik}\n";
    } else {
        $notFound++;
        echo "SKIP: NIP {$nip} tidak ditemukan di database.\n";
    }
}

fclose($file);

echo "\n--- HASIL IMPORT ---\n";
echo "Total baris di CSV: {$total}\n";
echo "Berhasil update   : {$updated}\n";
echo "NIP tidak ketemu  : {$notFound}\n";
echo "--------------------\n";
