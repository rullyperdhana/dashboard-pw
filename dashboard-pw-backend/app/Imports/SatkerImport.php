<?php

namespace App\Imports;

use App\Models\Satker;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SatkerImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        $data = [];
        $now = now();

        foreach ($rows as $row) {
            $kdskpd = trim($row['kdskpd'] ?? '');
            $kdsatker = trim($row['kdsatker'] ?? '');

            if (empty($kdskpd) || empty($kdsatker)) {
                continue;
            }

            $data[] = [
                'kdskpd' => $kdskpd,
                'kdsatker' => $kdsatker,
                'nmskpd' => trim($row['nmskpd'] ?? ''),
                'nmsatker' => trim($row['nmsatker'] ?? ''),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($data)) {
            // Upsert in batches of 500 for safety
            foreach (array_chunk($data, 500) as $chunk) {
                Satker::upsert(
                    $chunk,
                    ['kdskpd', 'kdsatker'], // Unique keys
                    ['nmskpd', 'nmsatker', 'updated_at'] // Fields to update
                );
            }
        }
    }
}
