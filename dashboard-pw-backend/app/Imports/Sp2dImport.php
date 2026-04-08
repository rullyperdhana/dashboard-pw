<?php

namespace App\Imports;

use App\Models\Sp2dRealization;
use App\Models\SkpdMapping;
use App\Models\Skpd;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class Sp2dImport implements ToCollection
{
    protected $targetType;
    protected $isPreview;
    public $previewData = [];

    public function __construct($targetType = null, $isPreview = false)
    {
        $this->targetType = $targetType;
        $this->isPreview = $isPreview;
    }

    public function collection(Collection $rows)
    {
        $headerIndex = -1;
        $mapping = [];

        // 1. Find Header Row (Scan first 20 rows)
        foreach ($rows as $idx => $row) {
            $rowValues = $row->map(fn($v) => strtoupper(trim((string)$v)))->toArray();
            
            // Check for keywords in row
            $isHeader = false;
            foreach ($rowValues as $val) {
                if ($val && (str_contains($val, 'NOMOR SP2D') || str_contains($val, 'NO. SP2D') || $val === 'NOMOR' || $val === 'NO SP2D')) {
                    $isHeader = true;
                    break;
                }
            }

            if ($isHeader) {
                $headerIndex = $idx;
                $mapping = $this->createMapping($rowValues);
                break;
            }
        }

        if ($headerIndex === -1 || empty($mapping)) {
            // Fallback: If no header found, try to use row 0 if it looks like data or just fail
             return;
        }

        // 2. Process Data Rows
        foreach ($rows as $idx => $row) {
            if ($idx <= $headerIndex) continue;

            $data = $this->mapRow($row, $mapping);
            
            // Skip empty/invalid rows
            if (empty($data['nomor_sp2d']) || $data['nomor_sp2d'] === 'NOMOR SP2D') continue;

            $tanggalSp2d = $this->parseDate($data['tanggal_sp2d'] ?? null);
            if (!$tanggalSp2d) continue;

            $tanggalCair = $this->parseDate($data['tanggal_cair'] ?? null);
            $keterangan = $data['keterangan'] ?? '';
            
            $jenisData = $this->targetType ?: $this->detectJenisData($keterangan);
            
            // Skip if not payroll and auto-detecting
            if (!$jenisData) continue;

            $namaSkpdSipd = $data['unit_skpd'] ?? '';
            $skpdId = $this->findSkpdId($namaSkpdSipd);

            $brutto = $this->cleanNum($data['brutto'] ?? 0);
            $potongan = $this->cleanNum($data['potongan'] ?? 0);
            $netto = $this->cleanNum($data['netto'] ?? 0);

            if ($this->isPreview) {
                $this->previewData[] = [
                    'nomor_sp2d' => $data['nomor_sp2d'],
                    'tanggal_sp2d' => $tanggalSp2d->format('Y-m-d'),
                    'nama_skpd' => $namaSkpdSipd,
                    'skpd_id' => $skpdId,
                    'skpd_match' => $skpdId ? Skpd::find($skpdId)->nama_skpd : null,
                    'jenis_data' => $jenisData,
                    'netto' => $netto,
                    'keterangan' => $keterangan
                ];
            } else {
                Sp2dRealization::updateOrCreate(
                    [
                        'nomor_sp2d' => $data['nomor_sp2d'],
                        'jenis_data' => $jenisData,
                    ],
                    [
                        'tanggal_sp2d' => $tanggalSp2d->format('Y-m-d'),
                        'tanggal_cair' => $tanggalCair ? $tanggalCair->format('Y-m-d') : null,
                        'nama_skpd_sipd' => $namaSkpdSipd,
                        'skpd_id' => $skpdId,
                        'keterangan' => $keterangan,
                        'brutto' => $brutto,
                        'potongan' => $potongan,
                        'netto' => $netto,
                        'bulan' => $tanggalSp2d->month,
                        'tahun' => $tanggalSp2d->year,
                    ]
                );
            }
        }
    }

    private function createMapping(array $headerValues)
    {
        $map = [
            'nomor_sp2d' => -1,
            'tanggal_sp2d' => -1,
            'tanggal_cair' => -1,
            'unit_skpd' => -1,
            'keterangan' => -1,
            'brutto' => -1,
            'potongan' => -1,
            'netto' => -1,
        ];

        foreach ($headerValues as $i => $val) {
            if (empty($val)) continue;
            $valUpper = strtoupper($val);

            if (str_contains($valUpper, 'NOMOR') || str_contains($valUpper, 'NO. SP2D') || $valUpper === 'NO') $map['nomor_sp2d'] = $i;
            if (str_contains($valUpper, 'TANGGAL SP2D') || str_contains($valUpper, 'TGL SP2D')) $map['tanggal_sp2d'] = $i;
            if (str_contains($valUpper, 'TANGGAL CAIR') || str_contains($valUpper, 'TGL CAIR')) $map['tanggal_cair'] = $i;
            if (str_contains($valUpper, 'UNIT') || str_contains($valUpper, 'SKPD')) $map['unit_skpd'] = $i;
            if (str_contains($valUpper, 'KETERANGAN') || $valUpper === 'URAIAN') $map['keterangan'] = $i;
            if (str_contains($valUpper, 'BRUTO') || str_contains($valUpper, 'KOTOR') || str_contains($valUpper, 'TOTAL') || str_contains($valUpper, 'NILAI BRUTO')) $map['brutto'] = $i;
            if (str_contains($valUpper, 'POTONGAN') || str_contains($valUpper, 'POT')) $map['potongan'] = $i;
            if (str_contains($valUpper, 'NETTO') || str_contains($valUpper, 'BERSIH') || str_contains($valUpper, 'JUMLAH DIBAYARKAN')) $map['netto'] = $i;
        }

        // Critical overrides for common SIPD Excel layouts if mapping failed
        if ($map['nomor_sp2d'] === -1) $map['nomor_sp2d'] = 1; // Often 2nd col
        if ($map['tanggal_sp2d'] === -1) $map['tanggal_sp2d'] = 2;
        if ($map['netto'] === -1) $map['netto'] = count($headerValues) - 1; // Often last col

        // Fallback: if brutto column not found, use netto column index
        if ($map['brutto'] === -1 && $map['netto'] !== -1) $map['brutto'] = $map['netto'];

        return $map;
    }

    private function mapRow(Collection $row, array $map)
    {
        $d = [];
        foreach ($map as $key => $idx) {
            $d[$key] = ($idx !== -1) ? ($row[$idx] ?? null) : null;
        }
        return $d;
    }

    private function parseDate($dateStr)
    {
        if (!$dateStr) return null;

        // If it's already a Carbon or Date object from Excel
        if ($dateStr instanceof \DateTimeInterface) {
            return Carbon::instance($dateStr);
        }

        // Handle numeric date from Excel
        if (is_numeric($dateStr) && $dateStr > 40000) {
            return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateStr));
        }

        $dateStr = (string)$dateStr;

        // Handle Indonesian month names
        $months = [
            'Januari' => 'January', 'Februari' => 'February', 'Maret' => 'March',
            'April' => 'April', 'Mei' => 'May', 'Juni' => 'June',
            'Juli' => 'July', 'Agustus' => 'August', 'September' => 'September',
            'Oktober' => 'October', 'November' => 'November', 'Desember' => 'December'
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
            if (str_contains($ket, 'PW') || str_contains($ket, 'PARUH')) return 'PPPK-PW-TPP';
            return 'TPP-INDUK';
        }

        // 2. PPPK Paruh Waktu (PPPK-PW)
        if ((str_contains($ket, 'PPPK PW') || str_contains($ket, 'P3K PW') || str_contains($ket, 'PARUH WAKTU')) && 
            (str_contains($ket, 'GAJI') || str_contains($ket, 'BELANJA PEGAWAI') || str_contains($ket, 'HONOR'))) {
            
            if (str_contains($ket, 'SUSULAN')) return 'PPPK-PW-SUSULAN';
            if (str_contains($ket, 'KEKURANGAN')) return 'PPPK-PW-KEKURANGAN';
            if (str_contains($ket, 'THR') || str_contains($ket, '14')) return 'PPPK-PW-THR';
            if (str_contains($ket, '13')) return 'PPPK-PW-G13';
            return 'PPPK-PW-INDUK';
        }

        // 3. PPPK Penuh Waktu
        if ((str_contains($ket, 'PPPK') || str_contains($ket, 'P3K')) && (str_contains($ket, 'GAJI') || str_contains($ket, 'BELANJA PEGAWAI'))) {
            if (str_contains($ket, 'SUSULAN')) return 'PPPK-SUSULAN';
            if (str_contains($ket, 'KEKURANGAN')) return 'PPPK-KEKURANGAN';
            if (str_contains($ket, 'TERUSAN')) return 'PPPK-TERUSAN';
            if (str_contains($ket, 'THR') || str_contains($ket, 'G14')) return 'PPPK-THR';
            if (str_contains($ket, '13')) return 'PPPK-G13';
            return 'PPPK-INDUK';
        }

        // 4. PNS / ASN
        if ((str_contains($ket, 'GAJI') || str_contains($ket, 'BELANJA PEGAWAI')) &&
            (str_contains($ket, 'PNS') || str_contains($ket, 'ASN') || str_contains($ket, 'DPRD') || str_contains($ket, 'INDUK') || str_contains($ket, 'SUSULAN') || str_contains($ket, 'KEKURANGAN'))) {

            if (str_contains($ket, 'SUSULAN')) return 'PNS-SUSULAN';
            if (str_contains($ket, 'KEKURANGAN')) return 'PNS-KEKURANGAN';
            if (str_contains($ket, 'TERUSAN')) return 'PNS-TERUSAN';
            if (str_contains($ket, 'THR') || str_contains($ket, 'G14')) return 'PNS-THR';
            if (str_contains($ket, '13')) return 'PNS-G13';
            return 'PNS-INDUK';
        }

        // Fallback for generic Gaji
        if (str_contains($ket, 'GAJI') && !str_contains($ket, 'UANG PERSEDIAAN')) return 'PNS-INDUK';

        return null;
    }

    private function findSkpdId($name)
    {
        if (empty($name)) return null;

        $originalName = trim($name);
        $normalizedSearch = $this->normalizeName($name);

        $mapping = SkpdMapping::where('source_name', $originalName)->first();
        if ($mapping) return $mapping->skpd_id;

        $skpd = Skpd::where('nama_skpd', $originalName)->first();
        if ($skpd) return $skpd->id_skpd;

        $allSkpds = Skpd::all();
        foreach ($allSkpds as $s) {
            if ($this->normalizeName($s->nama_skpd) === $normalizedSearch) return $s->id_skpd;
        }

        return Skpd::where('nama_skpd', 'LIKE', substr($originalName, 0, 30) . '%')->first()?->id_skpd;
    }

    private function normalizeName($name)
    {
        $name = strtoupper(trim($name));
        $name = str_replace(['DINAS ', 'BADAN ', 'SEKRETARIAT ', 'DAERAH ', 'PROVINSI ', 'KALSEL', 'UPTD ', 'UPPD ', 'BLUD ', 'UNIT ', 'BALAI '], '', $name);
        return preg_replace('/[^A-Z0-9]/', '', $name);
    }

    private function cleanNum($val)
    {
        if (is_numeric($val)) return (float) $val;
        if (is_string($val)) {
            $val = preg_replace('/[^\d,.]/', '', $val);
            if (strpos($val, ',') !== false && strpos($val, '.') !== false) {
                if (strrpos($val, ',') > strrpos($val, '.')) {
                    $val = str_replace('.', '', $val);
                    $val = str_replace(',', '.', $val);
                } else {
                    $val = str_replace(',', '', $val);
                }
            } elseif (strpos($val, ',') !== false) {
                if (preg_match('/,\d{3}$/', $val)) $val = str_replace(',', '', $val);
                else $val = str_replace(',', '.', $val);
            } elseif (strpos($val, '.') !== false && substr_count($val, '.') > 1) {
                $val = str_replace('.', '', $val);
            }
            return (float) $val;
        }
        return 0;
    }
}
