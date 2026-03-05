<template>
  <v-app class="modern-dashboard">
    <Navbar />
    <Sidebar />
    <v-main class="bg-light">
      <v-container fluid class="pa-6">
    <!-- Header Section -->
    <div class="d-flex align-center mb-6">
      <div class="icon-box mr-4">
        <v-icon icon="mdi-cash-fast" color="primary" size="32"></v-icon>
      </div>
      <div>
        <h1 class="text-h4 font-weight-bold mb-1">Perhitungan THR PPPK-PW</h1>
        <p class="text-subtitle-1 text-medium-emphasis">
          Simulasi dan Laporan THR PPPK Paruh Waktu (Januari 2026 - Sekarang)
        </p>
      </div>
      <v-spacer></v-spacer>
      <div class="d-flex ga-2">
        <v-btn
          prepend-icon="mdi-file-excel-outline"
          color="success"
          variant="tonal"
          rounded="lg"
          @click="exportData('excel')"
          :loading="exportLoading"
        >
          Ekspor Excel
        </v-btn>
        <v-btn
          prepend-icon="mdi-file-pdf-box"
          color="error"
          variant="tonal"
          rounded="lg"
          @click="exportData('pdf')"
          :loading="exportLoading"
        >
          Cetak PDF
        </v-btn>
      </div>
    </div>

    <!-- Filter Card -->
    <v-card class="glass-card mb-6 pa-4" variant="flat">
      <v-row align="center">
        <v-col cols="12" sm="4">
          <v-select
            v-model="selectedMonth"
            :items="months"
            item-title="title"
            item-value="value"
            label="Bulan Pembayaran THR"
            variant="outlined"
            density="comfortable"
            hide-details
            prepend-inner-icon="mdi-calendar-month"
            @update:modelValue="fetchData"
            rounded="lg"
          ></v-select>
        </v-col>
        <v-col cols="12" sm="8">
          <div class="d-flex ga-4 justify-end">
            <v-chip color="primary" variant="flat" size="large" class="px-4 py-6 rounded-xl">
              <v-icon start icon="mdi-account-group"></v-icon>
              <div class="d-flex flex-column align-start ml-2">
                <span class="text-caption" style="line-height: 1">Total Pegawai</span>
                <span class="text-h6 font-weight-black">{{ meta.total_employees || 0 }}</span>
              </div>
            </v-chip>
            <v-chip color="secondary" variant="flat" size="large" class="px-4 py-6 rounded-xl">
              <v-icon start icon="mdi-cash-multiple"></v-icon>
              <div class="d-flex flex-column align-start ml-2">
                <span class="text-caption" style="line-height: 1">Total THR</span>
                <span class="text-h6 font-weight-black">{{ formatCurrency(meta.total_thr_amount || 0) }}</span>
              </div>
            </v-chip>
          </div>
        </v-col>
      </v-row>
    </v-card>

    <!-- Table Section -->
    <v-card class="glass-card overflow-hidden" variant="flat">
      <v-data-table
        :headers="headers"
        :items="items"
        :loading="loading"
        class="custom-table"
        hover
        :items-per-page="15"
      >
        <template v-slot:item.gapok_basis="{ item }">
          {{ formatCurrency(item.gapok_basis) }}
        </template>
        <template v-slot:item.n_months="{ item }">
          <v-chip size="small" variant="tonal" color="info" rounded="lg">
            {{ item.n_months }}/12
          </v-chip>
        </template>
        <template v-slot:item.thr_amount="{ item }">
          <span class="font-weight-bold text-primary">{{ formatCurrency(item.thr_amount) }}</span>
        </template>
        
        <template v-slot:no-data>
          <div class="pa-12 text-center">
            <v-icon icon="mdi-account-search-outline" size="64" color="disabled" class="mb-4"></v-icon>
            <p class="text-h6 text-disabled">Gagal memuat data atau data masih kosong.</p>
          </div>
        </template>
      </v-data-table>
    </v-card>

    <!-- Formula Info -->
    <v-alert
      type="info"
      variant="tonal"
      class="mt-6 rounded-lg"
      icon="mdi-calculator-variant"
    >
      <strong>Rumus THR:</strong> Gaji Pokok (Data Pebruari 2026) × (n / 12). 
      Dimana <strong>n</strong> adalah jumlah bulan bekerja terhitung sejak 1 Januari 2026 sampai dengan bulan pembayaran THR yang dipilih.
    </v-alert>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../api'
import Sidebar from '../components/Sidebar.vue'
import Navbar from '../components/Navbar.vue'

const loading = ref(false)
const exportLoading = ref(false)
const selectedMonth = ref(4) // Default to April
const items = ref([])
const meta = ref({})

const months = [
  { title: 'Januari', value: 1 },
  { title: 'Februari', value: 2 },
  { title: 'Maret', value: 3 },
  { title: 'April', value: 4 },
  { title: 'Mei', value: 5 },
  { title: 'Juni', value: 6 },
  { title: 'Juli', value: 7 },
  { title: 'Agustus', value: 8 },
  { title: 'September', value: 9 },
  { title: 'Oktober', value: 10 },
  { title: 'November', value: 11 },
  { title: 'Desember', value: 12 },
]

const headers = [
  { title: 'Nama Pegawai', key: 'nama', align: 'start', sortable: true },
  { title: 'NIP', key: 'nip', align: 'start' },
  { title: 'Jabatan', key: 'jabatan', align: 'start' },
  { title: 'SKPD', key: 'skpd', align: 'start' },
  { title: 'Gapok (Basis)', key: 'gapok_basis', align: 'end' },
  { title: 'Masa Kerja', key: 'n_months', align: 'center' },
  { title: 'Besaran THR', key: 'thr_amount', align: 'end' },
]

const fetchData = async () => {
  loading.value = true
  try {
    const response = await api.get('/thr/pppk-pw', {
      params: { month: selectedMonth.value }
    })
    
    // Flatten the grouped data for the simple table view
    const allEmployees = []
    response.data.data.forEach(group => {
      group.employees.forEach(emp => {
        allEmployees.push({
          ...emp,
          skpd: group.skpd_name
        })
      })
    })
    
    items.value = allEmployees
    meta.value = response.data.meta
  } catch (error) {
    console.error('Error fetching THR data:', error)
  } finally {
    loading.value = false
  }
}

const exportData = async (type) => {
  exportLoading.value = true
  try {
    const url = `/thr/pppk-pw/${type}?month=${selectedMonth.value}`
    const response = await api.get(url, { responseType: 'blob' })
    
    const blob = new Blob([response.data])
    const link = document.createElement('a')
    link.href = window.URL.createObjectURL(blob)
    link.download = `THR_PPPK_PW_2026_${selectedMonth.value}.${type === 'excel' ? 'xlsx' : 'pdf'}`
    link.click()
  } catch (error) {
    console.error(`Error exporting ${type}:`, error)
    alert(`Gagal mengekspor ${type}. Silakan coba lagi.`)
  } finally {
    exportLoading.value = false
  }
}

const formatCurrency = (value) => {
  if (!value) return '-'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(value)
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.glass-card {
  background: rgba(255, 255, 255, 0.7) !important;
  backdrop-filter: blur(12px);
  border: 1px solid rgba(0, 0, 0, 0.05) !important;
  border-radius: 20px !important;
}

.icon-box {
  width: 56px;
  height: 56px;
  background: rgb(var(--v-theme-primary), 0.1);
  border-radius: 16px;
  display: flex;
  align-center: center;
  justify-content: center;
}

.custom-table :deep(th) {
  font-weight: 700 !important;
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.05em;
  color: rgba(var(--v-theme-surface-variant), 0.7);
  background: rgba(var(--v-theme-surface), 0.5) !important;
}

.custom-table :deep(td) {
  font-size: 0.875rem;
  padding: 12px 16px !important;
}

.transition-all {
  transition: all 0.3s ease;
}
</style>
