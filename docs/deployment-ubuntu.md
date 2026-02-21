# Panduan Deployment ke Server Ubuntu Sendiri

Panduan ini menjelaskan langkah-langkah untuk mendeploy aplikasi Dashboard Payroll ke server Ubuntu Anda sendiri.

---

## 1. Persiapan Server

Pastikan server Anda menjalankan **Ubuntu 22.04 LTS** atau yang lebih baru.

### Jalankan Script Setup Otomatis

Kami telah menyediakan script untuk menginstall semua kebutuhan environment (PHP 8.4, Nginx, MySQL, Node.js, Composer).

1. Clone repo di server:
   ```bash
   git clone https://github.com/rullyperdhana/dashboard-pw.git /var/www/dashboard-pw
   cd /var/www/dashboard-pw
   ```
2. Jalankan setup:
   ```bash
   sudo bash scripts/setup-ubuntu.sh
   ```

---

## 2. Konfigurasi Backend (Laravel)

1. Masuk ke folder backend:
   ```bash
   cd /var/www/dashboard-pw/dashboard-pw-backend
   ```
2. Setup Environment:
   ```bash
   cp .env.example .env
   ```
3. Edit file `.env` dan sesuaikan database:
   ```text
   DB_DATABASE=dashboard_pw
   DB_USERNAME=root
   DB_PASSWORD=(password_mysql_anda)
   ```
4. Jalankan perintah Laravel:
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan key:generate
   php artisan migrate --force
   ```

---

## 3. Konfigurasi Frontend (Vue)

1. Masuk ke folder frontend:
   ```bash
   cd /var/www/dashboard-pw/dashboard-pw-frontend
   ```
2. Build aplikasi:
   ```bash
   npm install
   npm run build
   ```
   *Hasil build akan tersimpan di folder `dist/`.*

---

## 4. Konfigurasi Web Server (Nginx)

Buat file konfigurasi Nginx:
```bash
sudo nano /etc/nginx/sites-available/dashboard-pw
```

Gunakan template berikut:
```nginx
server {
    listen 80;
    server_name IP_ADDRESS_ATAU_DOMAIN;

    # Frontend (Vue SPA)
    location / {
        root /var/www/dashboard-pw/dashboard-pw-frontend/dist;
        index index.html;
        try_files $uri $uri/ /index.html;
    }

    # Backend API (Laravel)
    location /api {
        alias /var/www/dashboard-pw/dashboard-pw-backend/public;
        try_files $uri $uri/ @backend;
    }

    location @backend {
        rewrite /api/(.*)$ /api/index.php?/$1 last;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME /var/www/dashboard-pw/dashboard-pw-backend/public/index.php;
        include fastcgi_params;
    }
}
```

Aktifkan konfigurasi:
```bash
sudo ln -s /etc/nginx/sites-available/dashboard-pw /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

---

## 5. Deployment Update Selanjutnya

Setiap ada penambahan fitur/update, Anda cukup menjalankan script deploy tunggal:

```bash
cd /var/www/dashboard-pw
bash scripts/deploy.sh
```

Script ini akan:
1. `git pull` dari GitHub
2. Update backend (composer, migrate, cache)
3. Re-build frontend (npm build)

---

## 🔐 Keamanan Tambahan

1. **HTTPS**: Gunakan Certbot untuk SSL gratis:
   ```bash
   sudo apt install certbot python3-certbot-nginx
   sudo certbot --nginx -d DOMAIN_ANDA
   ```
2. **Database**: Pastikan MySQL tidak bisa diakses dari luar (bind-address di 127.0.0.1).
