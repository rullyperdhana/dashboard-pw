<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MissingPayrollExport implements FromArray, WithHeadings, WithStyles
{
    protected $data;
    protected $title;

    public function __construct(array $data, string $title)
    {
        $this->data = $data;
        $this->title = $title;
    }

    public function headings(): array
    {
        return [
            [$this->title],
            [''],
            ['No', 'SKPD', 'Nama Pegawai', 'NIP', 'Jabatan', 'Gaji Pokok Basis', 'Keterangan/Alasan']
        ];
    }

    public function array(): array
    {
        $rows = [];
        $no = 1;

        foreach ($this->data as $item) {
            $rows[] = [
                $no++,
                $item['skpd_name'] ?? '',
                $item['nama'] ?? '',
                "'" . ($item['nip'] ?? ''),
                $item['jabatan'] ?? '',
                $item['gapok_basis'] ?? 0,
                $item['reason'] ?? ''
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A3:G3')->getFont()->setBold(true);
        $sheet->getStyle('A3:G3')->getFill()->setFillType('solid')->getStartColor()->setRGB('E9ECEF');
    }
}
