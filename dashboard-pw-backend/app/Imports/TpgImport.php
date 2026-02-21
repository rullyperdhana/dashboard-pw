<?php

namespace App\Imports;

use App\Models\TpgData;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class TpgImport implements ToCollection, WithHeadingRow
{
    protected $triwulan;
    protected $tahun;
    protected $jenis;

    public function __construct($triwulan, $tahun, $jenis = 'INDUK')
    {
        $this->triwulan = $triwulan;
        $this->tahun = $tahun;
        $this->jenis = strtoupper($jenis);
    }

    public function collection(Collection $rows)
    {
        try {
            $importedCount = 0;
            $skippedCount = 0;

            // Log actual headers from first row for debugging
            if ($rows->isNotEmpty()) {
                $keys = $rows->first()->keys()->toArray();
                Log::info('TPG Import - Excel column keys detected: ' . implode(', ', $keys));
            }

            foreach ($rows as $row) {
                // Skip rows without NIP
                $nip = $this->extractValue($row, ['nip']);
                if (empty($nip)) {
                    $skippedCount++;
                    continue;
                }
                $nip = trim((string) $nip);

                $nama = $this->extractValue($row, ['nama']) ?? '';

                $noRekening = $this->extractValue($row, [
                    'nama_pemilno_rekening_bank',
                    'nama_pemil_no_rekening_bank',
                    'no_rekening_bank',
                    'no_rekening',
                    'rekening',
                ]) ?? '';

                $satdik = $this->extractValue($row, ['satdik']) ?? '';

                // SALUR BRUT / SALUR BRUTO — try many possible header variations
                $salurBrut = $this->parseCurrency($this->extractValue($row, [
                    'salur_brut',
                    'salur_bruto',
                    'salur_brut_pph',
                    'bruto',
                    'brut',
                ]) ?? 0);

                // PPH — separate column
                $pph = $this->parseCurrency($this->extractValue($row, [
                    'pph',
                    'pot_pph',
                    'potongan_pph',
                    'pph21',
                ]) ?? 0);

                // POT. JKN
                $potJkn = $this->parseCurrency($this->extractValue($row, [
                    'pot_jkn',
                    'potongan_jkn',
                    'jkn',
                    'pot_jkn_1',
                ]) ?? 0);

                // SALUR NETT
                $salurNett = $this->parseCurrency($this->extractValue($row, [
                    'salur_nett',
                    'salur_netto',
                    'nett',
                    'netto',
                ]) ?? 0);

                // Upsert by NIP + triwulan + tahun + jenis
                TpgData::updateOrCreate(
                    [
                        'nip' => $nip,
                        'triwulan' => $this->triwulan,
                        'tahun' => $this->tahun,
                        'jenis' => $this->jenis,
                    ],
                    [
                        'nama' => $nama,
                        'no_rekening' => $noRekening,
                        'satdik' => $satdik,
                        'salur_brut' => $salurBrut,
                        'pph' => $pph,
                        'pot_jkn' => $potJkn,
                        'salur_nett' => $salurNett,
                    ]
                );

                $importedCount++;
            }

            Log::info("TPG Import Completed. Imported: {$importedCount}, Skipped: {$skippedCount}");

        } catch (\Exception $e) {
            Log::error('Error importing TPG: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Extract a value from the row by trying multiple possible column keys.
     */
    private function extractValue($row, array $possibleKeys)
    {
        foreach ($possibleKeys as $key) {
            if (isset($row[$key]) && $row[$key] !== null && $row[$key] !== '') {
                return $row[$key];
            }
        }

        // Also try partial matching on actual keys
        $rowKeys = $row->keys()->toArray();
        foreach ($possibleKeys as $needle) {
            foreach ($rowKeys as $actualKey) {
                if (str_contains((string) $actualKey, $needle)) {
                    if (isset($row[$actualKey]) && $row[$actualKey] !== null && $row[$actualKey] !== '') {
                        return $row[$actualKey];
                    }
                }
            }
        }

        return null;
    }

    /**
     * Parse currency values from various formats.
     */
    private function parseCurrency($value)
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = preg_replace('/[^0-9,.]/', '', (string) $value);

        if (empty($clean)) {
            return 0;
        }

        // Handle Indonesian format (1.000.000,00): remove dots, replace comma with dot
        $noDots = str_replace('.', '', $clean);
        $withDecimal = str_replace(',', '.', $noDots);

        return (float) $withDecimal;
    }
}
