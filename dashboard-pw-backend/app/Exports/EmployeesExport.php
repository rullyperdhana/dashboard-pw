<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmployeesExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function headings(): array
    {
        return [
            'No',
            'NIP',
            'NIK',
            'Nama',
            'JK',
            'Tempat Lahir',
            'Tgl Lahir',
            'Agama',
            'Status',
            'No HP',
            'Golongan',
            'Jabatan',
            'Eselon',
            'SKPD',
            'UPT',
            'Masa Kerja (Thn)',
            'Masa Kerja (Bln)',
            'Pendidikan',
            'Gaji Pokok',
            'Tunjangan',
        ];
    }

    public function array(): array
    {
        $rows = [];
        $no = 1;

        foreach ($this->data as $item) {
            $rows[] = [
                $no++,
                "'" . ($item['nip'] ?? ''),
                "'" . ($item['nik'] ?? ''),
                $item['nama'] ?? '',
                $item['jk'] ?? '',
                $item['tempat_lahir'] ?? '',
                $item['tgl_lahir'] ?? '',
                $item['agama'] ?? '',
                $item['status'] ?? '',
                $item['no_hp'] ?? '',
                $item['golru'] ?? '',
                $item['jabatan'] ?? '',
                $item['eselon'] ?? '',
                $item['skpd']['nama_skpd'] ?? '',
                $item['upt'] ?? '',
                $item['mk_thn'] ?? 0,
                $item['mk_bln'] ?? 0,
                ($item['tk_ijazah'] ?? '') . ' - ' . ($item['nm_pendidikan'] ?? ''),
                $item['gapok'] ?? 0,
                $item['tunjangan'] ?? 0,
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->data) + 1;

        // Format NIP and NIK columns as text
        $sheet->getStyle("B2:C{$lastRow}")->getNumberFormat()->setFormatCode('@');

        // Format currency columns
        $sheet->getStyle("S2:T{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');

        return [
            1 => ['font' => ['bold' => true, 'size' => 10]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 22,
            'C' => 20,
            'D' => 30,
            'E' => 12,
            'F' => 15,
            'G' => 12,
            'H' => 10,
            'I' => 12,
            'J' => 15,
            'K' => 10,
            'L' => 25,
            'M' => 8,
            'N' => 35,
            'O' => 20,
            'P' => 8,
            'Q' => 8,
            'R' => 25,
            'S' => 15,
            'T' => 15,
        ];
    }
}
