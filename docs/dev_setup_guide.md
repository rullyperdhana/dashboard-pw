# Panduan Pengaturan Lingkungan Development (Dev) & Alur Produksi

Panduan ini menjelaskan cara memisahkan fitur baru yang sedang dikembangkan (Dev) dari sistem yang sudah berjalan (Production) agar aman dari risiko error di sisi pengguna.

## 1. Strategi Pencabangan (Git Branching)

Saat ini Anda menggunakan branch `main`. Mari kita bagi menjadi dua:
*   **`dev`**: Tempat Anda dan saya bekerja, mencoba fitur baru, dan menguji perbaikan.
*   **`main`**: Kode stabil yang siap diakses oleh pengguna.

### Langkah-langkah di Lokal (Komputer Anda):
1.  **Buat branch dev**:
    ```bash
    git checkout -b dev
    ```
2.  **Push branch dev ke GitHub**:
    ```bash
    git push -u origin dev
    ```

---

## 2. Pengaturan Dev Server di VPS

Gunakan folder dan database terpisah di VPS yang sama.

### A. Database Baru
Buat database baru melalui phpMyAdmin atau MySQL terminal (misal: `u921668730_pw_dev`).

### B. Folder Dev Baru
Masuk ke VPS dan clone repository ke folder baru:
```bash
cd /var/www
git clone -b dev https://github.com/rullyperdhana/dashboard-pw.git dashboard-pw-dev
```

### C. Konfigurasi .env Dev
Copy file `.env` dari production ke dev, lalu update isinya:
```bash
cd dashboard-pw-dev/dashboard-pw-backend
cp .env.example .env (atau copy dari folder production)
```
**PENTING**: Ubah `DB_DATABASE` menjadi database dev yang baru dibuat, dan sesuaikan `APP_URL` menjadi `https://dev.sipgaji.my.id`.

### D. Konfigurasi Nginx / Subdomain
1.  Buat subdomain `dev.sipgaji.my.id` di panel VPS Anda (misal aaPanel atau Nginx manual).
2.  Arahkan *Document Root* frontend ke: `/var/www/dashboard-pw-dev/dashboard-pw-frontend/dist`.
3.  Arahkan API `/api` ke backend dev: `/var/www/dashboard-pw-dev/dashboard-pw-backend/public`.

---

## 3. Script Deploy Dev (`deploy-dev.sh`)

Buat file baru bernama `deploy-dev.sh` di folder root dev Anda di VPS. Isinya hampir sama dengan `deploy.sh` namun menarik data dari branch `dev`.

```bash
#!/bin/bash
# DEPLOY SCRIPT FOR DEV ENVIRONMENT
set -e
PROJECT_ROOT=$(pwd)

echo "=== Memperbarui Lingkungan DEV ==="
git pull origin dev

# Backend
cd "$PROJECT_ROOT/dashboard-pw-backend"
composer install
php artisan migrate --force
php artisan optimize:clear

# Frontend
cd "$PROJECT_ROOT/dashboard-pw-frontend"
npm install
npm run build

echo "✅ Dev Server Berhasil Diperbarui!"
```

### E. Background Worker (Baru)
Karena fitur rekonsiliasi SP2D v4.8.0 bersifat asinkron, Anda harus menjalankan worker pada tab terminal terpisah:
```bash
cd dashboard-pw-backend
php artisan queue:work --timeout=3600
```

---

## 4. Alur Kerja (Workflow) dari Dev ke Production

Ikuti urutan ini setiap kali Anda ingin merilis fitur baru:

### Tahap 1: Pengembangan (Lokal & Dev Server)
1.  Ubah kode di komputer lokal (dalam branch `dev`).
2.  Push ke GitHub: `bash push.sh "pesan update"` (pastikan script push.sh mengarah ke branch `dev`).
3.  Masuk ke folder `dashboard-pw-dev` di VPS, jalankan `bash deploy-dev.sh`.
4.  Cek hasilnya di `https://dev.sipgaji.my.id`.

### Tahap 2: Rilis ke Production (Hanya Jika Sudah Stabil)
1.  **Gabungkan kode** dari `dev` ke `main` di komputer lokal:
    ```bash
    git checkout main
    git merge dev
    git push origin main
    git checkout dev (kembali ke dev untuk kerja lagi)
    ```
2.  Masuk ke **folder production** di VPS (folder lama Anda), jalankan:
    ```bash
    bash deploy.sh
    ```

> [!TIP]
> Dengan alur ini, jika ada error di `https://dev.sipgaji.my.id`, pengguna tidak akan terganggu karena sistem utama (`https://sipgaji.my.id`) tetap berjalan pada kode yang stabil.
