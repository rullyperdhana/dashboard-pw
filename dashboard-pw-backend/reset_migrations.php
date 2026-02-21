<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

Schema::disableForeignKeyConstraints();
$tables = DB::select('SHOW TABLES');
foreach ($tables as $table) {
    if ($table->Tables_in_dashboard === 'migrations') {
        Schema::drop('migrations');
        echo "Migrations table dropped.\n";
    }
}
Schema::enableForeignKeyConstraints();
