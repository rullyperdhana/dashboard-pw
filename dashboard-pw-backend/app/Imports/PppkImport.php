<?php

namespace App\Imports;

use App\Models\GajiPppk;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;

class PppkImport implements ToModel, WithHeadingRow, WithChunkReading, WithBatchInserts
{
    protected $month;
    protected $year;
    protected $jenisGaji;

    public function __construct($month, $year, $jenisGaji = 'Induk')
    {
        $this->month = $month;
        $this->year = $year;
        $this->jenisGaji = $jenisGaji;
    }

    public function model(array $row)
    {
        // Detect which format: check if 'nip' or 'nip_pegawai' exists
        $nipColumn = isset($row['nip']) ? 'nip' : 'nip_pegawai';

        // Skip empty rows or rows without NIP
        if (!array_filter($row) || empty($row[$nipColumn])) {
            return null;
        }

        // Format 1: Old format (nip_pegawai, nama_pegawai, etc.)
        if ($nipColumn === 'nip_pegawai') {
            return new GajiPppk([
                'nip' => $row['nip_pegawai'] ?? null,
                'nama' => $row['nama_pegawai'] ?? 'Unknown',
                'golongan' => $row['golongan'] ?? null,
                'jabatan' => $row['nama_jabatan'] ?? $row['jabatan'] ?? null,
                'skpd' => $row['skpd'] ?? 'Unknown',
                'gaji_pokok' => $this->cleanNum($row['gaji_pokok'] ?? 0),
                'tunj_istri' => $this->cleanNum($row['tunjangan_istri'] ?? 0),
                'tunj_anak' => $this->cleanNum($row['tunjangan_anak'] ?? 0),
                'tunj_fungsional' => $this->cleanNum($row['tunjangan_fungsional'] ?? 0),
                'tunj_struktural' => $this->cleanNum($row['tunjangan_jabatan'] ?? 0),
                'tunj_umum' => $this->cleanNum($row['tunjangan_fungsional_umum'] ?? 0),
                'tunj_beras' => $this->cleanNum($row['tunjangan_beras'] ?? 0),
                'tunj_pph' => $this->cleanNum($row['tunjangan_pph'] ?? 0),
                'pembulatan' => $this->cleanNum($row['pembulatan_gaji'] ?? 0),
                'kotor' => $this->cleanNum($row['jumlah_gaji_dan_tunjangan'] ?? 0),
                'pot_iwp' => $this->cleanNum($row['potongan_iwp'] ?? 0),
                'pot_pph' => $this->cleanNum($row['potongan_pph_21'] ?? 0),
                'total_potongan' => $this->cleanNum($row['jumlah_potongan'] ?? 0),
                'bersih' => $this->cleanNum($row['jumlah_ditransfer'] ?? 0),
                'bulan' => $this->month,
                'tahun' => $this->year,
                'jenis_gaji' => $this->jenisGaji,
            ]);
        }

        // Format 2: New format (nip, nama, gapok, tjistri, etc.)
        return new GajiPppk([
            'nip' => $row['nip'] ?? null,
            'nama' => $row['nama'] ?? 'Unknown',
            'golongan' => $row['mkgolt'] ?? null,
            'kdpangkat' => $row['kdpangkat'] ?? null,
            'jabatan' => null,
            'skpd' => $row['nmskpd'] ?? 'Unknown',
            'satker' => $row['nmsatker'] ?? null,
            'kdskpd' => $row['kdskpd'] ?? null,
            'kdjenkel' => $row['kdjenkel'] ?? null,
            'pendidikan' => $row['pendidikan'] ?? null,
            'norek' => $row['norek'] ?? null,
            'npwp' => $row['npwp'] ?? null,
            'noktp' => $row['noktp'] ?? null,
            // Tunjangan
            'gaji_pokok' => $this->cleanNum($row['gapok'] ?? 0),
            'tunj_istri' => $this->cleanNum($row['tjistri'] ?? 0),
            'tunj_anak' => $this->cleanNum($row['tjanak'] ?? 0),
            'tunj_fungsional' => $this->cleanNum($row['tjfungsi'] ?? 0),
            'tunj_struktural' => $this->cleanNum($row['tjstruk'] ?? 0),
            'tunj_umum' => $this->cleanNum($row['tjumum'] ?? 0),
            'tunj_beras' => $this->cleanNum($row['tjberas'] ?? 0),
            'tunj_pph' => $this->cleanNum($row['tjpajak'] ?? 0),
            'tunj_tpp' => $this->cleanNum($row['tjtpp'] ?? 0),
            'tunj_eselon' => $this->cleanNum($row['tjeselon'] ?? 0),
            'tunj_guru' => $this->cleanNum($row['tjguru'] ?? 0),
            'tunj_langka' => $this->cleanNum($row['tjlangka'] ?? 0),
            'tunj_tkd' => $this->cleanNum($row['tjtkd'] ?? 0),
            'tunj_terpencil' => $this->cleanNum($row['tjterpencil'] ?? 0),
            'tunj_khusus' => $this->cleanNum($row['tjkhusus'] ?? 0),
            'tunj_askes' => $this->cleanNum($row['tjaskes'] ?? 0),
            'tunj_kk' => $this->cleanNum($row['tjkk'] ?? 0),
            'tunj_km' => $this->cleanNum($row['tjkm'] ?? 0),
            'pembulatan' => $this->cleanNum($row['tbulat'] ?? 0),
            'kotor' => $this->cleanNum($row['kotor'] ?? 0),
            // Potongan
            'pot_iwp' => $this->cleanNum($row['piwp'] ?? 0),
            'pot_iwp1' => $this->cleanNum($row['piwp1'] ?? 0),
            'pot_iwp8' => $this->cleanNum($row['piwp8'] ?? 0),
            'pot_askes' => $this->cleanNum($row['paskes'] ?? 0),
            'pot_pph' => $this->cleanNum($row['ppajak'] ?? 0),
            'pot_bulog' => $this->cleanNum($row['pbulog'] ?? 0),
            'pot_taperum' => $this->cleanNum($row['ptaperum'] ?? 0),
            'pot_sewa' => $this->cleanNum($row['psewa'] ?? 0),
            'pot_hutang' => $this->cleanNum($row['phutang'] ?? 0),
            'pot_korpri' => $this->cleanNum($row['pkorpri'] ?? 0),
            'pot_irdhata' => $this->cleanNum($row['pirdhata'] ?? 0),
            'pot_koperasi' => $this->cleanNum($row['pkoperasi'] ?? 0),
            'pot_jkk' => $this->cleanNum($row['pjkk'] ?? 0),
            'pot_jkm' => $this->cleanNum($row['pjkm'] ?? 0),
            'total_potongan' => $this->cleanNum($row['potongan'] ?? 0),
            'bersih' => $this->cleanNum($row['bersih'] ?? 0),
            'bulan' => $this->month,
            'tahun' => $this->year,
            'jenis_gaji' => $this->jenisGaji,
        ]);
    }

    private function cleanNum($val)
    {
        if (is_string($val)) {
            return (float) str_replace([',', '.'], '', $val);
        }
        return (float) $val;
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
