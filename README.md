# 📊 Dashboard Payroll — PNS, PPPK & PPPK Paruh Waktu

Aplikasi dashboard manajemen dan pelaporan gaji untuk pegawai **PNS**, **PPPK Penuh Waktu**, dan **PPPK Paruh Waktu** pada instansi pemerintah daerah.

---

## ✨ Fitur Utama

| Modul | Deskripsi |
|---|---|
| **Dashboard PNS** | Ringkasan anggaran PNS & PPPK, tren bulanan, laporan tahunan |
| **Dashboard PW** | Ringkasan gaji PPPK Paruh Waktu per periode |
| **Upload Gaji PNS** | Import data gaji PNS dari file Excel |
| **Upload Gaji PPPK** | Import data gaji PPPK Penuh Waktu dari file Excel |
| **Pembayaran PPPK-PW** | Manajemen pembayaran PPPK Paruh Waktu |
| **Upload TPP** | Import data Tambahan Penghasilan Pegawai |
| **Upload TPG** | Import data Tunjangan Profesi Guru (INDUK & SUSULAN) |
| **Daftar Pegawai PW** | Data master pegawai PW dengan status, sumber dana, dokumen |
| **Master Pegawai DBF** | Sinkronisasi data induk pegawai & keluarga dari file DBF |
| **Laporan Bulanan per SKPD** | Laporan gaji per SKPD dengan tab PNS, PPPK, PW, Gabungan |
| **Laporan Tahunan** | Rekapitulasi gaji 12 bulan per jenis kepegawaian |
| **Laporan Individual** | Slip gaji per pegawai dengan export PDF |
| **Rekon BPJS 4%** | Rekonsiliasi BPJS Kesehatan 4% dengan rumus UMP |
| **Estimasi JKK/JKM/JKN** | Estimasi iuran ketenagakerjaan per SKPD |
| **Sumber Dana SKPD** | Setting APBD/BLUD per SKPD (bulk update) |
| **Trace Gaji Pegawai** | Riwayat gaji per pegawai + kelola status & SK |
| **SKPD Mapping** | Pemetaan Kode & Nama SKPD untuk akurasi laporan |
| **Manajemen User** | Akun admin & admin SKPD |
| **Status Pajak (PTKP)**| Kelola status PTKP statis tahunan (K/0, TK/1, dll) |
| **API Integrasi**    | Endpoint API Key untuk integrasi sistem Simgaji |
| **Pajak TER (A2)**  | Perhitungan PPh 21 TER & Export Bukti Potong A2 |
| **Log Keamanan**     | Audit trail login (berhasil/gagal) & deteksi brute-force |
| **Verifikasi SP2D**  | Rekon rincian gaji SIMGAJI vs Realisasi SIPD (Gaji & TPP) |
| **Export Excel & PDF** | Export laporan sesuai tab yang aktif |

---

---
 
### 📑 Pajak TER & Bukti Potong A2 (v3.8)
- **Unified SKPD Engine:** Perhitungan PPh 21 kini menggunakan `skpd_id` tunggal yang terintegrasi untuk seluruh jenis pegawai (PNS & PPPK), memastikan laporan akurat meskipun dari sumber data yang berbeda.
- **Bulk Management Tools:** Fitur ceklist (multi-select) untuk penghapusan data masal, memudahkan Superadmin dalam melakukan pembersihan atau pemutakhiran data perhitungan dalam jumlah besar.
- **Administrative Controls:** Tombol hapus khusus Superadmin per baris laporan dengan konfirmasi keamanan untuk mencegah kesalahan operasional.
- **Automatic TER Calculation:** Sistem otomatis menggunakan tarif TER (Kategori A/B/C) untuk masa pajak Januari-November.
- **December Final Tax:** Perhitungan Pasal 17 otomatis pada bulan Desember untuk menyeimbangkan total pajak tahunan.
- **PTKP Intelligence:** Pemetaan otomatis status PTKP (Kawin/Anak) pegawai ke Kategori TER yang sesuai secara *real-time*.
- **Official A2 Export:** Export Bukti Potong A2 ke Excel sesuai format resmi.

### 📄 Laporan & Transparansi (v3.6)
- **AI-Driven Analytics (TAPD):** Dashboard cerdas untuk Tim Anggaran Pemerintah Daerah yang mensimulasikan kenaikan anggaran berdasarkan data rill **KGB** (2 tahunan), **Kenaikan Pangkat** (4 tahunan), dan jadwal pensiun massal.
- **Executive Mobile Dashboard:** Tampilan khusus yang dioptimalkan untuk perangkat mobile/smartphone bagi pimpinan (Kepala Dinas/Bupati) untuk memantau realisasi belanja pegawai secara *real-time*.
- **PWA Ready:** Aplikasi kini mendukung instalasi langsung ke layar utama HP (*Add to Home Screen*) untuk akses cepat modul eksekutif.
- **Generalized Extra Payroll System:** Restrukturisasi total modul THR menjadi sistem *Extra Payroll* universal yang mendukung berbagai jenis pembayaran tambahan seperti **Gaji 13**.
- **THR & Gaji 13 DBF Import:** Mendukung import file `GAJI.DBF` khusus untuk periode THR dan Gaji 13, memastikan data terpisah dari payroll bulanan reguler.
- **TPP THR & TPP 13 Support:** Kemampuan untuk mengunggah dan memberlakukan data TPP (Tambahan Penghasilan Pegawai) khusus untuk pembayaran THR dan Gaji 13.
- **Improved Prediction Model:** Perhitungan proyeksi anggaran kini menggunakan kombinasi regresi linear data historis 12 bulan terakhir (hanya data 'Induk') untuk mencegah data THR/Gaji 13 mengganggu akurasi prediksi.
- **Data Terdeteksi Terlewat:** Fitur deteksi otomatis pegawai yang memiliki slip gaji basis namun belum masuk ke dalam laporan THR/Gaji 13, lengkap dengan **Alasan Ketidakhadiran** (misal: "Gaji basis tidak ditemukan").
- **UPT-Aware Reporting:** Laporan otomatis mencakup pegawai di Unit Pelaksana Teknis (UPT) menggunakan prefix SKPD, memastikan sinkronisasi data 100%.
- **TPP Discrepancy Reporting:** Deteksi otomatis selisih pegawai antara data gaji dan file Excel TPP yang tersimpan permanen.
- **Persistent Extra Payroll Management:** Data disimpan permanen di database (`tb_extra_payroll_pppk_pw`), mendukung edit manual, penambahan catatan, dan batch delete.
- **Environment Version Indicator:** Label versi (v3.7.0) kini tampil di pojok kiri bawah menu samping.
- **Server-Side Pagination:** Optimasi performa untuk dataset 6.000+ pegawai agar UI tetap responsif.
- **Dedicated Summary Endpoint:** Tab Rekapitulasi menggunakan endpoint khusus yang sangat ringan untuk perhitungan total anggaran instan.
- **Professional PDF Export:** Redesain total template slip gaji dan payroll dengan layout profesional, border rapi, dan summary box.
- **Digital QR Verification:** Setiap PDF memiliki QR Code unik yang terhubung ke halaman verifikasi publik untuk validasi keaslian dokumen.
- **Nested THR Reporting:** Laporan THR PPPK-PW kini dikelompokkan secara hierarkis: **SKPD -> Sub Kegiatan -> Daftar Pegawai**.
- **SKPD Name Normalization:** Pembersihan otomatis dan penggabungan (merge) nama SKPD yang terduplikasi karena spasi atau perbedaan kode, memastikan filter dropdown selalu bersih dan unik.
- **VPS Migration Resiliency:** Penyesuaian skema database untuk mendukung migrasi yang lebih stabil pada berbagai konfigurasi server VPS (Foreign Key normalization).

### 🔐 Keamanan & Role-Based Access (v3.1)
- **THR Management Restriction:** Fitur Generate, Edit, Tambah, dan Hapus data THR dibatasi khusus untuk akun **Superadmin**.
- **Session Timeout:** Sesi otomatis berakhir setelah 30 menit tidak aktif.
- **Audit Logging:** Pencatatan aktivitas sensitif (hapus data, upload, posting) ke database.
- **Rate Limiting:** Pembatasan percobaan login (5 kali per 15 menit) untuk mencegah brute-force.
- **Konfirmasi Password:** Validasi password admin untuk aksi penghapusan data masal.

### 🛠️ Integrasi Master Data DBF (v2.6)
- **Import Langsung:** Mendukung import file `MST_PGW.DBF`, `KEL.DBF`, dan `GAJI.DBF`.
- **Sinkronisasi Otomatis:** Pemutakhiran data pegawai dan keluarga secara massal.
- **Riwayat Gaji Pokok:** Melacak perubahan gaji pokok pegawai dari waktu ke waktu.
- **Pemetaan SKPD Berbasis Kode:** Menggunakan `kdskpd` untuk akurasi data antar modul.

### 💳 Sumber Dana & BPJS
- **Sumber Dana APBD / BLUD:** Kolom `sumber_dana` khusus untuk pegawai PPPK-PW.
- **Rekon BPJS 4%:** Rumus cerdas berbasis UMP (Gaji Pokok vs Ambang Batas UMP).
- **Status "Meninggal":** Penanganan khusus untuk pelaporan pegawai yang wafat.

---

## 🛠️ Tech Stack

### Backend
- **PHP 8.4** + **Laravel 10**
- **MySQL** — database utama
- **Laravel Sanctum** — autentikasi API token
- **Maatwebsite Excel** — import/export Excel
- **Laravel DomPDF** — generate PDF

### Frontend
- **Vue 3** (Composition API)
- **Vuetify 3** — UI component library
- **Vite** — build tool
- **Axios** — HTTP client

---

## 📁 Struktur Project

```
dashboard-pw/
├── dashboard-pw-backend/     # Laravel API
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   │   ├── AuthController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── BpjsRekonController.php
│   │   │   ├── EmployeeController.php
│   │   │   ├── SettingController.php
│   │   │   ├── SumberDanaSettingController.php
│   │   │   ├── PnsPayrollController.php
│   │   │   ├── ReportController.php
│   │   │   ├── SkpdMappingController.php
│   │   │   └── ...
│   │   ├── Exports/          # Excel export classes
│   │   └── Models/
│   │       ├── Employee.php
│   │       ├── Setting.php
│   │       ├── Payment.php
│   │       └── ...
│   ├── database/migrations/
│   ├── routes/api.php
│   └── .env.example
│
├── dashboard-pw-frontend/    # Vue 3 SPA
│   ├── src/
│   │   ├── views/
│   │   │   ├── Dashboard.vue
│   │   │   ├── PnsDashboard.vue
│   │   │   ├── EmployeeList.vue
│   │   │   ├── EmployeeHistory.vue
│   │   │   ├── BpjsRekon.vue
│   │   │   ├── SumberDanaSetting.vue
│   │   │   ├── SkpdMonthlyReport.vue
│   │   │   └── Settings/
│   │   │       └── PppkSettings.vue
│   │   ├── components/       # Navbar, Sidebar, dll
│   │   ├── router/
│   │   └── api.js            # Axios instance
│   └── vite.config.js
│
├── push.sh                   # Script push ke GitHub
└── deploy.sh                 # Script deploy ke VPS
```

---

## 🚀 Cara Menjalankan (Development)

### 1. Clone repository

```bash
git clone https://github.com/rullyperdhana/dashboard-pw.git
cd dashboard-pw
```

### 2. Setup Backend

```bash
cd dashboard-pw-backend

# Install dependencies
composer install

# Salin dan konfigurasi .env
cp .env.example .env
# Edit: DB_DATABASE, DB_USERNAME, DB_PASSWORD

# Generate app key
php artisan key:generate

# Jalankan migrasi
php artisan migrate

# Jalankan server
php artisan serve
# Backend berjalan di http://localhost:8000
```

### 3. Setup Frontend

```bash
cd dashboard-pw-frontend

# Install dependencies
npm install

# Jalankan dev server
npm run dev
# Frontend berjalan di http://localhost:5173
```

---

## 📦 Deploy ke VPS

```bash
# 1. Push ke GitHub (Lokal)
bash push.sh "deskripsi perubahan"
 
# 2. Update di VPS
bash deploy.sh
```
 
> [!IMPORTANT]
> Untuk update database (migration baru) dan build ulang frontend, cukup jalankan `bash deploy.sh` di root folder VPS.
 
Lihat [README_DEPLOY.md](README_DEPLOY.md) untuk panduan update mesin dan troubleshooting.

---

## 🗄️ Database

| Tabel | Deskripsi |
|---|---|
| `gaji_pns` | Data gaji bulanan PNS |
| `gaji_pppk` | Data gaji bulanan PPPK Penuh Waktu |
| `tb_payment` | Header pembayaran PPPK Paruh Waktu |
| `tb_payment_detail` | Detail pembayaran per pegawai PW |
| `pegawai_pw` | Data master pegawai PPPK Paruh Waktu |
| `skpd` | Master data SKPD |
| `skpd_mapping` | Pemetaan nama SKPD dari Excel ke master |
| `settings` | Konfigurasi JKK, JKM, UMP, Bulan Basis Extra Payroll, dll |
| `users` | Akun pengguna |
| `tb_extra_payroll_pppk_pw` | Data THR & Gaji 13 PPPK-PW yang tersimpan (Database) |
| `pph21_calculations` | Hasil perhitungan pajak bulanan per pegawai |
| `pph21_ter_rates` | Master data tarif efektif rata-rata (Cat A, B, C) |
| `employee_statuses` | Riwayat status pegawai + SK |

### Kolom Penting `pegawai_pw`
| Kolom | Deskripsi |
|---|---|
| `sumber_dana` | Flag sumber pendanaan: `APBD` (default) atau `BLUD` |
| `status` | Status pegawai: Aktif, Pensiun, Keluar, Diberhentikan, Meninggal |

### Pengaturan di Tabel `settings`
| Key | Deskripsi | Default |
|---|---|---|
| `pppk_jkk_percentage` | Persentase iuran JKK | 0.24 |
| `pppk_jkm_percentage` | Persentase iuran JKM | 0.72 |
| `ump_kalsel` | UMP Provinsi Kalimantan Selatan | 3725000 |

---

## 🧮 Rumus Perhitungan

### BPJS 4% (Rekon PPPK Paruh Waktu)
| Kondisi | Formula |
|---|---|
| Gaji Pokok ≥ UMP | BPJS = Gaji Pokok × 4% |
| Gaji Pokok < UMP | BPJS = UMP × 4% (fixed) |

> UMP default Kalsel: **Rp 3.725.000** → BPJS minimum: **Rp 149.000**

### Estimasi JKK/JKM/BPJS Kesehatan
| Item | Formula |
|---|---|
| JKK | Gaji Pokok × JKK% (default 0.24%) |
| JKM | Gaji Pokok × JKM% (default 0.72%) |
| BPJS Kesehatan | MIN(Gaji Pokok + TPP, Rp 12.000.000) × 4% |

---

## 🔐 Akun Default

| Role | Username | Akses |
|---|---|---|
| Admin | *(sesuai setup)* | Full access |
| Admin SKPD | *(sesuai setup)* | SKPD tertentu saja |

---

## 📄 Laporan Bulanan per SKPD

Laporan di `/reports/skpd-monthly` memiliki 4 tab:

| Tab | Kolom |
|---|---|
| **Gabungan** | Ringkasan: Gaji Pokok, Tunjangan, Potongan, Bersih |
| **PNS** | Detail: GAPOK, TJISTRI, TJANAK, TJTPP, ..., PIWP, PPAJAK, BERSIH |
| **PPPK Penuh Waktu** | Detail: sama seperti PNS |
| **PPPK Paruh Waktu** | Ringkasan per SKPD |

Export Excel/PDF secara otomatis menyesuaikan kolom dengan tab yang aktif.

---

## 📂 Halaman Aplikasi

| Route | Halaman |
|---|---|
| `/` | Dashboard PPPK Paruh Waktu |
| `/pns-dashboard` | Dashboard PNS & PPPK |
| `/employees` | Daftar Pegawai PW |
| `/employee-trace` | Trace / Riwayat Gaji Pegawai |
| `/reports/thr-pppk-pw` | Perhitungan THR PPPK-PW |
| `/reports/gaji-13-pppk-pw` | Perhitungan Gaji 13 PPPK-PW |
| `/bpjs-rekon` | Rekon BPJS 4% |
| `/settings/pppk` | Estimasi JKK/JKM/JKN |
| `/settings/sumber-dana` | Setting Sumber Dana per SKPD |
| `/settings/users` | Manajemen User |
| `/upload/pns` | Upload Gaji PNS |
| `/upload/pppk` | Upload Gaji PPPK |
| `/upload/tpp` | Upload TPP |
| `/upload/tpg` | Upload TPG |
| `/reports/pph21` | Laporan PPh 21 TER & Bukti Potong A2 |
| `/settings/tax-status` | Manajemen Status Pajak (PTKP) |

---

## 👤 Developer

**Rully Perdhana**  
📧 rully.perdhana@egmail.com