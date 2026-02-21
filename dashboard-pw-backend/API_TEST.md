# Quick API Test - Dashboard PPPK

## Setelah Import Database, Test API:

### 1. Cek server Laravel running
```bash
curl http://localhost:8000/api/dashboard
# Harusnya error 401 (butuh auth) - ini normal!
```

### 2. Test Login (ganti username & password sesuai data di tabel users)
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}'
```

Copy TOKEN dari response, lalu:

### 3. Test Dashboard
```bash
curl http://localhost:8000/api/dashboard \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### 4. Test List Pegawai
```bash
curl http://localhost:8000/api/employees \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## ✅ Jika semua berhasil, backend siap digunakan!
