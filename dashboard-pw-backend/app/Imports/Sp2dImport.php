<?php

namespace App\Imports;

use App\Models\Sp2dRealization;
use App\Models\SkpdMapping;
use App\Models\Skpd;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class Sp2dImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Skip header rows if any (SIPD has a complex header)
        // Check if row has a numeric 'nomor' or a valid SP2D format
        if (!isset($row['nomor_sp2d']) || empty($row['nomor_sp2d']) || $row['nomor_sp2d'] == 'Nomor SP2D') {
            return null;
        }

        $tanggalSp2d = $this->parseDate($row['tanggal_sp2d'] ?? null);
        if (!$tanggalSp2d)
            return null;

        $keterangan = $row['keterangan'] ?? '';
        $jenisData = $this->detectJenisData($keterangan);

        $namaSkpdSipd = $row['unit_skpd'] ?? '';
        $skpdId = $this->findSkpdId($namaSkpdSipd);

        return new Sp2dRealization([
            'nomor_sp2d' => $row['nomor_sp2d'],
            'tanggal_sp2d' => $tanggalSp2d->format('Y-m-d'),
            'nama_skpd_sipd' => $namaSkpdSipd,
            'skpd_id' => $skpdId,
            'keterangan' => $keterangan,
            'jenis_data' => $jenisData,
            'brutto' => $this->cleanNum($row['brutto'] ?? 0),
            'potongan' => $this->cleanNum($row['potongan'] ?? 0),
            'netto' => $this->cleanNum($row['netto'] ?? 0),
            'bulan' => $tanggalSp2d->month,
            'tahun' => $tanggalSp2d->year,
        ]);
    }

    private function parseDate($dateStr)
    {
        if (!$dateStr)
            return null;

        // Handle Indonesian month names
        $months = [
            'Januari' => 'January',
            'Februari' => 'February',
            'Maret' => 'March',
            'April' => 'April',
            'Mei' => 'May',
            'Juni' => 'June',
            'Juli' => 'July',
            'Agustus' => 'August',
            'September' => 'September',
            'Oktober' => 'October',
            'November' => 'November',
            'Desember' => 'December'
        ];

        $dateStr = str_replace(array_keys($months), array_values($months), $dateStr);

        try {
            return Carbon::parse($dateStr);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function detectJenisData($ket)
    {
        $ket = strtoupper($ket);
        if (str_contains($ket, 'PPPK'))
            return 'PPPK';
        if (str_contains($ket, 'TPP') || str_contains($ket, 'TAMBAHAN PENGHASILAN'))
            return 'TPP';
        return 'PNS'; // Default to PNS for "Gaji" or "Gaji Induk"
    }

    private function findSkpdId($name)
    {
        if (empty($name))
            return null;

        // 1. Try exact match in skpd_mapping
        $mapping = SkpdMapping::where('source_name', $name)->first();
        if ($mapping)
            return $mapping->skpd_id;

        // 2. Try exact match in skpds table
        $skpd = Skpd::where('nama_skpd', $name)->first();
        if ($skpd)
            return $skpd->id_skpd;

        // 3. Try fuzzy match (first 30 chars or similar)
        $skpdFuzzy = Skpd::where('nama_skpd', 'LIKE', substr($name, 0, 30) . '%')->first();
        return $skpdFuzzy ? $skpdFuzzy->id_skpd : null;
    }

    private function cleanNum($val)
    {
        if (is_string($val)) {
            return (float) str_replace([',', '.'], ['', '.'], $val);
        }
        return (float) $val;
    }

    public function headingRow(): int
    {
        return 1; // Maatwebsite uses 1-based index, our analyze showed Row 0 (nomor, tanggal_sp2d...)
    }
}
