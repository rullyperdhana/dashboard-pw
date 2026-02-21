#!/bin/bash

# setup-ubuntu.sh - Environment setup for Ubuntu Server
# Usage: sudo bash setup-ubuntu.sh

set -e

echo "Updating system..."
apt update && apt upgrade -y

echo "Installing common dependencies..."
apt install -y software-properties-common curl git zip unzip libpng-dev libzip-dev

echo "Adding PHP 8.4 repository..."
add-apt-repository ppa:ondrej/php -y
apt update

echo "Installing PHP 8.4 and extensions..."
apt install -y php8.4-fpm php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-gd php8.4-bcmath php8.4-intl php8.4-sqlite3

echo "Installing Nginx..."
apt install -y nginx

echo "Installing MySQL Server..."
apt install -y mysql-server

echo "Installing Node.js and NPM..."
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

echo "Installing Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

echo "Setting up MySQL database..."
# Non-interactive MySQL setup (caution: no password set by default)
mysql -e "CREATE DATABASE IF NOT EXISTS dashboard_pw;"
echo "Database 'dashboard_pw' created."

echo "Environment setup complete!"
echo "Please remember to set a secure MySQL password if needed."
