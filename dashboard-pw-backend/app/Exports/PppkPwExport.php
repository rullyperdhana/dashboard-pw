<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PppkPwExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithMapping, WithColumnFormatting
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'NIP',
            'NAMA',
            'NIK',
            'JENIS KELAMIN',
            'SKPD',
            'UPT',
            'GOLONGAN',
            'JABATAN',
            'GAJI POKOK',
            'TUNJANGAN',
            'POTONGAN',
            'SUMBER DANA',
            'STATUS',
            'WAKTU EXPORT'
        ];
    }

    public function map($row): array
    {
        // Handle both object and array access
        $row = (object) $row;
        
        $skpdName = $row->skpd['nama_skpd'] ?? $row->skpd ?? '-';

        return [
            $row->nip . " ", // Force string
            $row->nama,
            ($row->nik ?? $row->noktp ?? '') . " ", 
            $row->jk,
            $skpdName,
            $row->upt,
            $row->golru,
            $row->jabatan,
            $row->gapok,
            $row->tunjangan ?? 0,
            $row->potongan ?? 0,
            $row->sumber_dana ?? 'APBD',
            $row->status ?? 'Aktif',
            date('Y-m-d H:i:s')
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'C' => NumberFormat::FORMAT_TEXT,
            'I' => '#,##0',
            'J' => '#,##0',
            'K' => '#,##0',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '22C55E'] // Green Success color
                ],
            ],
        ];
    }
}
