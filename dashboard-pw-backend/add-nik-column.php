<?php
// Add missing columns to pegawai_pw table
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "=== Adding missing columns to pegawai_pw ===\n";

    // Check if 'nik' exists
    $columns = DB::select("SHOW COLUMNS FROM pegawai_pw LIKE 'nik'");
    if (empty($columns)) {
        echo "Adding 'nik' column...\n";
        DB::statement("ALTER TABLE pegawai_pw ADD COLUMN nik VARCHAR(255) NULL AFTER nip");
        echo "✅ 'nik' column added.\n";
    } else {
        echo "ℹ️ 'nik' column already exists.\n";
    }

    echo "\n=== Verifying Final Structure ===\n";
    $results = DB::select("SHOW COLUMNS FROM pegawai_pw");
    foreach ($results as $row) {
        if ($row->Field === 'nik') {
            echo "Field: {$row->Field} | Type: {$row->Type} | Null: {$row->Null}\n";
        }
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
