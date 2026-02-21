# Error Fixes Applied

## Masalah yang Diperbaiki:

### 1. ✅ Laravel 11 Casts Syntax
**Error**: `protected function casts(): array`  
**Fix**: Ubah ke `protected $casts = []`

**File yang diperbaiki**:
- `app/Models/User.php`
- `app/Models/Employee.php`

### 2. ✅ API Routes Tidak Terdaftar
**Error**: Routes tidak terdeteksi  
**Fix**: Tambahkan `api: __DIR__.'/../routes/api.php'` di `bootstrap/app.php`

### 3. ✅ Cache/Session Driver
**Error**: Database connection failed saat clear cache  
**Fix**: Ubah SESSION_DRIVER dan CACHE_STORE dari `database` ke `file`

## Test Setelah Fix:

```bash
# Cek routes terdaftar
php artisan route:list --path=api

# Test API (tanpa database)
curl http://localhost:8000/api/login
# Harusnya return: {"message":"The username field is required."}
```

## Catatan:
Setelah import database SQL, semua API akan berfungsi normal.
