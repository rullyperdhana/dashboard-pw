# Panduan Deployment SIP-Gaji

Dokumen ini menjelaskan cara mengirim perubahan dari laptop lokal ke server (VPS).

## 1. Persiapan Awal
Pastikan Anda sudah memiliki akses SSH ke VPS dan Git sudah terkonfigurasi di kedua sisi.

## 2. Cara Update (Workflow)

### Langkah A: Di Laptop Lokal (Kirim ke GitHub)
Jalankan script `push.sh` untuk mengirim semua perubahan Anda ke repository GitHub.
```bash
bash push.sh "Pesan perubahan Anda di sini"
```
*Script ini akan melakukan: git add, git commit, dan git push.*

### Langkah B: Di VPS (Tarik dari GitHub)
1. Masuk ke VPS melalui Terminal/SSH.
2. Masuk ke folder project: `cd /www/wwwroot/dashboard-pw`
3. Jalankan script `deploy.sh`:
```bash
bash deploy.sh
```
*Script ini akan melakukan: git pull, update database (migrate), clear cache, dan rebuild frontend.*

---
**Catatan Penting:** 
- Jika ada penambahan library baru di Laravel, pastikan `composer` terinstall di VPS.
- Jika ada perubahan tampilan, script ini otomatis menjalankan `npm run build`.
