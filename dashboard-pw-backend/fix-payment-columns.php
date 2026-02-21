<?php
// Fix column names in tb_payment and tb_payment_detail
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "=== Renaming Columns to match Code Preferences ===\n";

    // 1. tb_payment
    echo "Updating tb_payment...\n";
    // Check if total_amount exists and total_amoun does not
    $cols = DB::select('SHOW COLUMNS FROM tb_payment');
    $hasAmount = false;
    $hasAmoun = false;
    $hasEmployees = false;
    $hasEmplo = false;
    $hasDate = false;
    $hasDat = false;

    foreach ($cols as $c) {
        if ($c->Field == 'total_amount')
            $hasAmount = true;
        if ($c->Field == 'total_amoun')
            $hasAmoun = true;
        if ($c->Field == 'total_employees')
            $hasEmployees = true;
        if ($c->Field == 'total_emplo')
            $hasEmplo = true;
        if ($c->Field == 'payment_date')
            $hasDate = true;
        if ($c->Field == 'payment_dat')
            $hasDat = true;
    }

    if ($hasAmount && !$hasAmoun) {
        DB::statement('ALTER TABLE tb_payment CHANGE total_amount total_amoun DECIMAL(15,2) NOT NULL DEFAULT 0');
        echo "  - Renamed total_amount -> total_amoun\n";
    }
    if ($hasEmployees && !$hasEmplo) {
        DB::statement('ALTER TABLE tb_payment CHANGE total_employees total_emplo INT NOT NULL DEFAULT 0');
        echo "  - Renamed total_employees -> total_emplo\n";
    }
    if ($hasDate && !$hasDat) {
        DB::statement('ALTER TABLE tb_payment CHANGE payment_date payment_dat DATE NULL');
        echo "  - Renamed payment_date -> payment_dat\n";
    }

    // 2. tb_payment_detail
    echo "Updating tb_payment_detail...\n";
    $colsDetail = DB::select('SHOW COLUMNS FROM tb_payment_detail');
    $hasAmountDetail = false;
    $hasAmounDetail = false;

    foreach ($colsDetail as $c) {
        if ($c->Field == 'total_amount')
            $hasAmountDetail = true;
        if ($c->Field == 'total_amoun')
            $hasAmounDetail = true;
    }

    if ($hasAmountDetail && !$hasAmounDetail) {
        DB::statement('ALTER TABLE tb_payment_detail CHANGE total_amount total_amoun DECIMAL(15,2) NOT NULL DEFAULT 0');
        echo "  - Renamed total_amount -> total_amoun\n";
    }

    echo "✅ Success! Column names are now synced with code.\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
