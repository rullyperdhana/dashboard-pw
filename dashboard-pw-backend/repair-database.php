<?php
// Repair users table schema
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "=== Repairing Users Table ===\n";

    // 1. Assign IDs to rows that have NULL IDs
    $users = DB::table('users')->whereNull('id')->get();
    if ($users->count() > 0) {
        echo "Found " . $users->count() . " users with NULL ID. Assigning IDs...\n";
        $maxId = DB::table('users')->max('id') ?? 100;
        foreach ($users as $u) {
            $maxId++;
            DB::table('users')->where('username', $u->username)->update(['id' => $maxId]);
            echo "Assigned ID $maxId to user: {$u->username}\n";
        }
    }

    // 2. Set ID column to NOT NULL
    echo "Setting 'id' to NOT NULL...\n";
    DB::statement("ALTER TABLE users MODIFY id INT NOT NULL");

    // 3. Add PRIMARY KEY if not already exists
    echo "Adding PRIMARY KEY to 'id'...\n";
    try {
        DB::statement("ALTER TABLE users ADD PRIMARY KEY (id)");
        echo "✅ Primary Key added.\n";
    } catch (\Exception $e) {
        echo "ℹ️ Primary Key might already exist or error: " . $e->getMessage() . "\n";
    }

    // 4. Set AUTO_INCREMENT
    echo "Setting AUTO_INCREMENT to 'id'...\n";
    DB::statement("ALTER TABLE users MODIFY id INT NOT NULL AUTO_INCREMENT");
    echo "✅ Auto-increment set.\n";

    echo "\n=== Verifying Final Structure ===\n";
    $results = DB::select("SHOW COLUMNS FROM users");
    foreach ($results as $row) {
        echo "Field: {$row->Field} | Key: {$row->Key} | Extra: {$row->Extra}\n";
    }

} catch (\Exception $e) {
    echo "❌ Error during repair: " . $e->getMessage() . "\n";
}
