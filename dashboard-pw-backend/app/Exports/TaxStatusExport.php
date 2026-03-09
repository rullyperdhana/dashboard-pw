<?php

namespace App\Exports;

use App\Models\TaxStatus;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TaxStatusExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithColumnFormatting, WithMapping
{
    protected $year;
    protected $type;

    public function __construct($year, $type = null)
    {
        $this->year = $year;
        $this->type = $type;
    }

    public function map($row): array
    {
        return [
            $row->nip . " ", // Add space to force string in some viewers
            $row->nama,
            $row->employee_type,
            $row->tax_status,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function collection()
    {
        $query = TaxStatus::select('nip', 'nama', 'employee_type', 'tax_status')
            ->where('year', $this->year);

        if ($this->type) {
            $query->where('employee_type', $this->type);
        }

        return $query->orderBy('nama')->get();
    }

    public function headings(): array
    {
        return [
            'NIP',
            'NAMA',
            'TIPE',
            'STATUS_PAJAK'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'] // Indigo/Primary
                ],
            ],
        ];
    }
}
