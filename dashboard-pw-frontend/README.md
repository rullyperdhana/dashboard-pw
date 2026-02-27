# Dashboard Payroll — Frontend

Frontend SPA untuk aplikasi Dashboard Payroll PNS, PPPK & PPPK Paruh Waktu.

## Tech Stack
- **Vue 3** (Composition API + `<script setup>`)
- **Vuetify 3** — Material Design Component Library
- **Vite** — Build Tool
- **Axios** — HTTP Client

## Menjalankan

```bash
# Install dependencies
npm install

# Development server
npm run dev
# → http://localhost:5173

# Build production
npm run build
```

## Environment
API backend dikonfigurasi di `src/api.js`:
- Development: `http://localhost:8000/api`
- Production: sesuai `.env` atau hardcoded

## Halaman Utama

| Route | View | Deskripsi |
|---|---|---|
| `/` | `Dashboard.vue` | Dashboard PPPK Paruh Waktu |
| `/pns-dashboard` | `PnsDashboard.vue` | Dashboard PNS & PPPK |
| `/employees` | `EmployeeList.vue` | Daftar pegawai PW |
| `/employee-trace` | `EmployeeHistory.vue` | Trace riwayat gaji |
| `/bpjs-rekon` | `BpjsRekon.vue` | Rekon BPJS 4% |
| `/settings/pppk` | `PppkSettings.vue` | Estimasi JKK/JKM/JKN |
| `/settings/sumber-dana` | `SumberDanaSetting.vue` | Sumber dana per SKPD |
| `/settings/users` | `UserManagement.vue` | Manajemen user |

## Fitur UI
- ✅ Light & Dark mode
- ✅ Responsive layout
- ✅ Export CSV/Excel/PDF
- ✅ Glassmorphism design
