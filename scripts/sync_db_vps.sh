#!/bin/bash

# ──────────────────────────────────────────────────────────────────────────────
# SYNC DATABASE FROM VPS TO LOCALHOST
# Jalankan di LOCALHOST (Mac/Linux)
# ──────────────────────────────────────────────────────────────────────────────

# 1. Konfigurasi VPS (Sesuaikan dengan server Anda)
VPS_USER="root"
VPS_HOST="sipgaji.my.id" # Ganti dengan IP atau domain VPS Anda
VPS_DB_NAME="dashboard_pw"

# 2. Konfigurasi LOCALHOST
LOCAL_DB_NAME="dashboardd"
LOCAL_DB_USER="root"
LOCAL_DB_PASS="root"
LOCAL_DB_PORT="8889"

echo "=== Memulai Sinkronisasi Database VPS -> Localhost ==="
echo "Mengambil data dari VPS: $VPS_HOST ($VPS_DB_NAME)..."

# 3. Stream data menggunakan SSH (Dump di VPS -> Restore di Local)
# Kita menggunakan gzip untuk kompresi saat pengiriman data lewat jaringan
ssh $VPS_USER@$VPS_HOST "mysqldump -u root -p $VPS_DB_NAME | gzip -c" | gunzip -c | /Applications/MAMP/Library/bin/mysql -u$LOCAL_DB_USER -p$LOCAL_DB_PASS -P$LOCAL_DB_PORT $LOCAL_DB_NAME

if [ $? -eq 0 ]; then
    echo "✅ Berhasil! Database localhost ($LOCAL_DB_NAME) sudah sama dengan VPS."
else
    echo "❌ Gagal melakukan sinkronisasi."
    echo "Tips: Pastikan koneksi SSH sudah disetup (Public Key) agar tidak ditanya password terus menerus."
    exit 1
fi
