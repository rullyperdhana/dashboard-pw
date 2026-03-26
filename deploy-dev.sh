#!/bin/bash
# ──────────────────────────────────────────────────────────────────────────────
# DEPLOY SCRIPT - DEV ENVIRONMENT
# Jalankan: bash deploy-dev.sh
# ──────────────────────────────────────────────────────────────────────────────

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

set -e

PROJECT_ROOT=$(pwd)

echo -e "${BLUE}=== Memulai Update LINGKUNGAN DEV ===${NC}"

# 1. Pull dari branch dev
echo -e "${YELLOW}1. Menarik data terbaru dari branch DEV...${NC}"
git pull origin dev

# 2. Update Backend (Laravel)
echo -e "${YELLOW}2. Memperbarui Backend Dev...${NC}"
cd "$PROJECT_ROOT/dashboard-pw-backend"
if [ -f "composer.json" ]; then
    composer install
fi
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache

# 3. Update Frontend (Vue.js)
echo -e "${YELLOW}3. Memperbarui Frontend Dev...${NC}"
cd "$PROJECT_ROOT/dashboard-pw-frontend"
npm install
npm run build

echo -e "\n${GREEN}✅ SIP-Gaji DEV Berhasil Diperbarui!${NC}"
echo -e "${BLUE}──────────────────────────────────────────────────────────────────────────────${NC}"
