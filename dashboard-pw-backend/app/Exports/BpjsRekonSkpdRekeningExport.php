<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BpjsRekonSkpdRekeningExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
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
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $monthName = $months[$this->month] ?? '';

        return [
            ['REKAPITULASI BPJS 4% PER SKPD & KODE REKENING'],
            ['PERIODE: ' . strtoupper($monthName) . ' ' . $this->year],
            [''],
            [
                'No',
                'SKPD',
                'Kode Rekening',
                'Kelompok Jabatan',
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
            $item = (array) $item;
            $rows[] = [
                $no++,
                $item['skpd'] ?? '',
                $item['kode_rekening'] ?? '',
                $item['nama_kelompok'] ?? '',
                $item['jumlah_pegawai'] ?? 0,
                $item['total_gaji_pokok'] ?? 0,
                $item['total_bpjs_4_persen'] ?? 0,
                $item['total_gaji_bersih'] ?? 0,
                $item['pegawai_bawah_ump'] ?? 0,
            ];
        }

        // Add Total Row
        $rows[] = [
            '',
            '',
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
        $lastRow = count($this->data) + 5;

        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A1:A2')->getAlignment()->setHorizontal('center');
        $sheet->getStyle("F5:H{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');

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
            'B' => 40,
            'C' => 18,
            'D' => 25,
            'E' => 15,
            'F' => 18,
            'G' => 15,
            'H' => 18,
            'I' => 12,
        ];
    }
}
