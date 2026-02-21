<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

try {
    $username = 'admin_baru';
    $password = 'password123'; // Temporary secure default

    // Check if exists (though we know it doesn't)
    $exists = DB::table('users')->where('username', $username)->exists();

    if ($exists) {
        // Update
        DB::table('users')->where('username', $username)->update([
            'password' => Hash::make($password),
            'role' => 'admin',
            'institution' => null, // Global admin
            'updated_at' => now()
        ]);
        echo "User '$username' updated with new password '$password'.\n";
    } else {
        // Insert
        DB::table('users')->insert([
            'username' => $username,
            'email' => 'admin_baru@example.com',
            'password' => Hash::make($password),
            'role' => 'admin',
            'institution' => null,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        echo "User '$username' created with password '$password'.\n";
    }

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
