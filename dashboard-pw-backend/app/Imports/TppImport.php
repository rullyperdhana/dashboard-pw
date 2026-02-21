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
                    // Update TPP
                    $oldTpp = $employee->tunj_tpp;
                    $employee->tunj_tpp = $nilai;

                    // Recalculate Totals
                    // total_tunjangan = istri + anak + fungsional + struktural + umum + beras + pph + TPP + others...
                    // Simpler approach: Calculate delta and adjust totals
                    $delta = $nilai - $oldTpp;

                    $employee->kotor += $delta;

                    // Recalculate total_tunjangan explicitly to be safe
                    $tunjanganFields = [
                        'tunj_istri',
                        'tunj_anak',
                        'tunj_fungsional',
                        'tunj_struktural',
                        'tunj_umum',
                        'tunj_beras',
                        'tunj_pph',
                        'tunj_tpp',
                        'tunj_eselon',
                        'tunj_guru',
                        'tunj_langka',
                        'tunj_tkd',
                        'tunj_terpencil',
                        'tunj_khusus',
                        'tunj_askes',
                        'tunj_kk',
                        'tunj_km',
                        'pembulatan'
                    ];

                    $totalTunjangan = 0;
                    foreach ($tunjanganFields as $field) {
                        $totalTunjangan += $employee->$field;
                    }

                    // We don't have a 'total_tunjangan' column in the database schema shown in tools,
                    // but we do have 'kotor' (Gross) and 'bersih' (Net).
                    // Gross = Gaji Pokok + Total Tunjangan
                    // Net = Gross - Total Potongan

                    // Re-calculate Gross (Kotor)
                    $employee->kotor = $employee->gaji_pokok + $totalTunjangan;

                    // Re-calculate Net (Bersih)
                    $employee->bersih = $employee->kotor - $employee->total_potongan;

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
