<template>
  <v-app class="modern-bg">
    <Navbar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />
    <Sidebar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />

    <v-main>
      <v-container fluid class="pa-8">
        <!-- Top Summary Cards -->
        <v-row class="mb-6">
          <v-col cols="12" md="4">
            <v-card class="glass-card rounded-xl pa-4" elevation="0">
              <v-card-text>
                <div class="text-overline text-grey-darken-1 mb-1">Last Payment Total</div>
                <div class="text-h4 font-weight-bold primary--text">
                  {{ formatCurrencyShort(payments[0]?.total_amoun) }}
                </div>
                <div class="d-flex align-center mt-2">
                  <v-icon color="success" size="small" class="mr-1">mdi-arrow-up-thin</v-icon>
                  <span class="text-caption success--text font-weight-bold">+0.8%</span>
                  <span class="text-caption text-grey ml-1">vs prev month</span>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
          
          <v-col cols="12" md="4">
            <v-card class="glass-card rounded-xl pa-4" elevation="0">
              <v-card-text>
                <div class="text-overline text-grey-darken-1 mb-1">Avg. Payment / Person</div>
                <div class="text-h4 font-weight-bold">
                  {{ formatCurrencyShort(payments.reduce((acc, p) => acc + p.total_amoun, 0) / (payments.reduce((acc, p) => acc + p.total_emplo, 0) || 1)) }}
                </div>
              </v-card-text>
            </v-card>
          </v-col>

          <v-col cols="12" md="4">
            <v-card class="glass-card rounded-xl pa-4 d-flex align-center h-100" elevation="0">
              <v-card-text class="d-flex align-center">
                <v-icon size="48" color="primary-lighten-4" class="mr-4">mdi-file-download-outline</v-icon>
                <div>
                  <div class="text-subtitle-2 font-weight-bold">Reports Center</div>
                  <div class="text-caption text-grey">Generate and export payroll reports</div>
                  <v-btn color="primary" variant="text" size="small" class="px-0 mt-1 font-weight-bold" to="/">VISIT REPORTS</v-btn>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <!-- Transaction List -->
        <v-card class="glass-card rounded-xl overflow-hidden" elevation="0">
          <v-toolbar color="transparent" flat class="px-6 py-4 border-b">
            <v-toolbar-title class="font-weight-bold text-h6">Statements History</v-toolbar-title>
            <v-spacer></v-spacer>
            <v-btn prepend-icon="mdi-filter-variant" variant="tonal" color="grey" rounded="pill" size="small" class="mr-2" @click="showComingSoon('Advanced Statement Filtering')">Filter</v-btn>
          </v-toolbar>

          <v-data-table
            :headers="headers"
            :items="payments"
            :loading="loading"
            class="modern-table"
            hover
          >
            <template v-slot:item.period="{ item }">
              <div class="d-flex align-center py-3">
                <v-avatar color="indigo-lighten-5" size="40" class="mr-4" rounded="lg">
                  <v-icon color="indigo">mdi-calendar-month</v-icon>
                </v-avatar>
                <div>
                  <div class="font-weight-bold">{{ formatMonth(item.month) }} {{ item.year }}</div>
                  <div class="text-caption text-grey">Payroll cycle active</div>
                </div>
              </div>
            </template>

            <template v-slot:item.total_amoun="{ item }">
              <div class="font-weight-bold text-body-1">{{ formatCurrency(item.total_amoun) }}</div>
            </template>

            <template v-slot:item.total_emplo="{ item }">
              <v-chip size="small" color="grey-lighten-4" class="font-weight-bold text-grey-darken-3">
                {{ item.total_emplo }} Employees
              </v-chip>
            </template>

            <template v-slot:item.status="{ item }">
              <v-chip color="success-lighten-1" size="small" variant="tonal" class="font-weight-bold">
                <v-icon start size="14">mdi-check-circle-outline</v-icon>
                DISBURSED
              </v-chip>
            </template>

            <template v-slot:item.actions="{ item }">
              <v-btn icon="mdi-file-pdf-box" variant="text" color="error" size="small" @click="handleDownloadPdf(item)"></v-btn>
              <v-btn icon="mdi-eye-outline" variant="text" color="primary" size="small" @click="handleViewDetails(item)"></v-btn>
            </template>

            <template v-slot:no-data>
              <div class="text-center py-12">
                <v-icon size="64" color="grey-lighten-3">mdi-cash-remove</v-icon>
                <div class="text-grey mt-2">No payroll history found.</div>
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
          <v-toolbar-title class="text-white font-weight-bold">Statement Details</v-toolbar-title>
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

           <v-divider class="mb-6"></v-divider>

           <div v-if="detailsLoading" class="text-center py-8">
              <v-progress-circular indeterminate color="primary"></v-progress-circular>
           </div>
           
           <div v-else>
              <v-table density="comfortable">
                <thead>
                  <tr class="bg-grey-lighten-4">
                    <th class="font-weight-bold">EMPLOYEE</th>
                    <th class="font-weight-bold text-right">BASIC SALARY</th>
                    <th class="font-weight-bold text-right">ALLOWANCE</th>
                    <th class="font-weight-bold text-right">DEDUCTION</th>
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
                    <td class="text-right text-success">{{ formatCurrency(det.tunjangan) }}</td>
                    <td class="text-right text-error">{{ formatCurrency(det.potongan + det.pajak + det.iwp) }}</td>
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
          <div class="text-caption">This feature is coming soon in the next update.</div>
        </div>
      </div>
      <template v-slot:actions>
        <v-btn variant="text" @click="snackbar = false">CLOSE</v-btn>
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
  { title: 'PAYMENT PERIOD', key: 'period', sortable: true },
  { title: 'TOTAL DISBURSED', key: 'total_amoun', sortable: true },
  { title: 'BENEFICIARIES', key: 'total_emplo', sortable: false },
  { title: 'DATE', key: 'payment_dat', sortable: true },
  { title: 'STATUS', key: 'status', sortable: false, width: '150px' },
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

const formatMonth = (m) => {
  const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
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

    payments.value = data

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
.modern-bg {
  background-color: #f8fafc !important;
}

.glass-nav {
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important;
  z-index: 1000;
}

.glass-card {
  background: white !important;
  border: 1px solid rgba(0, 0, 0, 0.05) !important;
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
  background: rgba(0, 0, 0, 0.02);
}

:deep(.v-data-table-header th) {
  font-weight: 700 !important;
  color: #64748b !important;
  text-transform: uppercase;
  font-size: 0.75rem !important;
  letter-spacing: 0.05em;
}

.border-b {
  border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important;
}
</style>
