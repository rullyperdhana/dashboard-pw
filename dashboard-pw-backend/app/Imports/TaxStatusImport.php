<?php

namespace App\Imports;

use App\Models\TaxStatus;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TaxStatusImport implements ToModel, WithHeadingRow
{
    protected $year;

    public function __construct($year)
    {
        $this->year = $year;
    }

    public function model(array $row)
    {
        if (empty($row['nip'])) {
            return null;
        }

        return TaxStatus::updateOrCreate(
            [
                'nip' => trim($row['nip']),
                'year' => $this->year
            ],
            [
                'nama' => $row['nama'] ?? null,
                'employee_type' => strtolower($row['tipe'] ?? 'pns'),
                'tax_status' => strtoupper($row['status_pajak'] ?? '-'),
                'is_manual' => true
            ]
        );
    }
}
