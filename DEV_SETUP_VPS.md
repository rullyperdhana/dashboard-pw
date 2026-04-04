# Panduan Setup Lingkungan Pengembangan (Dev) di VPS

Dokumen ini menjelaskan cara membuat satu folder terpisah di VPS untuk menjalankan **versi Development (branch `dev`)** tanpa mengganggu fitur yang sudah jalan di produksi.

## Garis Besar Arsitektur
*   **Produksi**: `/www/wwwroot/sip-gaji` (Domain: `sipgaji.my.id`)
*   **Development**: `/www/wwwroot/sip-gaji-dev` (Sub-domain: `dev.sipgaji.my.id`)
*   **Database**: `db_sipgaji` (Prod) vs `db_sipgaji_dev` (Dev)

---

## Langkah 1: Persiapan di aaPanel / Server
1.  **Buat Website Baru**: Di aaPanel, buat website dengan domain `dev.sipgaji.my.id`.
2.  **Buat Database Baru**: Buat database kosong khusus untuk dev, misalnya `db_sipgaji_dev`.
3.  **Catat Path Baru**: Misalnya di `/www/wwwroot/sip-gaji-dev`.

## Langkah 2: Cloning & Setup Awal
Masuk ke Terminal VPS dan jalankan perintah berikut:

```bash
# Pindah ke directory root web server
cd /www/wwwroot/

# Clone repository ke folder baru
git clone https://github.com/rullyperdhana/dashboard-pw.git sip-gaji-dev

# Masuk ke folder baru
cd sip-gaji-dev

# Pindah ke branch dev
git checkout dev
```

## Langkah 3: Konfigurasi `.env`
Salin file `.env` dari versi produksi atau dari contoh:

```bash
cd dashboard-pw-backend
cp .env.example .env
```

**Edit file `.env` menggunakan terminal (nano/vim) atau editor aaPanel:**
```text
APP_NAME="SIP-Gaji DEV"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://dev.sipgaji.my.id

# Hubungkan ke database dev yang baru dibuat
DB_DATABASE=db_sipgaji_dev
DB_USERNAME=user_dev
DB_PASSWORD=password_dev
```

## Langkah 4: Install Dependencies & Migrasi
Jalankan perintah berikut di folder backend:

```bash
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
```

## Langkah 5: Deployment Otomatis (Update Berkala)
Setiap kali ada perubahan di branch `dev` dari laptop lokal, Anda cukup menjalankan perintah berikut di folder `/www/wwwroot/sip-gaji-dev`:

```bash
bash deploy-dev.sh
```

> [!TIP]
> Script `deploy-dev.sh` sudah saya siapkan untuk otomatis melakukan:
> 1. `git pull origin dev`
> 2. `composer install`
> 3. `php artisan migrate`
> 4. `npm run build` (di folder frontend)

---

## Troubleshooting
*   **Frontend blank?**: Pastikan `.env` di folder `dashboard-pw-frontend` memiliki `VITE_API_BASE_URL` yang mengarah ke API sub-domain dev Anda.
*   **Permission error?**: Jalankan `chown -R www:www /www/wwwroot/sip-gaji-dev` untuk memberikan akses ke web server.

---
**Catatan:** Pastikan Anda selalu melakukan `git checkout dev` sebelum menjalankan deployment di lingkungan ini.
