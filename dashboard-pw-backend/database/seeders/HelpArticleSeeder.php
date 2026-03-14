<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HelpArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\HelpArticle::create([
            'title' => 'Panduan Riwayat Ekspor (Audit Log)',
            'slug' => 'panduan-riwayat-ekspor',
            'category' => 'Keamanan',
            'keywords' => 'audit, log, ekspor, ip address',
            'content' => "### Apa itu Riwayat Ekspor?\nFitur ini memungkinkan Superadmin untuk melacak aktivitas cetak PDF atau unduh Excel. \n\n### Cara Menggunakan:\n1. Masuk ke menu **Manajemen Sistem > Riwayat Ekspor**.\n2. Anda akan melihat daftar aksi, waktu, nama pengguna, dan alamat IP.\n3. Gunakan filter tanggal jika ingin mencari data di periode tertentu.\n\n### Masalah Umum:\nJika IP Address terdeteksi 127.0.0.1, pastikan konfigurasi *Trusted Proxies* sudah aktif.",
        ]);

        \App\Models\HelpArticle::create([
            'title' => 'Cara Melakukan Pemetaan (Mapping) SKPD',
            'slug' => 'cara-mapping-skpd',
            'category' => 'Mapping',
            'keywords' => 'mapping, skpd, unit, uptd',
            'content' => "### Langkah-langkah Pemetaan:\n1. Buka menu **Pengaturan > Pemetaan SKPD**.\n2. Klik tab **SP2D (SIPD)** untuk melihat nama yang belum dikenali sistem.\n3. Klik tombol **Petakan**.\n4. Sistem akan memberikan **Saran** jika ada kemiripan nama.\n5. Klik **Simpan**.\n\n### Normalisasi Otomatis:\nSistem sekarang otomatis membersihkan awalan `UPTD`, `UPPD`, `BLUD` untuk memudahkan pencocokan.",
        ]);
    }
}
