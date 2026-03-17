<?php

namespace App\Imports;

use App\Models\GajiPns;
use App\Models\GajiPppk;
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
                    $employee->save();
                    $updatedCount++;
                } else {
                    $notFoundInDbCount++;
                    Log::warning("TPP Import: Employee not found in DB for NIP: {$nip}, Month: {$this->month}, Year: {$this->year}");
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
                ->select('nip', 'nama', 'skpd')
                ->get();

            foreach ($missingEmployees as $emp) {
                \App\Models\TppDiscrepancyLog::create([
                    'month' => $this->month,
                    'year' => $this->year,
                    'employee_type' => $this->type,
                    'nip' => $emp->nip,
                    'nama' => $emp->nama,
                    'skpd' => $emp->skpd,
                    'reason' => 'Tidak ditemukan di file Excel TPP'
                ]);
            }

            Log::info("TPP Import Completed. Updated: {$updatedCount}, Missing in Excel logged: " . $missingEmployees->count());

        } catch (\Exception $e) {
            Log::error('Error importing TPP: ' . $e->getMessage());
            throw $e;
        }
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
}
