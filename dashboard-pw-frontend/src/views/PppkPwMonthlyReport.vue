<template>
  <div class="modern-dashboard">
    <Navbar />
    <Sidebar />

    <v-main class="bg-dashboard">
      <v-container fluid class="pa-8">
        <!-- Header Section -->
        <div class="d-flex justify-space-between align-center mb-6">
          <div>
            <h1 class="text-h4 font-weight-black text-primary mb-1">
              Laporan Penggajian
            </h1>
            <p class="text-subtitle-1 text-medium-emphasis">
              Rekap realisasi pembayaran gaji berdasarkan periode dan sub kegiatan
            </p>
          </div>
          <v-chip color="primary" variant="tonal" class="font-weight-bold px-4" size="large">
            {{ selectedYear }}
          </v-chip>
        </div>

        <!-- Filter Card -->
        <v-card class="glass-panel mb-8 pa-6" elevation="0">
          <div class="d-flex align-center mb-6">
            <v-icon color="primary" class="mr-2">mdi-filter-variant</v-icon>
            <span class="text-overline font-weight-bold text-primary">FILTER & PARAMETER LAPORAN</span>
          </div>

          <v-row>
            <v-col cols="12" md="3">
              <label class="text-caption font-weight-bold text-grey-darken-1 mb-1 d-block">
                <v-icon size="14" class="mr-1">mdi-calendar-range</v-icon>Tahun
              </label>
              <v-select
                v-model="selectedYear"
                :items="years"
                variant="outlined"
                density="comfortable"
                rounded="lg"
                hide-details
              ></v-select>
            </v-col>

            <v-col cols="12" md="3">
              <label class="text-caption font-weight-bold text-grey-darken-1 mb-1 d-block">
                <v-icon size="14" class="mr-1">mdi-calendar-month</v-icon>Bulan
              </label>
              <v-select
                v-model="selectedMonth"
                :items="months"
                item-title="title"
                item-value="value"
                variant="outlined"
                density="comfortable"
                rounded="lg"
                hide-details
              ></v-select>
            </v-col>

            <v-col cols="12" md="3">
              <label class="text-caption font-weight-bold text-grey-darken-1 mb-1 d-block">
                <v-icon size="14" class="mr-1">mdi-office-building</v-icon>SKPD
              </label>
              <v-autocomplete
                v-model="selectedSkpd"
                :items="skpdOptions"
                item-title="title"
                item-value="value"
                variant="outlined"
                density="comfortable"
                rounded="lg"
                hide-details
                placeholder="Semua SKPD"
                clearable
              ></v-autocomplete>
            </v-col>

            <v-col cols="12" md="3">
              <label class="text-caption font-weight-bold text-grey-darken-1 mb-1 d-block">
                <v-icon size="14" class="mr-1">mdi-file-tree</v-icon>Sub Kegiatan
              </label>
              <v-autocomplete
                v-model="selectedSubGiat"
                :items="subGiatOptions"
                item-title="title"
                item-value="value"
                variant="outlined"
                density="comfortable"
                rounded="lg"
                hide-details
                placeholder="— Semua Sub Kegiatan —"
                clearable
              >
                <template v-slot:item="{ props, item }">
                  <v-list-item v-bind="props" :subtitle="item.raw.kode_sub_giat"></v-list-item>
                </template>
              </v-autocomplete>
            </v-col>
          </v-row>

          <div class="d-flex justify-space-between align-center mt-6">
            <div class="d-flex ga-3">
              <v-btn
                color="primary"
                prepend-icon="mdi-magnify"
                size="large"
                rounded="lg"
                @click="fetchData"
                :loading="loading"
                elevation="2"
              >
                Tampilkan
              </v-btn>
              <v-btn
                color="indigo-lighten-5"
                class="text-indigo-darken-2"
                prepend-icon="mdi-file-excel"
                size="large"
                rounded="lg"
                variant="flat"
                @click="exportExcel"
                :loading="exporting"
              >
                Export Excel
              </v-btn>
              <v-btn
                color="success"
                prepend-icon="mdi-printer"
                size="large"
                rounded="lg"
                variant="flat"
                @click="printReport"
              >
                Cetak
              </v-btn>
            </div>
            <div v-if="items.length" class="text-caption text-medium-emphasis">
              {{ items.length }} pegawai ditemukan
            </div>
          </div>
        </v-card>

        <!-- KPI Summary -->
        <v-row class="mb-8">
          <v-col cols="12" md="3">
            <v-card class="kpi-card glass-panel border-left-blue pa-4" elevation="0">
              <div class="d-flex align-center">
                <v-avatar color="blue-lighten-5" class="mr-4">
                  <v-icon color="blue-darken-2">mdi-account-group</v-icon>
                </v-avatar>
                <div>
                  <div class="text-h5 font-weight-black">{{ summary.total_pegawai }}</div>
                  <div class="text-caption text-medium-emphasis font-weight-bold">Total Pegawai</div>
                </div>
              </div>
            </v-card>
          </v-col>

          <v-col cols="12" md="3">
            <v-card class="kpi-card glass-panel border-left-green pa-4" elevation="0">
              <div class="d-flex align-center">
                <v-avatar color="green-lighten-5" class="mr-4">
                  <v-icon color="green-darken-2">mdi-cash-multiple</v-icon>
                </v-avatar>
                <div>
                  <div class="text-h5 font-weight-black">{{ formatCurrency(summary.total_gaji_pokok) }}</div>
                  <div class="text-caption text-medium-emphasis font-weight-bold">Total Gaji Pokok</div>
                </div>
              </div>
            </v-card>
          </v-col>

          <v-col cols="12" md="3">
            <v-card class="kpi-card glass-panel border-left-red pa-4" elevation="0">
              <div class="d-flex align-center">
                <v-avatar color="red-lighten-5" class="mr-4">
                  <v-icon color="red-darken-2">mdi-cash-minus</v-icon>
                </v-avatar>
                <div>
                  <div class="text-h5 font-weight-black">{{ formatCurrency(summary.total_potongan) }}</div>
                  <div class="text-caption text-medium-emphasis font-weight-bold">Total Potongan</div>
                </div>
              </div>
            </v-card>
          </v-col>

          <v-col cols="12" md="3">
            <v-card class="kpi-card glass-panel border-left-purple pa-4" elevation="0">
              <div class="d-flex align-center">
                <v-avatar color="purple-lighten-5" class="mr-4">
                  <v-icon color="purple-darken-2">mdi-bank-transfer</v-icon>
                </v-avatar>
                <div>
                  <div class="text-h5 font-weight-black">{{ formatCurrency(summary.total_bersih) }}</div>
                  <div class="text-caption text-medium-emphasis font-weight-bold">Total Bersih Diterima</div>
                </div>
              </div>
            </v-card>
          </v-col>
        </v-row>

        <!-- Table Card -->
        <v-card class="glass-panel rounded-xl overflow-hidden" elevation="0">
          <div class="pa-4 d-flex justify-space-between align-center bg-grey-lighten-4 border-bottom">
            <div class="d-flex align-center">
              <v-icon color="primary" class="mr-2">mdi-table-large</v-icon>
              <h2 class="text-h6 font-weight-black text-grey-darken-3">HASIL LAPORAN</h2>
              <v-chip color="primary" size="small" class="ml-3 font-weight-bold">{{ selectedMonthName }} {{ selectedYear }}</v-chip>
            </div>
            <v-text-field
              v-model="search"
              prepend-inner-icon="mdi-magnify"
              placeholder="Cari nama, NIP, jabatan..."
              variant="outlined"
              density="compact"
              rounded="lg"
              hide-details
              style="max-width: 300px"
              class="bg-white"
            ></v-text-field>
          </div>

          <v-data-table
            :headers="headers"
            :items="items"
            :search="search"
            :loading="loading"
            class="report-table"
            hover
            items-per-page="50"
            no-data-text="Belum ada data. Silakan pilih filter dan klik Tampilkan."
          >
            <template v-slot:item.no="{ index }">
              {{ index + 1 }}
            </template>
            
            <template v-slot:item.nama="{ item }">
              <div class="font-weight-black text-grey-darken-3">{{ item.nama }}</div>
            </template>

            <template v-slot:item.nip="{ item }">
              <span class="text-caption font-weight-bold text-grey-darken-1">{{ item.nip }}</span>
            </template>

            <template v-slot:item.jabatan="{ item }">
              <div class="text-caption font-weight-medium text-wrap" style="max-width: 200px">
                {{ item.jabatan }}
              </div>
            </template>

            <template v-slot:item.skpd="{ item }">
              <div class="text-caption font-weight-medium text-wrap" style="max-width: 200px">
                {{ item.skpd }}
              </div>
            </template>

            <template v-slot:item.nama_sub_giat="{ item }">
              <v-chip size="x-small" color="blue" variant="tonal" class="font-weight-bold text-wrap" style="max-height: auto; height: auto; padding: 4px 8px;">
                {{ item.nama_sub_giat }}
              </v-chip>
            </template>

            <template v-slot:item.sumber_dana="{ item }">
              <v-chip size="x-small" :color="item.sumber_dana === 'BLUD' ? 'orange' : 'indigo'" variant="tonal" class="font-weight-bold">
                {{ item.sumber_dana || 'APBD' }}
              </v-chip>
            </template>

            <template v-slot:item.gaji_pokok="{ item }">
              <div class="text-right font-weight-bold">{{ formatCurrency(item.gaji_pokok) }}</div>
            </template>

            <template v-slot:item.pajak="{ item }">
              <div class="text-right text-red font-weight-bold">{{ formatCurrency(item.pajak) }}</div>
            </template>

            <template v-slot:item.iwp="{ item }">
              <div class="text-right font-weight-bold">{{ formatCurrency(item.iwp) }}</div>
            </template>

            <template v-slot:item.potongan="{ item }">
              <div class="text-right text-red font-weight-bold">{{ formatCurrency(item.potongan) }}</div>
            </template>

            <template v-slot:item.total_amoun="{ item }">
              <div class="text-right font-weight-black text-success">{{ formatCurrency(item.total_amoun) }}</div>
            </template>
          </v-data-table>
        </v-card>
      </v-container>
    </v-main>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import Navbar from '../components/Navbar.vue'
import Sidebar from '../components/Sidebar.vue'
import api from '../api'

const loading = ref(false)
const exporting = ref(false)
const search = ref('')
const items = ref([])
const summary = ref({
  total_pegawai: 0,
  total_gaji_pokok: 0,
  total_potongan: 0,
  total_bersih: 0
})

const skpdOptions = ref([])
const subGiatOptions = ref([])

const selectedYear = ref(new Date().getFullYear())
const selectedMonth = ref(new Date().getMonth() + 1)
const selectedSkpd = ref(null)
const selectedSubGiat = ref(null)

const years = [2024, 2025, 2026, 2027, 2028]
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
  { title: 'Desember', value: 12 }
]

const selectedMonthName = computed(() => {
  return months.find(m => m.value === selectedMonth.value)?.title || ''
})

const headers = [
  { title: '#', key: 'no', sortable: false, width: '40px' },
  { title: 'NIP', key: 'nip', width: '150px' },
  { title: 'NAMA PEGAWAI', key: 'nama' },
  { title: 'JABATAN', key: 'jabatan' },
  { title: 'SKPD', key: 'skpd' },
  { title: 'SUB KEGIATAN', key: 'nama_sub_giat' },
  { title: 'SUMBER DANA', key: 'sumber_dana', align: 'center' },
  { title: 'GAJI POKOK', key: 'gaji_pokok', align: 'end' },
  { title: 'PAJAK', key: 'pajak', align: 'end' },
  { title: 'IWP', key: 'iwp', align: 'end' },
  { title: 'POT. LAIN', key: 'potongan', align: 'end' },
  { title: 'BERSIH DITERIMA', key: 'total_amoun', align: 'end' },
]

const fetchData = async () => {
  loading.value = true
  try {
    const res = await api.get('/reports/pppk-pw-monthly', {
      params: {
        year: selectedYear.value,
        month: selectedMonth.value,
        idskpd: selectedSkpd.value,
        rka_id: selectedSubGiat.value
      }
    })
    if (res.data.success) {
      items.value = res.data.data
      summary.value = res.data.summary
      skpdOptions.value = res.data.filters.skpds
      subGiatOptions.value = res.data.filters.sub_kegiatans
    }
  } catch (e) {
    console.error('Error fetching data:', e)
  } finally {
    loading.value = false
  }
}

const exportExcel = async () => {
  exporting.value = true
  try {
    const res = await api.get('/reports/pppk-pw-monthly-export', {
      params: {
        year: selectedYear.value,
        month: selectedMonth.value,
        idskpd: selectedSkpd.value,
        rka_id: selectedSubGiat.value
      },
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `laporan_pppk_pw_bulanan_${selectedMonth.value}_${selectedYear.value}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (e) {
    console.error('Error exporting excel:', e)
  } finally {
    exporting.value = false
  }
}

const printReport = () => {
  window.print()
}

const formatCurrency = (v) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(v || 0).replace('Rp', 'Rp ')
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.modern-dashboard {
  min-height: 100vh;
}

.bg-dashboard {
  background-color: #f8fafc !important;
  background-image: radial-gradient(at 0% 0%, rgba(var(--v-theme-primary), 0.03) 0, transparent 50%),
                    radial-gradient(at 100% 100%, rgba(var(--v-theme-info), 0.03) 0, transparent 50%);
}

.glass-panel {
  background: rgba(255, 255, 255, 0.8) !important;
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(var(--v-border-color), 0.08) !important;
  border-radius: 20px !important;
}

.border-left-blue { border-left: 5px solid #3b82f6 !important; }
.border-left-green { border-left: 5px solid #10b981 !important; }
.border-left-red { border-left: 5px solid #ef4444 !important; }
.border-left-purple { border-left: 5px solid #8b5cf6 !important; }

.kpi-card {
  transition: transform 0.2s ease;
}

.kpi-card:hover {
  transform: translateY(-5px);
}

.report-table {
  background: transparent !important;
}

:deep(.v-data-table-header) {
  background: #f1f5f9 !important;
}

:deep(.v-data-table-header th) {
  font-weight: 800 !important;
  text-transform: uppercase;
  font-size: 0.75rem !important;
  color: #475569 !important;
  letter-spacing: 0.05em;
  border-bottom: 2px solid #e2e8f0 !important;
}

:deep(.v-data-table__tr:hover) {
  background-color: rgba(var(--v-theme-primary), 0.02) !important;
}

:deep(.v-data-table__td) {
  padding: 12px 16px !important;
  border-bottom: 1px solid #f1f5f9 !important;
}

.text-wrap {
  white-space: normal !important;
}

@media print {
  .v-navigation-drawer, .v-app-bar, .v-btn, .v-card:not(.report-table-card) {
    display: none !important;
  }
  .v-main {
    padding: 0 !important;
  }
  .glass-panel {
    border: none !important;
    background: white !important;
  }
}
</style>
