<?php
/**
 * Test Laravel database connection
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $pdo = DB::connection()->getPdo();
    echo "✅ Database connected successfully!\n\n";

    // Test query to show tables
    echo "=== TABLES IN DATABASE ===\n";
    $tables = DB::select('SHOW TABLES');
    foreach ($tables as $table) {
        $tableName = array_values((array) $table)[0];
        echo "  - $tableName\n";
    }

    echo "\n=== SAMPLE DATA FROM pegawai_pw ===\n";
    $employees = DB::table('pegawai_pw')->limit(5)->get();
    echo "Found " . count($employees) . " sample employees\n";
    foreach ($employees as $emp) {
        echo "  - ID: {$emp->id_pegawai}, NIP: {$emp->nip}, Name: {$emp->nama}\n";
    }

} catch (\Exception $e) {
    echo "❌ Database connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
