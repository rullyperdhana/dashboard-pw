#!/bin/bash

# ==============================================================================
# 🚀 MONSTER VPS OPTIMIZATION SCRIPT (16 Core / 16 GB RAM)
# ==============================================================================
# Optimized for: PHP 8.3, Nginx, MariaDB/MySQL (aaPanel / Generic Linux)
# ==============================================================================

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${BLUE}============================================================${NC}"
echo -e "${BLUE}   🔥 HIGH-PERFORMANCE TUNING (16GB RAM / 16 CORES)   ${NC}"
echo -e "${BLUE}============================================================${NC}"

if [ "$EUID" -ne 0 ]; then 
  echo -e "${RED}Harap jalankan sebagai root (sudo bash ...)${NC}"
  exit 1
fi

# 1. PHP-FPM & PHP.INI TUNING (Targeting PHP 8.3)
echo -e "${GREEN}[1/3] Mengoptimalkan PHP 8.3 Limits & FPM...${NC}"

# Detect PHP 8.3 paths (Common in aaPanel/Ubuntu)
PHP_INI="/www/server/php/83/etc/php.ini"
PHP_FPM_CONF="/www/server/php/83/etc/php-fpm.conf"

if [ -f "$PHP_INI" ]; then
    # Increase limits for massive PDF generation
    sed -i "s/memory_limit = .*/memory_limit = 4096M/" "$PHP_INI"
    sed -i "s/max_execution_time = .*/max_execution_time = 600/" "$PHP_INI"
    sed -i "s/max_input_time = .*/max_input_time = 600/" "$PHP_INI"
    sed -i "s/post_max_size = .*/post_max_size = 100M/" "$PHP_INI"
    sed -i "s/upload_max_filesize = .*/upload_max_filesize = 100M/" "$PHP_INI"
    echo -e "✅ PHP.ini: Memory 4GB & Execution 600s applied."
fi

# PHP-FPM Performance (Static mode for high reliability on 16GB RAM)
# Path might be different (e.g., etc/php-fpm.d/www.conf), checking common locations
FPM_WWW_CONF="/www/server/php/83/etc/php-fpm.d/www.conf"
[ ! -f "$FPM_WWW_CONF" ] && FPM_WWW_CONF="/www/server/php/83/etc/php-fpm.conf"

if [ -f "$FPM_WWW_CONF" ]; then
    sed -i "s/pm = .*/pm = static/" "$FPM_WWW_CONF"
    sed -i "s/pm.max_children = .*/pm.max_children = 100/" "$FPM_WWW_CONF"
    echo -e "✅ PHP-FPM: Static mode (100 children) applied."
fi

# 2. NGINX TIMEOUT TUNING
echo -e "${GREEN}[2/3] Mengoptimalkan Nginx Timeouts...${NC}"
NGINX_CONF="/www/server/nginx/conf/nginx.conf"
if [ -f "$NGINX_CONF" ]; then
    # Add or update timeout settings in http block (heuristic)
    # This is safer to do via aaPanel UI, but we add to a custom include if possible
    echo -e "ℹ️  Nginx timeouts sebaiknya dicek juga via Dashboard aaPanel (Set ke 600s)."
fi

# 3. DATABASE (MySQL/MariaDB) RECOMMENDATION
echo -e "${GREEN}[3/3] Menyiapkan Saran Konfigurasi Database...${NC}"
echo -e "${YELLOW}PENTING: Gunakan setelan ini di Dashboard aaPanel > MySQL > Settings > Optimization:${NC}"
echo -e "------------------------------------------------------------"
echo -e "  - innodb_buffer_pool_size : ${GREEN}8192M${NC} (8GB)"
echo -e "  - key_buffer_size         : ${GREEN}512M${NC}"
echo -e "  - query_cache_size        : ${GREEN}256M${NC}"
echo -e "  - tmp_table_size          : ${GREEN}256M${NC}"
echo -e "  - max_connections         : ${GREEN}1000${NC}"
echo -e "------------------------------------------------------------"

echo -e "${BLUE}============================================================${NC}"
echo -e "${BLUE}   ✨ TUNING SELESAI!   ${NC}"
echo -e "${BLUE}============================================================${NC}"
echo -e "Silakan restart service untuk menerapkan perubahan:"
echo -e "${YELLOW}/etc/init.d/php-fpm-83 restart${NC}"
echo -e "${YELLOW}/etc/init.d/nginx restart${NC}"
echo -e "${YELLOW}/etc/init.d/mysql restart${NC}"
