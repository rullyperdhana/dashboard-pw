<?php

namespace App\Imports;

use App\Models\GajiPns;
use App\Models\GajiPppk;
use App\Models\StandaloneTpp;
use App\Models\Skpd;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class TppImport implements ToCollection, WithHeadingRow
{
    protected $month;
    protected $year;
    protected $type;
    protected $jenisGaji;
    protected $jobId;

    public function __construct($month, $year, $type = 'pns', $jenisGaji = 'Induk', $jobId = null)
    {
        $this->month = $month;
        $this->year = $year;
        $this->type = $type;
        $this->jenisGaji = $jenisGaji;
        $this->jobId = $jobId;
    }

    protected function logProgress($message)
    {
        if ($this->jobId) {
            $logPath = storage_path('logs/upload_jobs/job_' . $this->jobId . '.log');
            $timestamp = now()->format('Y-m-d H:i:s');
            file_put_contents($logPath, "[$timestamp] $message\n", FILE_APPEND);
        }
    }

    public function collection(Collection $rows)
    {
        try {
            // Clear previous discrepancy logs for this period/type
            \App\Models\TppDiscrepancyLog::where('month', $this->month)
                ->where('year', $this->year)
                ->where('employee_type', $this->type)
                ->delete();
                
            // Clear previous TppDetails for this period/type/jenis_gaji
            if ($this->type === 'gabungan') {
                \App\Models\TppDetail::where('month', $this->month)
                    ->where('year', $this->year)
                    ->where('jenis_gaji', $this->jenisGaji)
                    ->delete();
            } else {
                \App\Models\TppDetail::where('month', $this->month)
                    ->where('year', $this->year)
                    ->where('employee_type', $this->type)
                    ->where('jenis_gaji', $this->jenisGaji)
                    ->delete();
            }

            // Note: We don't delete standalone records here anymore to preserve manual mappings.
            // We will cleanup at the end for NIPs not present in the new Excel.

            $excelNips = [];
            $updatedCount = 0;
            $notFoundInDbCount = 0;

            foreach ($rows as $row) {
                if (!isset($row['nip'])) {
                    continue;
                }

                $nip = (string) $row['nip'];
                $excelNips[] = $nip;
                $nilaiRaw = $row['yang_dibayarkan_transfer'] ?? 0;
                $nilai = $this->parseCurrency($nilaiRaw);
                
                $namaPegawai = $row['nama_lengkap'] ?? $row['nama'] ?? 'Tanpa Nama';
                $this->logProgress("Memproses NIP: $nip - $namaPegawai");
                
                $employee = null;
                $actualEmployeeType = $this->type;

                if ($this->type === 'gabungan') {
                    // Cek di tabel PNS dulu
                    $employee = GajiPns::where('nip', $nip)
                        ->where('bulan', $this->month)
                        ->where('tahun', $this->year)
                        ->where('jenis_gaji', $this->jenisGaji)
                        ->first();
                    $actualEmployeeType = 'pns';

                    // Jika tidak ada di PNS, cek di PPPK
                    if (!$employee) {
                        $employee = GajiPppk::where('nip', $nip)
                            ->where('bulan', $this->month)
                            ->where('tahun', $this->year)
                            ->where('jenis_gaji', $this->jenisGaji)
                            ->first();
                        $actualEmployeeType = 'pppk';
                    }
                    
                    // Fallback jika tidak ketemu di keduanya, gunakan status dari excel
                    if (!$employee && isset($row['status_pegawai'])) {
                        $statusExcel = strtolower(trim($row['status_pegawai']));
                        if ($statusExcel === 'cpns' || $statusExcel === 'pns') {
                            $actualEmployeeType = 'pns';
                        } elseif (str_contains($statusExcel, 'pppk')) {
                            $actualEmployeeType = 'pppk';
                        }
                    }
                } else {
                    $model = $this->type === 'pppk' ? GajiPppk::class : GajiPns::class;
                    $employee = $model::where('nip', $nip)
                        ->where('bulan', $this->month)
                        ->where('tahun', $this->year)
                        ->where('jenis_gaji', $this->jenisGaji)
                        ->first();
                }

                // Save to tpp_details table for reporting purposes
                \App\Models\TppDetail::create([
                    'month' => $this->month,
                    'year' => $this->year,
                    'employee_type' => $actualEmployeeType,
                    'jenis_gaji' => $this->jenisGaji,
                    'nip' => $nip,
                    'nama_lengkap' => $row['nama_lengkap'] ?? $row['nama'] ?? null,
                    'instansi_upt' => $row['instansi_upt'] ?? $row['skpd'] ?? $row['unit_skpd'] ?? null,
                    'jabatan' => $row['jabatan'] ?? null,
                    'status_pegawai' => $row['status_pegawai'] ?? null,
                    'tpp_bruto' => $this->parseCurrency($row['tpp_bruto'] ?? 0),
                    'bruto_plus' => $this->parseCurrency($row['bruto_plus'] ?? 0),
                    'tpp_netto' => $this->parseCurrency($row['tpp_netto'] ?? 0),
                    'dpp_pajak' => $this->parseCurrency($row['dpp_pajak'] ?? 0),
                    'pph_21' => $this->parseCurrency($row['pph_21'] ?? 0),
                    'potongan_tpp_lainnya' => $this->parseCurrency($row['potongan_tpp_lainnya'] ?? 0),
                    'iuran_iwp' => $this->parseCurrency($row['iuran_iwp'] ?? 0),
                    'total_potongan' => $this->parseCurrency($row['total_potongan'] ?? 0),
                    'yang_dibayarkan_transfer' => $nilai,
                ]);

                if ($employee) {
                    $employee->tunj_tpp = $nilai;
                    
                    // Recalculate kotor and bersih
                    $this->recalculate($employee);
                    
                    $employee->save();
                    $updatedCount++;
                    
                    // If employee is found in DB, we should REMOVE them from standalone_tpp if they were there
                    StandaloneTpp::where('month', $this->month)
                        ->where('year', $this->year)
                        ->where('nip', $nip)
                        ->where('jenis_gaji', $this->jenisGaji)
                        ->delete();
                } else {
                    $notFoundInDbCount++;
                    Log::warning("TPP Import: Employee not found in DB for NIP: {$nip}, Month: {$this->month}, Year: {$this->year}. Saving to standalone_tpp.");

                    // Save to standalone_tpp so operator can map it
                    $skpdId = null;
                    if (isset($row['instansi_upt']) || isset($row['skpd']) || isset($row['unit_skpd'])) {
                        $skpdName = $row['instansi_upt'] ?? $row['skpd'] ?? $row['unit_skpd'];
                        $skpdId = $this->findSkpdIdByName($skpdName);
                    }

                    $standalone = StandaloneTpp::firstOrCreate(
                        [
                            'month' => $this->month,
                            'year' => $this->year,
                            'employee_type' => $actualEmployeeType,
                            'nip' => $nip,
                            'jenis_gaji' => $this->jenisGaji
                        ]
                    );

                    $standalone->nama = $row['nama_lengkap'] ?? $row['nama'] ?? $standalone->nama;
                    $standalone->nilai = $nilai;

                    // Only set skpd_id if it's currently null AND we found a match from the file
                    if (!$standalone->skpd_id && $skpdId) {
                        $standalone->skpd_id = $skpdId;
                    }

                    $standalone->save();
                }
            }

            // Cleanup standalone records for this period/type/jenis_gaji that are NOT in the current Excel
            StandaloneTpp::where('month', $this->month)
                ->where('year', $this->year)
                ->where('employee_type', $this->type)
                ->where('jenis_gaji', $this->jenisGaji)
                ->whereNotIn('nip', $excelNips)
                ->delete();

            // Find missing employees: In DB but NOT in Excel
            $model = $this->type === 'pppk' ? GajiPppk::class : GajiPns::class;
            $missingEmployees = $model::where('bulan', $this->month)
                ->where('tahun', $this->year)
                ->where('jenis_gaji', $this->jenisGaji)
                ->whereNotIn('nip', $excelNips)
                ->select('nip', 'nama', 'skpd', 'kdskpd', 'tunj_tpp')
                ->get();

            foreach ($missingEmployees as $emp) {
                $skpdName = $emp->skpd;
                
                // If SKPD is "Unknown", try to resolve it via kdskpd and mapping
                if ($skpdName === 'Unknown' && isset($emp->kdskpd)) {
                    $mapping = \App\Models\SkpdMapping::where('source_code', $emp->kdskpd)
                        ->whereIn('type', [$this->type, 'all'])
                        ->first();
                    if ($mapping && $mapping->skpd) {
                        $skpdName = $mapping->skpd->nama_skpd;
                    }
                }

                \App\Models\TppDiscrepancyLog::create([
                    'month' => $this->month,
                    'year' => $this->year,
                    'employee_type' => $this->type,
                    'nip' => $emp->nip,
                    'nama' => $emp->nama,
                    'skpd' => $skpdName,
                    'nilai' => $emp->tunj_tpp,
                    'reason' => 'Tidak ditemukan di file Excel TPP'
                ]);
            }

            Log::info("TPP Import Completed. Updated: {$updatedCount}, Missing in Excel logged: " . $missingEmployees->count());

        } catch (\Exception $e) {
            Log::error('Error importing TPP: ' . $e->getMessage());
            throw $e;
        }
    }

    private function recalculate($employee)
    {
        // Get all tunjangan columns
        $tunjanganColumns = [
            'gaji_pokok', 'tunj_istri', 'tunj_anak', 'tunj_fungsional', 'tunj_struktural',
            'tunj_umum', 'tunj_beras', 'tunj_pph', 'tunj_tpp', 'tunj_eselon',
            'tunj_guru', 'tunj_langka', 'tunj_tkd', 'tunj_terpencil', 'tunj_khusus',
            'tunj_askes', 'tunj_kk', 'tunj_km', 'pembulatan'
        ];

        $kotor = 0;
        foreach ($tunjanganColumns as $col) {
            $kotor += (float) $employee->{$col};
        }

        $employee->kotor = $kotor;
        
        // Bersih = Kotor - Total Potongan
        // Standard formula from initial import
        $totalPotongan = (float) $employee->total_potongan;
        $employee->bersih = $kotor - $totalPotongan;
    }

    private function parseCurrency($value)
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        // Remove non-numeric characters except comma and dot
        $clean = preg_replace('/[^0-9,.]/', '', $value);

        // If empty, return 0
        if (empty($clean)) {
            return 0;
        }

        // English format (1,000.00) vs Indonesian format (1.000,00) detection
        // If it has multiple dots, it's likely Indonesian thousands separator (1.000.000)
        // If it has a comma at the end, it's likely Indonesian decimal (100,00)

        // Simple heuristic for common Excel exports: remove dots, replace comma with dot
        // This assumes input like "1.500.000,00" or "1.500.000"
        $noDots = str_replace('.', '', $clean);
        $withDecimal = str_replace(',', '.', $noDots);

        return (float) $withDecimal;
    }

    private function findSkpdIdByName($name)
    {
        if (empty($name)) return null;
        $name = trim($name);
        
        $skpd = Skpd::where('nama_skpd', 'LIKE', '%' . $name . '%')
            ->first();
            
        return $skpd ? $skpd->id_skpd : null;
    }
}
