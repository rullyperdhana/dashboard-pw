#!/bin/bash

# ==============================================================================
# aaPanel Deployment Script for SIP-Gaji
# ==============================================================================
# Usage: bash scripts/deploy-aapanel.sh
# ==============================================================================

# 1. Konfigurasi (Sesuaikan jika perlu)
PHP_PATH=$(which php) # Default php. Gunakan path lengkap jika ingin versi spesifik, misal: /www/server/php/81/bin/php
PROJECT_ROOT=$(pwd)

# Warna untuk output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}=== Memulai Proses Update (aaPanel) ===${NC}"

# 2. Tarik kode terbaru
echo -e "${YELLOW}1. Menarik kode dari GitHub...${NC}"
git pull origin main

# 3. Update Backend (Laravel)
echo -e "${YELLOW}2. Memperbarui Backend (Laravel)...${NC}"
cd "$PROJECT_ROOT/dashboard-pw-backend"

# Install dependencies (jika ada update composer.json)
if [ -f "composer.json" ]; then
    composer install --no-dev --optimize-autoloader
fi

# Jalankan migrasi database
$PHP_PATH artisan migrate --force

# Bersihkan & Optimasi Cache
$PHP_PATH artisan config:cache
$PHP_PATH artisan route:cache
$PHP_PATH artisan view:cache

# Set Hak Akses (Standar aaPanel menggunakan user 'www')
echo -e "${YELLOW}3. Mengatur Hak Akses Storage...${NC}"
chown -R www:www storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# 4. Update Frontend (Vue.js)
echo -e "${YELLOW}4. Memperbarui Frontend (Vue.js)...${NC}"
cd "$PROJECT_ROOT/dashboard-pw-frontend"

# Check if node_modules exists, if not install
if [ ! -d "node_modules" ]; then
    echo "Installing frontend dependencies..."
    npm install
fi

# Build aplikasi
npm run build

echo -e "${GREEN}=== Update Berhasil Selesai! ===${NC}"
echo -e "${BLUE}Note: Pastikan 'Running Directory' situs Laravel di aaPanel diarahkan ke /dashboard-pw-backend/public${NC}"
