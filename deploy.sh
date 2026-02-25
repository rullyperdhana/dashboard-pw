#!/bin/bash
# ──────────────────────────────────────────────────────────────────────────────
# DEPLOY SCRIPT - Pull and Update on VPS
# Jalankan: bash deploy.sh
# ──────────────────────────────────────────────────────────────────────────────

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

set -e

PROJECT_ROOT=$(pwd)

echo -e "${BLUE}=== Memulai Proses Update di VPS ===${NC}"

# 1. Pull Kode Terbaru
echo -e "${YELLOW}1. Menarik data terbaru dari GitHub...${NC}"
git pull origin main

# 2. Update Backend (Laravel)
echo -e "${YELLOW}2. Memperbarui Backend...${NC}"
cd "$PROJECT_ROOT/dashboard-pw-backend"
if [ -f "composer.json" ]; then
    composer install --no-dev --optimize-autoloader
fi
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan view:cache

# 3. Update Frontend (Vue.js)
echo -e "${YELLOW}3. Memperbarui Frontend...${NC}"
cd "$PROJECT_ROOT/dashboard-pw-frontend"
if [ ! -d "node_modules" ]; then
    npm install
fi
npm run build

# 4. Finalisasi Hak Akses (Opsional, sesuaikan dengan user web server Anda)
cd "$PROJECT_ROOT"
# Uncomment baris di bawah jika Anda menggunakan aaPanel (user www)
# chown -R www:www dashboard-pw-backend/storage dashboard-pw-backend/bootstrap/cache

echo -e "\n${GREEN}✅ SIP-Gaji Berhasil Diperbarui!${NC}"
echo -e "${BLUE}──────────────────────────────────────────────────────────────────────────────${NC}"
