<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ThrExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected $data;
    protected $year;
    protected $nMonths;
    protected $thrMonthName;
    protected $calculationBasis;

    public function __construct(array $data, int $year, int $nMonths, string $thrMonthName, ?string $calculationBasis = null)
    {
        $this->data = $data;
        $this->year = $year;
        $this->nMonths = $nMonths;
        $this->thrMonthName = $thrMonthName;
        $this->calculationBasis = $calculationBasis ?? "Gaji Pokok Pebruari ({$nMonths}/12)";
    }

    public function headings(): array
    {
        return [
            ["DAFTAR PEMBAYARAN THR PEGAWAI PPPK PARUH WAKTU"],
            ["TAHUN {$this->year} (PEMBAYARAN BULAN " . strtoupper($this->thrMonthName) . ")"],
            ["DASAR PERHITUNGAN: " . strtoupper($this->calculationBasis)],
            [''],
            [
                'No',
                'NIP',
                'Nama',
                'Jabatan',
                'Gaji Pokok Basis',
                'Masa Kerja (Bulan)',
                'Besaran THR'
            ]
        ];
    }

    public function array(): array
    {
        $rows = [];
        $grandTotal = 0;

        foreach ($this->data as $skpd) {
            // Level 1: SKPD Header
            $rows[] = ['SKPD: ' . $skpd['skpd_name'], '', '', '', '', '', ''];

            foreach ($skpd['sub_giat_groups'] as $subGiat) {
                // Level 2: Sub Kegiatan Header
                $rows[] = ['', 'Sub Kegiatan: ' . $subGiat['sub_giat_name'], '', '', '', '', ''];

                $no = 1;
                foreach ($subGiat['employees'] as $item) {
                    $rows[] = [
                        $no++,
                        "'" . ($item['nip'] ?? ''),
                        $item['nama'] ?? '',
                        $item['jabatan'] ?? '',
                        $item['gapok_basis'] ?? 0,
                        $item['n_months'] ?? 0,
                        $item['thr_amount'] ?? 0,
                    ];
                }

                // Subtotal Sub Kegiatan
                $rows[] = [
                    '',
                    'SUBTOTAL SUB KEGIATAN',
                    '',
                    '',
                    '',
                    '',
                    $subGiat['subtotal_thr']
                ];
                $rows[] = ['']; // Spacer
            }

            // Total SKPD
            $rows[] = [
                'TOTAL SKPD: ' . $skpd['skpd_name'],
                '',
                '',
                '',
                '',
                '',
                $skpd['total_thr_skpd']
            ];
            $rows[] = ['']; // Big Spacer
            $rows[] = [''];

            $grandTotal += $skpd['total_thr_skpd'];
        }

        // Grand Total row
        $rows[] = [
            'TOTAL KESELURUHAN THR',
            '',
            '',
            '',
            '',
            '',
            $grandTotal
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        // Styling headers and summary rows
        $sheet->mergeCells("A1:G1");
        $sheet->mergeCells("A2:G2");
        $sheet->mergeCells("A3:G3");

        $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal('center');

        // Styles for header
        $sheet->getStyle('A5:G5')->getFont()->setBold(true);
        $sheet->getStyle('A5:G5')->getFill()->setFillType('solid')->getStartColor()->setRGB('E9ECEF');

        // Loop through data to find subtotal and group header rows for styling
        $rowIdx = 6;
        $styles = [
            1 => ['font' => ['bold' => true, 'size' => 12]],
            2 => ['font' => ['bold' => true, 'size' => 11]],
            3 => ['font' => ['italic' => true]],
        ];

        foreach ($this->data as $skpd) {
            // SKPD Header
            $sheet->mergeCells("A{$rowIdx}:G{$rowIdx}");
            $sheet->getStyle("A{$rowIdx}")->getFont()->setBold(true);
            $sheet->getStyle("A{$rowIdx}")->getFill()->setFillType('solid')->getStartColor()->setRGB('D1ECF1');
            $rowIdx++;

            foreach ($skpd['sub_giat_groups'] as $subGiat) {
                // Sub Giat Header
                $sheet->mergeCells("B{$rowIdx}:G{$rowIdx}");
                $sheet->getStyle("B{$rowIdx}")->getFont()->setBold(true);
                $rowIdx++;

                // Data rows
                $dataCount = count($subGiat['employees']);
                if ($dataCount > 0) {
                    $sheet->getStyle("E{$rowIdx}:G" . ($rowIdx + $dataCount - 1))
                        ->getNumberFormat()->setFormatCode('#,##0');
                    $rowIdx += $dataCount;
                }

                // Subtotal Row
                $sheet->getStyle("A{$rowIdx}:G{$rowIdx}")->getFont()->setBold(true);
                $sheet->getStyle("G{$rowIdx}")->getNumberFormat()->setFormatCode('#,##0');
                $rowIdx++;

                // Spacer Row
                $rowIdx++;
            }

            // Total SKPD Row
            $sheet->getStyle("A{$rowIdx}:G{$rowIdx}")->getFont()->setBold(true);
            $sheet->getStyle("G{$rowIdx}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("A{$rowIdx}:G{$rowIdx}")->getFill()->setFillType('solid')->getStartColor()->setRGB('E2E3E5');
            $rowIdx++;

            // Big Spacers
            $rowIdx += 2;
        }

        // Grand Total Row
        $sheet->getStyle("A{$rowIdx}:G{$rowIdx}")->getFont()->setBold(true);
        $sheet->getStyle("A{$rowIdx}")->getFont()->setSize(12);
        $sheet->getStyle("G{$rowIdx}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$rowIdx}:G{$rowIdx}")->getFill()->setFillType('solid')->getStartColor()->setRGB('CCE5FF');

        return $styles;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 22,
            'C' => 35,
            'D' => 30,
            'E' => 18,
            'F' => 18,
            'G' => 20,
        ];
    }
}
