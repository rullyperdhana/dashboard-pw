# 📊 Dashboard Payroll — PNS, PPPK & PPPK Paruh Waktu

Aplikasi dashboard manajemen dan pelaporan gaji untuk pegawai **PNS**, **PPPK Penuh Waktu**, dan **PPPK Paruh Waktu** pada instansi pemerintah daerah.

---

## ✨ Fitur Utama

| Modul | Deskripsi |
|---|---|
| **Dashboard PNS** | Ringkasan anggaran PNS & PPPK, tren bulanan, laporan tahunan |
| **Dashboard PW** | Ringkasan gaji PPPK Paruh Waktu per periode |
| **Upload Gaji PNS** | Import data gaji PNS dari file Excel |
| **Upload Gaji PPPK** | Import data gaji PPPK Penuh Waktu dari file Excel |
| **Pembayaran PPPK-PW** | Manajemen pembayaran PPPK Paruh Waktu |
| **Upload TPP** | Import data Tambahan Penghasilan Pegawai |
| **Upload TPG** | Import data Tunjangan Profesi Guru (Bulanan) |
| **Laporan Konsolidasi** | Rekapitulasi Gaji + TPP + TPG per pegawai (Bulanan) |
| **Dashboard TPG** | Monitoring realisasi TPG bulanan & sisa penyaluran |
| **Daftar Pegawai PW** | Data master pegawai PW dengan status, sumber dana, dokumen |
| **Master Pegawai DBF** | Sinkronisasi data induk pegawai & keluarga dari file DBF |
| **Laporan Bulanan per SKPD** | Laporan gaji per SKPD dengan tab PNS, PPPK, PW, Gabungan |
| **Laporan Tahunan** | Rekapitulasi gaji 12 bulan per jenis kepegawaian |
| **Laporan Individual** | Slip gaji per pegawai dengan export PDF |
| **Rekon BPJS 4%** | Rekonsiliasi BPJS Kesehatan 4% dengan rekap per SKPD & Jabatan |
| **Estimasi JKK/JKM/JKN** | Estimasi iuran ketenagakerjaan per SKPD |
| **Sumber Dana SKPD** | Setting APBD/BLUD per SKPD (bulk update) |
| **Trace Gaji Pegawai** | Riwayat gaji per pegawai + kelola status & SK |
| **SKPD Mapping** | Pemetaan Kode & Nama SKPD untuk akurasi laporan |
| **Manajemen User** | Akun admin & admin SKPD |
| **Status Pajak (PTKP)**| Kelola status PTKP statis tahunan (K/0, TK/1, dll) |
| **API Integrasi**    | Endpoint API Key untuk integrasi sistem eksternal (SIMGAJI) |
| **Pajak TER (A2)**  | Perhitungan PPh 21 TER & Export Bukti Potong A2 |
| **Welcome Hub**    | Beranda interaktif dengan glassmorphism & pengumuman |
| **Pengumuman**     | Manajemen pengumuman (CRUD) khusus Superadmin |
| **Pengaturan Akun**| Kelola profil & keamanan (Ganti Password) terintegrasi |
| **Pusat Bantuan**   | Akses informasi bantuan untuk seluruh level user |
| **Log Keamanan**     | Audit trail login (berhasil/gagal) & deteksi brute-force |
| **Audit Aktivitas** | Log perubahan parameter & aktivitas administratif (Superadmin) |
| **Verifikasi SP2D** | Rekon rincian gaji SIMGAJI vs Realisasi SIPD (Asinkron / Background Job) |
| **Anggaran & Realisasi**| Input Pagu Anggaran (Murni/Pergeseran) & Komparasi Kinerja Penyerapan |
| **Cache Management** | Optimasi performa dashboard dangan sistem caching otomatis |
| **Export Excel & PDF** | Export laporan sesuai tab yang aktif |

---

### 🚀 TPG Monthly & Consolidated Report (v5.0.0)
- **Monthly TPG Reporting:** Migrasi sistem pelaporan TPG dari Triwulan ke Bulanan. Mendukung upload file rincian TPG bulanan dengan template Excel otomatis.
- **Consolidated Payroll Report:** Laporan baru yang menggabungkan Gaji Bruto, TPP, dan TPG dalam satu tampilan terpadu. Memudahkan pemantauan total penghasilan riil pegawai per bulan.
- **SIMGAJI Master Integration:** Sinkronisasi nama SKPD pada laporan konsolidasi menggunakan data master SIMGAJI untuk akurasi pelaporan unit kerja.
- **Smart Data Deduplication:** Implementasi normalisasi data pada join query untuk mencegah duplikasi baris akibat perbedaan kode satker di bawah satu SKPD.

### 🛠️ PPPK-PW Estimation & UI Consistency (v4.9.2)
- **PPPK-PW SKPD Breakdown Fix:** Menambahkan rincian per SKPD pada tab PPPK Paruh Waktu di halaman Estimasi Iuran. Sebelumnya hanya menampilkan summary global tanpa breakdown unit kerja.
- **Enhanced Estimation Table:** Menambahkan kolom "Tunjangan" pada tabel rincian estimasi (PNS, PPPK, PW) untuk transparansi perhitungan iuran yang lebih baik.

### 🛠️ Maintenance Reliability & Theme Optimization (v4.9.1)
- **Database Restore Fix:** Perbaikan bug kritis (syntax error) pada backend `SettingController` yang menyebabkan kegagalan proses impor database (500 error).
- **SKPD Mapping Integrity:** Perbaikan sinkronisasi data pada dropdown SKPD di halaman Pemeliharaan Data. Data kini dipetakan dengan benar dari response API yang terbungkus (*wrapped response*).
- **Modern Theme Engine:** Migrasi sistem penggantian tema dari properti deprecated `theme.global.name.value` ke API baru `theme.name.value` untuk kompatibilitas Vuetify versi terbaru dan performa UI yang lebih stabil.

### 🛠️ SKPD Mapping Recovery & Intelligent Bridge (v4.9.0)
- **Intelligent Bridge Detection:** Sistem kini secara cerdas mendeteksi "Mapping Terputus" (SKPD yang sudah terikat ke data realisasi namun kehilangan jembatan kodenya), mencegah kesalahan rekonsiliasi "Minus" yang tidak terduga.
- **One-Click Standard Recovery:** Penambahan fitur pemulihan massal untuk mengembalikan 150+ pemetaan unit kerja standar dengan resolusi nama otomatis dari tabel Master Satker.
- **Global Mapping Assistant:** Tombol "Petakan Semua Saran" kini hadir secara global untuk melakukan automapping pada seluruh kategori (PNS, PPPK, SP2D) dalam satu kali klik.
- **UI Management Restoration:** Pengembalian tombol kontrol utama (Refresh, Hapus ALL) dan penambahan indikator visual untuk status pemetaan yang membutuhkan perhatian.

### 🚀 Audit Aktivitas & Optimasi SP2D (v4.8.0)
- **Background SP2D Reconciliation:** Kalkulasi rekonsiliasi SP2D yang berat kini dipindahkan ke *background job* (Laravel Queue). Pengguna mendapatkan *real-time progress bar* dan sistem terhindar dari *timeout* pada data besar.
- **System Audit Log UI:** Halaman dashboard baru khusus Superadmin untuk memantau setiap perubahan parameter gaji dan aktivitas administratif. Mendukung tampilan perbandingan data lama (*old values*) dan baru (*new values*).
- **Intelligent Caching:** Hasil rekonsiliasi SP2D kini disimpan dalam *cache* selama 1 jam untuk akses instan, dengan opsi "Hitung Ulang" untuk memperbarui data secara manual.
- **Security Audit Trail:** Perekaman otomatis IP Address, User Agent, dan detail teknis (JSON) untuk setiap aksi krusial di sistem.
 

  

### 📊 Anggaran & Realisasi Belanja (v4.7.0)
- **Kategori Anggaran Fleksibel:** Kini mendukung pemisahan struktur anggaran (PNS, PPPK, TPP, dll.) dalam input Pagu.
- **Multiple Budget Revisions:** Laporan mendukung multi-skenario (Murni, Pergeseran/Perubahan ke-N, hingga nilai Terakhir).
- **Bruto Realizations Check:** Perhitungan "Sisa Anggaran" dan penyerapan performa dikalkulasi berdasarkan Realisasi SSP2D berbasis "Bruto" yang presisi agar sinkron dengan laporan formal.

### 📊 PPPK-PW Fund Source Split & Realization (v4.6.0)
- **Separate Funding Sources:** Pemisahan total anggaran dan jumlah pegawai antara **APBD** dan **BLUD** pada seluruh modul dashboard & laporan (Dashboard Utama, Dashboard Eksekutif/Mobile, dan Laporan Bulanan).
- **Dashboard Realization (IDR):** Kartu distribusi "Sumber Dana" kini menyertakan nilai nominal Rupiah (Total Realisasi) di samping jumlah pegawai untuk transparansi anggaran yang lebih baik.
- **Enhanced Monthly Filters:** Penambahan filter "Sumber Dana" pada Laporan Bulanan PPPK-PW, memungkinkan penyaringan data dan ekspor (Excel/PDF) yang spesifik per sumber dana.
- **Backward Compatibility:** Dukungan kunci data `pw` dan label `PPPK-PW` tetap tersedia di backend untuk menjamin kompatibilitas fitur lama yang belum diperbarui.

### 📊 Analitik TAPD & Optimasi Teknis (v4.5.0)
- **Advanced Budget Simulation:** Fitur simulasi belanja pegawai yang mendukung parameter "Kenaikan Gaji Pokok (%)", "Simulasi Pegawai Baru", dan "Faktor Pertumbuhan". Mendukung rincian otomatis per Kode Rekening.
- **Smart Caching System:** Implementasi Laravel Cache pada layanan dashboard untuk mempercepat waktu muat data statistik. Mengurangi beban database hingga 80% pada akses berulang.
- **Automated Cache Invalidation:** Sistem otomatis menghapus cache dashboard setiap kali ada aktivitas data (Import DBF, Upload Gaji, TPP, TPG, SP2D) untuk menjamin akurasi data.
- **Manual Data Refresh:** Menambahkan kontrol "Refresh Data" di dashboard analitik untuk pembersihan cache secara manual oleh administrator.
- **Detailed Calculation Docs:** Menambahkan dokumentasi internal mengenai metodologi perhitungan prediksi anggaran (Gaji 14x, efisiensi pensiun, KGB/KP).

### 🛠️ Advanced Data Maintenance & UI Shortcuts (v4.4.2)
- **Direct Shortcuts:** Menambahkan opsi "Gaji Kekurangan PNS/PPPK" langsung pada menu Target Data untuk akses cepat.
- **Improved UI Visibility:** Kolom filter (Jenis Gaji, SKPD) kini selalu terlihat di halaman pemeliharaan, mempermudah identifikasi fitur filter bagi administrator.
- **SKPD-Specific Clearing:** Dukungan filter SKPD untuk penghapusan yang lebih terkontrol.

### 📊 Komprehensif Analytics & Gross Salary (v4.3.0)
- **Gaji Kotor / Bruto Visibility:** Menambahkan metrik Gaji Kotor (Gross Salary) di seluruh Dashboard PNS, termasuk KPI card utama, kolom baru di tabel Histori Transaksi Tahunan, dan ringkasan data tahunan. Mendukung tampilan gabungan (PNS | PPPK).
- **Responsive Font Fluidity:** Mengimplementasikan CSS `clamp()` pada angka-angka KPI Card untuk memastikan ukuran font mengecil secara otomatis pada perangkat mobile atau saat jendela browser dipersempit, mencegah tampilan angka yang meluber/kacau.
- **Executive Data Alignment:** Memperbaiki bug *double-counting* TPP pada perhitungan Dashboard Eksekutif. Nilai TPP kini tidak lagi ditambahkan dua kali ke dalam Gaji Kotor.
- **Enhanced Executive Transparency:** Penambahan label indikator "PNS + PPPK + PW" pada dashboard mobile untuk memperjelas cakupan data yang ditampilkan bagi pimpinan.

### 🚀 Gaji PNS & Sinkronisasi SKPD (v4.1.0-v4.2.0)
- **Cumulative Batch Upload for Arrears (v4.2.0):** Data gaji rincian kini mendukung mode "Tambah" (*Append*) khusus untuk jenis **Kekurangan**. Pengguna dapat mengunggah beberapa file batch kekurangan untuk bulan yang sama tanpa menghapus data sebelumnya, sehingga nilai rekapitulasi akan terakumulasi otomatis.
- **Real-time Payroll Calculation:** Implementasi *Vue Watchers* pada formulir Gaji PNS yang melakukan kalkulasi otomatis Gaji Kotor, Total Potongan, dan Gaji Bersih saat rincian tunjangan diinput.
- **Tunjangan Khusus (TJKHUSUS) Support:** Penambahan field Tunjangan Khusus di seluruh sistem, mulai dari Input Form, Detail View, hingga rekapitulasi Laporan Bulanan (Web, Excel, & PDF).
- **Automated SKPD Discovery:** Sistem kini otomatis mendeteksi dan menyinkronkan kode SKPD baru dari tabel Master Simgaji (`satkers`) ke dalam aplikasi utama, menghilangkan kebutuhan input manual untuk unit baru (seperti sekolah/UPT).
- **Searchable SKPD Dropdown:** Mengupgrade dropdown SKPD menjadi *Autocomplete* cerdas untuk menangani ratusan data unit kerja dengan performa cepat dan pencarian teks.
- **Backend Import Re-calculation:** Backend kini menghitung ulang seluruh total gaji saat proses impor DBF dilakukan, memastikan integritas data 100% meskipun file sumber memiliki anomali penjumlahan.

### 💨 Optimalisasi & Laporan Dinamis (v4.0.0)
- **ESS Experience Upgrade:** Dashboard Employee Self-Service (ESS) kini mendukung histori slip gaji hingga 5 tahun (60 bulan) dengan fitur pengelompokan tab berbasis tahun (*Yearly Tabs*), serta menampilkan rincian spesifik Gaji Pokok dan TPP pada tiap kartu slip.
- **Fixed Allowance Data:** Perbaikan kueri inti SQL pada laporan bulanan untuk menyertakan pilar tunjangan **Pembulatan** yang sebelumnya tersembunyi. Data pembulatan kini tampil akurat di tabel UI Web, PNS Dashboard, cetak Excel, hingga *export* PDF.
- **PDF Export Resilience:** Mengalihkan *driver* penyimpanan *export* PDF ke direktori publik yang lebih terjamin aksesibilitasnya, serta men-sintesis ulang struktur DomPDF dengan melumpuhkan komponen berat (*QR Code generator*) sementara waktu untuk mengatasi masalah lambatnya proses antrean latar belakang (*Job Queue bottleneck*).
- **Sticky DataGrid UX:** Mengimplementasikan fitur penguncian layar otomatis (*Freeze Header* & *Freeze SKPD Column*) pada tabel Laporan Bulanan SKPD menggunakan arsitektur CSS modern, mencegah disorientasi pengguna saat menelusuri ratusan kolom tunjangan ke arah kanan.

### 🛠️ Maintenance & Refinement (v3.9.1)
- **Automatic SKPD Synchronization:** Penentuan SKPD Utama kini otomatis tersinkron dangan pilihan pertama pada modul Manajemen User, memastikan identitas Sidebar selalu akurat.
- **Enhanced Login Parity:** Respon otentikasi kini menyertakan data perizinan lengkap (`skpd_access`, `app_access`) untuk menghilangkan jeda sinkronisasi *cache* setelah perubahan admin.
- **Fixed PPPK-PW PDF Export:** Perbaikan variabel dan mapping data pada template PDF untuk laporan THR dan Gaji-13 PPPK Paruh Waktu.
- **Dashboard Metric Accuracy:** Penyesuaian logika hitung personil (diambil dari data bulan terakhir, bukan akumulasi tahunan) dan tampilan angka nominal penuh pada footer Dashboard PNS.


---
### 🚀 Executive Dashboard & Welcome Hub (v3.9.0)
- **"Simple but Informative" Dashboard:** Redesain total dashboard PPPK-PW dengan fokus pada metrik utama dan kemudahan navigasi.
- **Submission Progress Gauge:** Meteran progres radial yang memantau status pelaporan SKPD secara *real-time* di header.
- **Consolidated KPI Cards:** Ringkasan statistik (Anggaran, Pegawai, Unit, Rata-rata Biaya) dalam format kartu premium dengan efek *glassmorphism*.
- **Smart Insights Panel:** Panel peringatan cerdas yang menampilkan item tindakan (misal: SKPD terlambat, data pending) untuk mempercepat pengambilan keputusan.
- **Collapsible Detailed Reports:** Layout tabel yang lebih bersih dengan bagian yang dapat disiutkan (*collapsible*) untuk daftar gaji yang belum masuk dan sudah terbayar.
- **Welcome Hub Experience:** Landing page interaktif dengan desain modern untuk akses cepat ke seluruh modul aplikasi.
- **Integrated Account Management:** Pengaturan profil dan keamanan (Ganti Password) yang terintegrasi dangan mulus di sidebar dan navbar.
- **Enhanced Dashboard Filtering:** Dashboard secara cerdas memisahkan data PPPK-PW dari PNS/PPPK Penuh Waktu untuk akurasi laporan.
- **Global Help Access:** Halaman Pusat Bantuan kini dapat diakses oleh semua level pengguna (Admin & Operator).
- **Environment Version Indicator:** Label versi aplikasi (v4.5.0) yang transparan di sidebar.

### 🛠️ Maintenance & Refinement (v3.9.1)
- **Automatic SKPD Synchronization:** Penentuan SKPD Utama kini otomatis tersinkron dangan pilihan pertama pada modul Manajemen User, memastikan identitas Sidebar selalu akurat.
- **Enhanced Login Parity:** Respon otentikasi kini menyertakan data perizinan lengkap (`skpd_access`, `app_access`) untuk menghilangkan jeda sinkronisasi *cache* setelah perubahan admin.
- **Fixed PPPK-PW PDF Export:** Perbaikan variabel dan mapping data pada template PDF untuk laporan THR dan Gaji-13 PPPK Paruh Waktu.
- **Dashboard Metric Accuracy:** Penyesuaian logika hitung personil (diambil dari data bulan terakhir, bukan akumulasi tahunan) dan tampilan angka nominal penuh pada footer Dashboard PNS.

### 💨 Optimalisasi & Laporan Dinamis (v4.0.0)
- **ESS Experience Upgrade:** Dashboard Employee Self-Service (ESS) kini mendukung histori slip gaji hingga 5 tahun (60 bulan) dengan fitur pengelompokan tab berbasis tahun (*Yearly Tabs*), serta menampilkan rincian spesifik Gaji Pokok dan TPP pada tiap kartu slip.
- **Fixed Allowance Data:** Perbaikan kueri inti SQL pada laporan bulanan untuk menyertakan pilar tunjangan **Pembulatan** yang sebelumnya tersembunyi. Data pembulatan kini tampil akurat di tabel UI Web, PNS Dashboard, cetak Excel, hingga *export* PDF.
- **PDF Export Resilience:** Mengalihkan *driver* penyimpanan *export* PDF ke direktori publik yang lebih terjamin aksesibilitasnya, serta men-sintesis ulang struktur DomPDF dengan melumpuhkan komponen berat (*QR Code generator*) sementara waktu untuk mengatasi masalah lambatnya proses antrean latar belakang (*Job Queue bottleneck*).
- **Sticky DataGrid UX:** Mengimplementasikan fitur penguncian layar otomatis (*Freeze Header* & *Freeze SKPD Column*) pada tabel Laporan Bulanan SKPD menggunakan arsitektur CSS modern, mencegah disorientasi pengguna saat menelusuri ratusan kolom tunjangan ke arah kanan.

### 🐛 Patch & UX Improvements (v4.0.1)
- **Standalone TPP Reactivity:** Perbaikan kueri Frontend pada halaman Upload TPP untuk memastikan tabel *Data TPP Belum Terhubung (Standalone)* dan *Laporan Selisih* otomatis diperbarui (re-fetch) secara real-time setiap kali user mengubah filter Bulan, Tahun, atau Tipe Pegawai.
- **Enhanced Estimations Filter:** Modifikasi *query* estimasi iuran (JKK/JKM/BPJS) untuk secara eksplisit mengecualikan data *Extra Payroll* (THR & Gaji 13) sehingga proyeksi iuran gaji bulanan reguler menjadi lebih akurat.
- **Reporting Stability:** Memperbaiki insiden 500 Internal Server error pada *endpoint* Executive Summary akibat inisialisasi variabel kosong, serta perbaikan peletakan tombol export Excel yang tadinya tumpang tindih.

---
 
### 📑 Pajak TER & Bukti Potong A2 (v3.8)
- **Unified SKPD Engine:** Perhitungan PPh 21 kini menggunakan `skpd_id` tunggal yang terintegrasi untuk seluruh jenis pegawai (PNS & PPPK), memastikan laporan akurat meskipun dari sumber data yang berbeda.
- **Bulk Management Tools:** Fitur ceklist (multi-select) untuk penghapusan data masal, memudahkan Superadmin dalam melakukan pembersihan atau pemutakhiran data perhitungan dalam jumlah besar.
- **Administrative Controls:** Tombol hapus khusus Superadmin per baris laporan dengan konfirmasi keamanan untuk mencegah kesalahan operasional.
- **Automatic TER Calculation:** Sistem otomatis menggunakan tarif TER (Kategori A/B/C) untuk masa pajak Januari-November.
- **December Final Tax:** Perhitungan Pasal 17 otomatis pada bulan Desember untuk menyeimbangkan total pajak tahunan.
- **PTKP Intelligence:** Pemetaan otomatis status PTKP (Kawin/Anak) pegawai ke Kategori TER yang sesuai secara *real-time*.
- **Official A2 Export:** Export Bukti Potong A2 ke Excel sesuai format resmi.
- **Tunjangan Pembulatan Support:** Perhitungan PPh 21 kini secara eksplisit mencakup Tunjangan Pembulatan dari Simgaji sebagai objek pajak.

### 📄 Laporan & Transparansi (v3.6)
- **AI-Driven Analytics (TAPD):** Dashboard cerdas untuk Tim Anggaran Pemerintah Daerah yang mensimulasikan kenaikan anggaran berdasarkan data rill **KGB** (2 tahunan), **Kenaikan Pangkat** (4 tahunan), dan jadwal pensiun massal.
- **Executive Mobile Dashboard:** Tampilan khusus yang dioptimalkan untuk perangkat mobile/smartphone bagi pimpinan (Kepala Dinas/Bupati) untuk memantau realisasi belanja pegawai secara *real-time*.
- **PWA Ready:** Aplikasi kini mendukung instalasi langsung ke layar utama HP (*Add to Home Screen*) untuk akses cepat modul eksekutif.
- **Generalized Extra Payroll System:** Restrukturisasi total modul THR menjadi sistem *Extra Payroll* universal yang mendukung berbagai jenis pembayaran tambahan seperti **Gaji 13**.
- **THR & Gaji 13 DBF Import:** Mendukung import file `GAJI.DBF` khusus untuk periode THR dan Gaji 13, memastikan data terpisah dari payroll bulanan reguler.
- **TPP THR & TPP 13 Support:** Kemampuan untuk mengunggah dan memberlakukan data TPP (Tambahan Penghasilan Pegawai) khusus untuk pembayaran THR dan Gaji 13.
- **Improved Prediction Model:** Perhitungan proyeksi anggaran kini menggunakan kombinasi regresi linear data historis 12 bulan terakhir (hanya data 'Induk') untuk mencegah data THR/Gaji 13 mengganggu akurasi prediksi.
- **Data Terdeteksi Terlewat:** Fitur deteksi otomatis pegawai yang memiliki slip gaji basis namun belum masuk ke dalam laporan THR/Gaji 13, lengkap dengan **Alasan Ketidakhadiran** (misal: "Gaji basis tidak ditemukan").
- **UPT-Aware Reporting:** Laporan otomatis mencakup pegawai di Unit Pelaksana Teknis (UPT) menggunakan prefix SKPD, memastikan sinkronisasi data 100%.
- **TPP Discrepancy Reporting:** Deteksi otomatis selisih pegawai antara data gaji dan file Excel TPP yang tersimpan permanen.
- **Persistent Extra Payroll Management:** Data disimpan permanen di database (`tb_extra_payroll_pppk_pw`), mendukung edit manual, penambahan catatan, dan batch delete.
- **Server-Side Pagination:** Optimasi performa untuk dataset 6.000+ pegawai agar UI tetap responsif.
- **Dedicated Summary Endpoint:** Tab Rekapitulasi menggunakan endpoint khusus yang sangat ringan untuk perhitungan total anggaran instan.
- **Professional PDF Export:** Redesain total template slip gaji dan payroll dengan layout profesional, border rapi, dan summary box.
- **Digital QR Verification:** Setiap PDF memiliki QR Code unik yang terhubung ke halaman verifikasi publik untuk validasi keaslian dokumen.
- **Nested THR Reporting:** Laporan THR PPPK-PW kini dikelompokkan secara hierarkis: **SKPD -> Sub Kegiatan -> Daftar Pegawai**.
- **SKPD Name Normalization:** Pembersihan otomatis dan penggabungan (merge) nama SKPD yang terduplikasi karena spasi atau perbedaan kode, memastikan filter dropdown selalu bersih dan unik.
- **VPS Migration Resiliency:** Penyesuaian skema database untuk mendukung migrasi yang lebih stabil pada berbagai konfigurasi server VPS (Foreign Key normalization).

### 🔐 Keamanan & Role-Based Access (v3.1)
- **THR Management Restriction:** Fitur Generate, Edit, Tambah, dan Hapus data THR dibatasi khusus untuk akun **Superadmin**.
- **Session Timeout:** Sesi otomatis berakhir setelah 30 menit tidak aktif.
- **Audit Logging:** Pencatatan aktivitas sensitif (hapus data, upload, posting) ke database.
- **Rate Limiting:** Pembatasan percobaan login (5 kali per 15 menit) untuk mencegah brute-force.
- **Konfirmasi Password:** Validasi password admin untuk aksi penghapusan data masal.

### 🛠️ Integrasi Master Data DBF (v2.6)
- **Import Langsung:** Mendukung import file `MST_PGW.DBF`, `KEL.DBF`, dan `GAJI.DBF`.
- **Sinkronisasi Otomatis:** Pemutakhiran data pegawai dan keluarga secara massal.
- **Riwayat Gaji Pokok:** Melacak perubahan gaji pokok pegawai dari waktu ke waktu.
- **Pemetaan SKPD Berbasis Kode:** Menggunakan `kdskpd` untuk akurasi data antar modul.

### 💳 Sumber Dana & BPJS
- **Sumber Dana APBD / BLUD:** Kolom `sumber_dana` khusus untuk pegawai PPPK-PW.
- **Rekon BPJS 4%:** Rumus cerdas berbasis UMP (Gaji Pokok vs Ambang Batas UMP).
- **Status "Meninggal":** Penanganan khusus untuk pelaporan pegawai yang wafat.

---

## 🛠️ Tech Stack

### Backend
- **PHP 8.4** + **Laravel 10**
- **MySQL** — database utama
- **Laravel Sanctum** — autentikasi API token
- **Maatwebsite Excel** — import/export Excel
- **Laravel DomPDF** — generate PDF

### Frontend
- **Vue 3** (Composition API)
- **Vuetify 3** — UI component library
- **Vite** — build tool
- **Axios** — HTTP client

---

## 📁 Struktur Project

```
dashboard-pw/
├── dashboard-pw-backend/     # Laravel API
│   ├── app/
│   │   ├── Http/Controllers/Api/
│   │   │   ├── AuthController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── BpjsRekonController.php
│   │   │   ├── EmployeeController.php
│   │   │   ├── SettingController.php
│   │   │   ├── SumberDanaSettingController.php
│   │   │   ├── PnsPayrollController.php
│   │   │   ├── ReportController.php
│   │   │   ├── SkpdMappingController.php
│   │   │   └── ...
│   │   ├── Exports/          # Excel export classes
│   │   └── Models/
│   │       ├── Employee.php
│   │       ├── Setting.php
│   │       ├── Payment.php
│   │       └── ...
│   ├── database/migrations/
│   ├── routes/api.php
│   └── .env.example
│
├── dashboard-pw-frontend/    # Vue 3 SPA
│   ├── src/
│   │   ├── views/
│   │   │   ├── Dashboard.vue
│   │   │   ├── PnsDashboard.vue
│   │   │   ├── EmployeeList.vue
│   │   │   ├── EmployeeHistory.vue
│   │   │   ├── BpjsRekon.vue
│   │   │   ├── SumberDanaSetting.vue
│   │   │   ├── SkpdMonthlyReport.vue
│   │   │   └── Settings/
│   │   │       └── PppkSettings.vue
│   │   ├── components/       # Navbar, Sidebar, dll
│   │   ├── router/
│   │   └── api.js            # Axios instance
│   └── vite.config.js
│
├── push.sh                   # Script push ke GitHub
└── deploy.sh                 # Script deploy ke VPS
```

---

## 🚀 Cara Menjalankan (Development)

### 1. Clone repository

```bash
git clone https://github.com/rullyperdhana/dashboard-pw.git
cd dashboard-pw
```

### 2. Setup Backend

```bash
cd dashboard-pw-backend

# Install dependencies
composer install

# Salin dan konfigurasi .env
cp .env.example .env
# Edit: DB_DATABASE, DB_USERNAME, DB_PASSWORD

# Generate app key
php artisan key:generate

# Jalankan migrasi
php artisan migrate

# Jalankan server
php artisan serve
# Backend berjalan di http://localhost:8000
```

### 3. Setup Frontend

```bash
cd dashboard-pw-frontend

# Install dependencies
npm install

# Jalankan dev server
npm run dev
# Frontend berjalan di http://localhost:5173
```

---

## 📦 Deploy ke VPS

```bash
# 1. Push ke GitHub (Lokal)
bash push.sh "deskripsi perubahan"
 
# 2. Update di VPS
bash deploy.sh
```
 
> [!IMPORTANT]
> Untuk update database (migration baru) dan build ulang frontend, cukup jalankan `bash deploy.sh` di root folder VPS.
 
Lihat [README_DEPLOY.md](README_DEPLOY.md) untuk panduan update mesin dan troubleshooting.

---

## 🗄️ Database

| Tabel | Deskripsi |
|---|---|
| `gaji_pns` | Data gaji bulanan PNS |
| `gaji_pppk` | Data gaji bulanan PPPK Penuh Waktu |
| `tb_payment` | Header pembayaran PPPK Paruh Waktu |
| `tb_payment_detail` | Detail pembayaran per pegawai PW |
| `pegawai_pw` | Data master pegawai PPPK Paruh Waktu |
| `skpd` | Master data SKPD |
| `skpd_mapping` | Pemetaan nama SKPD dari Excel ke master |
| `settings` | Konfigurasi JKK, JKM, UMP, Bulan Basis Extra Payroll, dll |
| `users` | Akun pengguna |
| `tb_extra_payroll_pppk_pw` | Data THR & Gaji 13 PPPK-PW yang tersimpan (Database) |
| `audit_logs` | Catatan aktivitas administratif dan perubahan sistem |
| `pph21_calculations` | Hasil perhitungan pajak bulanan per pegawai |
| `pph21_ter_rates` | Master data tarif efektif rata-rata (Cat A, B, C) |
| `employee_statuses` | Riwayat status pegawai + SK |

### Kolom Penting `pegawai_pw`
| Kolom | Deskripsi |
|---|---|
| `sumber_dana` | Flag sumber pendanaan: `APBD` (default) atau `BLUD` |
| `status` | Status pegawai: Aktif, Pensiun, Keluar, Diberhentikan, Meninggal |

### Pengaturan di Tabel `settings`
| Key | Deskripsi | Default |
|---|---|---|
| `pppk_jkk_percentage` | Persentase iuran JKK | 0.24 |
| `pppk_jkm_percentage` | Persentase iuran JKM | 0.72 |
| `ump_kalsel` | UMP Provinsi Kalimantan Selatan | 3725000 |

---

## 🧮 Rumus Perhitungan

### BPJS 4% (Rekon PPPK Paruh Waktu)
| Kondisi | Formula |
|---|---|
| Gaji Pokok ≥ UMP | BPJS = Gaji Pokok × 4% |
| Gaji Pokok < UMP | BPJS = UMP × 4% (fixed) |

> [!NOTE]
> Laporan kini mendukung tampilan **Rekap per Jabatan** untuk memudahkan analisis distribusi iuran berdasarkan jenis posisi pegawai.

> UMP default Kalsel: **Rp 3.725.000** → BPJS minimum: **Rp 149.000**

### Estimasi JKK/JKM/BPJS Kesehatan
| Item | Formula |
|---|---|
| JKK | Gaji Pokok × JKK% (default 0.24%) |
| JKM | Gaji Pokok × JKM% (default 0.72%) |
| BPJS Kesehatan | MIN(Gaji Pokok + TPP, Rp 12.000.000) × 4% |

---

## 🔐 Akun Default

| Role | Username | Akses |
|---|---|---|
| Admin | *(sesuai setup)* | Full access |
| Admin SKPD | *(sesuai setup)* | SKPD tertentu saja |

---

## 📄 Laporan Bulanan per SKPD

Laporan di `/reports/skpd-monthly` memiliki 4 tab:

| Tab | Kolom |
|---|---|
| **Gabungan** | Ringkasan: Gaji Pokok, Tunjangan, Potongan, Bersih |
| **PNS** | Detail: GAPOK, TJISTRI, TJANAK, TJTPP, ..., PIWP, PPAJAK, BERSIH |
| **PPPK Penuh Waktu** | Detail: sama seperti PNS |
| **PPPK Paruh Waktu** | Ringkasan per SKPD |

Export Excel/PDF secara otomatis menyesuaikan kolom dengan tab yang aktif.

---

## 📂 Halaman Aplikasi

| Route | Halaman |
|---|---|
| `/welcome` | Beranda (Welcome Hub) & Pengumuman |
| `/dashboard-pppk-pw` | Dashboard PPPK Paruh Waktu |
| `/pns-dashboard` | Dashboard PNS & PPPK |
| `/employees` | Daftar Pegawai PW |
| `/employee-trace` | Trace / Riwayat Gaji Pegawai |
| `/reports/thr-pppk-pw` | Perhitungan THR PPPK-PW |
| `/reports/gaji-13-pppk-pw` | Perhitungan Gaji 13 PPPK-PW |
| `/bpjs-rekon` | Rekon BPJS 4% |
| `/settings/pppk` | Estimasi JKK/JKM/JKN |
| `/settings/sumber-dana` | Setting Sumber Dana per SKPD |
| `/settings/users` | Manajemen User |
| `/settings/announcements` | Kelola Pengumuman (Superadmin) |
| `/help` | Pusat Bantuan |
| `/upload/pns` | Upload Gaji PNS |
| `/upload/pppk` | Upload Gaji PPPK |
| `/upload/tpp` | Upload TPP |
| `/upload/tpg` | Upload TPG |
| `/reports/pph21` | Laporan PPh 21 TER & Bukti Potong A2 |
| `/settings/tax-status` | Manajemen Status Pajak (PTKP) |
| `/settings/audit-logs` | Log Aktivitas Sistem (Superadmin) |
| `/sp2d-verification` | Rekonsiliasi SP2D vs Simgaji (Background Processing) |

---

## 👤 Developer

**Rully Perdhana**  
📧 rully.perdhana@egmail.com