<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// List of migrations that already exist in the database (tables exist)
$existingMigrations = [
    '0001_01_01_000000_create_users_table',
    '0001_01_01_000001_create_cache_table',
    '0001_01_01_000002_create_jobs_table',
    '2026_01_31_101508_create_gaji_pns_table',
    '2026_02_01_043055_create_gaji_pppk_table',
];

foreach ($existingMigrations as $migration) {
    if (DB::table('migrations')->where('migration', $migration)->doesntExist()) {
        DB::table('migrations')->insert([
            'migration' => $migration,
            'batch' => 1
        ]);
        echo "Inserted migration record: $migration\n";
    } else {
        echo "Migration record already exists: $migration\n";
    }
}
