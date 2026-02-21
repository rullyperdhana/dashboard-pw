<?php
// Repair pegawai_pw table schema
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "=== Repairing pegawai_pw Table ===\n";

    // 1. Assign IDs to rows that have NULL IDs if any
    $count = DB::table('pegawai_pw')->whereNull('id')->count();
    if ($count > 0) {
        echo "Found $count users with NULL ID. Assigning IDs...\n";
        $maxId = DB::table('pegawai_pw')->max('id') ?? 0;
        $users = DB::table('pegawai_pw')->whereNull('id')->get();
        foreach ($users as $u) {
            $maxId++;
            // We use nip as a fallback unique identifier for update
            DB::table('pegawai_pw')->where('nip', $u->nip)->whereNull('id')->limit(1)->update(['id' => $maxId]);
        }
    }

    // 2. Set ID column to NOT NULL
    echo "Setting 'id' to NOT NULL...\n";
    DB::statement("ALTER TABLE pegawai_pw MODIFY id INT NOT NULL");

    // 3. Add PRIMARY KEY if not already exists
    echo "Adding PRIMARY KEY to 'id'...\n";
    try {
        DB::statement("ALTER TABLE pegawai_pw ADD PRIMARY KEY (id)");
        echo "✅ Primary Key added.\n";
    } catch (\Exception $e) {
        echo "ℹ️ Primary Key might already exist: " . $e->getMessage() . "\n";
    }

    // 4. Set AUTO_INCREMENT
    echo "Setting AUTO_INCREMENT to 'id'...\n";
    DB::statement("ALTER TABLE pegawai_pw MODIFY id INT NOT NULL AUTO_INCREMENT");
    echo "✅ Auto-increment set.\n";

} catch (\Exception $e) {
    echo "❌ Error during repair: " . $e->getMessage() . "\n";
}
