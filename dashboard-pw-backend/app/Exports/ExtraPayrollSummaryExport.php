<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExtraPayrollSummaryExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected $data;
    protected $title;
    protected $year;
    protected $monthName;

    public function __construct(array $data, string $title, int $year, string $monthName)
    {
        $this->data = $data;
        $this->title = $title;
        $this->year = $year;
        $this->monthName = $monthName;
    }

    public function headings(): array
    {
        return [
            [$this->title],
            ["TAHUN {$this->year} (PEMBAYARAN BULAN " . strtoupper($this->monthName) . ")"],
            [''],
            [
                'No',
                'Satuan Kerja (SKPD)',
                'Sumber Dana',
                'Jumlah Pegawai',
                'Total Pembayaran'
            ]
        ];
    }

    public function array(): array
    {
        $rows = [];
        $no = 1;
        $totalEmployees = 0;
        $totalAmount = 0;

        foreach ($this->data as $item) {
            $rows[] = [
                $no++,
                $item['skpd_name'],
                $item['sumber_dana'] ?? 'APBD',
                $item['total_employees_skpd'],
                $item['total_amount_skpd'],
            ];
            $totalEmployees += $item['total_employees_skpd'];
            $totalAmount += $item['total_amount_skpd'];
        }

        // Grand Total row
        $rows[] = [
            '',
            'TOTAL KESELURUHAN',
            '',
            $totalEmployees,
            $totalAmount
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        // Merge cells for title
        $sheet->mergeCells("A1:E1");
        $sheet->mergeCells("A2:E2");
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal('center');

        // Header style
        $sheet->getStyle('A4:E4')->getFont()->setBold(true);
        $sheet->getStyle('A4:E4')->getFill()->setFillType('solid')->getStartColor()->setRGB('E9ECEF');

        // Border for data
        $lastRow = count($this->data) + 5;
        $sheet->getStyle("A4:E{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Styling the last (Grand Total) row
        $sheet->getStyle("A{$lastRow}:E{$lastRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$lastRow}:E{$lastRow}")->getFill()->setFillType('solid')->getStartColor()->setRGB('CCE5FF');

        // Number formatting
        $sheet->getStyle("D5:D{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("E5:E{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');

        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 55,
            'C' => 15,
            'D' => 18,
            'E' => 25,
        ];
    }
}
