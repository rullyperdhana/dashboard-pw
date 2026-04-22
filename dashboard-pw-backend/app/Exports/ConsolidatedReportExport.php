<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ConsolidatedReportExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    protected $data;
    protected $month;
    protected $year;

    public function __construct($data, $month, $year)
    {
        $this->data = $data;
        $this->month = $month;
        $this->year = $year;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function title(): string
    {
        return "Laporan Konsolidasi {$this->month}-{$this->year}";
    }

    public function headings(): array
    {
        return [
            'NO',
            'NIP',
            'NAMA',
            'SKPD',
            'GAJI BRUTO (TANPA TPP)',
            'TPP',
            'TPG',
            'TOTAL BRUTO'
        ];
    }

    public function map($row): array
    {
        static $no = 0;
        $no++;
        
        return [
            $no,
            "'" . $row->nip, // Force string for NIP
            $row->nama,
            $row->skpd,
            $row->gaji_bruto,
            $row->tpp,
            $row->tpg,
            $row->total_bruto
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
