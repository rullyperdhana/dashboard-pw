<?php

namespace App\Imports;

use App\Models\BkdPegawai;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Carbon\Carbon;

class BkdImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts
{
    protected $batch;

    public function __construct($batch = null)
    {
        $this->batch = $batch ?? now()->format('YmdHis');
    }

    public function model(array $row)
    {
        // Map heading columns (case-insensitive)
        $nip = $row['nip'] ?? null;
        $nama = $row['nama'] ?? null;
        $nik = $row['nik'] ?? null;
        $jabatan = $row['jabatan'] ?? null;
        $golongan = $row['gol'] ?? $row['golongan'] ?? null;
        $tglLahir = $row['tgl_lahir'] ?? $row['tgl lahir'] ?? null;
        $jenisKelamin = $row['jenis_kelamin'] ?? $row['jenis kelamin'] ?? null;

        if (!$nip || !trim($nip)) {
            return null;
        }

        // Clean NIP (remove apostrophes/spaces)
        $nip = trim(str_replace("'", '', $nip));
        $nik = $nik ? trim(str_replace("'", '', $nik)) : null;

        // Parse date
        $parsedDate = null;
        if ($tglLahir) {
            try {
                $parsedDate = Carbon::parse($tglLahir)->format('Y-m-d');
            } catch (\Exception $e) {
                // Try dd-mm-yyyy
                try {
                    $parsedDate = Carbon::createFromFormat('d-m-Y', $tglLahir)->format('Y-m-d');
                } catch (\Exception $e2) {
                    $parsedDate = null;
                }
            }
        }

        // Clean golongan (e.g. "IV/e" -> "IV/e")
        $golongan = $golongan ? trim($golongan) : null;

        // Normalize jenis kelamin
        if ($jenisKelamin) {
            $jk = strtoupper(trim($jenisKelamin));
            if (str_contains($jk, 'LAKI')) $jenisKelamin = 'L';
            elseif (str_contains($jk, 'PEREMPUAN') || str_contains($jk, 'WANITA')) $jenisKelamin = 'P';
            else $jenisKelamin = $jk;
        }

        return new BkdPegawai([
            'nip'           => $nip,
            'nama'          => $nama ? trim($nama) : null,
            'nik'           => $nik,
            'jabatan'       => $jabatan ? trim($jabatan) : null,
            'golongan'      => $golongan,
            'tgl_lahir'     => $parsedDate,
            'jenis_kelamin' => $jenisKelamin,
            'upload_batch'  => $this->batch,
        ]);
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function batchSize(): int
    {
        return 500;
    }
}
