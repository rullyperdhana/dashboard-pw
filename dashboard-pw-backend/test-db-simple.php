<?php
/**
 * Simple database connection test without Laravel's migration system
 */

$host = '153.92.15.2';
$port = '3306';
$dbname = 'u921668730_pwpayroll';
$username = 'u921668730_pwpayroll';
$password = 'Sp2dC4!r';

echo "Testing database connection for Laravel...\n\n";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    echo "✅ Connected successfully!\n\n";

    // Test basic queries
    echo "=== Testing Basic Queries ===\n";

    // Count employees
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pegawai_pw");
    $result = $stmt->fetch();
    echo "Total PPPK Employees: {$result->total}\n";

    // Count users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $result = $stmt->fetch();
    echo "Total Users: {$result->total}\n";

    // Count SKPDs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM skpd");
    $result = $stmt->fetch();
    echo "Total SKPDs: {$result->total}\n";

    //Sample employee data
    echo "\n=== Sample Employee Data ===\n";
    $stmt = $pdo->query("SELECT id_pegawai, nip, nama, jabatan FROM pegawai_pw LIMIT 5");
    while ($row = $stmt->fetch()) {
        echo "  - {$row->nama} (NIP: {$row->nip}) - {$row->jabatan}\n";
    }

    echo "\n✅ All database queries working correctly!\n";
    echo "👍 You can proceed with Laravel development using this database.\n";

} catch (PDOException $e) {
    echo "❌ Connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
