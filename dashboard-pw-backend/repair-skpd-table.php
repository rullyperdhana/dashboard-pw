<?php
// Repair skpd table schema
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "=== Repairing skpd Table ===\n";

    // 1. Assign IDs to rows that have NULL IDs if any (though id_skpd is the PK here)
    // Actually id_skpd already has values from the user's import, but it's not a PK.

    // 2. Set id_skpd to NOT NULL
    echo "Setting 'id_skpd' to NOT NULL...\n";
    DB::statement("ALTER TABLE skpd MODIFY id_skpd INT NOT NULL");

    // 3. Add PRIMARY KEY to 'id_skpd'
    echo "Adding PRIMARY KEY to 'id_skpd'...\n";
    try {
        DB::statement("ALTER TABLE skpd ADD PRIMARY KEY (id_skpd)");
        echo "✅ Primary Key added.\n";
    } catch (\Exception $e) {
        echo "ℹ️ Primary Key might already exist: " . $e->getMessage() . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Error during repair: " . $e->getMessage() . "\n";
}
