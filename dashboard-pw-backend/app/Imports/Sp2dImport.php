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
    protected $targetType;
    protected $isPreview;
    public $previewData = [];

    public function __construct($targetType = null, $isPreview = false)
    {
        $this->targetType = $targetType;
        $this->isPreview = $isPreview;
    }

    public function model(array $row)
    {
        // Skip header rows if any (SIPD has a complex header)
        if (!isset($row['nomor_sp2d']) || empty($row['nomor_sp2d']) || $row['nomor_sp2d'] == 'Nomor SP2D') {
            return null;
        }

        $tanggalSp2d = $this->parseDate($row['tanggal_sp2d'] ?? null);
        if (!$tanggalSp2d)
            return null;

        $tanggalCair = $this->parseDate($row['tanggal_cair'] ?? $row['tgl_cair'] ?? null);

        $keterangan = $row['keterangan'] ?? '';
        
        // Use explicit target type (e.g., 'PPPK-PW') if provided, otherwise auto-detect
        $jenisData = $this->targetType ?: $this->detectJenisData($keterangan);

        // SKIP if not payroll
        if (!$jenisData) {
            return null;
        }

        $namaSkpdSipd = $row['unit_skpd'] ?? '';
        $skpdId = $this->findSkpdId($namaSkpdSipd);

        if ($this->isPreview) {
            $this->previewData[] = [
                'nomor_sp2d' => $row['nomor_sp2d'],
                'tanggal_sp2d' => $tanggalSp2d->format('Y-m-d'),
                'nama_skpd' => $namaSkpdSipd,
                'skpd_id' => $skpdId,
                'skpd_match' => $skpdId ? \App\Models\Skpd::find($skpdId)->nama_skpd : null,
                'jenis_data' => $jenisData,
                'netto' => $this->cleanNum($row['netto'] ?? 0),
                'keterangan' => $keterangan
            ];
            return null;
        }

        return Sp2dRealization::updateOrCreate(
            [
                'nomor_sp2d' => $row['nomor_sp2d'],
                'jenis_data' => $jenisData,
            ],
            [
                'tanggal_sp2d' => $tanggalSp2d->format('Y-m-d'),
                'tanggal_cair' => $tanggalCair ? $tanggalCair->format('Y-m-d') : null,
                'nama_skpd_sipd' => $namaSkpdSipd,
                'skpd_id' => $skpdId,
                'keterangan' => $keterangan,
                'brutto' => $this->cleanNum($row['brutto'] ?? 0),
                'potongan' => $this->cleanNum($row['potongan'] ?? 0),
                'netto' => $this->cleanNum($row['netto'] ?? 0),
                'bulan' => $tanggalSp2d->month,
                'tahun' => $tanggalSp2d->year,
            ]
        );
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

        // 1. TPP / Tunjangan Kinerja
        if (str_contains($ket, 'TPP ') || str_contains($ket, ' TAMBAHAN PENGHASILAN') || str_contains($ket, 'TUNJANGAN KINERJA')) {
            // Check for PPPK-PW TPP if any
            if (str_contains($ket, 'PW') || str_contains($ket, 'PARUH')) {
                return 'PPPK-PW-TPP';
            }
            return 'TPP-INDUK';
        }

        // 2. PPPK Paruh Waktu (PPPK-PW) - Check this BEFORE standard PPPK
        if ((str_contains($ket, 'PPPK PW') || str_contains($ket, 'P3K PW') || str_contains($ket, 'PPPK PARUH WAKTU') || str_contains($ket, 'P3K PARUH WAKTU')) && 
            (str_contains($ket, 'GAJI') || str_contains($ket, 'PEMBAYARAN BELANJA PEGAWAI') || str_contains($ket, 'BELANJA GAJI') || str_contains($ket, 'HONOR'))) {
            
            if (str_contains($ket, 'SUSULAN'))
                return 'PPPK-PW-SUSULAN';
            if (str_contains($ket, 'KEKURANGAN'))
                return 'PPPK-PW-KEKURANGAN';
            if (str_contains($ket, 'THR'))
                return 'PPPK-PW-THR';
            if (str_contains($ket, '14'))
                return 'PPPK-PW-THR';
            if (str_contains($ket, '13'))
                return 'PPPK-PW-G13';
                
            return 'PPPK-PW-INDUK';
        }

        // 3. PPPK Penuh Waktu / P3K Gaji
        if ((str_contains($ket, 'PPPK') || str_contains($ket, 'P3K')) && (str_contains($ket, 'GAJI') || str_contains($ket, 'PEMBAYARAN BELANJA PEGAWAI') || str_contains($ket, 'BELANJA GAJI'))) {
            if (str_contains($ket, 'SUSULAN'))
                return 'PPPK-SUSULAN';
            if (str_contains($ket, 'KEKURANGAN'))
                return 'PPPK-KEKURANGAN';
            if (str_contains($ket, 'TERUSAN'))
                return 'PPPK-TERUSAN';
            if (str_contains($ket, 'THR') || str_contains($ket, 'G14'))
                return 'PPPK-THR';
            if (str_contains($ket, '13'))
                return 'PPPK-G13';
            return 'PPPK-INDUK';
        }

        // 4. PNS / ASN / DPRD Gaji
        if (
            (str_contains($ket, 'GAJI') || str_contains($ket, 'PEMBAYARAN BELANJA PEGAWAI') || str_contains($ket, 'BELANJA GAJI')) &&
            (str_contains($ket, 'PNS') || str_contains($ket, 'ASN') || str_contains($ket, 'DPRD') || str_contains($ket, 'INDUK') || str_contains($ket, 'SUSULAN') || str_contains($ket, 'TERUSAN') || str_contains($ket, 'KEPALA DAERAH') || str_contains($ket, 'KEKURANGAN'))
        ) {

            if (str_contains($ket, 'SUSULAN'))
                return 'PNS-SUSULAN';
            if (str_contains($ket, 'KEKURANGAN'))
                return 'PNS-KEKURANGAN';
            if (str_contains($ket, 'TERUSAN'))
                return 'PNS-TERUSAN';
            if (str_contains($ket, 'THR') || str_contains($ket, 'G14'))
                return 'PNS-THR';
            if (str_contains($ket, '13'))
                return 'PNS-G13';
            return 'PNS-INDUK';
        }

        // 5. Iuran JKK/JKM/BPJS
        if (str_contains($ket, 'IURAN') && (str_contains($ket, 'JKK') || str_contains($ket, 'JKM') || str_contains($ket, 'BPJS') || str_contains($ket, 'KES'))) {
             // Try to detect if it's for PPPK-PW
             if (str_contains($ket, 'PW') || str_contains($ket, 'PARUH')) {
                 return 'PPPK-PW-INDUK';
             }
             if (str_contains($ket, 'PPPK') || str_contains($ket, 'P3K')) {
                 return 'PPPK-INDUK';
             }
            return 'PNS-INDUK';
        }

        // 6. Fallback for generic Gaji
        if (str_contains($ket, 'GAJI') && !str_contains($ket, 'UANG PERSEDIAAN') && !str_contains($ket, 'TAMBAHAN UANG')) {
            return 'PNS-INDUK';
        }

        return null;
    }

    private function findSkpdId($name)
    {
        if (empty($name))
            return null;

        $originalName = trim($name);
        $normalizedSearch = $this->normalizeName($name);

        // 1. Try exact match in skpd_mapping
        $mapping = SkpdMapping::where('source_name', $originalName)->first();
        if ($mapping)
            return $mapping->skpd_id;

        // 2. Try exact match in skpds table
        $skpd = Skpd::where('nama_skpd', $originalName)->first();
        if ($skpd)
            return $skpd->id_skpd;

        // 3. Try Normalized match
        $allSkpds = Skpd::all();
        foreach ($allSkpds as $s) {
            if ($this->normalizeName($s->nama_skpd) === $normalizedSearch) {
                return $s->id_skpd;
            }
        }

        // 4. Try fuzzy match (first 30 chars)
        $skpdFuzzy = Skpd::where('nama_skpd', 'LIKE', substr($originalName, 0, 30) . '%')->first();
        return $skpdFuzzy ? $skpdFuzzy->id_skpd : null;
    }

    private function normalizeName($name)
    {
        $name = strtoupper(trim($name));
        $name = str_replace(['DINAS ', 'BADAN ', 'SEKRETARIAT ', 'DAERAH ', 'PROVINSI ', 'KALIMANTAN SELATAN', 'PROV. ', 'KALSEL'], '', $name);
        $name = preg_replace('/[^A-Z0-9]/', '', $name); // Strip everything else
        return $name;
    }

    private function cleanNum($val)
    {
        if (is_numeric($val)) {
            return (float) $val;
        }

        if (is_string($val)) {
            // Remove any whitespace and currency symbols
            $val = preg_replace('/[^\d,.]/', '', $val);

            // Indonesian / European: 1.234.567,89
            // US / Standard: 1,234,567.89

            // If there's both a comma and a period
            if (strpos($val, ',') !== false && strpos($val, '.') !== false) {
                if (strrpos($val, ',') > strrpos($val, '.')) {
                    // Indonesian: Comma is the last separator, so it's the decimal
                    $val = str_replace('.', '', $val);
                    $val = str_replace(',', '.', $val);
                } else {
                    // US: Period is the last separator
                    $val = str_replace(',', '', $val);
                }
            } elseif (strpos($val, ',') !== false) {
                // Only comma: Is it thousands (US) or decimal (ID)? 
                // e.g. "1,234" vs "1,23"
                // If it's 3 digits after comma, it might be thousands.
                // But usually in SIPD, a single comma is a decimal.
                if (preg_match('/,\d{3}$/', $val) && !preg_match('/,\d{3,}/', $val)) {
                    // Looks like thousands (US)
                    $val = str_replace(',', '', $val);
                } else {
                    // Assume decimal (ID)
                    $val = str_replace(',', '.', $val);
                }
            } elseif (strpos($val, '.') !== false) {
                // Only periods: If multiple, they are thousands (ID)
                if (substr_count($val, '.') > 1) {
                    $val = str_replace('.', '', $val);
                }
                // If single period, it's usually decimal (US style) in Excel.
                // No change needed for single period.
            }

            return (float) $val;
        }
        return 0;
    }

    public function headingRow(): int
    {
        return 1; // Maatwebsite uses 1-based index, our analyze showed Row 0 (nomor, tanggal_sp2d...)
    }
}
