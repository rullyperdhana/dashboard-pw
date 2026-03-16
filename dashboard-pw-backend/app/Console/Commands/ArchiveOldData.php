<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ArchiveOldData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:archive-old-data {--years=2 : Jumlah tahun data yang dipertahankan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pindahkan data pembayaran lama ke tabel arsip untuk menjaga performa';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $years = (int) $this->option('years');
        $thresholdYear = now()->subYears($years)->year;

        $this->info("Memulai proses pengarsipan data sebelum tahun {$thresholdYear}...");

        try {
            DB::beginTransaction();

            // 1. Pastikan tabel arsip ada
            $this->ensureArchiveTablesExist();

            // 2. Ambil ID pembayaran yang akan diarsip
            $oldPaymentIds = DB::table('tb_payment')
                ->where('year', '<', $thresholdYear)
                ->pluck('id');

            if ($oldPaymentIds->isEmpty()) {
                $this->info("Tidak ada data lama yang ditemukan untuk diarsip.");
                DB::rollBack();
                return;
            }

            $count = $oldPaymentIds->count();
            $this->info("Ditemukan {$count} data pembayaran lama. Memindahkan...");

            // 3. Pindahkan Detail (Child)
            DB::statement("INSERT INTO tb_payment_detail_archive SELECT * FROM tb_payment_detail WHERE payment_id IN (SELECT id FROM tb_payment WHERE year < {$thresholdYear})");
            
            // 4. Pindahkan Master (Parent)
            DB::statement("INSERT INTO tb_payment_archive SELECT * FROM tb_payment WHERE year < {$thresholdYear}");

            // 5. Hapus dari tabel utama (Cascade akan menghapus detail jika FK didefinisikan ON DELETE CASCADE)
            // Namun untuk keamanan, kita hapus manual detailnya dulu jika FK tidak ada di archive
            DB::table('tb_payment_detail')->whereIn('payment_id', $oldPaymentIds)->delete();
            DB::table('tb_payment')->whereIn('id', $oldPaymentIds)->delete();

            DB::commit();

            $this->info("Data berhasil diarsip.");
            
            // 6. Optimasi Tabel
            $this->info("Mengoptimasi tabel...");
            DB::statement("OPTIMIZE TABLE tb_payment, tb_payment_detail");
            
            $this->info("Selesai.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Gagal mengarsip data: " . $e->getMessage());
        }
    }

    protected function ensureArchiveTablesExist()
    {
        if (!Schema::hasTable('tb_payment_archive')) {
            $this->info("Membuat tabel tb_payment_archive...");
            DB::statement("CREATE TABLE tb_payment_archive LIKE tb_payment");
            // Hilangkan AI agar id tetap sama
            DB::statement("ALTER TABLE tb_payment_archive MODIFY id INT NOT NULL");
            // Hilangkan FK Constraints agar tidak error saat pemindahan
            $this->dropForeignKeys('tb_payment_archive');
        }

        if (!Schema::hasTable('tb_payment_detail_archive')) {
            $this->info("Membuat tabel tb_payment_detail_archive...");
            DB::statement("CREATE TABLE tb_payment_detail_archive LIKE tb_payment_detail");
            DB::statement("ALTER TABLE tb_payment_detail_archive MODIFY id INT NOT NULL");
            $this->dropForeignKeys('tb_payment_detail_archive');
        }
    }

    protected function dropForeignKeys($table)
    {
        $fks = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$table}' AND CONSTRAINT_NAME <> 'PRIMARY'");
        foreach ($fks as $fk) {
            try {
                DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
            } catch (\Exception $e) {
                // Mungkin sudah dihapus atau bukan FK
            }
        }
    }
}
