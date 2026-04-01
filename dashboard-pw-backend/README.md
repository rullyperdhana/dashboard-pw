# 🚀 Dashboard Payroll Backend (Laravel 10)

API core untuk aplikasi manajemen penggajian kalsel.

## ✨ Fitur Backend Baru (v4.5.0)
- **Advanced Budget Prediction Engine**: Menggunakan rata-rata 3 bulan (Induk) x 14 bulan basis. Mendukung simulasi penambahan pegawai baru dan kenaikan gaji pokok.
- **Smart Dashboard Caching**: Menggunakan Redis/File cache untuk menyimpan data ringkasan eksekutif dan dashboard statistik (TTL 6 jam).
- **Automated Cache Invalidation**: Trait `CacheClearer` yang diintegrasikan pada 5+ controller import untuk pembersihan cache otomatis saat data berubah.
- **Manual Cache Management Endpoint**: Endpoint baru `POST /api/cache/clear` khusus Superadmin.

## 🛠️ Tech Stack
- PHP 8.4
- Laravel 10
- MySQL
- Laravel Sanctum (Auth)
- Maatwebsite Excel (Import/Export)

## 📁 Struktur Penting
- `app/Http/Controllers/Api/BudgetPredictionController.php`: Logika simulasi anggaran.
- `app/Services/DashboardService.php`: Layanan dashboard dengan caching.
- `app/Traits/CacheClearer.php`: Logika pembersihan cache otomatis.
- `routes/api.php`: Definisi endpoint API.

## 🚀 Instalasi
1. `composer install`
2. `cp .env.example .env` (Konfigurasi DB_*)
3. `php artisan key:generate`
4. `php artisan migrate`
5. `php artisan serve`
