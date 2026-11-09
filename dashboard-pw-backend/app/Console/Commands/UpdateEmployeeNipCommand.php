<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateEmployeeNipCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'employee:update-nip 
                            {old_nip : NIP lama pegawai yang akan diubah} 
                            {new_nip : NIP baru pegawai pengganti}
                            {--force : Jalankan update tanpa konfirmasi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ubah/perbarui NIP pegawai secara menyeluruh di semua tabel terkait (Master, Gaji, TPP, Pajak)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $oldNip = trim($this->argument('old_nip'));
        $newNip = trim($this->argument('new_nip'));

        if (empty($oldNip) || empty($newNip)) {
            $this->error('NIP lama dan NIP baru tidak boleh kosong.');
            return 1;
        }

        if ($oldNip === $newNip) {
            $this->warn('NIP lama dan NIP baru sama. Tidak ada perubahan yang dilakukan.');
            return 0;
        }

        $this->info("=== PERSIAPAN UPDATE NIP ===");
        $this->line("NIP Lama : <comment>{$oldNip}</comment>");
        $this->line("NIP Baru : <info>{$newNip}</info>");

        // Daftar tabel dan kolom yang akan dicek dan di-update
        $tables = [
            ['table' => 'pegawai_pw', 'column' => 'nip', 'label' => 'Pegawai PW (Non-ASN)'],
            ['table' => 'master_pegawai', 'column' => 'nip', 'label' => 'Master Pegawai (PNS/PPPK)'],
            ['table' => 'master_keluarga', 'column' => 'nip', 'label' => 'Master Keluarga (NIP Pegawai)'],
            ['table' => 'master_keluarga', 'column' => 'nipsuamiis', 'label' => 'Master Keluarga (NIP Pasangan)'],
            ['table' => 'history_gaji_pokok', 'column' => 'nip', 'label' => 'Riwayat Gaji Pokok'],
            ['table' => 'gaji_pns', 'column' => 'nip', 'label' => 'Riwayat Gaji PNS'],
            ['table' => 'gaji_pppk', 'column' => 'nip', 'label' => 'Riwayat Gaji PPPK'],
            ['table' => 'tpp_details', 'column' => 'nip', 'label' => 'Rincian TPP'],
            ['table' => 'standalone_tpp', 'column' => 'nip', 'label' => 'TPP Standalone'],
            ['table' => 'tpg_data', 'column' => 'nip', 'label' => 'Data TPG (Sertifikasi Guru)'],
            ['table' => 'thr_pppk_pw', 'column' => 'nip', 'label' => 'THR PPPK PW'],
            ['table' => 'extra_payroll_pppk_pw', 'column' => 'nip', 'label' => 'Gaji Tambahan PPPK PW'],
            ['table' => 'tax_statuses', 'column' => 'nip', 'label' => 'Status PTKP Pajak'],
            ['table' => 'pph21_calculations', 'column' => 'nip', 'label' => 'Kalkulasi PPh 21'],
            ['table' => 'master_pegawai_rekon', 'column' => 'nip', 'label' => 'Master Pegawai Rekon'],
            ['table' => 'bkd_pegawai', 'column' => 'nip', 'label' => 'Data BKD Pegawai'],
            ['table' => 'users', 'column' => 'nip', 'label' => 'Akun Pengguna (ESS/User)'],
        ];

        // 1. Periksa apakah NIP lama ditemukan
        $foundSummary = [];
        $totalFound = 0;

        foreach ($tables as $t) {
            if (!Schema::hasTable($t['table']) || !Schema::hasColumn($t['table'], $t['column'])) {
                continue;
            }

            $count = DB::table($t['table'])->where($t['column'], $oldNip)->count();
            if ($count > 0) {
                $foundSummary[] = [
                    'table' => $t['table'],
                    'column' => $t['column'],
                    'label' => $t['label'],
                    'count' => $count,
                ];
                $totalFound += $count;
            }
        }

        if ($totalFound === 0) {
            $this->error("Data dengan NIP lama '{$oldNip}' tidak ditemukan di tabel manapun.");
            return 1;
        }

        $this->newLine();
        $this->info("Ditemukan {$totalFound} baris data terkait NIP {$oldNip}:");
        $this->table(
            ['Tabel', 'Kolom', 'Keterangan', 'Jumlah Baris'],
            array_map(fn($item) => [$item['table'], $item['column'], $item['label'], $item['count']], $foundSummary)
        );

        // 2. Cek apakah NIP baru sudah ada di tabel kunci (untuk menghindari tabrakan data)
        $conflicts = [];
        $uniqueTables = ['pegawai_pw', 'master_pegawai'];
        foreach ($uniqueTables as $ut) {
            if (Schema::hasTable($ut) && Schema::hasColumn($ut, 'nip')) {
                if (DB::table($ut)->where('nip', $newNip)->exists()) {
                    $conflicts[] = $ut;
                }
            }
        }

        if (!empty($conflicts)) {
            $this->error("PERINGATAN: NIP baru '{$newNip}' SUDAH ADA di tabel: " . implode(', ', $conflicts));
            $this->error("Proses dibatalkan untuk menghindari duplikasi data unik.");
            return 1;
        }

        // 3. Konfirmasi dari pengguna (kecuali flag --force)
        if (!$this->option('force') && !$this->confirm("Apakah Anda yakin ingin memperbarui NIP {$oldNip} menjadi {$newNip}?", true)) {
            $this->warn('Proses dibatalkan oleh pengguna.');
            return 0;
        }

        // 4. Eksekusi update dalam transaksi database
        DB::beginTransaction();
        try {
            $updatedSummary = [];

            foreach ($tables as $t) {
                if (!Schema::hasTable($t['table']) || !Schema::hasColumn($t['table'], $t['column'])) {
                    continue;
                }

                $query = DB::table($t['table'])->where($t['column'], $oldNip);

                if ($t['table'] === 'master_pegawai' && $t['column'] === 'nip') {
                    // Pada master pegawai simpan juga nilai niplama jika kolomnya ada
                    $updateData = ['nip' => $newNip];
                    if (Schema::hasColumn('master_pegawai', 'niplama')) {
                        $updateData['niplama'] = $oldNip;
                    }
                    $affected = $query->update($updateData);
                } else {
                    $affected = $query->update([$t['column'] => $newNip]);
                }

                if ($affected > 0) {
                    $updatedSummary[] = [
                        'table' => $t['table'],
                        'column' => $t['column'],
                        'label' => $t['label'],
                        'affected' => $affected,
                    ];
                }
            }

            DB::commit();

            $this->newLine();
            $this->info("BERHASIL! NIP berhasil diperbarui dari {$oldNip} ke {$newNip}.");
            $this->table(
                ['Tabel', 'Kolom', 'Keterangan', 'Baris Diperbarui'],
                array_map(fn($item) => [$item['table'], $item['column'], $item['label'], $item['affected']], $updatedSummary)
            );

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Terjadi kesalahan saat memperbarui database: ' . $e->getMessage());
            return 1;
        }
    }
}

