<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BpjsRekonDetailExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
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
        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];
        $monthName = $months[$this->month] ?? '';

        return [
            ['DETAIL PERHITUNGAN BPJS 4%'],
            ['PERIODE: ' . strtoupper($monthName) . ' ' . $this->year],
            [''],
            [
                'No',
                'NIP',
                'Nama Pegawai',
                'SKPD',
                'Jabatan',
                'Gaji Pokok',
                'BPJS 4%',
                'Basis',
                'Gaji Bersih'
            ]
        ];
    }

    public function array(): array
    {
        $rows = [];
        $no = 1;

        foreach ($this->data as $item) {
            $rows[] = [
                $no++,
                "'" . ($item->nip ?? ''), // Force text format for NIP
                $item->nama ?? '',
                $item->skpd ?? $item->upt ?? '',
                $item->jabatan ?? '',
                $item->gaji_pokok ?? 0,
                $item->bpjs_4_persen ?? 0,
                $item->basis_hitung ?? '',
                $item->total_amoun ?? 0,
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->data) + 4;

        // Center and bold titles
        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal('center');

        // Format currency columns
        $sheet->getStyle("F5:G{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("I5:I{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');

        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            2 => ['font' => ['bold' => true, 'size' => 11]],
            4 => ['font' => ['bold' => true, 'border' => ['bottom' => ['style' => 'thin']]]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 22,
            'C' => 30,
            'D' => 30,
            'E' => 25,
            'F' => 15,
            'G' => 12,
            'H' => 10,
            'I' => 15,
        ];
    }
}
