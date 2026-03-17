<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

function dumpTable($name) {
    echo "\n--- Table: $name ---\n";
    $row = DB::table($name)->first();
    if ($row) {
        print_r(array_keys((array)$row));
    } else {
        echo "Table empty or not found.\n";
    }
}

dumpTable('pegawai_pw');
dumpTable('master_pegawai');
dumpTable('satkers');
dumpTable('tb_payment');
