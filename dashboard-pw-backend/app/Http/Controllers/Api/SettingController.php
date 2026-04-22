<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Exports\EstimationExport;
use App\Models\GajiPppk;
use App\Models\GajiPns;
use App\Models\PegawaiPw;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

use App\Services\PayrollEstimationService;

class SettingController extends Controller
{
    protected $payrollService;

    public function __construct(PayrollEstimationService $payrollService)
    {
        $this->payrollService = $payrollService;
    }
    public function index()
    {
        $settings = \Illuminate\Support\Facades\Cache::remember('app_settings', 3600, function () {
            return Setting::all()->keyBy('key');
        });

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required'
        ]);

        foreach ($validated['settings'] as $item) {
            $key = $item['key'];
            $value = $item['value'];
            $setting = Setting::where('key', $key)->first();
            
            if ($setting && $setting->value != $value) {
                // Log change for all payroll parameters
                \App\Models\AuditLog::log(
                    'update_payroll_parameter',
                    "Changed $key from '{$setting->value}' to '$value'",
                    [
                        'table_name' => 'settings',
                        'record_id' => $setting->id,
                        'old_values' => ['value' => $setting->value],
                        'new_values' => ['value' => $value]
                    ]
                );
                
                $setting->update(['value' => $value]);
            } elseif (!$setting) {
                Setting::create([
                    'key' => $key,
                    'value' => $value,
                    'group' => 'general'
                ]);
            }
        }
        
        \Illuminate\Support\Facades\Cache::forget('app_settings');

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
    }

    public function pppkEstimation(Request $request)
    {
        $month = $request->month;
        $year = $request->year;

        if (!$month || !$year) {
            $latest = $this->payrollService->getLatestPeriod(GajiPppk::class);
            if (!$latest) {
                return response()->json(['success' => false, 'message' => 'No PPPK data found']);
            }
            $month = $latest->bulan;
            $year = $latest->tahun;
        }

        $filters = ['jenis_gaji' => $request->jenis_gaji];
        $summary = $this->payrollService->getEstimationSummary(GajiPppk::class, $month, $year, $filters);
        $details = $this->payrollService->getEstimationDetails(GajiPppk::class, $month, $year, $filters, 'pppk');

        return response()->json([
            'success' => true,
            'data' => array_merge($summary, ['details' => $details])
        ]);
    }

    public function pppkPwEstimation(Request $request)
    {
        $summary = $this->payrollService->getPppkPwEstimationSummary();
        $details = $this->payrollService->getPppkPwEstimationSkpdDetails();

        return response()->json([
            'success' => true,
            'data' => array_merge($summary, ['details' => $details])
        ]);
    }

    public function pnsEstimation(Request $request)
    {
        $month = $request->month;
        $year = $request->year;

        if (!$month || !$year) {
            $latest = $this->payrollService->getLatestPeriod(GajiPns::class);
            if (!$latest) {
                return response()->json(['success' => false, 'message' => 'No PNS data found']);
            }
            $month = $latest->bulan;
            $year = $latest->tahun;
        }

        $filters = ['jenis_gaji' => $request->jenis_gaji];
        $summary = $this->payrollService->getEstimationSummary(GajiPns::class, $month, $year, $filters);
        $details = $this->payrollService->getEstimationDetails(GajiPns::class, $month, $year, $filters, 'pns');

        return response()->json([
            'success' => true,
            'data' => array_merge($summary, ['details' => $details])
        ]);
    }

    public function clearPayrollData(Request $request)
    {
        $validated = $request->validate([
            'target' => 'required|string|in:pns,pppk,both',
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer',
            'jenis_gaji' => 'nullable|string',
            'confirmation_code' => 'required|string',
        ]);

        // Validate dynamic password: HHMMDD (jam + bulan + tanggal)
        $expectedCode = now()->format('Hmd');
        if ($validated['confirmation_code'] !== $expectedCode) {
            return response()->json([
                'success' => false,
                'message' => 'Kode konfirmasi salah. Gunakan format: JamBulanTanggal (contoh: jam 14, bulan 03, tanggal 05 = 140305)',
            ], 422);
        }

        $target = $validated['target'];
        $month = $validated['month'] ?? null;
        $year = $validated['year'] ?? null;
        $jenisGaji = $validated['jenis_gaji'] ?? null;

        $results = [];

        if ($target === 'pns' || $target === 'both') {
            $query = GajiPns::query();
            if ($month)
                $query->where('bulan', $month);
            if ($year)
                $query->where('tahun', $year);
            if ($jenisGaji)
                $query->where('jenis_gaji', $jenisGaji);

            $count = $query->delete();
            $results['pns'] = $count;
        }

        if ($target === 'pppk' || $target === 'both') {
            $query = GajiPppk::query();
            if ($month)
                $query->where('bulan', $month);
            if ($year)
                $query->where('tahun', $year);
            if ($jenisGaji)
                $query->where('jenis_gaji', $jenisGaji);

            $count = $query->delete();
            $results['pppk'] = $count;
        }

        Log::info("User " . auth()->user()->username . " cleared payroll data", [
            'results' => $results,
            'params' => $validated
        ]);

        try {
            \App\Models\AuditLog::log('clear_data', 'Hapus data gaji', [
                'table_name' => $target === 'both' ? 'gaji_pns, gaji_pppk' : 'gaji_' . $target,
                'new_values' => $results,
                'old_values' => ['params' => $validated],
            ]);
        } catch (\Exception $e) {
            // Fallback: if foreign key fails (e.g. user_id mismatch on VPS), log without user_id relation
            DB::table('audit_logs')->insert([
                'user_id' => null, // Set null to bypass FK
                'username' => auth()->user()->username ?? 'system',
                'action' => 'clear_data',
                'description' => 'Hapus data gaji (FK Fallback)',
                'table_name' => $target === 'both' ? 'gaji_pns, gaji_pppk' : 'gaji_' . $target,
                'new_values' => json_encode($results),
                'old_values' => json_encode(['params' => $validated]),
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus.',
            'details' => $results
        ]);
    }

    // ========== PER-EMPLOYEE DETAIL METHODS ==========

    private function getEmployeeDetailPnsOrPppk($request, $modelClass, $type)
    {
        $filters = ['jenis_gaji' => $request->jenis_gaji];
        return $this->payrollService->getEmployeeDetailsByNip(
            $modelClass,
            $request->nip,
            $request->month,
            $request->year,
            $filters
        );
    }

    public function pppkEstimationDetail(Request $request)
    {
        $data = $this->getEmployeeDetailPnsOrPppk($request, GajiPppk::class, 'pppk');
        $settings = $this->payrollService->getSettings();
        return response()->json(['success' => true, 'data' => $data, 'settings' => $settings]);
    }

    public function pnsEstimationDetail(Request $request)
    {
        $data = $this->getEmployeeDetailPnsOrPppk($request, GajiPns::class, 'pns');
        $settings = $this->payrollService->getSettings();
        return response()->json(['success' => true, 'data' => $data, 'settings' => $settings]);
    }

    public function pppkPwEstimationDetail(Request $request)
    {
        $data = $this->payrollService->getPppkPwEstimationDetails($request->kdskpd);
        $settings = $this->payrollService->getSettings();
        return response()->json(['success' => true, 'data' => $data, 'settings' => $settings]);
    }

    // ========== EXPORT METHODS ==========

    public function pppkEstimationExport(Request $request)
    {
        $data = $this->getEmployeeDetailPnsOrPppk($request, GajiPppk::class, 'pppk');
        $month = (int) $request->month;
        $year = (int) $request->year;
        $skpdName = $request->skpd_name ?? '';
        $filename = "estimasi_pppk_{$month}_{$year}.xlsx";

        return Excel::download(new EstimationExport($data->toArray(), $month, $year, 'pppk', $skpdName), $filename);
    }

    public function pnsEstimationExport(Request $request)
    {
        $data = $this->getEmployeeDetailPnsOrPppk($request, GajiPns::class, 'pns');
        $month = (int) $request->month;
        $year = (int) $request->year;
        $skpdName = $request->skpd_name ?? '';
        $filename = "estimasi_pns_{$month}_{$year}.xlsx";

        return Excel::download(new EstimationExport($data->toArray(), $month, $year, 'pns', $skpdName), $filename);
    }

    public function pppkPwEstimationExport(Request $request)
    {
        $data = $this->payrollService->getPppkPwEstimationDetails($request->kdskpd);
        $month = (int) ($request->month ?? date('n'));
        $year = (int) ($request->year ?? date('Y'));
        $skpdName = $request->skpd_name ?? '';

        $filename = "estimasi_pppk_pw_{$month}_{$year}.xlsx";
        return Excel::download(new EstimationExport($data->toArray(), $month, $year, 'pppk_pw', $skpdName), $filename);
    }

    public function backupDatabase()
    {
        try {
            if (!function_exists('exec')) {
                return response()->json(['success' => false, 'message' => 'Fungsi exec() dinonaktifkan di server (PHP disable_functions).'], 500);
            }
            
            if (!function_exists('popen')) {
                return response()->json(['success' => false, 'message' => 'Fungsi popen() dinonaktifkan di server. Beritahu admin server.'], 500);
            }

            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');
            $dbHost = config('database.connections.mysql.host');
            $dbPort = config('database.connections.mysql.port', '3306');
            
            $filename = "backup_db_" . now()->format('Y-m-d_His') . ".sql";

            $mysqldump = 'mysqldump';
            $possiblePaths = [
                '/Applications/MAMP/Library/bin/mysql80/bin/mysqldump',
                '/Applications/MAMP/Library/bin/mysql57/bin/mysqldump',
                '/Applications/MAMP/Library/bin/mysqldump',
                '/Applications/MAMP PRO/Contents/Resources/bin/mysqldump',
                '/usr/local/mysql/bin/mysqldump',
                '/usr/local/bin/mysqldump',
                '/opt/homebrew/bin/mysqldump',
                getenv('HOME') . '/Library/Application Support/Herd/bin/mysqldump'
            ];
            foreach ($possiblePaths as $p) {
                if (@file_exists($p) && @is_executable($p)) {
                    $mysqldump = $p;
                    break;
                }
            }
            if ($mysqldump === 'mysqldump') {
                $checkPath = @exec('which mysqldump');
                if ($checkPath) $mysqldump = $checkPath;
            }

            return response()->streamDownload(function () use ($dbHost, $dbPort, $dbUser, $dbPass, $dbName, $mysqldump) {
                $command = sprintf(
                    '%s --column-statistics=0 -h %s -P %s -u %s --password=%s --no-tablespaces %s 2>&1',
                    $mysqldump,
                    escapeshellarg($dbHost),
                    escapeshellarg($dbPort),
                    escapeshellarg($dbUser),
                    escapeshellarg($dbPass),
                    escapeshellarg($dbName)
                );
                
                $handle = popen($command, 'r');
                if ($handle) {
                    while (!feof($handle)) {
                        echo fread($handle, 1024 * 8); // 8kb buffer
                        flush();
                    }
                    pclose($handle);
                } else {
                    echo "Gagal menjalankan perintah mysqldump.";
                }
            }, $filename);

        } catch (\Throwable $e) {
            Log::error("Fatal error in backupDatabase: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Fatal Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function importDatabase(Request $request)
    {
        try {
            if (!function_exists('exec')) {
                return response()->json(['success' => false, 'message' => 'Fungsi exec() dinonaktifkan di server (php.ini).'], 500);
            }

            $request->validate(['file' => 'required|file']);
            $file = $request->file('file');
            $path = $file->getRealPath();
            
            $dbName = config('database.connections.mysql.database');
            $dbUser = config('database.connections.mysql.username');
            $dbPass = config('database.connections.mysql.password');
            $dbHost = config('database.connections.mysql.host');
            $dbPort = config('database.connections.mysql.port', '3306');

            $ext = strtolower($file->getClientOriginalExtension());
            $tempSqlPath = null;
            $importPath = $path;

            // Handle ZIP
            if ($ext === 'zip') {
                $zip = new \ZipArchive();
                if ($zip->open($path) === TRUE) {
                    $sqlEntry = null;
                    for($i = 0; $i < $zip->numFiles; $i++) {
                        $name = $zip->getNameIndex($i);
                        if (str_ends_with(strtolower($name), '.sql')) {
                            $sqlEntry = $name;
                            break;
                        }
                    }
                    
                    if (!$sqlEntry) {
                        $zip->close();
                        return response()->json(['success' => false, 'message' => 'Tidak ditemukan file .sql di dalam ZIP.'], 400);
                    }

                    $tempSqlPath = storage_path('app/temp_import_' . time() . '.sql');
                    file_put_contents($tempSqlPath, $zip->getFromName($sqlEntry));
                    $importPath = $tempSqlPath;
                    $zip->close();
                } else {
                    return response()->json(['success' => false, 'message' => 'Gagal mengekstrak file ZIP.'], 400);
                }
            }
            
            $mysql = 'mysql';
            // Cek path mysql (termasuk path MAMP, MAMP PRO, Herd)
            $possibleMysqlPaths = [
                '/Applications/MAMP/Library/bin/mysql80/bin/mysql',
                '/Applications/MAMP/Library/bin/mysql57/bin/mysql',
                '/Applications/MAMP/Library/bin/mysql',
                '/Applications/MAMP PRO/Contents/Resources/bin/mysql',
                '/usr/local/mysql/bin/mysql',
                '/usr/local/bin/mysql',
                '/opt/homebrew/bin/mysql',
                getenv('HOME') . '/Library/Application Support/Herd/bin/mysql'
            ];
            foreach ($possibleMysqlPaths as $p) {
                if (@file_exists($p) && @is_executable($p)) {
                    $mysql = $p;
                    break;
                }
            }
            if ($mysql === 'mysql') {
                $checkMysqlPath = @exec('which mysql');
                if ($checkMysqlPath) $mysql = $checkMysqlPath;
            }

            if ($ext === 'gz') {
                $command = sprintf(
                    'gunzip < %s | %s -h %s -P %s -u %s --password=%s %s 2>&1',
                    escapeshellarg($importPath),
                    $mysql,
                    escapeshellarg($dbHost),
                    escapeshellarg($dbPort),
                    escapeshellarg($dbUser),
                    escapeshellarg($dbPass),
                    escapeshellarg($dbName)
                );
            } else {
                $command = sprintf(
                    '%s -h %s -P %s -u %s --password=%s %s < %s 2>&1',
                    $mysql,
                    escapeshellarg($dbHost),
                    escapeshellarg($dbPort),
                    escapeshellarg($dbUser),
                    escapeshellarg($dbPass),
                    escapeshellarg($dbName),
                    escapeshellarg($importPath)
                );
            }

            exec($command, $output, $returnVar);

            // Cleanup temp file if exists
            if ($tempSqlPath && file_exists($tempSqlPath)) {
                unlink($tempSqlPath);
            }

            if ($returnVar !== 0) {
                 return response()->json([
                     'success' => false, 
                     'message' => 'Gagal impor (CMD: ' . (strpos($mysql, '/') !== false ? 'Full Path' : 'Short Name') . '): ' . implode(' ', $output)
                 ], 500);
            }

            return response()->json(['success' => true, 'message' => 'Database berhasil dipulihkan dari ' . $file->getClientOriginalName()]);
        } catch (\Throwable $e) {
            Log::error("Fatal error in importDatabase: " . $e->getMessage(), [
                'trace' => substr($e->getTraceAsString(), 0, 500)
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Fatal Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
