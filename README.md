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
| **Laporan Bulanan per SKPD** | Laporan gaji per SKPD dengan tab PNS, PPPK, PW, Gabungan |
| **Laporan Tahunan** | Rekapitulasi gaji 12 bulan per jenis kepegawaian |
| **Laporan Individual** | Slip gaji per pegawai dengan export PDF |
| **Rekon BPJS 4%** | Rekonsiliasi BPJS Kesehatan 4% dengan rumus UMP |
| **Estimasi JKK/JKM/JKN** | Estimasi iuran ketenagakerjaan per SKPD |
| **Sumber Dana SKPD** | Setting APBD/BLUD per SKPD (bulk update) |
| **Trace Gaji Pegawai** | Riwayat gaji per pegawai + kelola status & SK |
| **SKPD Mapping** | Pemetaan nama SKPD dari Excel ke master SKPD |
| **Manajemen User** | Akun admin & admin SKPD |
| **Export Excel & PDF** | Export laporan sesuai tab yang aktif |

---

## 🔧 Fitur Terbaru (v2.5)

### Sumber Dana APBD / BLUD
- Kolom `sumber_dana` pada tabel `pegawai_pw` (default: APBD)
- Halaman setting bulk per SKPD: `/settings/sumber-dana`
- Filter sumber dana pada Rekon BPJS 4%

### Rekon BPJS 4% dengan Rumus UMP
- **Rumus:** Jika gaji pokok ≥ UMP → BPJS = Gaji × 4%. Jika gaji < UMP → BPJS = UMP × 4%
- UMP default: **Rp 3.725.000** (Prov. Kalsel), dapat diubah via Setting UMP
- Kolom basis hitung (UMP/GAJI) dan statistik per SKPD

### Status Pegawai "Meninggal"
- Ditambahkan ke daftar status: Aktif, Pensiun, Keluar, Diberhentikan, **Meninggal**
- Tersedia di filter, form edit, dan trace gaji

### Dark Mode Fix
- Semua halaman mendukung mode gelap (dark mode) dengan benar
- Menggunakan CSS variable Vuetify (`--v-theme-surface`, `text-medium-emphasis`)

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
# 1. Push ke GitHub
bash push.sh "deskripsi perubahan"

# 2. Di VPS
bash deploy.sh
```

Lihat [README_DEPLOY.md](README_DEPLOY.md) untuk panduan lengkap.

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
| `settings` | Konfigurasi JKK, JKM, UMP, dll |
| `users` | Akun pengguna |
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
| `/reports/skpd-monthly` | Laporan Bulanan per SKPD |
| `/bpjs-rekon` | Rekon BPJS 4% |
| `/settings/pppk` | Estimasi JKK/JKM/JKN |
| `/settings/sumber-dana` | Setting Sumber Dana per SKPD |
| `/settings/users` | Manajemen User |
| `/upload/pns` | Upload Gaji PNS |
| `/upload/pppk` | Upload Gaji PPPK |
| `/upload/tpp` | Upload TPP |
| `/upload/tpg` | Upload TPG |

---

## 👤 Developer

**Rully Perdhana**  
📧 rullyperdhana@email.com
