#!/bin/bash

# deploy.sh - Pull latest code and rebuild
# Usage: bash scripts/deploy.sh

set -e

PROJECT_ROOT=$(pwd)

echo "Pulling latest code from GitHub..."
git pull origin main

# Backend
echo "Updating Backend..."
cd $PROJECT_ROOT/dashboard-pw-backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Frontend
echo "Updating Frontend..."
cd $PROJECT_ROOT/dashboard-pw-frontend
npm install
npm run build

echo "Deployment finished successfully!"
