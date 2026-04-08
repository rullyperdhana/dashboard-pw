<template>
  <div class="modern-dashboard">
    <Navbar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />
    <Sidebar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />

    <v-main class="bg-light">
      <v-container fluid class="pa-8">
        <!-- Header -->
        <v-row class="mb-6 align-center">
          <v-col cols="12" md="5">
            <h1 class="text-h4 font-weight-bold mb-1">
              <v-icon start :color="themeColor" size="36">{{ isPwMode ? 'mdi-account-clock-outline' : 'mdi-calendar-range' }}</v-icon>
              {{ isPwMode ? 'Laporan Periodik PPPK-PW' : 'Laporan Periodik' }}
            </h1>
            <p class="text-subtitle-1 text-grey-darken-1">{{ isPwMode ? 'Rekapitulasi gaji PPPK Paruh Waktu per SKPD untuk periode tertentu.' : 'Rekapitulasi gaji per SKPD untuk periode tertentu (Triwulan, Semester, Tahunan, Custom).' }}</p>
          </v-col>
          <v-col cols="12" md="7" class="d-flex justify-end align-center ga-2 flex-wrap">
            <!-- Period Controls -->
            <v-menu v-model="menu" :close-on-content-click="false">
              <template v-slot:activator="{ props }">
                <v-btn :color="themeColor" variant="tonal" v-bind="props" prepend-icon="mdi-calendar-range" size="large">
                  {{ periodDisplayLabel }}
                </v-btn>
              </template>
              <v-card min-width="360" class="pa-4 rounded-xl">
                <v-row dense>
                  <!-- Period type selector -->
                  <v-col cols="12">
                    <v-btn-toggle v-model="periodType" mandatory :color="themeColor" density="compact" class="mb-2" rounded="lg" divided>
                      <v-btn value="triwulan" size="small">Triwulan</v-btn>
                      <v-btn value="semester" size="small">Semester</v-btn>
                      <v-btn value="tahunan" size="small">Tahunan</v-btn>
                      <v-btn value="custom" size="small">Custom</v-btn>
                    </v-btn-toggle>
                  </v-col>

                  <!-- Triwulan selector -->
                  <v-col cols="12" v-if="periodType === 'triwulan'">
                    <v-select v-model="selectedTriwulan" :items="triwulanOptions" item-title="title" item-value="value" label="Pilih Triwulan" density="compact" variant="outlined" hide-details></v-select>
                  </v-col>

                  <!-- Semester selector -->
                  <v-col cols="12" v-if="periodType === 'semester'">
                    <v-select v-model="selectedSemester" :items="semesterOptions" item-title="title" item-value="value" label="Pilih Semester" density="compact" variant="outlined" hide-details></v-select>
                  </v-col>

                  <!-- Custom period -->
                  <template v-if="periodType === 'custom'">
                    <v-col cols="6">
                      <v-select v-model="customMonthFrom" :items="months" item-title="title" item-value="value" label="Dari Bulan" density="compact" variant="outlined" hide-details></v-select>
                    </v-col>
                    <v-col cols="6">
                      <v-select v-model="customMonthTo" :items="months" item-title="title" item-value="value" label="Sampai Bulan" density="compact" variant="outlined" hide-details></v-select>
                    </v-col>
                  </template>

                  <!-- Year -->
                  <v-col cols="12">
                    <v-select v-model="selectedYear" :items="years" label="Tahun" density="compact" variant="outlined" hide-details></v-select>
                  </v-col>

                  <!-- Jenis Gaji -->
                  <v-col cols="12">
                    <v-select v-model="selectedJenisGaji" :items="jenisGajiOptions" label="Jenis Gaji" density="compact" variant="outlined" hide-details></v-select>
                  </v-col>

                  <!-- Paper size for PDF -->
                  <v-col cols="12">
                    <v-select v-model="selectedPaperSize" :items="paperSizeOptions" item-title="title" item-value="value" label="Ukuran Kertas (PDF)" density="compact" variant="outlined" hide-details></v-select>
                  </v-col>

                  <v-col cols="12" class="mt-2 text-right">
                    <v-btn block :color="themeColor" @click="fetchData(); menu = false">TERAPKAN</v-btn>
                  </v-col>
                </v-row>
              </v-card>
            </v-menu>

            <v-btn color="success" prepend-icon="mdi-file-excel" @click="exportData('excel')" :loading="exporting" variant="flat">Excel</v-btn>
            <v-btn color="error" prepend-icon="mdi-file-pdf-box" @click="exportData('pdf')" :loading="exporting" variant="flat">PDF</v-btn>
          </v-col>
        </v-row>

        <!-- Period Chip -->
        <v-row class="mb-2">
          <v-col>
            <v-chip :color="themeColor" variant="tonal" label size="small" class="font-weight-bold mr-2">
              <v-icon start size="14">mdi-calendar-clock</v-icon>
              {{ periodDisplayLabel }}
            </v-chip>
            <v-chip v-if="selectedJenisGaji !== 'Semua'" color="blue-grey" variant="tonal" label size="small" class="font-weight-bold">
              {{ selectedJenisGaji }}
            </v-chip>
          </v-col>
        </v-row>

        <!-- Tab bar (hidden in PW-only mode) -->
        <v-tabs v-if="!isPwMode" v-model="activeTab" :color="themeColor" class="mb-4" @update:model-value="fetchData()">
          <v-tab v-for="tab in visibleTabs" :key="tab.type" :value="tab.type">
            <v-icon start :icon="tab.icon" size="18"></v-icon>
            {{ tab.label }}
            <v-chip v-if="summary[tab.type]" size="x-small" :color="tab.color" class="ml-2">
              {{ summary[tab.type].total_skpd }}
            </v-chip>
          </v-tab>
        </v-tabs>

        <!-- Grand total bar -->
        <v-card class="rounded-lg mb-4 pa-3" :color="isPwMode ? 'orange-lighten-5' : 'deep-purple-lighten-5'" elevation="0" v-if="meta">
          <v-row align="center" dense>
            <v-col cols="auto">
              <v-chip :color="currentTab.color" label size="small" class="font-weight-bold">{{ currentTab.label }}</v-chip>
            </v-col>
            <v-col>
              <span class="text-body-2 text-medium-emphasis">
                <strong>{{ meta.total_skpd }}</strong> SKPD &nbsp;·&nbsp;
                <strong>{{ formatNumber(meta.total_employees) }}</strong> Pegawai &nbsp;·&nbsp;
                Total Bersih: <strong :class="isPwMode ? 'text-orange-darken-4' : 'text-deep-purple'">{{ formatCurrency(meta.grand_total) }}</strong>
              </span>
            </v-col>
          </v-row>
        </v-card>

        <!-- Data table -->
        <v-card class="glass-card rounded-xl" :loading="loading" elevation="0">
          <v-data-table
            :headers="currentHeaders"
            :items="items"
            :loading="loading"
            class="bg-transparent detail-table"
            hover
            fixed-header
            height="calc(100vh - 420px)"
            items-per-page="50"
            density="compact"
          >
            <!-- Currency formatting -->
            <template v-for="col in currencyCols" :key="col" v-slot:[`item.${col}`]="{ item }">
              <span :class="highlightCol(col) ? 'font-weight-bold text-deep-purple' : ''">
                {{ formatCurrency(item[col]) }}
              </span>
            </template>

            <template v-slot:item.jumlah_pegawai="{ item }">
              <v-chip size="x-small" color="blue-grey" variant="tonal">{{ item.jumlah_pegawai }}</v-chip>
            </template>
            <template v-slot:item.employee_count="{ item }">
              <v-chip size="x-small" color="blue-grey" variant="tonal">{{ item.employee_count }}</v-chip>
            </template>
            <template v-slot:item.kode_skpd="{ item }">
              <span class="font-weight-medium text-caption text-grey-darken-1">{{ item.kode_skpd }}</span>
            </template>
            <template v-slot:item.sumber_dana="{ item }">
              <v-chip size="x-small" :color="item.sumber_dana === 'APBD' ? 'indigo' : 'teal'" variant="tonal" class="font-weight-bold">
                {{ item.sumber_dana }}
              </v-chip>
            </template>

            <template v-slot:no-data>
              <div class="py-8 text-center text-medium-emphasis">
                <v-icon icon="mdi-database-off-outline" size="48" class="mb-2"></v-icon>
                <div>Tidak ada data untuk periode ini.</div>
              </div>
            </template>

            <!-- Summary Footer Row -->
            <template v-slot:tfoot v-if="items.length > 0">
              <tr class="summary-footer-row">
                <td class="text-start font-weight-bold summary-cell" :colspan="mode === 'detail' ? 2 : 3">
                  <v-icon size="16" class="mr-1">mdi-sigma</v-icon>
                  TOTAL ({{ items.length }} SKPD)
                </td>
                <td v-if="mode === 'detail'" class="text-center summary-cell">
                  <v-chip size="x-small" color="deep-purple" variant="flat" class="font-weight-bold">
                    {{ formatNumber(columnTotal('jumlah_pegawai')) }}
                  </v-chip>
                </td>
                <td v-else class="text-center summary-cell">
                  <v-chip size="x-small" color="deep-purple" variant="flat" class="font-weight-bold">
                    {{ formatNumber(columnTotal('employee_count')) }}
                  </v-chip>
                </td>
                <template v-if="mode === 'detail'">
                  <td v-for="col in detailCurrencyCols" :key="col" class="text-end summary-cell font-weight-bold">
                    <span :class="col === 'bersih' ? 'text-deep-purple' : ''">{{ formatCurrency(columnTotal(col)) }}</span>
                  </td>
                </template>
                <template v-else>
                  <td v-for="col in summaryCurrencyCols" :key="col" class="text-end summary-cell font-weight-bold">
                    <span :class="col === 'total_bersih' ? 'text-deep-purple' : ''">{{ formatCurrency(columnTotal(col)) }}</span>
                  </td>
                </template>
              </tr>
            </template>
          </v-data-table>
        </v-card>
      </v-container>
    </v-main>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import api from '../api'
import Navbar from '../components/Navbar.vue'
import Sidebar from '../components/Sidebar.vue'

const props = defineProps({
  forcedType: { type: String, default: null } // 'pw' when accessed from PPPK-PW group
})

const isPwMode = computed(() => props.forcedType === 'pw')
const themeColor = computed(() => isPwMode.value ? 'orange-darken-2' : 'deep-purple')

const loading   = ref(false)
const exporting = ref(false)
const items     = ref([])
const meta      = ref(null)
const mode      = ref('summary')
const activeTab = ref(props.forcedType || 'all')
const menu      = ref(false)
const summary   = ref({})

const selectedYear     = ref(new Date().getFullYear())
const selectedJenisGaji = ref('Semua')
const selectedPaperSize = ref('a4')

// Period type
const periodType = ref('triwulan')
const selectedTriwulan = ref(getCurrentQuarter())
const selectedSemester = ref(getCurrentSemester())
const customMonthFrom  = ref(1)
const customMonthTo    = ref(new Date().getMonth() + 1)

function getCurrentQuarter() {
  const m = new Date().getMonth() + 1
  if (m <= 3) return 1
  if (m <= 6) return 2
  if (m <= 9) return 3
  return 4
}

function getCurrentSemester() {
  return new Date().getMonth() + 1 <= 6 ? 1 : 2
}

const triwulanOptions = [
  { title: 'Triwulan I (Jan - Mar)', value: 1 },
  { title: 'Triwulan II (Apr - Jun)', value: 2 },
  { title: 'Triwulan III (Jul - Sep)', value: 3 },
  { title: 'Triwulan IV (Okt - Des)', value: 4 },
]

const semesterOptions = [
  { title: 'Semester I (Jan - Jun)', value: 1 },
  { title: 'Semester II (Jul - Des)', value: 2 },
]

const months = [
  { title: 'Januari',   value: 1  }, { title: 'Februari',  value: 2  },
  { title: 'Maret',     value: 3  }, { title: 'April',     value: 4  },
  { title: 'Mei',       value: 5  }, { title: 'Juni',      value: 6  },
  { title: 'Juli',      value: 7  }, { title: 'Agustus',   value: 8  },
  { title: 'September', value: 9  }, { title: 'Oktober',   value: 10 },
  { title: 'November',  value: 11 }, { title: 'Desember',  value: 12 },
]

const years = [2023, 2024, 2025, 2026, 2027]
const jenisGajiOptions = ['Semua', 'Induk', 'THR', 'Gaji 13', 'Susulan', 'Kekurangan', 'Terusan']
const paperSizeOptions = [
  { title: 'A4 (Standar)',      value: 'a4' },
  { title: 'Legal / F4',       value: 'legal' },
  { title: 'A3 (Sangat Lebar)', value: 'a3' },
  { title: 'A2 (Ultra Lebar)',  value: 'a2' },
  { title: 'Letter',           value: 'letter' },
]

// Compute monthFrom / monthTo based on period type
const monthRange = computed(() => {
  if (periodType.value === 'triwulan') {
    const q = selectedTriwulan.value
    return { from: (q - 1) * 3 + 1, to: q * 3 }
  }
  if (periodType.value === 'semester') {
    const s = selectedSemester.value
    return { from: s === 1 ? 1 : 7, to: s === 1 ? 6 : 12 }
  }
  if (periodType.value === 'tahunan') {
    return { from: 1, to: 12 }
  }
  // custom
  return { from: customMonthFrom.value, to: customMonthTo.value }
})

const monthNameMap = {
  1: 'Januari', 2: 'Februari', 3: 'Maret', 4: 'April',
  5: 'Mei', 6: 'Juni', 7: 'Juli', 8: 'Agustus',
  9: 'September', 10: 'Oktober', 11: 'November', 12: 'Desember'
}

const periodDisplayLabel = computed(() => {
  const { from, to } = monthRange.value
  if (periodType.value === 'triwulan') {
    return `Triwulan ${['I','II','III','IV'][selectedTriwulan.value - 1]} ${selectedYear.value}`
  }
  if (periodType.value === 'semester') {
    return `Semester ${selectedSemester.value === 1 ? 'I' : 'II'} ${selectedYear.value}`
  }
  if (periodType.value === 'tahunan') {
    return `Tahunan ${selectedYear.value}`
  }
  return `${monthNameMap[from]} - ${monthNameMap[to]} ${selectedYear.value}`
})

// Tabs
const allTabs = [
  { type: 'all',  label: 'Gabungan',          icon: 'mdi-layers-triple',          color: 'deep-purple' },
  { type: 'pns',  label: 'PNS',               icon: 'mdi-account-tie-outline',    color: 'blue'        },
  { type: 'pppk', label: 'PPPK Penuh Waktu',  icon: 'mdi-account-check-outline',  color: 'green'       },
  { type: 'pw',   label: 'PPPK Paruh Waktu',  icon: 'mdi-account-clock-outline',  color: 'orange'      },
]
const visibleTabs = computed(() => {
  if (isPwMode.value) return allTabs.filter(t => t.type === 'pw')
  return allTabs.filter(t => t.type !== 'pw') // PW has its own dedicated page
})
const currentTab = computed(() => allTabs.find(t => t.type === activeTab.value) ?? allTabs[0])

// Headers
const summaryHeaders = [
  { title: 'Kode SKPD',      key: 'kode_skpd',        align: 'start',  width: 120, fixed: true },
  { title: 'Nama SKPD',      key: 'nama_skpd',        align: 'start', minWidth: 250, fixed: true },
  { title: 'Sumber Dana',    key: 'sumber_dana',       align: 'start',  width: 120 },
  { title: 'PEG',            key: 'employee_count',    align: 'center', width: 80  },
  { title: 'Gaji Pokok',     key: 'total_gaji_pokok',  align: 'end'   },
  { title: 'Tunjangan',      key: 'total_tunjangan',   align: 'end'   },
  { title: 'Potongan',       key: 'total_potongan',    align: 'end'   },
  { title: 'Bersih',         key: 'total_bersih',      align: 'end'   },
]

const detailHeaders = [
  { title: 'Kode SKPD',   key: 'kode_skpd',       align: 'start',  width: 120, fixed: true },
  { title: 'Nama SKPD',   key: 'nama_skpd',       align: 'start', minWidth: 250, fixed: true },
  { title: 'PEG',         key: 'jumlah_pegawai',   align: 'center', width: 60  },
  { title: 'GAPOK',       key: 'gapok',            align: 'end'   },
  { title: 'TJISTRI',     key: 'tj_istri',         align: 'end'   },
  { title: 'TJANAK',      key: 'tj_anak',          align: 'end'   },
  { title: 'TJTPP',       key: 'tj_tpp',           align: 'end'   },
  { title: 'TJESELON',    key: 'tj_eselon',        align: 'end'   },
  { title: 'TJFUNGSI',    key: 'tj_fungsi',        align: 'end'   },
  { title: 'TJBERAS',     key: 'tj_beras',         align: 'end'   },
  { title: 'TJPAJAK',     key: 'tj_pajak',         align: 'end'   },
  { title: 'TJUMUM',      key: 'tj_umum',          align: 'end'   },
  { title: 'TJKHUSUS',    key: 'tj_khusus',        align: 'end'   },
  { title: 'BULAT',       key: 'pembulatan',       align: 'end'   },
  { title: 'KOTOR',       key: 'kotor',            align: 'end'   },
  { title: 'PIWP',        key: 'pot_iwp',          align: 'end'   },
  { title: 'PIWP2',       key: 'pot_iwp2',         align: 'end'   },
  { title: 'PIWP8',       key: 'pot_iwp8',         align: 'end'   },
  { title: 'PPAJAK',      key: 'pot_pajak',        align: 'end'   },
  { title: 'POTONGAN',    key: 'total_potongan',   align: 'end'   },
  { title: 'BERSIH',      key: 'bersih',           align: 'end'   },
]

const currentHeaders = computed(() => mode.value === 'detail' ? detailHeaders : summaryHeaders)

const detailCurrencyCols = ['gapok','tj_istri','tj_anak','tj_tpp','tj_eselon','tj_fungsi',
            'tj_beras','tj_pajak','tj_umum','tj_khusus','pembulatan','kotor',
            'pot_iwp','pot_iwp2','pot_iwp8','pot_pajak','total_potongan','bersih']
const summaryCurrencyCols = ['total_gaji_pokok','total_tunjangan','total_potongan','total_bersih']

const currencyCols = computed(() => {
  if (mode.value === 'detail') return detailCurrencyCols
  return summaryCurrencyCols
})

const highlightCol = (col) => col === 'bersih' || col === 'total_bersih'

const columnTotal = (col) => {
  return items.value.reduce((sum, row) => sum + (parseFloat(row[col]) || 0), 0)
}

// Fetch data
const fetchData = async () => {
  loading.value = true
  try {
    const { from, to } = monthRange.value
    const res = await api.get('/reports/periodic-skpds', {
      params: {
        month_from: from,
        month_to: to,
        year: selectedYear.value,
        type: activeTab.value,
        jenis_gaji: selectedJenisGaji.value
      }
    })
    items.value = res.data.data
    meta.value  = res.data.meta
    mode.value  = res.data.mode ?? 'summary'
    summary.value[activeTab.value] = res.data.meta
  } catch (e) {
    console.error('Error fetching periodic data:', e)
  } finally {
    loading.value = false
  }
}

const preloadSummaries = async () => {
  if (isPwMode.value) return // no need to preload other tabs
  for (const tab of visibleTabs.value) {
    if (summary.value[tab.type]) continue
    try {
      const { from, to } = monthRange.value
      const res = await api.get('/reports/periodic-skpds', {
        params: {
          month_from: from,
          month_to: to,
          year: selectedYear.value,
          type: tab.type,
          jenis_gaji: selectedJenisGaji.value
        }
      })
      summary.value[tab.type] = res.data.meta
    } catch (e) { /* silent */ }
  }
}

const exportData = async (format) => {
  exporting.value = true
  try {
    const { from, to } = monthRange.value
    const res = await api.get('/reports/periodic-export', {
      params: {
        month_from: from,
        month_to: to,
        year: selectedYear.value,
        format,
        type: activeTab.value,
        jenis_gaji: selectedJenisGaji.value,
        paper_size: selectedPaperSize.value
      },
      responseType: 'blob'
    })
    const url  = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href  = url
    const ext  = format === 'pdf' ? 'pdf' : 'xlsx'
    link.setAttribute('download', `laporan_periodik_${activeTab.value}_${from}-${to}_${selectedYear.value}.${ext}`)
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
  preloadSummaries()
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
  background-color: rgb(var(--v-theme-surface)) !important;
  z-index: 2 !important;
}
:deep(.v-data-table__td) {
  white-space: nowrap;
  font-size: 0.78rem;
  background-color: rgb(var(--v-theme-surface)) !important;
}

/* Summary Footer */
.summary-footer-row {
  position: sticky;
  bottom: 0;
  z-index: 3;
}
.summary-cell {
  background-color: rgba(103, 58, 183, 0.08) !important;
  border-top: 2px solid rgba(103, 58, 183, 0.3) !important;
  white-space: nowrap;
  font-size: 0.78rem;
  padding: 8px 16px !important;
}
:deep(.v-data-table__tr:hover .v-data-table__td) { background-color: rgba(103, 58, 183, 0.05) !important; }
</style>
