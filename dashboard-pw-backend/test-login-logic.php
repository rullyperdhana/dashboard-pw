<?php
// Test login logic manually
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$username = 'superuser'; // Using one from debug output
$password = 'password'; // Trying a common default, or the user might have set it

echo "Attempting to find user: $username\n";
$user = \App\Models\User::where('username', $username)->first();

if ($user) {
    echo "User found: " . $user->username . "\n";
    echo "Status: " . $user->status . "\n";
    echo "Institution: " . $user->institution . "\n";

    // Test a dummy password just to see if logic flows
    echo "Testing password check (this will likely fail unless 'password' is correct)...\n";
    if (\Illuminate\Support\Facades\Hash::check($password, $user->password)) {
        echo "✅ Password correct!\n";
    } else {
        echo "❌ Password incorrect.\n";
    }
} else {
    echo "❌ User not found.\n";
}
