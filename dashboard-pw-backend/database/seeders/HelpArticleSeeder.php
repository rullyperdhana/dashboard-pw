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
        \App\Models\HelpArticle::updateOrCreate(
            ['slug' => 'panduan-riwayat-ekspor'],
            [
                'title' => 'Panduan Riwayat Ekspor (Audit Log)',
                'category' => 'Keamanan',
                'keywords' => 'audit, log, ekspor, ip address',
                'content' => "### Apa itu Riwayat Ekspor?\nFitur ini memungkinkan Superadmin untuk melacak aktivitas cetak PDF atau unduh Excel. \n\n### Cara Menggunakan:\n1. Masuk ke menu **Manajemen Sistem > Riwayat Ekspor**.\n2. Anda akan melihat daftar aksi, waktu, nama pengguna, dan alamat IP.\n3. Gunakan filter tanggal jika ingin mencari data di periode tertentu.\n\n### Masalah Umum:\nJika IP Address terdeteksi 127.0.0.1, pastikan konfigurasi *Trusted Proxies* sudah aktif.",
            ]
        );

        \App\Models\HelpArticle::updateOrCreate(
            ['slug' => 'cara-mapping-skpd'],
            [
                'title' => 'Cara Melakukan Pemetaan (Mapping) SKPD',
                'category' => 'Mapping',
                'keywords' => 'mapping, skpd, unit, uptd',
                'content' => "### Langkah-langkah Pemetaan:\n1. Buka menu **Pengaturan > Pemetaan SKPD**.\n2. Klik tab **SP2D (SIPD)** untuk melihat nama yang belum dikenali sistem.\n3. Klik tombol **Petakan**.\n4. Sistem akan memberikan **Saran** jika ada kemiripan nama.\n5. Klik **Simpan**.\n\n### Normalisasi Otomatis:\nSistem sekarang otomatis membersihkan awalan `UPTD`, `UPPD`, `BLUD` untuk memudahkan pencocokan.",
            ]
        );

        \App\Models\HelpArticle::updateOrCreate(
            ['slug' => 'impor-data-master-dbf'],
            [
                'title' => 'Impor Data Master Pegawai (DBF)',
                'category' => 'Import',
                'keywords' => 'dbf, master, pegawai, import',
                'content' => "### Persiapan File:\nSistem menerima file database (.dbf) dari aplikasi Simgaji.\n\n### Langkah Impor:\n1. Buka menu **PNS & PPPK > Master Pegawai (DBF)**.\n2. Pilih file `.dbf` yang sesuai (Master Pegawai atau Keluarga).\n3. Klik **Upload** dan tunggu hingga proses selesai.\n\n### Catatan:\nProses ini akan memperbarui data pegawai yang sudah ada dan menambah pegawai baru jika belum terdaftar.",
            ]
        );

        \App\Models\HelpArticle::updateOrCreate(
            ['slug' => 'verifikasi-sp2d-sipd'],
            [
                'title' => 'Verifikasi & Rekonsiliasi SP2D',
                'category' => 'Laporan',
                'keywords' => 'sp2d, sipd, rekon, verifikasi',
                'content' => "### Tujuan:\nMemastikan data pengeluaran gaji di SIPD sudah sama dengan data realisasi di Simgaji.\n\n### Cara Verifikasi:\n1. Masuk ke menu **Laporan & Verif > Verifikasi SP2D**.\n2. Pilih Bulan dan Tahun.\n3. Klik **Cek Sinkronisasi**.\n4. Sistem akan menampilkan selisih (jika ada) antara Simgaji dan SIPD.\n\n### Export Rekon:\nBapak bisa mengunduh file Excel hasil rekonsiliasi dengan mengklik tombol **Export Excel**.",
            ]
        );

        \App\Models\HelpArticle::updateOrCreate(
            ['slug' => 'update-nik-massal'],
            [
                'title' => 'Panduan Update NIK Massal',
                'category' => 'Management',
                'keywords' => 'nik, update, massal, excel',
                'content' => "### Kapan Menggunakan Ini?\nGunakan jika banyak NIK pegawai yang masih salah atau kosong setelah impor DBF.\n\n### Langkah-langkah:\n1. Buka menu **PNS & PPPK > Update NIK Massal**.\n2. Unduh **Template Excel** yang telah disediakan.\n3. Isi kolom NIP dan NIK yang baru di file tersebut.\n4. Unggah kembali file Excel tersebut ke sistem.\n\n### Peringatan:\nPastikan NIP benar agar tidak salah memperbarui data orang lain.",
            ]
        );

        \App\Models\HelpArticle::updateOrCreate(
            ['slug' => 'manajemen-user-akses'],
            [
                'title' => 'Manajemen User & Hak Akses',
                'category' => 'Sistem',
                'keywords' => 'user, role, akses, operator',
                'content' => "### Level Akses:\n1. **Superadmin:** Akses penuh ke seluruh fitur dan pengaturan sistem.\n2. **Operator SKPD:** Hanya bisa melihat data dan laporan sesuai SKPD yang ditugaskan.\n\n### Cara Menambah User:\n1. Menu **Manajemen Sistem > Manajemen User**.\n2. Klik **Tambah User**.\n3. Pilih Role dan tentukan SKPD jika role-nya adalah Operator.",
            ]
        );

        \App\Models\HelpArticle::updateOrCreate(
            ['slug' => 'peta-navigasi-struktur-menu'],
            [
                'title' => 'Peta Navigasi & Struktur Menu',
                'category' => 'Sistem',
                'keywords' => 'menu, navigasi, fitur, struktur',
                'content' => "### Ikhtisar Menu Aplikasi\nAplikasi ini dibagi menjadi 5 kategori utama di Sidebar:\n\n#### 1. PPPK-PW (Paruh Waktu)\nKhusus untuk pengelolaan data PPPK Paruh Waktu.\n- **Dashboard PW:** Ringkasan data real-time.\n- **Pegawai PW:** Daftar personil PPPK-PW.\n- **Payroll PW:** Pengelolaan pembayaran/gaji.\n- **Trace Gaji:** Pelacakan detail riwayat gaji.\n- **THR PPPK-PW:** Cetak laporan THR khusus Paruh Waktu.\n\n#### 2. PNS & PPPK\nPengelolaan data pegawai ASN (PNS dan PPPK).\n- **Dashboard PNS:** Statistik pegawai ASN.\n- **Update NIK Massal:** Perbaikan data NIK via Excel.\n- **Master Pegawai (DBF):** Sinkronisasi data dari aplikasi Simgaji.\n- **Upload TPP:** Pengunggahan data TPP khusus.\n\n#### 3. Laporan & Verif\nKumpulan fitur audit dan pelaporan bulanan.\n- **Verifikasi SP2D:** Pencocokan data SIPD dan Simgaji.\n- **Laporan SKPD:** Rekapitulasi laporan per instansi.\n- **Rekon BPJS 4%:** Sinkronisasi iuran BPJS.\n- **Dashboard/Upload TPG:** Pengelolaan Tunjangan Profesi Guru.\n\n#### 4. Data Referensi\nPengaturan master data pendukung.\n- **SKPD Mapping:** Pemetaan nama dinas SIPD ke Simgaji.\n- **Ref Satker PNS:** Pengaturan Satuan Kerja.\n- **Sumber Dana PW:** Pengaturan kode pendanaan.\n\n#### 5. Manajemen Sistem\nPengaturan teknis dan keamanan.\n- **Posting Data:** Penguncian data agar tidak bisa diubah.\n- **Manajemen User:** Pengelolaan login dan hak akses.\n- **Riwayat Ekspor:** Audit aktivitas unduh data.\n- **Pusat Bantuan:** (Halaman ini) Dokumentasi mandiri.",
            ]
        );
    }
}
