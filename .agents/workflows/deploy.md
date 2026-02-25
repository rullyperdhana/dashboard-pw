---
description: cara deploy aplikasi dari lokal ke VPS
---

1. Di Laptop Lokal, simpan semua perubahan kode.
// turbo
2. Jalankan perintah push di root folder:
```bash
bash push.sh "Pesan commit deskriptif"
```
3. Login ke VPS melalui SSH atau Terminal aaPanel.
4. Masuk ke direktori aplikasi:
```bash
cd /www/wwwroot/dashboard-pw
```
// turbo
5. Jalankan perintah deploy di VPS:
```bash
bash deploy.sh
```
6. Verifikasi perubahan di browser.
