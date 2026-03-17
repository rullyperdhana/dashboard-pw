<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use Carbon\Carbon;

try {
    echo "Testing predictPW logic...\n";
    
    echo "1. Getting last 3 months from tb_payment...\n";
    $last3Months = DB::table('tb_payment')
        ->select('year', 'month')
        ->distinct()
        ->orderBy('year', 'desc')
        ->orderBy('month', 'desc')
        ->limit(3)
        ->get();
    echo "Found: " . $last3Months->count() . " months.\n";

    if ($last3Months->isNotEmpty()) {
        foreach ($last3Months as $p) {
            $total = DB::table('tb_payment_detail')
                ->join('tb_payment', 'tb_payment_detail.payment_id', '=', 'tb_payment.id')
                ->where('tb_payment.month', $p->month)
                ->where('tb_payment.year', $p->year)
                ->sum('tb_payment_detail.total_amoun');
            echo "Total for $p->month/$p->year: $total\n";
        }
    }

    echo "2. Getting retiring employees from pegawai_pw joined with skpd...\n";
    $retiringEmployees = DB::table('pegawai_pw')
        ->leftJoin('skpd', 'pegawai_pw.idskpd', '=', 'skpd.id_skpd')
        ->where('pegawai_pw.status', 'Aktif')
        ->whereNotNull('pegawai_pw.tgl_lahir')
        ->select('pegawai_pw.*', 'skpd.nama_skpd as skpd_name')
        ->get();
    echo "Found: " . $retiringEmployees->count() . " active employees with tgl_lahir.\n";

    echo "3. Testing KGB loop...\n";
    $kgbEmployees = Employee::active()
        ->whereNotNull('tmt_golru')
        ->get();
    echo "Found: " . $kgbEmployees->count() . " active employees for KGB.\n";

    echo "SUCCESS: Logic seems fine for these basic queries.\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
}
