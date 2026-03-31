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

        $nip = trim($row['nip']);
        $exists = TaxStatus::where('nip', $nip)->where('year', $this->year)->exists();

        if ($exists) {
            return null;
        }

        return new TaxStatus([
            'nip' => $nip,
            'year' => $this->year,
            'nama' => $row['nama'] ?? null,
            'employee_type' => strtolower($row['tipe'] ?? 'pns'),
            'tax_status' => strtoupper($row['status_pajak'] ?? '-'),
            'is_manual' => true
        ]);
    }
}
