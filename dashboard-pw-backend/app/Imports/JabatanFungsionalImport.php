<?php

namespace App\Imports;

use App\Models\RefJabatanFungsional;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class JabatanFungsionalImport implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    public function model(array $row)
    {
        // The header "KDFUNGSI=URUTAN DARI KELOMPOK FUNGSI" becomes this slug
        $kdfungsi = trim($row['kdfungsiurutan_dari_kelompok_fungsi'] ?? '');
        if ($kdfungsi === '') {
            return null;
        }

        // Parse TMT date (Excel serial number)
        $tmtJabatan = null;
        $rawTmt = $row['tmtjabatan'] ?? null;
        if ($rawTmt && is_numeric($rawTmt)) {
            try {
                $tmtJabatan = Date::excelToDateTimeObject((int) $rawTmt)->format('Y-m-d');
            } catch (\Exception $e) {
                $tmtJabatan = null;
            }
        }

        return new RefJabatanFungsional([
            'kdfungsi' => $kdfungsi,
            'nama_jabatan' => trim($row['nama_jabatan'] ?? 'Unknown'),
            'tunjangan' => (int) ($row['jml'] ?? 0),
            'usia_pensiun' => (int) ($row['pensiun'] ?? 0) ?: null,
            'kelompok_fungsi' => (int) ($row['kelompok_fungsi'] ?? 0) ?: null,
            'tmt_jabatan' => $tmtJabatan,
        ]);
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function batchSize(): int
    {
        return 500;
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
