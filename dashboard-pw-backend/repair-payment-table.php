<?php
// Repair tb_payment table schema
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "=== Repairing tb_payment Table ===\n";

    // 1. Assign IDs to rows that have NULL IDs
    $count = DB::table('tb_payment')->whereNull('id')->count();
    if ($count > 0) {
        echo "Found $count records with NULL ID. Assigning IDs...\n";
        $maxId = DB::table('tb_payment')->max('id') ?? 100;
        $records = DB::table('tb_payment')->whereNull('id')->get();
        foreach ($records as $r) {
            $maxId++;
            // Try to find a unique combination for update if id is null
            DB::table('tb_payment')
                ->where('rka_id', $r->rka_id)
                ->where('month', $r->month)
                ->where('year', $r->year)
                ->whereNull('id')
                ->limit(1)
                ->update(['id' => $maxId]);
        }
    }

    // 2. Set ID column to NOT NULL
    echo "Setting 'id' to NOT NULL...\n";
    DB::statement("ALTER TABLE tb_payment MODIFY id INT NOT NULL");

    // 3. Add PRIMARY KEY
    echo "Adding PRIMARY KEY to 'id'...\n";
    try {
        DB::statement("ALTER TABLE tb_payment ADD PRIMARY KEY (id)");
        echo "✅ Primary Key added.\n";
    } catch (\Exception $e) {
        echo "ℹ️ Primary Key might already exist: " . $e->getMessage() . "\n";
    }

    // 4. Set AUTO_INCREMENT
    echo "Setting AUTO_INCREMENT to 'id'...\n";
    DB::statement("ALTER TABLE tb_payment MODIFY id INT NOT NULL AUTO_INCREMENT");
    echo "✅ Auto-increment set.\n";

} catch (\Exception $e) {
    echo "❌ Error during repair: " . $e->getMessage() . "\n";
}
