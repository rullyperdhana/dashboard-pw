<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UnpaidDataExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected $data;
    protected $viewBy;
    protected $month;
    protected $year;

    public function __construct(array $data, string $viewBy, int $month, int $year)
    {
        $this->data = $data;
        $this->viewBy = $viewBy;
        $this->month = $month;
        $this->year = $year;
    }

    public function headings(): array
    {
        switch ($this->viewBy) {
            case 'skpd':
                return ['No', 'SKPD Name', 'SKPD Code'];
            case 'upt':
                return ['No', 'UPT Name', 'SKPD Name'];
            case 'employees':
                return ['No', 'NIP', 'Name', 'Position', 'UPT', 'SKPD'];
            default:
                return ['No', 'Name'];
        }
    }

    public function array(): array
    {
        $rows = [];
        $no = 1;

        switch ($this->viewBy) {
            case 'skpd':
                foreach ($this->data as $item) {
                    $rows[] = [
                        $no++,
                        $item['nama_skpd'] ?? '',
                        $item['kode_skpd'] ?? '',
                    ];
                }
                break;

            case 'upt':
                foreach ($this->data as $item) {
                    $rows[] = [
                        $no++,
                        $item['upt'] ?? '',
                        $item['nama_skpd'] ?? '',
                    ];
                }
                break;

            case 'employees':
                foreach ($this->data as $skpdGroup) {
                    foreach ($skpdGroup['employees'] as $emp) {
                        $rows[] = [
                            $no++,
                            "'" . ($emp['nip'] ?? ''),  // Prefix with apostrophe to force text format
                            $emp['nama'] ?? '',
                            $emp['jabatan'] ?? '',
                            $emp['upt'] ?? '',
                            $skpdGroup['skpd_name'] ?? '',
                        ];
                    }
                }
                break;
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        // Format NIP column as text for employees view
        if ($this->viewBy === 'employees') {
            $lastRow = count($this->data) + 1;
            $sheet->getStyle("B2:B{$lastRow}")->getNumberFormat()->setFormatCode('@');
        }

        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }

    public function columnWidths(): array
    {
        switch ($this->viewBy) {
            case 'skpd':
                return ['A' => 5, 'B' => 50, 'C' => 15];
            case 'upt':
                return ['A' => 5, 'B' => 40, 'C' => 50];
            case 'employees':
                return ['A' => 5, 'B' => 22, 'C' => 35, 'D' => 30, 'E' => 30, 'F' => 50];
            default:
                return ['A' => 5, 'B' => 50];
        }
    }
}
