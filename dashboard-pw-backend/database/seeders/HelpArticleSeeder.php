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
            ['slug' => 'katalog-dokumentasi-api'],
            [
                'title' => 'Katalog & Dokumentasi API (Integrasi)',
                'category' => 'Developer',
                'keywords' => 'api, integration, key, developer, endpoint',
                'content' => "### Integrasi Simgaji\nAplikasi menyediakan API untuk integrasi data dengan pihak ketiga (seperti Puskom atau Dashboard Provinsi).\n\n### Keamanan:\nSetiap request wajib menyertakan **X-API-KEY** di header HTTP. API Key dapat dikelola di menu **Manajemen Sistem > API Keys**.\n\n### Endpoints Utama:\n1. `GET /api/listinstansi`: Mengambil daftar kode dan nama SKPD aktif.\n2. `GET /api/listpegawai`: Mengambil data master identitas pegawai (NIK, NIP, Nama).\n3. `GET /api/listgaji`: Mengambil detail komponen gaji (Induk, Gaji-13, THR) baik PNS maupun PPPK.\n\n### Parameter Utama:\n- `period`: Format YYYY-MM (mis: 2026-04).\n- `kode_instansi`: Filter berdasarkan kode SKPD.",
            ]
        );

        \App\Models\HelpArticle::updateOrCreate(
            ['slug' => 'logika-perhitungan-jkk-jkm-bpjs'],
            [
                'title' => 'Logika Perhitungan JKK, JKM, & BPJS',
                'category' => 'Financial',
                'keywords' => 'jkk, jkm, bpjs, perhitungan, rumus, ump',
                'content' => "### 1. JKK & JKM (PPPK)\nPersentase dapat diatur di menu **Estimasi JKK/JKM**.\n- **JKK (Jaminan Kecelakaan Kerja):** Default 0.24% dari Gaji Pokok.\n- **JKM (Jaminan Kematian):** Default 0.72% dari Gaji Pokok.\n\n### 2. BPJS Kesehatan (ASN & PPPK)\n- **Share Pemda (Employer):** 4%.\n- **Batas Atas (Cap):** Maksimal basis perhitungan adalah Rp 12.000.000.\n- **Rumus:** `4% x MIN(Gaji Pokok + Tunjangan Keluarga + Tunjangan Jabatan + TPP, 12.000.000)`.\n\n### 3. Rekon BPJS 4% (PPPK Paruh Waktu)\nKhusus untuk Paruh Waktu, berlaku aturan ambang batas UMP:\n- Jika Gaji Pokok >= UMP → `Gaji Pokok x 4%`.\n- Jika Gaji Pokok < UMP → `UMP x 4%` (dibayar sesuai standar upah minimum).\n\n*Setting UMP dapat diubah di menu Rekon BPJS 4%.*",
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
            ['slug' => 'panduan-perhitungan-thr-gaji-13'],
            [
                'title' => 'Panduan Perhitungan THR & Gaji-13',
                'category' => 'Financial',
                'keywords' => 'thr, gaji 13, perhitungan, pppk pw, pns, terlewat, missing',
                'content' => "### 1. Extra Payroll (PPPK Paruh Waktu)\nSistem kini mendukung modul **THR** dan **Gaji-13** menggunakan basis data yang sama namun dengan bulan basis yang dapat diatur terpisah.\n\n### Metode Perhitungan:\n- **Metode Proporsional (n/12):** Menggunakan `Gaji Pokok Basis x (n/12)`. Nilai `n` adalah masa kerja dalam bulan yang memenuhi syarat.\n- **Metode Tetap (Fixed):** Menggunakan angka nominal rupiah yang ditetapkan Admin.\n\n### 2. THR & Gaji-13 ASN (PNS/PPPK)\nPerhitungan otomatis mengikuti data yang diimpor dari Simgaji. Sistem kini mendukung:\n- **Import DBF Khusus:** Anda dapat mengunggah file DBF khusus untuk periode THR atau Gaji 13 melalui menu Upload.\n- **Dukungan TPP THR/13:** TPP dapat diunggah secara khusus untuk jenis gaji THR atau Gaji 13 untuk memastikan sinkronisasi data tunjangan.\n- **Filter Dashboard:** Gunakan filter 'Jenis Gaji' di Dashboard untuk memisahkan realisasi belanja pegawai antara Induk, THR, dan Gaji-13.\n\n### Laporan Data Belum Terbentuk:\nJika jumlah pegawai di laporan berbeda dengan Dashboard, buka tab **'Data Belum Terbentuk'**. Sistem akan menampilkan:\n- Pegawai yang tidak memiliki slip gaji pada bulan basis.\n- **Keterangan Alasan**: Memberikan penjelasan teknis mengapa pegawai tersebut tidak masuk laporan.",
            ]
        );

        \App\Models\HelpArticle::updateOrCreate(
            ['slug' => 'monitoring-pensiun-pegawai'],
            [
                'title' => 'Monitoring & Estimasi Pensiun',
                'category' => 'Management',
                'keywords' => 'pensiun, retirement, monitor, batasan usia',
                'content' => "### Batasan Usia Pensiun (BUP)\nSistem secara otomatis menghitung estimasi pensiun berdasarkan tanggal lahir:\n- **Umum:** Estimasi BUP adalah 58 tahun.\n- **Notifikasi:** Di Dashboard Analytics, sistem akan menampilkan daftar pegawai yang memasuki usia kritis (57 tahun ke atas) agar segera diproses administrasinya.\n\n### Dampak ke Payroll:\nPegawai yang telah melewati BUP secara otomatis akan disaring (filter) keluar dari perhitungan payroll bulanan dan estimasi untuk periode mendatang.",
            ]
        );

        \App\Models\HelpArticle::updateOrCreate(
            ['slug' => 'posting-data-penguncian'],
            [
                'title' => 'Posting Data (Penguncian Periode)',
                'category' => 'Keamanan',
                'keywords' => 'posting, kunci, lock, periode, finalisasi',
                'content' => "### Fungsi Posting:\nFitur ini berfungsi untuk mengunci data payroll pada bulan/tahun tertentu agar tidak dapat diubah lagi oleh operator manapun.\n\n### Cara Melakukan Posting:\n1. Masuk ke menu **Manajemen Sistem > Posting Data**.\n2. Pilih Bulan, Tahun, dan Jenis Gaji yang ingin dikunci.\n3. Klik **Kunci Data**.\n\n### Catatan Penting:\nData yang sudah diposting bersifat FINAL. Jika terdapat kesalahan data setelah diposting, Bapak harus membuka kunci (Unposting) terlebih dahulu melalui menu yang sama.",
            ]
        );

        \App\Models\HelpArticle::updateOrCreate(
            ['slug' => 'peta-navigasi-struktur-menu'],
            [
                'title' => 'Peta Navigasi & Struktur Menu',
                'category' => 'Sistem',
                'keywords' => 'menu, navigasi, fitur, struktur',
                'content' => "### Ikhtisar Menu Aplikasi\nAplikasi ini dibagi menjadi 5 kategori utama di Sidebar:\n\n#### 1. PPPK-PW (Paruh Waktu)\nKhusus untuk pengelolaan data PPPK Paruh Waktu.\n- **Dashboard PW:** Ringkasan data real-time.\n- **Pegawai PW:** Daftar personil PPPK-PW.\n- **Payroll PW:** Pengelolaan pembayaran/gaji bulanan.\n- **Trace Gaji:** Pelacakan detail riwayat gaji individual.\n- **THR PPPK-PW:** Modul perhitungan THR Paruh Waktu.\n- **Gaji 13 PPPK-PW:** Modul perhitungan Gaji ke-13 Paruh Waktu.\n\n#### 2. PNS & PPPK\nPengelolaan data pegawai ASN (PNS dan PPPK).\n- **Dashboard PNS:** Statistik pegawai ASN.\n- **Update NIK Massal:** Perbaikan data NIK via Excel.\n- **Master Pegawai (DBF):** Sinkronisasi data dari aplikasi Simgaji.\n- **Upload TPP:** Pengunggahan data TPP khusus.\n\n#### 3. Laporan & Verif\nKumpulan fitur audit dan pelaporan bulanan.\n- **Verifikasi SP2D:** Pencocokan data SIPD dan Simgaji.\n- **Laporan SKPD:** Rekapitulasi laporan per instansi.\n- **Rekon BPJS 4%:** Sinkronisasi iuran BPJS.\n- **Dashboard/Upload TPG:** Pengelolaan Tunjangan Profesi Guru.\n- **Pajak TER (A2):** Laporan pemotongan PPh 21 terintegrasi dengan metode TER dan ekspor Bukti Potong A2.\n\n#### 4. Data Referensi\nPengaturan master data pendukung.\n- **SKPD Mapping:** Pemetaan nama dinas SIPD ke Simgaji.\n- **Ref Satker PNS:** Pengaturan Satuan Kerja.\n- **Sumber Dana PW:** Pengaturan kode pendanaan.\n\n#### 5. Manajemen Sistem\nPengaturan teknis dan keamanan.\n- **Posting Data:** Penguncian data agar tidak bisa diubah.\n- **Manajemen User:** Pengelolaan login dan hak akses.\n- **Riwayat Ekspor:** Audit aktivitas unduh data.\n- **Pusat Bantuan:** (Halaman ini) Dokumentasi mandiri.",
            ]
        );
        \App\Models\HelpArticle::updateOrCreate(
            ['slug' => 'panduan-pph21-ter-bukti-potong'],
            [
                'title' => 'Panduan PPh 21 TER & Bukti Potong A2',
                'category' => 'Pajak',
                'keywords' => 'pph21, ter, a2, pajak, thr, gaji 13, ptkp',
                'content' => "### 1. Dasar Aturan TER (2024)\nSistem mengikuti **PMK No. 168 Tahun 2023** dan **PER-2/2024**. Pemotongan bulanan (Jan-Nov) menggunakan tarif efektif (TER), sedangkan bulan Desember menggunakan Pasal 17 untuk penyeimbangan tahunan.\n\n### 2. Konsolidasi Data (PNS & PPPK)\nSistem secara otomatis menggabungkan seluruh penghasilan pegawai (Gaji Induk, TPP, THR, Gaji 13) meskipun berasal dari sumber data yang berbeda (SimGaji, PW, SIPD) ke dalam satu **Total Bruto Bulanan**.\n\n### 3. Penanganan THR & Gaji 13\n- **Akumulasi Otomatis**: Jika THR/Gaji 13 dibayarkan di bulan yang sama dengan Gaji Induk, sistem akan menjumlahkan keduanya.\n- **Hitung Ulang**: Bapak cukup klik **'Hitung Pajak'** kembali jika ada data penghasilan tambahan baru masuk di bulan tersebut. Sistem akan memperbarui total kewajiban pajak bulanan.\n\n### 4. PTKP Intelligence vs Fixed PTKP\n- **Dinamis**: Sistem secara otomatis membaca status PTKP (Kawin/Anak) dari database utama pegawai.\n- **Locked (Fixed)**: Jika Bapak ingin status pajak tidak berubah selama 1 tahun, Bapak bisa mengaturnya di menu **Referensi > PTKP PNS**. Sistem akan memprioritaskan data 'Locked' ini.\n\n### 5. Bukti Potong A2 (Excel)\nSistem menghasilkan file Excel A2 yang siap diimpor ke aplikasi pajak. Komponen THR/Gaji 13 secara otomatis dipetakan ke **Kolom T (Tunjangan Lain)** agar total penghasilan di breakdown sesuai dengan total pajak yang dipotong.",
            ]
        );
    }
}
