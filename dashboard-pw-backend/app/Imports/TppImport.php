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

    public function __construct($month, $year, $type = 'pns')
    {
        $this->month = $month;
        $this->year = $year;
        $this->type = $type;
    }

    public function collection(Collection $rows)
    {
        try {
            $updatedCount = 0;
            $notFoundCount = 0;

            foreach ($rows as $row) {
                // Ensure required keys exist
                if (!isset($row['nip'])) {
                    continue;
                }

                $nip = (string) $row['nip'];
                $nama = $row['nama'] ?? '';
                // Handle various number formats (e.g. "1.500.000", "1500000", "1,500,000")
                $nilaiRaw = $row['nilai'] ?? 0;
                $nilai = $this->parseCurrency($nilaiRaw);

                // Find employee record
                $model = $this->type === 'pppk' ? GajiPppk::class : GajiPns::class;

                $employee = $model::where('nip', $nip)
                    ->where('bulan', $this->month)
                    ->where('tahun', $this->year)
                    ->first();

                if ($employee) {
                    // Update TPP only, do not change kotor or bersih totals
                    $employee->tunj_tpp = $nilai;
                    $employee->save();
                    $updatedCount++;
                } else {
                    $notFoundCount++;
                    Log::warning("TPP Import: Employee not found for NIP: {$nip}, Month: {$this->month}, Year: {$this->year}");
                }
            }

            Log::info("TPP Import Completed. Updated: {$updatedCount}, Not Found: {$notFoundCount}");

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
