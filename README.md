# 📊 Dashboard Payroll — PNS, PPPK & PPPK Paruh Waktu

Aplikasi dashboard manajemen dan pelaporan gaji untuk pegawai **PNS**, **PPPK Penuh Waktu**, dan **PPPK Paruh Waktu** pada instansi pemerintah daerah.

---

## ✨ Fitur Utama

| Modul | Deskripsi |
|---|---|
| **Dashboard** | Ringkasan anggaran, jumlah pegawai, dan tren bulanan |
| **Upload Gaji PNS** | Import data gaji PNS dari file Excel |
| **Upload Gaji PPPK** | Import data gaji PPPK Penuh Waktu dari file Excel |
| **Pembayaran PPPK-PW** | Manajemen pembayaran PPPK Paruh Waktu |
| **Upload TPP** | Import data Tambahan Penghasilan Pegawai |
| **Upload TPG** | Import data Tunjangan Profesi Guru (INDUK & SUSULAN) |
| **Laporan Bulanan per SKPD** | Laporan gaji per SKPD dengan tab PNS, PPPK, PW, Gabungan |
| **Laporan Tahunan** | Rekapitulasi gaji 12 bulan per jenis kepegawaian |
| **SKPD Mapping** | Pemetaan nama SKPD dari Excel ke master SKPD |
| **Pengaturan** | Konfigurasi JKK/JKM, estimasi anggaran |
| **Export Excel & PDF** | Export laporan sesuai tab yang aktif |

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
│   │   │   ├── PnsPayrollController.php
│   │   │   ├── ReportController.php
│   │   │   ├── SkpdMappingController.php
│   │   │   └── ...
│   │   ├── Exports/          # Excel export classes
│   │   └── Models/
│   ├── database/migrations/
│   ├── routes/api.php
│   └── .env.example
│
├── dashboard-pw-frontend/    # Vue 3 SPA
│   ├── src/
│   │   ├── views/            # Halaman utama
│   │   │   ├── Dashboard.vue
│   │   │   ├── SkpdMonthlyReport.vue
│   │   │   ├── PnsDashboard.vue
│   │   │   └── Settings/
│   │   ├── components/       # Navbar, Sidebar, dll
│   │   ├── router/
│   │   └── api.js            # Axios instance
│   └── vite.config.js
│
└── push.sh                   # Script push ke GitHub
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

## 📦 Deploy Update ke GitHub

Setelah menambah fitur baru, jalankan:

```bash
bash push.sh "deskripsi perubahan"
```

Atau manual:

```bash
git add .
git commit -m "feat: tambah fitur X"
git push
```

---

## 🗄️ Database Utama

| Tabel | Deskripsi |
|---|---|
| `gaji_pns` | Data gaji bulanan PNS |
| `gaji_pppk` | Data gaji bulanan PPPK Penuh Waktu |
| `tb_payment` | Header pembayaran PPPK Paruh Waktu |
| `tb_payment_detail` | Detail pembayaran per pegawai PW |
| `pegawai_pw` | Data master pegawai PPPK Paruh Waktu |
| `skpd` | Master data SKPD |
| `skpd_mapping` | Pemetaan nama SKPD dari Excel ke master |
| `settings` | Konfigurasi JKK, JKM, dll |
| `users` | Akun pengguna |

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

## 👤 Developer

**Rully Perdhana**  
📧 rullyperdhana@email.com
