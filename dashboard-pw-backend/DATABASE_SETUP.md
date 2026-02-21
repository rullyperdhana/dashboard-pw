# Setup Local Database untuk Dashboard PPPK

## Langkah-langkah:

### 1. Buat Database Lokal

Jalankan perintah berikut di MySQL:

```sql
CREATE DATABASE dashboard_pw CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Import SQL Dumps

Import semua file SQL yang ada:

```bash
# Dari folder dashboard-pw
mysql -u root -p dashboard_pw < pegawai_pw.sql
mysql -u root -p dashboard_pw < users.sql
mysql -u root -p dashboard_pw < skpd.sql
mysql -u root -p dashboard_pw < kegiatan.sql
mysql -u root -p dashboard_pw < sub_kegiatan.sql
mysql -u root -p dashboard_pw < tb_payment.sql
mysql -u root -p dashboard_pw < tb_payment_detail.sql
mysql -u root -p dashboard_pw < pptk_settings.sql
mysql -u root -p dashboard_pw < rka_settings.sql
```

**ATAU gunakan GUI MySQL** (phpMyAdmin, Sequel Ace, TablePlus):
1. Buka database `dashboard_pw`
2. Import semua file .sql satu per satu

### 3. Verifikasi Import

Jalankan test koneksi:

```bash
cd dashboard-pw-backend
php artisan tinker
```

Kemudian test query:

```php
DB::table('pegawai_pw')->count();
DB::table('users')->count();
DB::table('skpd')->count();
```

### 4. Setup Laravel Selesai

Setelah database berhasil di-import, development bisa dilanjutkan!

---

## Konfigurasi Database

Laravel sudah dikonfigurasi untuk menggunakan database lokal:

- **Host**: 127.0.0.1 (localhost)
- **Port**: 3306
- **Database**: dashboard_pw
- **Username**: root
- **Password**: (kosong atau sesuai MySQL Anda)

Jika password MySQL Anda berbeda, edit file `.env`:

```env
DB_PASSWORD=your_mysql_password
```
