<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class AuditPegawaiPw extends Command
{
    protected $signature = 'audit:pegawai-pw';
    protected $description = 'Audit perbandingan Data CSV vs Database Pegawai PW';

    public function handle()
    {
        $csvPath = '/Users/rullyperdhana/dashboard-pw/pegawai_pw.csv';
        $outputPath = '/Users/rullyperdhana/dashboard-pw/Hasil_Audit_Pegawai_PW.xls';

        if (!File::exists($csvPath)) {
            $this->error("File CSV tidak ditemukan di: $csvPath");
            return;
        }

        $this->info("Memulai proses audit...");

        // 1. Baca Data CSV
        $csvData = [];
        if (($handle = fopen($csvPath, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 10000, ",")) !== FALSE) {
                // Berdasarkan 'head' sebelumnya: Index 2=NIP, 3=Nama, 9=NIK, 16=SKPD
                $nip = trim($data[2]);
                if ($nip && is_numeric($nip)) {
                    $csvData[$nip] = [
                        'nip' => $nip,
                        'nama' => trim($data[3]),
                        'nik' => trim($data[9]),
                        'skpd' => trim($data[16]),
                    ];
                }
            }
            fclose($handle);
        }

        // 2. Ambil Data dari Database
        $dbData = DB::table('pegawai_pw')->get()->keyBy('nip')->toArray();

        // 3. Bandingkan Data
        $results = [];
        $allNips = array_unique(array_merge(array_keys($csvData), array_keys($dbData)));

        foreach ($allNips as $nip) {
            $csv = $csvData[$nip] ?? null;
            $db = $dbData[$nip] ?? null;

            if ($csv && !$db) {
                $results[] = [
                    'status' => 'BELUM TERDAFTAR (Cuma ada di CSV)',
                    'nip' => $nip,
                    'nama_csv' => $csv['nama'],
                    'nama_db' => '-',
                    'nik_csv' => $csv['nik'],
                    'nik_db' => '-',
                    'skpd_csv' => $csv['skpd'],
                    'skpd_db' => '-',
                    'perbedaan' => 'Pegawai baru, mohon input ke sistem.'
                ];
            } elseif (!$csv && $db) {
                $results[] = [
                    'status' => 'KELEBIHAN DI DB (Tidak ada di CSV)',
                    'nip' => $nip,
                    'nama_csv' => '-',
                    'nama_db' => $db->nama,
                    'nik_csv' => '-',
                    'nik_db' => $db->nik,
                    'skpd_csv' => '-',
                    'skpd_db' => $db->skpd,
                    'perbedaan' => 'Mungkin data lama atau sudah tidak aktif.'
                ];
            } else {
                // Cek perbedaan field
                $diff = [];
                if ($csv['nama'] !== $db->nama) $diff[] = "Nama berbeda";
                if ($csv['nik'] !== $db->nik) $diff[] = "NIK berbeda";
                if ($csv['skpd'] !== $db->skpd) $diff[] = "SKPD berbeda";

                if (count($diff) > 0) {
                    $results[] = [
                        'status' => 'DATA TIDAK SINKRON',
                        'nip' => $nip,
                        'nama_csv' => $csv['nama'],
                        'nama_db' => $db->nama,
                        'nik_csv' => $csv['nik'],
                        'nik_db' => $db->nik,
                        'skpd_csv' => $csv['skpd'],
                        'skpd_db' => $db->skpd,
                        'perbedaan' => implode(", ", $diff)
                    ];
                }
            }
        }

        // 4. Generate Excel (HTML Format)
        if (count($results) == 0) {
            $this->info("Audit Selesai: Data sudah sinkron 100%!");
            return;
        }

        $html = "<html><head><meta charset='utf-8'></head><body>";
        $html .= "<h3>LAPORAN AUDIT DATA PEGAWAI PW - ".date('d/m/Y H:i')."</h3>";
        $html .= "<table border='1'>";
        $html .= "<tr style='background-color:#eee; font-weight:bold;'>";
        $html .= "<td>STATUS AUDIT</td><td>NIP</td><td>NAMA (CSV)</td><td>NAMA (DB)</td><td>NIK (CSV)</td><td>NIK (DB)</td><td>SKPD (CSV)</td><td>SKPD (DB)</td><td>KETERANGAN</td>";
        $html .= "</tr>";

        foreach ($results as $r) {
            $color = ($r['status'] == 'BELUM TERDAFTAR (Cuma ada di CSV)') ? '#fff8e1' : (($r['status'] == 'DATA TIDAK SINKRON') ? '#ffebee' : '#f5f5f5');
            $html .= "<tr style='background-color:$color;'>";
            foreach ($r as $val) {
                $html .= "<td>$val</td>";
            }
            $html .= "</tr>";
        }
        $html .= "</table></body></html>";

        File::put($outputPath, $html);
        $this->info("Audit Selesai! File disimpan di: $outputPath");
        $this->info("Jumlah temuan: " . count($results));
    }
}
