<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BpjsRekonSkpdExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected $data;
    protected $month;
    protected $year;
    protected $grandTotal;

    public function __construct(array $data, int $month, int $year, array $grandTotal)
    {
        $this->data = $data;
        $this->month = $month;
        $this->year = $year;
        $this->grandTotal = $grandTotal;
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
            ['REKAPITULASI BPJS 4% PER SKPD'],
            ['PERIODE: ' . strtoupper($monthName) . ' ' . $this->year],
            [''],
            [
                'No',
                'SKPD',
                'Jumlah Pegawai',
                'Total Gaji Pokok',
                'BPJS 4%',
                'Total Gaji Bersih',
                'Pegawai < UMP'
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
                $item->skpd ?? '',
                $item->jumlah_pegawai ?? 0,
                $item->total_gaji_pokok ?? 0,
                $item->total_bpjs_4_persen ?? 0,
                $item->total_gaji_bersih ?? 0,
                $item->pegawai_bawah_ump ?? 0,
            ];
        }

        // Add Total Row
        $rows[] = [
            '',
            'TOTAL',
            $this->grandTotal['jumlah_pegawai'] ?? 0,
            $this->grandTotal['total_gaji_pokok'] ?? 0,
            $this->grandTotal['total_bpjs_4_persen'] ?? 0,
            $this->grandTotal['total_gaji_bersih'] ?? 0,
            $this->grandTotal['pegawai_bawah_ump'] ?? 0,
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->data) + 5; // Header is at row 4, data starts at 5

        // Center and bold titles
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');

        // Format alignments
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal('center');

        // Format currency columns
        $sheet->getStyle("D5:F{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');

        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            2 => ['font' => ['bold' => true, 'size' => 11]],
            4 => ['font' => ['bold' => true]],
            $lastRow => ['font' => ['bold' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,
            'B' => 50,
            'C' => 15,
            'D' => 20,
            'E' => 15,
            'F' => 20,
            'G' => 15,
        ];
    }
}
