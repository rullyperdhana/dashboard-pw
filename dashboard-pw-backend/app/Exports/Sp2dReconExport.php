<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Sp2dReconExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
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
            ['REKONSILIASI SIMGAJI VS SIPD'],
            ['Periode: ' . $this->month . ' - ' . $this->year],
            [''],
            [
                'SIMGAJI',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'SIPD',
                '',
                '',
                '',
                '',
                '',
                '',
                ''
            ],
            [
                'No',
                'SKPD SIMGAJI',
                'Kategori',
                'Brutto',
                'Potongan',
                'Netto',
                'GAJI PNS',
                'GAJI PPPK',
                'TPP PNS',
                'TPP PPPK',
                'Tgl Pembuatan',
                'Tgl Pencairan',
                'Nomor SP2D',
                'SKPD SIPD',
                'Keterangan',
                'Brutto',
                'Potongan',
                'Netto'
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
                $item['simgaji']['nama_skpd'] ?? '',
                $item['simgaji']['jenis_gaji'] ?? '',
                $item['simgaji']['brutto'] ?? 0,
                $item['simgaji']['potongan'] ?? 0,
                $item['simgaji']['netto'] ?? 0,
                $item['simgaji']['gaji_pns'] ?? 0,
                $item['simgaji']['gaji_pppk'] ?? 0,
                $item['simgaji']['tpp_pns'] ?? 0,
                $item['simgaji']['tpp_pppk'] ?? 0,
                $item['sipd']['tanggal_sp2d'] ?? '',
                $item['sipd']['tanggal_cair'] ?? '',
                $item['sipd']['nomor_sp2d'] ?? '',
                $item['sipd']['nama_skpd'] ?? '',
                $item['sipd']['keterangan'] ?? '',
                $item['sipd']['brutto'] ?? 0,
                $item['sipd']['potongan'] ?? 0,
                $item['sipd']['netto'] ?? 0,
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->data) + 5;

        // Merge cells for title
        $sheet->mergeCells('A1:R1');
        $sheet->mergeCells('A2:R2');

        // Merge for SIMGAJI and SIPD group headers
        $sheet->mergeCells('A4:J4');
        $sheet->mergeCells('K4:R4');

        // Center group headers
        $sheet->getStyle('A4:R4')->getAlignment()->setHorizontal('center');

        // Number formats for currency columns (D to J and P to R)
        $sheet->getStyle("D6:J{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("P6:R{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');

        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true, 'size' => 11]],
            4 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'E2EFDA']]],
            5 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => 'F2F2F2']]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 35,
            'C' => 15,
            'D' => 15,
            'E' => 15,
            'F' => 15,
            'G' => 15,
            'H' => 15,
            'I' => 15,
            'J' => 15,
            'K' => 15,
            'L' => 15,
            'M' => 25,
            'N' => 35,
            'O' => 40,
            'P' => 15,
            'Q' => 15,
            'R' => 15,
        ];
    }
}
