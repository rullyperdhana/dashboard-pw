<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Memulai pembaruan Jenis Kelamin (JK) berdasarkan NIP...\n";

$employees = DB::table('pegawai_pw')
    ->whereNotNull('nip')
    ->whereRaw('LENGTH(nip) >= 4')
    ->get();

$total = count($employees);
$updated = 0;
$skipped = 0;

foreach ($employees as $employee) {
    $nip = trim($employee->nip);
    
    // Karakter ke-4 dari belakang
    $genderCode = substr($nip, -4, 1);
    
    $newJk = '';
    if ($genderCode === '1') {
        $newJk = 'LAKI-LAKI';
    } elseif ($genderCode === '2') {
        $newJk = 'PEREMPUAN';
    }
    
    if ($newJk !== '') {
        DB::table('pegawai_pw')
            ->where('id', $employee->id)
            ->update(['jk' => $newJk]);
        $updated++;
        echo "Updated NIP: {$nip} -> {$newJk}\n";
    } else {
        $skipped++;
        echo "Skipped NIP: {$nip} (Gender code invalid: '{$genderCode}')\n";
    }
}

echo "\nSelesai!\n";
echo "Total diproses: {$total}\n";
echo "Berhasil diupdate: {$updated}\n";
echo "Gagal/Lewati: {$skipped}\n";
