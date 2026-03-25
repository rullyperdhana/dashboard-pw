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

    public function __construct($month, $year, $type = 'pns', $jenisGaji = 'Induk')
    {
        $this->month = $month;
        $this->year = $year;
        $this->type = $type;
        $this->jenisGaji = $jenisGaji;
    }

    public function collection(Collection $rows)
    {
        try {
            $excelNips = [];
            $updatedCount = 0;
            $notFoundInDbCount = 0;

            foreach ($rows as $row) {
                if (!isset($row['nip'])) {
                    continue;
                }

                $nip = (string) $row['nip'];
                $excelNips[] = $nip;
                $nilaiRaw = $row['nilai'] ?? 0;
                $nilai = $this->parseCurrency($nilaiRaw);

                $model = $this->type === 'pppk' ? GajiPppk::class : GajiPns::class;

                $employee = $model::where('nip', $nip)
                    ->where('bulan', $this->month)
                    ->where('tahun', $this->year)
                    ->where('jenis_gaji', $this->jenisGaji)
                    ->first();

                if ($employee) {
                    $employee->tunj_tpp = $nilai;
                    
                    // Recalculate kotor and bersih
                    $this->recalculate($employee);
                    
                    $employee->save();
                    $updatedCount++;
                    
                    // Also update/sync standalone record if exists to keep consistent
                    StandaloneTpp::updateOrCreate(
                        [
                            'month' => $this->month,
                            'year' => $this->year,
                            'employee_type' => $this->type,
                            'nip' => $nip,
                            'jenis_gaji' => $this->jenisGaji
                        ],
                        [
                            'nama' => $row['nama'] ?? null,
                            'nilai' => $nilai,
                            'skpd_id' => $employee->idskpd // If found in DB, use its SKPD
                        ]
                    );
                } else {
                    $notFoundInDbCount++;
                    Log::warning("TPP Import: Employee not found in DB for NIP: {$nip}, Month: {$this->month}, Year: {$this->year}. Saving to standalone_tpp.");

                    // Save to standalone_tpp so operator can map it
                    $skpdId = null;
                    if (isset($row['skpd']) || isset($row['unit_skpd'])) {
                        $skpdName = $row['skpd'] ?? $row['unit_skpd'];
                        $skpdId = $this->findSkpdIdByName($skpdName);
                    }

                    StandaloneTpp::updateOrCreate(
                        [
                            'month' => $this->month,
                            'year' => $this->year,
                            'employee_type' => $this->type,
                            'nip' => $nip,
                            'jenis_gaji' => $this->jenisGaji
                        ],
                        [
                            'nama' => $row['nama'] ?? null,
                            'nilai' => $nilai,
                            'skpd_id' => $skpdId
                        ]
                    );
                }
            }

            // Clear previous logs for this period/type
            \App\Models\TppDiscrepancyLog::where('month', $this->month)
                ->where('year', $this->year)
                ->where('employee_type', $this->type)
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
            ->orWhere('kode_simgaji', $name)
            ->first();
            
        return $skpd ? $skpd->id_skpd : null;
    }
}
