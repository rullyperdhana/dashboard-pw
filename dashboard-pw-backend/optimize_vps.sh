#!/bin/bash

# ==============================================================================
# VPS Optimization Script for Laravel & High Performance
# ==============================================================================
# Target: PHP 8.x, Nginx, MySQL/MariaDB
# Description: This script performs safe optimizations for memory, caching, and 
#              database performance.
# ==============================================================================

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}============================================================${NC}"
echo -e "${BLUE}   🚀 VPS OPTIMIZATION SCRIPT - DASHBOARD-PW   ${NC}"
echo -e "${BLUE}============================================================${NC}"

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
  echo -e "${YELLOW}Please run as root or using sudo${NC}"
  exit 1
fi

# 1. LARAVEL OPTIMIZATION
echo -e "${GREEN}[1/5] Running Laravel Optimization...${NC}"
# Assuming standard directory structure, try to find artisan
if [ -f "artisan" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    echo -e "✅ Laravel caches updated."
else
    echo -e "⚠️  artisan file not found in current directory. Skipping Laravel cache."
fi

# 2. PHP OPCACHE & MEMORY TUNING
echo -e "${GREEN}[2/5] Optimizing PHP Configuration...${NC}"
# Find the active php.ini
PHP_INI=$(php --ini | grep "Loaded Configuration File" | awk '{print $4}')
if [ -f "$PHP_INI" ]; then
    echo -e "📄 Found php.ini at: $PHP_INI"
    
    # Enable OPcache if not configured
    if ! grep -q "opcache.enable=1" "$PHP_INI"; then
        echo "opcache.enable=1" >> "$PHP_INI"
        echo "opcache.memory_consumption=256" >> "$PHP_INI"
        echo "opcache.interned_strings_buffer=16" >> "$PHP_INI"
        echo "opcache.max_accelerated_files=20000" >> "$PHP_INI"
        echo "opcache.validate_timestamps=0" >> "$PHP_INI" # For production
        echo -e "✅ OPcache settings added to $PHP_INI"
    else
        echo -e "ℹ️  OPcache already enabled."
    fi
fi

# 3. SYSTEM SWAP CHECK
echo -e "${GREEN}[3/5] Checking System SWAP...${NC}"
SWAP_EXISTS=$(free | grep "Swap" | awk '{print $2}')
if [ "$SWAP_EXISTS" -eq 0 ]; then
    echo -e "⚠️  No SWAP file found. Creating 2GB SWAP for safety..."
    fallocate -l 2G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile
    echo '/swapfile none swap sw 0 0' >> /etc/fstab
    echo -e "✅ 2GB SWAP file created and activated."
else
    echo -e "✅ SWAP already exists ($((SWAP_EXISTS/1024)) MB)."
fi

# 4. SYSCTL TWEAKS (Network & File Limits)
echo -e "${GREEN}[4/5] Applying System Tweaks (sysctl)...${NC}"
cat <<EOF > /etc/sysctl.d/99-laravel-tune.conf
vm.swappiness = 10
fs.file-max = 2097152
net.core.somaxconn = 65535
net.ipv4.tcp_max_syn_backlog = 65535
net.ipv4.tcp_slow_start_after_idle = 0
EOF
sysctl -p /etc/sysctl.d/99-laravel-tune.conf > /dev/null
echo -e "✅ System kernel parameters optimized."

# 5. DATABASE CACHE SUGGESTION
echo -e "${GREEN}[5/5] Database Recommendation...${NC}"
echo -e "💡 TIP: Ensure your database has a large 'query_cache_size' and 'innodb_buffer_pool_size'."
echo -e "   Run 'mysqltuner' for specific DB advice if available."

echo -e "${BLUE}============================================================${NC}"
echo -e "${BLUE}   ✨ OPTIMIZATION COMPLETED!   ${NC}"
echo -e "${BLUE}============================================================${NC}"
echo -e "NOTE: You may need to restart PHP-FPM and Nginx for changes to take effect:"
echo -e "      service php$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)-fpm restart"
echo -e "      service nginx restart"
