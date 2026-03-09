<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Sp2dRealization;

echo "Updating existing SP2D data labels to catch PPPK-PW...\n";

$records = Sp2dRealization::where('jenis_data', 'LIKE', 'PPPK%')
    ->where('jenis_data', 'NOT LIKE', 'PPPK-PW%')
    ->get();

$updated = 0;
foreach ($records as $record) {
    $ket = strtoupper($record->keterangan);

    if (str_contains($ket, 'PPPK PW') || str_contains($ket, 'P3K PW') || str_contains($ket, 'PPPK PARUH WAKTU') || str_contains($ket, 'P3K PARUH WAKTU')) {
        $oldType = $record->jenis_data;
        $newType = 'PPPK-PW-INDUK';

        if (str_contains($ket, 'SUSULAN'))
            $newType = 'PPPK-PW-SUSULAN';
        if (str_contains($ket, 'KEKURANGAN'))
            $newType = 'PPPK-PW-KEKURANGAN';

        $record->jenis_data = $newType;
        $record->save();
        $updated++;
        echo "Updated [{$record->nomor_sp2d}]: {$oldType} -> {$newType}\n";
    }
}

echo "\nTotal records updated: {$updated}\n";
