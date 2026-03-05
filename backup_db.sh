#!/bin/bash

# ──────────────────────────────────────────────────────────────────────────────
# BACKUP DATABASE SCRIPT - SIP-Gaji
# ──────────────────────────────────────────────────────────────────────────────

# 1. Configuration
BACKUP_DIR="/www/wwwroot/dashboard-pw/backups"
DB_NAME="dashboard_pw" # Sesuaikan dengan nama DB di VPS
DB_USER="root"         # Sesuaikan dengan user DB di VPS
DATE=$(date +%Y-%m-%d_%H%M%S)
RETENTION_DAYS=7

# Load DB credentials from .env if available
if [ -f "/www/wwwroot/dashboard-pw/dashboard-pw-backend/.env" ]; then
    DB_NAME=$(grep DB_DATABASE /www/wwwroot/dashboard-pw/dashboard-pw-backend/.env | cut -d '=' -f2)
    DB_USER=$(grep DB_USERNAME /www/wwwroot/dashboard-pw/dashboard-pw-backend/.env | cut -d '=' -f2)
    DB_PASS=$(grep DB_PASSWORD /www/wwwroot/dashboard-pw/dashboard-pw-backend/.env | cut -d '=' -f2)
fi

# 2. Setup directory
mkdir -p "$BACKUP_DIR"

# 3. Perform backup
echo "Starting backup for database: $DB_NAME..."
mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/db_backup_$DATE.sql"

if [ $? -eq 0 ]; then
    echo "✅ Backup success: $BACKUP_DIR/db_backup_$DATE.sql"
    
    # 4. Cleanup old backups (Retention)
    echo "Cleaning up backups older than $RETENTION_DAYS days..."
    find "$BACKUP_DIR" -name "db_backup_*.sql" -type f -mtime +$RETENTION_DAYS -delete
    echo "✅ Cleanup done."
else
    echo "❌ Backup failed!"
    exit 1
fi
