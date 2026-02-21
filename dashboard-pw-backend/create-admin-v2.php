<?php
// Script to create a new admin user
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$username = 'admin_baru';
$password = 'password123';
$email = 'admin@example.com';

echo "Membuat user baru: $username...\n";

try {
    // Check if exists
    $existing = \App\Models\User::where('username', $username)->first();
    if ($existing) {
        $existing->delete();
        echo "User lama dengan username sama dihapus.\n";
    }

    $user = \App\Models\User::create([
        'username' => $username,
        'password' => \Illuminate\Support\Facades\Hash::make($password),
        'email' => $email,
        'role' => 'superadmin',
        'status' => 'approved',
        'institution' => null,
    ]);

    echo "✅ User berhasil dibuat!\n";
    echo "--------------------------\n";
    echo "Username: $username\n";
    echo "Password: $password\n";
    echo "--------------------------\n";

} catch (\Exception $e) {
    echo "❌ Gagal membuat user: " . $e->getMessage() . "\n";
}
