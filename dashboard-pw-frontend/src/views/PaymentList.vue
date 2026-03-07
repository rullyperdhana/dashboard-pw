<template>
  <v-app>
    <Navbar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />
    <Sidebar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />

    <v-main>
      <v-container fluid class="pa-8">
        <!-- Top Summary Cards -->
        <v-row class="mb-6">
          <v-col cols="12" md="4">
            <v-card class="glass-card rounded-xl pa-4" elevation="0">
              <v-card-text>
                <div class="text-overline text-grey-darken-1 mb-1">Total Pembayaran Terakhir</div>
                <div class="text-h4 font-weight-bold primary--text">
                  {{ formatCurrencyShort(payments[0]?.total_amoun) }}
                </div>
                <div class="d-flex align-center mt-2">
                  <v-icon color="success" size="small" class="mr-1">mdi-arrow-up-thin</v-icon>
                  <span class="text-caption success--text font-weight-bold">+0.8%</span>
                  <span class="text-caption text-grey ml-1">vs bulan lalu</span>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
          
          <v-col cols="12" md="4">
            <v-card class="glass-card rounded-xl pa-4" elevation="0">
              <v-card-text>
                <div class="text-overline text-grey-darken-1 mb-1">Rata-rata Gaji / Pegawai</div>
                <div class="text-h4 font-weight-bold">
                  {{ formatCurrencyShort(payments.reduce((acc, p) => acc + (p.total_amoun || 0), 0) / (payments.reduce((acc, p) => acc + (p.total_emplo || 0), 0) || 1)) }}
                </div>
              </v-card-text>
            </v-card>
          </v-col>

          <v-col cols="12" md="4">
            <v-card class="glass-card rounded-xl pa-4 d-flex align-center h-100" elevation="0">
              <v-card-text class="d-flex align-center">
                <v-icon size="48" color="primary-lighten-4" class="mr-4">mdi-file-download-outline</v-icon>
                <div>
                  <div class="text-subtitle-2 font-weight-bold">Pusat Laporan</div>
                  <div class="text-caption text-grey">Generate dan ekspor laporan payroll</div>
                  <v-btn color="primary" variant="text" size="small" class="px-0 mt-1 font-weight-bold" to="/">KUNJUNGI LAPORAN</v-btn>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <!-- Transaction List -->
        <v-card class="glass-card rounded-xl overflow-hidden" elevation="0">
          <v-toolbar color="transparent" flat class="px-6 py-4 border-b">
            <v-toolbar-title class="font-weight-bold text-h6">Riwayat Pembayaran</v-toolbar-title>
            <v-spacer></v-spacer>
            <v-text-field
              v-model="search"
              prepend-inner-icon="mdi-magnify"
              label="Cari data pembayaran..."
              variant="solo-filled"
              flat
              hide-details
              rounded="pill"
              density="compact"
              class="max-width-300 mr-4"
              color="primary"
              clearable
            ></v-text-field>
            <v-btn prepend-icon="mdi-filter-variant" variant="tonal" color="grey" rounded="pill" size="small" class="mr-2" @click="showComingSoon('Advanced Statement Filtering')">Filter</v-btn>
          </v-toolbar>

          <v-data-table
            :headers="headers"
            :items="payments"
            :loading="loading"
            :search="search"
            class="modern-table"
            hover
          >
            <template v-slot:item.period_search="{ item }">
              <div class="d-flex align-center py-3">
                <v-avatar color="indigo-lighten-5" size="40" class="mr-4" rounded="lg">
                  <v-icon color="indigo">mdi-calendar-month</v-icon>
                </v-avatar>
                <div>
                  <div class="font-weight-bold">{{ formatMonth(item.month) }} {{ item.year }}</div>
                  <div class="text-caption text-grey">Siklus payroll aktif</div>
                </div>
              </div>
            </template>

            <template v-slot:item.total_amoun="{ item }">
              <div class="font-weight-bold text-body-1">{{ formatCurrency(item.total_amoun) }}</div>
            </template>

            <template v-slot:item.total_emplo="{ item }">
              <v-chip size="small" variant="tonal" class="font-weight-bold">
                {{ item.total_emplo }} Pegawai
              </v-chip>
            </template>

            <template v-slot:item.skpd_search="{ item }">
              <div class="text-body-2 font-weight-bold">{{ item.skpd_search || '-' }}</div>
            </template>

            <template v-slot:item.sub_giat_search="{ item }">
              <div class="d-flex align-center">
                 <v-chip size="x-small" color="blue-lighten-4" variant="flat" class="mr-2 font-weight-bold">{{ item.sub_giat_code || '-' }}</v-chip>
                 <div class="text-caption font-weight-bold text-truncate" style="max-width: 250px;">{{ item.sub_giat_search || '-' }}</div>
              </div>
            </template>

            <template v-slot:item.status="{ item }">
              <v-chip color="success-lighten-1" size="small" variant="tonal" class="font-weight-bold">
                <v-icon start size="14">mdi-check-circle-outline</v-icon>
                DIBAYARKAN
              </v-chip>
            </template>

            <template v-slot:item.actions="{ item }">
              <v-btn icon="mdi-file-pdf-box" variant="text" color="error" size="small" @click="handleDownloadPdf(item)"></v-btn>
              <v-btn icon="mdi-eye-outline" variant="text" color="primary" size="small" @click="handleViewDetails(item)"></v-btn>
            </template>

            <template v-slot:no-data>
              <div class="text-center py-12">
                <v-icon size="64" color="grey-lighten-3">mdi-cash-remove</v-icon>
                <div class="text-grey mt-2">Data riwayat payroll tidak ditemukan.</div>
              </div>
            </template>
          </v-data-table>
        </v-card>
      </v-container>
    </v-main>

    <!-- Details Dialog -->
    <v-dialog v-model="detailsDialog" max-width="800px" scrollable>
      <v-card class="rounded-xl overflow-hidden glass-card">
        <v-toolbar color="primary" class="px-4">
          <v-icon color="white" class="mr-3">mdi-text-box-search-outline</v-icon>
          <v-toolbar-title class="text-white font-weight-bold">Detail Laporan</v-toolbar-title>
          <v-spacer></v-spacer>
          <v-btn icon="mdi-close" color="white" variant="text" @click="detailsDialog = false"></v-btn>
        </v-toolbar>
        
        <v-card-text class="pa-6" v-if="selectedPayment">
           <div class="mb-6 d-flex align-center justify-space-between">
              <div>
                 <div class="text-h6 font-weight-bold">{{ formatMonth(selectedPayment.month) }} {{ selectedPayment.year }}</div>
                 <div class="text-caption text-grey">Statement ID: #{{ selectedPayment.id }}</div>
              </div>
              <v-btn color="error" prepend-icon="mdi-file-pdf-box" variant="tonal" rounded="pill" @click="handleDownloadPdf(selectedPayment)">
                DOWNLOAD PDF
              </v-btn>
           </div>

           <!-- New Summary Card from Screenshot -->
           <v-card variant="flat" class="mb-8 rounded-lg border-0 bg-transparent">
              <v-row>
                <!-- Left Column -->
                <v-col cols="12" md="6">
                   <div class="summary-item mb-4">
                      <div class="text-overline text-grey-darken-1 mb-n1">PERIODE</div>
                      <div class="text-h6 font-weight-bold">{{ formatMonth(selectedPayment.month) }} {{ selectedPayment.year }}</div>
                   </div>
                   <div class="summary-item mb-4">
                      <div class="text-overline text-grey-darken-1 mb-n1">TANGGAL PEMBAYARAN</div>
                      <div class="text-h6 font-weight-bold">{{ formatDate(selectedPayment.payment_dat) }}</div>
                   </div>
                   <div class="summary-item mb-4">
                      <div class="text-overline text-grey-darken-1 mb-n1">SUB KEGIATAN</div>
                      <div class="d-flex align-start mt-1">
                         <v-chip size="x-small" color="blue-lighten-4" text-color="blue-darken-4" class="mr-2 font-weight-bold px-2 mt-1" variant="flat">
                            {{ selectedPayment.rka_setting?.kode_sub_giat || '-' }}
                         </v-chip>
                         <div class="text-subtitle-1 font-weight-bold leading-tight">
                            {{ selectedPayment.rka_setting?.nama_sub_giat || '-' }}
                         </div>
                      </div>
                   </div>
                </v-col>

                <!-- Right Column -->
                <v-col cols="12" md="6">
                   <div class="summary-item mb-4">
                      <div class="text-overline text-grey-darken-1 mb-n1">KEGIATAN</div>
                      <div class="d-flex align-start mt-1">
                         <v-chip size="x-small" color="blue-lighten-4" text-color="blue-darken-4" class="mr-2 font-weight-bold px-2 mt-1" variant="flat">
                            {{ selectedPayment.rka_setting?.kode_giat || '-' }}
                         </v-chip>
                         <div class="text-subtitle-1 font-weight-bold leading-tight">
                            {{ selectedPayment.rka_setting?.nama_giat || '-' }}
                         </div>
                      </div>
                   </div>
                   <div class="summary-item mb-4">
                      <div class="text-overline text-grey-darken-1 mb-n1">TOTAL PEGAWAI</div>
                      <div class="text-h6 font-weight-bold d-flex align-center">
                         <v-icon color="info" class="mr-2" size="24">mdi-account-group</v-icon>
                         {{ selectedPayment.total_emplo }} Pegawai
                      </div>
                   </div>
                   <div class="summary-item mb-4">
                      <div class="text-overline text-grey-darken-1 mb-n1">TOTAL PEMBAYARAN</div>
                      <div class="text-h6 font-weight-bold d-flex align-center text-success">
                         <v-icon color="success" class="mr-2" size="24">mdi-cash-multiple</v-icon>
                         {{ formatCurrency(selectedPayment.total_amoun) }}
                      </div>
                   </div>
                </v-col>
              </v-row>

              <!-- Optional Notes/Catatan -->
              <v-alert
                v-if="selectedPayment.notes"
                color="blue-lighten-5"
                class="mt-4 rounded-lg border-blue-lighten-4"
                variant="flat"
                icon="mdi-information-outline"
                density="compact"
              >
                <div class="d-flex align-start">
                   <div class="text-blue-darken-4 font-weight-bold mr-4" style="min-width: 80px;">Catatan:</div>
                   <div class="text-blue-darken-3">{{ selectedPayment.notes }}</div>
                </div>
              </v-alert>
           </v-card>

           <v-divider class="mb-6"></v-divider>

           <div v-if="detailsLoading" class="text-center py-8">
              <v-progress-circular indeterminate color="primary"></v-progress-circular>
           </div>

           <div v-else>
              <v-table density="comfortable" class="details-table">
                <thead>
                  <tr>
                    <th class="font-weight-bold">PEGAWAI</th>
                    <th class="font-weight-bold text-right">GAJI POKOK</th>
                    <th class="font-weight-bold text-right">PAJAK</th>
                    <th class="font-weight-bold text-right">IWP</th>
                    <th class="font-weight-bold text-right">TOTAL</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="det in selectedPayment.details" :key="det.id">
                    <td>
                       <div class="font-weight-bold text-body-2">{{ det.employee?.nama }}</div>
                       <div class="text-caption text-grey">{{ det.employee?.nip }}</div>
                    </td>
                    <td class="text-right">{{ formatCurrency(det.gaji_pokok) }}</td>
                    <td class="text-right text-error">{{ formatCurrency(det.pajak) }}</td>
                    <td class="text-right text-error">{{ formatCurrency(det.iwp) }}</td>
                    <td class="text-right font-weight-bold">
                       {{ formatCurrency(det.total_amoun) }}
                    </td>
                  </tr>
                </tbody>
              </v-table>
           </div>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Global Feedback Snackbar -->
    <v-snackbar v-model="snackbar" :timeout="3000" color="primary" rounded="lg" elevation="24">
      <div class="d-flex align-center">
        <v-icon class="mr-3">mdi-wallet-membership</v-icon>
        <div>
          <div class="font-weight-bold">{{ snackbarTitle }}</div>
          <div class="text-caption">Fitur ini akan segera hadir pada pembaruan berikutnya.</div>
        </div>
      </div>
      <template v-slot:actions>
        <v-btn variant="text" @click="snackbar = false">TUTUP</v-btn>
      </template>
    </v-snackbar>
  </v-app>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api'
import ThemeToggle from '../components/ThemeToggle.vue'
import Navbar from '../components/Navbar.vue'
import Sidebar from '../components/Sidebar.vue'

const route = useRoute()
const loading = ref(true)
const search = ref('')
const detailsLoading = ref(false)
const payments = ref([])
const snackbar = ref(false)
const snackbarTitle = ref('')
const detailsDialog = ref(false)
const selectedPayment = ref(null)
const filterSkpdId = ref(null)
const filterMonth = ref(null)
const filterYear = ref(null)
const skpdName = ref('')

const showComingSoon = (feature) => {
  snackbarTitle.value = feature
  snackbar.value = true
}
const headers = [
  { title: 'PERIODE PEMBAYARAN', key: 'period_search', sortable: true },
  { title: 'SKPD', key: 'skpd_search', sortable: true },
  { title: 'SUB KEGIATAN', key: 'sub_giat_search', sortable: true },
  { title: 'TOTAL DIBAYARKAN', key: 'total_amoun', sortable: true },
  { title: 'JUMLAH PENERIMA', key: 'total_emplo', sortable: false },
  { title: 'TANGGAL', key: 'payment_dat', sortable: true },
  { title: 'STATUS', key: 'status', sortable: false, width: '120px' },
  { title: '', key: 'actions', sortable: false, align: 'end' },
]

const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value || 0)
}

const formatCurrencyShort = (value) => {
  if (!value) return 'Rp 0'
  if (value >= 1000000000) return 'Rp ' + (value / 1000000000).toFixed(2) + ' Miliar'
  if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(2) + ' Juta'
  return formatCurrency(value)
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  }).format(date)
}

const formatMonth = (m) => {
  const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
  return months[m - 1] || m;
}

const handleDownloadPdf = async (item) => {
  try {
     const response = await api.get(`/payments/${item.id}/pdf`, {
       responseType: 'blob'
     });
     const url = window.URL.createObjectURL(new Blob([response.data]));
     const link = document.createElement('a');
     link.href = url;
     link.setAttribute('download', `payroll-${item.month}-${item.year}.pdf`);
     document.body.appendChild(link);
     link.click();
     link.remove();
  } catch (err) {
     console.error('PDF Download Error:', err);
     alert('Gagal mendownload PDF. Pastikan data lengkap.');
  }
}

const handleViewDetails = async (item) => {
  selectedPayment.value = item;
  detailsDialog.value = true;
  detailsLoading.value = true;
  
  try {
    const params = {}
    if (filterSkpdId.value) {
      params.skpd_id = filterSkpdId.value
    }
    const response = await api.get(`/payments/${item.id}`, { params });
    if (response.data.success) {
      selectedPayment.value = response.data.data;
    }
  } catch (err) {
    console.error('Details Error:', err);
  } finally {
    detailsLoading.value = false;
  }
}

onMounted(async () => {
  // Read query params
  filterMonth.value = route.query.month ? parseInt(route.query.month) : null
  filterYear.value = route.query.year ? parseInt(route.query.year) : null
  filterSkpdId.value = route.query.skpd_id ? parseInt(route.query.skpd_id) : null

  try {
    const response = await api.get('/payments')
    let data = response.data.data.data

    // Filter by month and year if provided
    if (filterMonth.value && filterYear.value) {
      data = data.filter(p => p.month === filterMonth.value && p.year === filterYear.value)
    }

    payments.value = data.map(item => ({
      ...item,
      // Add searchable flat strings
      period_search: `${formatMonth(item.month)} ${item.year}`,
      skpd_search: item.rka_setting?.pptk_setting?.skpd?.nama_skpd || '',
      sub_giat_search: `${item.rka_setting?.kode_sub_giat || ''} ${item.rka_setting?.nama_sub_giat || ''}`,
      sub_giat_code: item.rka_setting?.kode_sub_giat || ''
    }))

    // If skpd_id provided, auto-open first payment details
    if (filterSkpdId.value && data.length > 0) {
      // Fetch SKPD name
      try {
        const skpdRes = await api.get(`/skpd/${filterSkpdId.value}`)
        if (skpdRes.data.success) {
          skpdName.value = skpdRes.data.data.nama_skpd
        }
      } catch (e) {}
      
      // Auto-open details for the specific period
      handleViewDetails(data[0])
    }
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.glass-card {
  background: rgba(var(--v-theme-surface), 0.95) !important;
  border: 1px solid rgba(var(--v-border-color), 0.08) !important;
}

.text-primary-gradient {
  background: linear-gradient(45deg, #1867C0, #5CBBF6);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  font-weight: 800;
}

/* Table Styling */
:deep(.modern-table) {
  background: transparent !important;
}

:deep(.v-data-table-header) {
  background: rgba(var(--v-border-color), 0.04);
}

:deep(.v-data-table-header th) {
  font-weight: 700 !important;
  text-transform: uppercase;
  font-size: 0.75rem !important;
  letter-spacing: 0.05em;
}

.border-b {
  border-bottom: 1px solid rgba(var(--v-border-color), 0.08) !important;
}

.max-width-300 {
  max-width: 300px !important;
}

.leading-tight {
  line-height: 1.25 !important;
}

:deep(.details-table) {
  background: transparent !important;
}

:deep(.details-table th) {
  background: rgba(var(--v-border-color), 0.02) !important;
  font-size: 0.75rem !important;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
</style>
