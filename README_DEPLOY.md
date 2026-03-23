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
2. Masuk ke folder project: `cd /www/wwwroot/sip-gaji`
3. Jalankan script `deploy.sh`:
```bash
# Script ini otomatis menjalankan git pull, composer install, artisan migrate, dan npm build
bash deploy.sh
```

> [!IMPORTANT]
> Jika ada penambahan fitur besar (seperti Master Data DBF), script `deploy.sh` akan otomatis mendeteksi perubahan tabel dan menjalankan `php artisan migrate`. Pastikan koneksi database di `.env` VPS sudah benar.

## 3. Daftar Migration Terbaru (Maret 2026)

Berikut adalah tabel penambahan yang baru saja diimplementasikan. Pastikan semua migrate berhasil dijalankan di VPS.

| Migration | Deskripsi |
|---|---|
| `create_audit_logs_table` | **[NEW]** Tabel pencatatan aktivitas sensitif (Audit Trail) |
| `create_master_pegawai_table` | Tabel induk data pegawai dari DBF (`master_pegawai`) |
| `create_master_keluarga_table` | Tabel induk data keluarga dari DBF (`master_keluarga`) |
| `create_satkers_table` | Tabel referensi Satker/SKPD untuk pemetaan nama |
| `add_source_code_to_skpd_mapping` | Penambahan kolom `source_code` (kdskpd) pada pemetaan SKPD |
| `add_dbf_fields_to_gaji` | Kolom tambahan untuk dukungan import data DBF |
| `create_payroll_postings` | Tabel log untuk posting/unposting penggajian |

### Perintah Manual Jika Diperlukan:
Jika `deploy.sh` mengalami kendala saat migrasi:
```bash
cd dashboard-pw-backend
php artisan migrate --force
```

### Seed Data Manual:
Jika Anda perlu menambahkan mapping SKPD secara manual atau reset cache:
```bash
php artisan optimize:clear
php artisan config:cache
```

---
## 5. Keamanan & Perawatan
- **Audit Logs:** Cek tabel `audit_logs` secara berkala untuk memantau aktivitas admin.
- **Database Backup:** Pastikan script `backup_db.sh` telah dikonfigurasi di `crontab` VPS untuk backup otomatis.
  ```bash
  # Contoh setup crontab (setiap jam 01:00 pagi)
  0 1 * * * /www/wwwroot/sip-gaji/backup_db.sh >> /www/wwwroot/sip-gaji/backup.log 2>&1
  ```

---
## 6. Sinkronisasi Database (VPS ke Localhost)
Jika Anda ingin mengambil database terbaru dari server ke laptop lokal Anda untuk keperluan pengembangan:

1.  Buka Terminal di **Laptop Lokal**.
2.  Jalankan script sinkronisasi:
    ```bash
    bash scripts/sync_db_vps.sh
    ```
    *Script ini akan melakukan `mysqldump` di server secara remote dan merestore-nya langsung ke database localhost Anda.*

> [!TIP]
> Pastikan Anda sudah mengatur **SSH Key** (tanpa password) agar proses sinkronisasi berjalan otomatis tanpa perlu mengetik password VPS berkali-kali.

---
- **Audit Logs:** Cek tabel `audit_logs` secara berkala untuk memantau aktivitas admin.
- **Cache Management:** Jika data tidak berubah setelah update, jalankan `php artisan optimize:clear`.

---

## 4. Troubleshooting

| Masalah | Solusi |
|---|---|
| Error `table not found` | Jalankan `php artisan migrate` |
| Frontend blank | Rebuild: `cd dashboard-pw-frontend && npm run build` |
| Cache issue | `php artisan config:clear && php artisan cache:clear` |
| Permission error | `chown -R www:www storage bootstrap/cache` |

---
**Catatan Penting:** 
- Jika ada penambahan library baru di Laravel, pastikan `composer` terinstall di VPS.
- Jika ada perubahan tampilan, script deploy otomatis menjalankan `npm run build`.
