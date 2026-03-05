<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CombinedAllowanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $data;
    protected $month;
    protected $year;
    protected $monthName;

    public function __construct($data, $month, $year, $monthName)
    {
        $this->data = collect($data);
        $this->month = $month;
        $this->year = $year;
        $this->monthName = $monthName;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            ["LAPORAN RINCIAN TUNJANGAN BULANAN GABUNGAN (PNS + PPPK)"],
            ["Bulan: {$this->monthName} {$this->year}"],
            [""],
            [
                "Jenis Tunjangan / Potongan",
                "PNS",
                "PPPK",
                "Total"
            ]
        ];
    }

    public function map($row): array
    {
        return [
            $row['label'],
            $row['pns'],
            $row['pppk'],
            $row['total']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E0E0']]],
        ];

        // Dynamic styling for bold rows
        foreach ($this->data as $index => $row) {
            $rowIndex = $index + 5; // Accounts for 4 rows of headers
            $label = $row['label'] ?? '';

            // Bold section headers (A., B., C.) or Total rows
            if (preg_match('/^[ABC]\./', $label) || str_contains(strtoupper($label), 'TOTAL')) {
                $styles[$rowIndex] = ['font' => ['bold' => true]];
            }
        }

        return $styles;
    }
}
