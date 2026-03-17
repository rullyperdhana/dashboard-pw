<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

try {
    $currentDate = Carbon::now()->format('Y-m-d');
    $targetDate = Carbon::now()->addYear()->format('Y-m-d');

    echo "Testing optimized predictPW logic...\n";
    
    echo "1. Getting retiring employees using DATE_ADD...\n";
    $retiringEmployees = DB::table('pegawai_pw')
        ->leftJoin('skpd', 'pegawai_pw.idskpd', '=', 'skpd.id_skpd')
        ->where('pegawai_pw.status', 'Aktif')
        ->whereNotNull('pegawai_pw.tgl_lahir')
        ->whereRaw("DATE_ADD(pegawai_pw.tgl_lahir, INTERVAL 58 YEAR) BETWEEN ? AND ?", [$currentDate, $targetDate])
        ->select('pegawai_pw.*', 'skpd.nama_skpd as skpd_name')
        ->get();
    echo "Found: " . $retiringEmployees->count() . " retiring employees.\n";

    echo "2. Testing KGB simulation using MOD and TIMESTAMPDIFF...\n";
    $kgbEmployeesCount = DB::table('pegawai_pw')
        ->where('status', 'Aktif')
        ->whereNotNull('tmt_golru')
        ->whereRaw("MOD(TIMESTAMPDIFF(YEAR, tmt_golru, DATE_ADD(?, INTERVAL 1 YEAR)), 2) = 0", [$currentDate])
        ->count();
    echo "KGB Count: $kgbEmployeesCount\n";

    echo "Testing optimized predictMasterPegawai (PNS) logic...\n";
    $kgbPns = DB::table('master_pegawai')
        ->whereIn('kdstapeg', [1, 2, 3, 4, 5, 11, 12])
        ->whereNotNull('tmtkgbyad')
        ->where('kd_jns_peg', '<', 3)
        ->whereBetween('tmtkgbyad', [$currentDate, $targetDate])
        ->count();
    echo "KGB PNS Count: $kgbPns\n";

    echo "SUCCESS: Logic seems correct if this runs.\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "SQL: " . ($e instanceof \Illuminate\Database\QueryException ? $e->getSql() : 'N/A') . "\n";
}
