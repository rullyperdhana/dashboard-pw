<template>
  <div class="modern-dashboard">
    <Navbar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />
    <Sidebar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />

    <v-main class="bg-light">
      <v-container fluid class="pa-8">
        <!-- Header -->
        <v-row class="mb-6 align-center">
          <v-col cols="12" md="6">
            <h1 class="text-h4 font-weight-bold mb-1">
              <v-icon start color="orange-darken-2" size="36">mdi-account-clock-outline</v-icon>
              Laporan Bulanan PPPK-PW
            </h1>
            <p class="text-subtitle-1 text-grey-darken-1">Rekapitulasi gaji PPPK Paruh Waktu per SKPD.</p>
          </v-col>
          <v-col cols="12" md="6" class="d-flex justify-end align-center ga-2 flex-wrap">
            <v-menu v-model="menu" :close-on-content-click="false">
              <template v-slot:activator="{ props }">
                <v-btn color="orange-darken-2" variant="tonal" v-bind="props" prepend-icon="mdi-calendar" size="large">
                  {{ selectedMonthName }} {{ selectedYear }}
                </v-btn>
              </template>
              <v-card min-width="300" class="pa-4 rounded-xl">
                <v-row dense>
                  <v-col cols="6">
                    <v-select v-model="selectedMonth" :items="months" item-title="title" item-value="value" label="Bulan" density="compact" variant="outlined" hide-details></v-select>
                  </v-col>
                  <v-col cols="6">
                    <v-select v-model="selectedYear" :items="years" label="Tahun" density="compact" variant="outlined" hide-details></v-select>
                  </v-col>
                  <v-col cols="12">
                    <v-select v-model="selectedJenisGaji" :items="jenisGajiOptions" label="Jenis Gaji" density="compact" variant="outlined" hide-details></v-select>
                  </v-col>
                  <v-col cols="12" class="mt-2 text-right">
                    <v-btn block color="orange-darken-2" @click="fetchData(); menu = false">TERAPKAN</v-btn>
                  </v-col>
                </v-row>
              </v-card>
            </v-menu>
            <v-btn color="success" prepend-icon="mdi-file-excel" @click="exportData('excel')" :loading="exporting" variant="flat">Excel</v-btn>
            <v-btn color="error"   prepend-icon="mdi-file-pdf-box" @click="exportData('pdf')" :loading="exporting" variant="flat">PDF</v-btn>
          </v-col>
        </v-row>

        <!-- Grand total bar -->
        <v-card class="rounded-lg mb-4 pa-3" color="orange-lighten-5" elevation="0" v-if="meta">
          <v-row align="center" dense>
            <v-col cols="auto">
              <v-chip color="orange-darken-2" label size="small" class="font-weight-bold">PPPK PARUH WAKTU</v-chip>
            </v-col>
            <v-col>
              <span class="text-body-2 text-medium-emphasis">
                <strong>{{ meta.total_skpd }}</strong> SKPD &nbsp;·&nbsp;
                <strong>{{ formatNumber(meta.total_employees) }}</strong> Pegawai &nbsp;·&nbsp;
                Total Bersih: <strong class="text-orange-darken-4">{{ formatCurrency(meta.grand_total) }}</strong>
              </span>
            </v-col>
          </v-row>
        </v-card>

        <!-- Data table -->
        <v-card class="glass-card rounded-xl" :loading="loading" elevation="0">
          <v-data-table
            :headers="summaryHeaders"
            :items="items"
            :loading="loading"
            class="bg-transparent detail-table"
            hover
            items-per-page="25"
            density="compact"
          >
            <!-- Currency formatting -->
            <template v-for="col in currencyCols" :key="col" v-slot:[`item.${col}`]="{ item }">
              <span :class="col === 'total_bersih' ? 'font-weight-bold text-orange-darken-2' : ''">
                {{ formatCurrency(item[col]) }}
              </span>
            </template>

            <template v-slot:item.employee_count="{ item }">
              <v-chip size="x-small" color="blue-grey" variant="tonal">{{ item.employee_count }}</v-chip>
            </template>
            <template v-slot:item.kode_skpd="{ item }">
              <span class="font-weight-medium text-caption text-grey-darken-1">{{ item.kode_skpd }}</span>
            </template>

            <template v-slot:no-data>
              <div class="py-8 text-center text-medium-emphasis">
                <v-icon icon="mdi-database-off-outline" size="48" class="mb-2"></v-icon>
                <div>Tidak ada data untuk periode ini.</div>
              </div>
            </template>
          </v-data-table>
        </v-card>
      </v-container>
    </v-main>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '../api'
import Navbar from '../components/Navbar.vue'
import Sidebar from '../components/Sidebar.vue'

const loading   = ref(false)
const exporting = ref(false)
const items     = ref([])
const meta      = ref(null)
const selectedMonth = ref(new Date().getMonth() + 1)
const selectedYear  = ref(new Date().getFullYear())
const selectedJenisGaji = ref('Induk')
const jenisGajiOptions = ['Semua', 'Induk', 'THR', 'Gaji 13', 'Susulan', 'Kekurangan', 'Terusan']
const menu          = ref(false)

const summaryHeaders = [
  { title: 'Kode SKPD',      key: 'kode_skpd',        align: 'start',  width: 140 },
  { title: 'Nama SKPD',      key: 'nama_skpd',         align: 'start'  },
  { title: 'PEG',            key: 'employee_count',    align: 'center', width: 80  },
  { title: 'Gaji Pokok',     key: 'total_gaji_pokok',  align: 'end'   },
  { title: 'Tunjangan',      key: 'total_tunjangan',   align: 'end'   },
  { title: 'Potongan',       key: 'total_potongan',    align: 'end'   },
  { title: 'Bersih',         key: 'total_bersih',      align: 'end'   },
]

const currencyCols = ['total_gaji_pokok','total_tunjangan','total_potongan','total_bersih']

const months = [
  { title: 'Januari',   value: 1  }, { title: 'Februari',  value: 2  },
  { title: 'Maret',     value: 3  }, { title: 'April',     value: 4  },
  { title: 'Mei',       value: 5  }, { title: 'Juni',      value: 6  },
  { title: 'Juli',      value: 7  }, { title: 'Agustus',   value: 8  },
  { title: 'September', value: 9  }, { title: 'Oktober',   value: 10 },
  { title: 'November',  value: 11 }, { title: 'Desember',  value: 12 },
]
const years = [2023, 2024, 2025, 2026, 2027]
const selectedMonthName = computed(() => months.find(m => m.value === selectedMonth.value)?.title ?? '')

const fetchData = async () => {
  loading.value = true
  try {
    const res = await api.get('/reports/paid-skpds', {
      params: { 
        month: selectedMonth.value, 
        year: selectedYear.value, 
        type: 'pw',
        jenis_gaji: selectedJenisGaji.value
      }
    })
    items.value = res.data.data
    meta.value  = res.data.meta
  } catch (e) {
    console.error('Error fetching data:', e)
  } finally {
    loading.value = false
  }
}

const exportData = async (format) => {
  exporting.value = true
  try {
    const res = await api.get('/reports/paid-export', {
      params: { 
        month: selectedMonth.value, 
        year: selectedYear.value, 
        format, 
        type: 'pw',
        jenis_gaji: selectedJenisGaji.value
      },
      responseType: 'blob'
    })
    const url  = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href  = url
    const ext  = format === 'pdf' ? 'pdf' : 'xlsx'
    link.setAttribute('download', `laporan_skpd_pw_${selectedMonth.value}_${selectedYear.value}.${ext}`)
    document.body.appendChild(link)
    link.click()
    link.remove()
  } catch (e) { console.error('Error exporting:', e) }
  finally { exporting.value = false }
}

const formatCurrency = (v) =>
  new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(v ?? 0)

const formatNumber = (v) => new Intl.NumberFormat('id-ID').format(v ?? 0)

onMounted(async () => {
  await fetchData()
})
</script>

<style scoped>
.modern-dashboard { background-color: rgb(var(--v-theme-background)) !important; }
.bg-light         { background-color: rgb(var(--v-theme-background)) !important; }
.glass-card {
  background-color: rgb(var(--v-theme-surface)) !important;
  border: 1px solid rgba(var(--v-border-color), 0.08) !important;
  box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07) !important;
  overflow-x: auto;
}

:deep(.v-data-table) { background: transparent !important; }
:deep(.v-data-table__th) {
  font-weight: 700 !important;
  text-transform: uppercase;
  font-size: 0.7rem;
  letter-spacing: 0.04em;
  white-space: nowrap;
}
:deep(.v-data-table__td) { white-space: nowrap; font-size: 0.78rem; }
:deep(.v-data-table__tr:hover) { background-color: rgba(255, 152, 0, 0.05) !important; }
</style>
