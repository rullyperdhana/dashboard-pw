<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TppTemplateExport implements FromArray, WithHeadings, WithStyles
{
    public function array(): array
    {
        return [
            [
                1, 'Juli 2026', '198104122003122008', 'Dr. Ir. Hj GALUH TANTRI NARINDRA ST., MT',
                'SEKRETARIAT DAERAH', 'ASISTEN PEMERINTAHAN DAN KESEJAHTERAAN RAKYAT', 'PNS',
                36000000, 44346242, 36000000, 44346242, 7538861, 0, 37262, 37262, 35962738
            ],
            [
                2, 'Juli 2026', '198201012010012009', 'NAMA CONTOH PPPK',
                'DINAS KESEHATAN', 'FUNGSIONAL UMUM', 'PPPK',
                5000000, 5500000, 5000000, 5500000, 250000, 0, 50000, 50000, 4950000
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'NO',
            'Periode',
            'NIP',
            'Nama Lengkap',
            'Instansi / UPT',
            'Jabatan',
            'Status Pegawai',
            'TPP Bruto',
            'Bruto Plus',
            'TPP Netto',
            'DPP Pajak',
            'PPh 21',
            'Potongan TPP (Lainnya)',
            'Iuran IWP',
            'Total Potongan',
            'Yang Dibayarkan (Transfer)'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '008080'] // Teal color to match theme, or Blue as requested
                ],
            ],
        ];
    }
}
