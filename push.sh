#!/bin/bash
# deploy.sh — Script untuk push kode ke GitHub
# Jalankan: bash deploy.sh "pesan commit"
# ──────────────────────────────────────────────

set -e

MSG="${1:-update kode}"

echo "🚀 Memulai push ke GitHub..."
echo "📝 Pesan commit: $MSG"

cd "$(dirname "$0")"

git add .
git commit -m "$MSG" || echo "ℹ️  Tidak ada perubahan untuk di-commit"
git push origin main

echo ""
echo "✅ Selesai! Kode berhasil di-push ke GitHub."
echo "🔗 Lihat di: $(git remote get-url origin)"
