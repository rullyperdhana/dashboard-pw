<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PaidSkpdExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    protected $data;
    protected $month;
    protected $year;
    protected $mode;   // 'detail' | 'summary'

    public function __construct(array $data, int $month, int $year, string $mode = 'summary')
    {
        $this->data = $data;
        $this->month = $month;
        $this->year = $year;
        $this->mode = $mode;
    }

    public function headings(): array
    {
        if ($this->mode === 'detail') {
            return [
                'No',
                'Kode SKPD',
                'Nama SKPD',
                'PEG',
                'GAPOK',
                'TJISTRI',
                'TJANAK',
                'TJTPP',
                'TJESELON',
                'TJFUNGSI',
                'TJBERAS',
                'TJPAJAK',
                'TJUMUM',
                'TBILAT',
                'KOTOR',
                'PIWP',
                'PIWP2',
                'PIWP8',
                'PPAJAK',
                'POTONGAN',
                'BERSIH',
            ];
        }

        return [
            'No',
            'Kode SKPD',
            'Nama SKPD',
            'Jumlah Pegawai',
            'Total Gaji Pokok',
            'Total Tunjangan',
            'Total Potongan',
            'Total Bersih',
        ];
    }

    public function array(): array
    {
        $rows = [];
        $no = 1;

        foreach ($this->data as $item) {
            $item = (array) $item;

            if ($this->mode === 'detail') {
                $rows[] = [
                    $no++,
                    $item['kode_skpd'] ?? '',
                    $item['nama_skpd'] ?? '',
                    $item['jumlah_pegawai'] ?? 0,
                    $item['gapok'] ?? 0,
                    $item['tj_istri'] ?? 0,
                    $item['tj_anak'] ?? 0,
                    $item['tj_tpp'] ?? 0,
                    $item['tj_eselon'] ?? 0,
                    $item['tj_fungsi'] ?? 0,
                    $item['tj_beras'] ?? 0,
                    $item['tj_pajak'] ?? 0,
                    $item['tj_umum'] ?? 0,
                    $item['tj_bilat'] ?? 0,
                    $item['kotor'] ?? 0,
                    $item['pot_iwp'] ?? 0,
                    $item['pot_iwp2'] ?? 0,
                    $item['pot_iwp8'] ?? 0,
                    $item['pot_pajak'] ?? 0,
                    $item['total_potongan'] ?? 0,
                    $item['bersih'] ?? 0,
                ];
            } else {
                $rows[] = [
                    $no++,
                    $item['kode_skpd'] ?? '',
                    $item['nama_skpd'] ?? '',
                    $item['employee_count'] ?? 0,
                    $item['total_gaji_pokok'] ?? 0,
                    $item['total_tunjangan'] ?? 0,
                    $item['total_potongan'] ?? 0,
                    $item['total_bersih'] ?? 0,
                ];
            }
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->data) + 1;

        if ($this->mode === 'detail') {
            // columns E-U are currency (cols 5-21)
            $sheet->getStyle("E2:U{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
        } else {
            $sheet->getStyle("E2:H{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
        }

        return [
            1 => ['font' => ['bold' => true, 'size' => 10]],
        ];
    }

    public function columnWidths(): array
    {
        if ($this->mode === 'detail') {
            return [
                'A' => 5,
                'B' => 14,
                'C' => 38,
                'D' => 6,
                'E' => 14,
                'F' => 12,
                'G' => 12,
                'H' => 12,
                'I' => 12,
                'J' => 12,
                'K' => 12,
                'L' => 12,
                'M' => 12,
                'N' => 12,
                'O' => 14,
                'P' => 12,
                'Q' => 12,
                'R' => 12,
                'S' => 12,
                'T' => 14,
                'U' => 15,
            ];
        }

        return [
            'A' => 5,
            'B' => 14,
            'C' => 40,
            'D' => 15,
            'E' => 18,
            'F' => 18,
            'G' => 18,
            'H' => 20,
        ];
    }
}
