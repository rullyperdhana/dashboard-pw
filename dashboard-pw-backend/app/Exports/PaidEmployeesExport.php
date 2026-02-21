<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaidEmployeesExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected $data;
    protected $month;
    protected $year;

    public function __construct(array $data, int $month, int $year)
    {
        $this->data = $data;
        $this->month = $month;
        $this->year = $year;
    }

    public function headings(): array
    {
        return [
            'No',
            'NIP',
            'Nama Pegawai',
            'Jabatan',
            'UPT',
            'SKPD',
            'Gaji Pokok',
            'Pajak',
            'IWP',
            'Tunjangan',
            'Potongan',
            'Total Bersih'
        ];
    }

    public function array(): array
    {
        $rows = [];
        $no = 1;

        foreach ($this->data as $item) {
            $rows[] = [
                $no++,
                "'" . ($item['nip'] ?? ''),  // Prefix with apostrophe to force text format
                $item['nama'] ?? '',
                $item['jabatan'] ?? '',
                $item['upt'] ?? '',
                $item['nama_skpd'] ?? '',
                $item['gaji_pokok'] ?? 0,
                $item['pajak'] ?? 0,
                $item['iwp'] ?? 0,
                $item['tunjangan'] ?? 0,
                $item['potongan'] ?? 0,
                $item['total_bersih'] ?? 0,
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        // Format currency columns
        $lastRow = count($this->data) + 1;
        $sheet->getStyle("G2:L{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');

        // Format NIP column as text
        $sheet->getStyle("B2:B{$lastRow}")->getNumberFormat()->setFormatCode('@');

        return [
            1 => ['font' => ['bold' => true, 'size' => 10]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 22,
            'C' => 30,
            'D' => 25,
            'E' => 20,
            'F' => 35,
            'G' => 15,
            'H' => 12,
            'I' => 12,
            'J' => 15,
            'K' => 12,
            'L' => 15,
        ];
    }
}
