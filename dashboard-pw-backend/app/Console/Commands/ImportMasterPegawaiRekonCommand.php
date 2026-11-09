<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportMasterPegawaiRekonCommand extends Command
{
    protected $signature = 'import:master-rekon {file}';
    protected $description = 'Import master pegawai dari file CSV untuk rekon TPP';

    public function handle()
    {
        $file = $this->argument('file');
        
        if (!file_exists($file)) {
            $this->error("File tidak ditemukan: $file");
            return 1;
        }

        $this->info("Membaca file CSV...");
        
        try {
            DB::table('master_pegawai_rekon')->truncate();
            $this->info("Tabel master_pegawai_rekon dikosongkan.");

            $handle = fopen($file, "r");
            if ($handle === FALSE) {
                $this->error("Gagal membuka file CSV.");
                return 1;
            }

            $batchSize = 1000;
            $data = [];
            $count = 0;

            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($row) < 7) continue; // Now we have 7 columns
                
                $nip = trim($row[0]);
                $nama = trim($row[1]);
                $status = trim($row[2]);
                $skpd = trim($row[3]);
                $upt = trim($row[4]);
                $satker = trim($row[5]);
                $jenis = trim($row[6]);
                
                if (!empty($nip)) {
                    $data[] = [
                        'nip' => $nip,
                        'nama' => $nama,
                        'status' => $status,
                        'skpd' => $skpd,
                        'upt' => $upt,
                        'satker' => $satker,
                        'jenis' => $jenis,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                    
                    if (count($data) >= $batchSize) {
                        DB::table('master_pegawai_rekon')->insert($data);
                        $count += count($data);
                        $this->info("Ter-insert $count baris...");
                        $data = [];
                    }
                }
            }
            
            if (count($data) > 0) {
                DB::table('master_pegawai_rekon')->insert($data);
                $count += count($data);
            }
            
            fclose($handle);
            
            $this->info("Selesai. Total $count pegawai berhasil di-import.");
            
        } catch (\Exception $e) {
            $this->error("Terjadi kesalahan: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
