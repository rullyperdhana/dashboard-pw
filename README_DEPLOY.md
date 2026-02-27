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

## 3. Jika Ada Migration Baru

Setelah deploy, pastikan migration sudah jalan:
```bash
cd /www/wwwroot/sip-gaji/dashboard-pw-backend
php artisan migrate
```

### Migration Terbaru:
| Migration | Deskripsi |
|---|---|
| `add_sumber_dana_to_pegawai_pw` | Kolom `sumber_dana` (APBD/BLUD) di tabel `pegawai_pw` |
| `create_app_settings_table` | Tabel `app_settings` (tidak terpakai, bisa diabaikan) |

### Seed Data Manual (Jika Diperlukan):
Jika UMP belum ada di tabel `settings`:
```bash
php artisan tinker --execute="App\Models\Setting::setValue('ump_kalsel', '3725000', 'UMP Kalsel');"
```

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
