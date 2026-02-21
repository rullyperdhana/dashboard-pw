<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TppTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            ['196104011989031005', 'IMAN WACHYUDI', 6000000],
            ['196112291982032010', 'RINI SETIASIH', 8800000],
        ];
    }

    public function headings(): array
    {
        return [
            'NIP',
            'NAMA',
            'NILAI',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '008080'] // Teal color to match theme, or Blue as requested
                ],
            ],
        ];
    }
}
