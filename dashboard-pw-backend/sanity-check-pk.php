<?php
// Check other tables for PK
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = ['pegawai_pw', 'skpd', 'tb_payment'];
foreach ($tables as $table) {
    echo "=== Table structure: $table ===\n";
    try {
        $results = DB::select("SHOW COLUMNS FROM $table");
        foreach ($results as $row) {
            if ($row->Key === 'PRI')
                echo "✅ Found PK: {$row->Field}\n";
        }
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
