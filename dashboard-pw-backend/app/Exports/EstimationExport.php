<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EstimationExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected $data;
    protected $month;
    protected $year;
    protected $type;
    protected $skpdName;

    public function __construct(array $data, int $month, int $year, string $type, string $skpdName = '')
    {
        $this->data = $data;
        $this->month = $month;
        $this->year = $year;
        $this->type = $type;
        $this->skpdName = $skpdName;
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

        $typeLabel = match ($this->type) {
            'pns' => 'PNS',
            'pppk' => 'PPPK Penuh Waktu',
            'pppk_pw' => 'PPPK Paruh Waktu',
            default => strtoupper($this->type)
        };

        $headings = [
            ["ESTIMASI PEMBAYARAN JKK, JKM & BPJS KESEHATAN - {$typeLabel}"],
            ["PERIODE: " . strtoupper($monthName) . " {$this->year}"],
        ];

        if ($this->skpdName) {
            $headings[] = ["SKPD: {$this->skpdName}"];
        }

        $headings[] = [''];

        if ($this->type === 'pppk_pw') {
            $headings[] = [
                'No',
                'NIP',
                'Nama',
                'Jabatan',
                'SKPD',
                'Gaji Pokok',
                'Tunjangan',
                'BPJS Base',
                'JKK',
                'JKM',
                'BPJS Kes 4%',
                'Total Estimasi'
            ];
        } else {
            $headings[] = [
                'No',
                'NIP',
                'Nama',
                'Jabatan',
                'SKPD',
                'Gaji Pokok',
                'Tunj. Keluarga',
                'Tunj. Jabatan/Umum',
                'TPP',
                'BPJS Base',
                'JKK',
                'JKM',
                'BPJS Kes 4%',
                'Total Estimasi'
            ];
        }

        return $headings;
    }

    public function array(): array
    {
        $rows = [];
        $no = 1;
        $currentJabatan = null;

        foreach ($this->data as $item) {
            if ($this->type === 'pppk_pw') {
                $jabatan = $item['jabatan'] ?? '';

                // Add Group Header for Jabatan
                if ($jabatan !== $currentJabatan) {
                    $currentJabatan = $jabatan;

                    // Add an empty row between groups if not the first group
                    if (!empty($rows)) {
                        $rows[] = array_fill(0, 12, '');
                    }

                    $rows[] = [
                        '', // No
                        '', // NIP
                        '', // Nama
                        strtoupper($jabatan), // Jabatan (Group Title)
                        '', // SKPD
                        '', // Gapok
                        '', // Tunj
                        '', // BPJS Base
                        '', // JKK
                        '', // JKM
                        '', // BPJS Kes
                        '', // Total
                    ];
                }

                $rows[] = [
                    $no++,
                    "'" . ($item['nip'] ?? ''),
                    $item['nama'] ?? '',
                    $item['jabatan'] ?? '',
                    $item['skpd'] ?? '',
                    $item['gaji_pokok'] ?? 0,
                    $item['tunjangan'] ?? 0,
                    $item['bpjs_base'] ?? 0,
                    $item['jkk'] ?? 0,
                    $item['jkm'] ?? 0,
                    $item['bpjs_kesehatan'] ?? 0,
                    $item['total_estimation'] ?? 0,
                ];
            } else {
                $rows[] = [
                    $no++,
                    "'" . ($item['nip'] ?? ''),
                    $item['nama'] ?? '',
                    $item['jabatan'] ?? '',
                    $item['skpd'] ?? '',
                    $item['gaji_pokok'] ?? 0,
                    $item['tunj_keluarga'] ?? 0,
                    $item['tunj_jabatan'] ?? 0,
                    $item['tunj_tpp'] ?? 0,
                    $item['bpjs_base'] ?? 0,
                    $item['jkk'] ?? 0,
                    $item['jkm'] ?? 0,
                    $item['bpjs_kesehatan'] ?? 0,
                    $item['total_estimation'] ?? 0,
                ];
            }
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $headerRows = $this->skpdName ? 5 : 4;
        $lastCol = $this->type === 'pppk_pw' ? 'L' : 'N';
        $currStart = $this->type === 'pppk_pw' ? 'F' : 'F'; // Both start at F now

        // Get total data rows (including grouping rows)
        $arrayData = $this->array();
        $lastRow = count($arrayData) + $headerRows;

        // Merge title rows
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->mergeCells("A2:{$lastCol}2");
        if ($this->skpdName) {
            $sheet->mergeCells("A3:{$lastCol}3");
        }

        $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal('center');

        // Format currency columns
        $dataStart = $headerRows + 1;
        $sheet->getStyle("{$currStart}{$dataStart}:{$lastCol}{$lastRow}")
            ->getNumberFormat()->setFormatCode('#,##0');

        $styles = [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            2 => ['font' => ['bold' => true, 'size' => 11]],
            $headerRows => ['font' => ['bold' => true]],
        ];

        // Bold the grouping rows
        foreach ($arrayData as $index => $row) {
            if ($this->type === 'pppk_pw' && $row[0] === '' && $row[3] !== '') {
                $rowIndex = $headerRows + $index + 1;
                $styles[$rowIndex] = [
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E3F2FD']
                    ]
                ];
            }
        }

        return $styles;
    }

    public function columnWidths(): array
    {
        if ($this->type === 'pppk_pw') {
            return [
                'A' => 5,
                'B' => 22,
                'C' => 30,
                'D' => 25,
                'E' => 30, // SKPD
                'F' => 15,
                'G' => 15,
                'H' => 15,
                'I' => 12,
                'J' => 12,
                'K' => 12,
                'L' => 15,
            ];
        }
        return [
            'A' => 5,
            'B' => 22,
            'C' => 30,
            'D' => 25,
            'E' => 30,
            'F' => 15,
            'G' => 15,
            'H' => 18,
            'I' => 15,
            'J' => 15,
            'K' => 12,
            'L' => 12,
            'M' => 12,
            'N' => 15,
        ];
    }
}
