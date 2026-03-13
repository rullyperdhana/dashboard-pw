<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NikUpdateImport implements ToCollection, WithHeadingRow
{
    public $updatedCount = 0;
    public $notFoundCount = 0;
    public $errors = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $nip = ltrim(trim($row['nip'] ?? ''), "'");
            $nik = ltrim(trim($row['nik'] ?? ''), "'");

            if (empty($nip)) {
                continue;
            }

            $exists = DB::table('master_pegawai')->where('nip', $nip)->exists();

            if ($exists) {
                DB::table('master_pegawai')
                    ->where('nip', $nip)
                    ->update(['noktp' => $nik]);
                $this->updatedCount++;
            } else {
                $this->notFoundCount++;
                $this->errors[] = "Baris " . ($index + 2) . ": NIP {$nip} tidak ditemukan.";
            }
        }
    }
}
