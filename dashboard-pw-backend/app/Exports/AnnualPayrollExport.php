<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AnnualPayrollExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected $data;
    protected $year;
    protected $type;
    protected $jenisGaji;

    public function __construct(array $data, int $year, string $type, ?string $jenisGaji = null)
    {
        $this->data = $data;
        $this->year = $year;
        $this->type = strtoupper($type);
        $this->jenisGaji = $jenisGaji ?? 'SEMUA';
    }

    public function headings(): array
    {
        return [
            ["HISTORI TRANSAKSI TAHUNAN {$this->type}"],
            ["TAHUN {$this->year} - JENIS GAJI: " . strtoupper($this->jenisGaji)],
            [''],
            [
                'BULAN / TIPE',
                'PERSONIL',
                'GAJI POKOK',
                'TJ. KELUARGA',
                'TJ. JABATAN',
                'BERAS',
                'TPP',
                'POTONGAN',
                'BERSIH'
            ]
        ];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->data as $month) {
            if (empty($month['types'])) continue;

            // Type sub-rows
            foreach ($month['types'] as $type) {
                $rows[] = [
                    $month['month_name'] . ' - ' . $type['jenis_gaji'],
                    $type['total_employees'],
                    $type['total_gaji_pokok'],
                    ($type['total_tunj_istri'] ?? 0) + ($type['total_tunj_anak'] ?? 0),
                    ($type['total_tunj_fungsional'] ?? 0) + ($type['total_tunj_struktural'] ?? 0) + ($type['total_tunj_umum'] ?? 0),
                    $type['total_tunj_beras'] ?? 0,
                    $type['total_tunj_tpp'] ?? 0,
                    $type['total_potongan'] ?? 0,
                    $type['total_bersih'] ?? 0,
                ];
            }

            // Monthly Total Row (only if multiple types)
            if (count($month['types']) > 1) {
                $rows[] = [
                    'TOTAL ' . strtoupper($month['month_name']),
                    $month['total_employees'],
                    $month['total_gaji_pokok'],
                    ($month['total_tunj_istri'] ?? 0) + ($month['total_tunj_anak'] ?? 0),
                    ($month['total_tunj_fungsional'] ?? 0) + ($month['total_tunj_struktural'] ?? 0) + ($month['total_tunj_umum'] ?? 0),
                    $month['total_tunj_beras'] ?? 0,
                    $month['total_tunj_tpp'] ?? 0,
                    $month['total_potongan'] ?? 0,
                    $month['total_bersih'] ?? 0,
                ];
            }
            
            $rows[] = ['']; // Spacer
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        // Global styles
        $sheet->mergeCells("A1:I1");
        $sheet->mergeCells("A2:I2");
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        // Header Row
        $sheet->getStyle('A4:I4')->getFont()->setBold(true);
        $sheet->getStyle('A4:I4')->getFill()->setFillType('solid')->getStartColor()->setRGB('E9ECEF');

        $rowIdx = 5;
        foreach ($this->data as $month) {
            if (empty($month['types'])) continue;

            $typeCount = count($month['types']);
            
            // Sub-rows indices
            $startRange = $rowIdx;
            $endRange = $rowIdx + $typeCount - 1;
            
            // Format numbers for types
            $sheet->getStyle("B{$startRange}:I{$endRange}")->getNumberFormat()->setFormatCode('#,##0');
            $rowIdx += $typeCount;

            // Monthly Total Row
            if ($typeCount > 1) {
                $sheet->getStyle("A{$rowIdx}:I{$rowIdx}")->getFont()->setBold(true);
                $sheet->getStyle("A{$rowIdx}:I{$rowIdx}")->getFill()->setFillType('solid')->getStartColor()->setRGB('D1ECF1');
                $sheet->getStyle("B{$rowIdx}:I{$rowIdx}")->getNumberFormat()->setFormatCode('#,##0');
                $rowIdx++;
            }

            // Spacer
            $rowIdx++;
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 12,
            'C' => 18,
            'D' => 18,
            'E' => 18,
            'F' => 15,
            'G' => 15,
            'H' => 18,
            'I' => 18,
        ];
    }
}
