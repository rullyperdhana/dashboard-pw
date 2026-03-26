#!/bin/bash
# ──────────────────────────────────────────────────────────────────────────────
# PUSH SCRIPT - Upload code to GitHub
# Jalankan: bash push.sh "pesan commit"
# ──────────────────────────────────────────────────────────────────────────────

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

set -e

MSG="${1:-update: perbaikan dan peningkatan fitur}"

echo -e "${BLUE}🚀 Memulai proses push ke GitHub...${NC}"
echo -e "${YELLOW}📝 Pesan commit: $MSG${NC}"

# Masuk ke direktori script
cd "$(dirname "$0")"

# Git operations
git add .
if git commit -m "$MSG"; then
    echo -e "${GREEN}✅ Commit berhasil.${NC}"
else
    echo -e "${YELLOW}ℹ️  Tidak ada perubahan yang perlu di-commit.${NC}"
fi

# Detect current branch
CURRENT_BRANCH=$(git branch --show-current)

echo -e "${BLUE}📤 Mengirim ke repository remote (branch: $CURRENT_BRANCH)...${NC}"
git push origin "$CURRENT_BRANCH"

echo -e "\n${GREEN}✨ Selesai! Kode berhasil di-push ke GitHub.${NC}"
echo -e "${BLUE}🔗 Repository: $(git remote get-url origin)${NC}"
echo -e "${YELLOW}👉 Selanjutnya: Masuk ke VPS dan jalankan 'bash deploy.sh'${NC}"
