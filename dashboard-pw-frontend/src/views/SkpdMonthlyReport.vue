<template>
  <v-app class="modern-dashboard">
    <Navbar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />
    <Sidebar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />

    <v-main class="bg-light">
      <v-container fluid class="pa-8">
        <!-- Header -->
        <v-row class="mb-6 align-center">
          <v-col cols="12" md="6">
            <h1 class="text-h4 font-weight-bold mb-1">
              <v-icon start color="teal" size="36">mdi-file-table</v-icon>
              Laporan Bulanan per SKPD
            </h1>
            <p class="text-subtitle-1 text-grey-darken-1">Rekapitulasi gaji per SKPD berdasarkan jenis kepegawaian.</p>
          </v-col>
          <v-col cols="12" md="6" class="d-flex justify-end align-center ga-2 flex-wrap">
            <v-menu v-model="menu" :close-on-content-click="false">
              <template v-slot:activator="{ props }">
                <v-btn color="teal" variant="tonal" v-bind="props" prepend-icon="mdi-calendar" size="large">
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
                  <v-col cols="12" class="mt-2 text-right">
                    <v-btn block color="teal" @click="fetchData(); menu = false">TERAPKAN</v-btn>
                  </v-col>
                </v-row>
              </v-card>
            </v-menu>
            <v-btn color="success" prepend-icon="mdi-file-excel" @click="exportData('excel')" :loading="exporting" variant="flat">Excel</v-btn>
            <v-btn color="error"   prepend-icon="mdi-file-pdf-box" @click="exportData('pdf')" :loading="exporting" variant="flat">PDF</v-btn>
          </v-col>
        </v-row>

        <!-- Tab bar -->
        <v-tabs v-model="activeTab" color="teal" class="mb-4" @update:model-value="fetchData()">
          <v-tab v-for="tab in tabs" :key="tab.type" :value="tab.type">
            <v-icon start :icon="tab.icon" size="18"></v-icon>
            {{ tab.label }}
            <v-chip v-if="summary[tab.type]" size="x-small" :color="tab.color" class="ml-2">
              {{ summary[tab.type].total_skpd }}
            </v-chip>
          </v-tab>
        </v-tabs>

        <!-- Grand total bar -->
        <v-card class="rounded-lg mb-4 pa-3" color="teal-lighten-5" elevation="0" v-if="meta">
          <v-row align="center" dense>
            <v-col cols="auto">
              <v-chip :color="currentTab.color" label size="small" class="font-weight-bold">{{ currentTab.label }}</v-chip>
            </v-col>
            <v-col>
              <span class="text-body-2 text-medium-emphasis">
                <strong>{{ meta.total_skpd }}</strong> SKPD &nbsp;·&nbsp;
                <strong>{{ formatNumber(meta.total_employees) }}</strong> Pegawai &nbsp;·&nbsp;
                Total Bersih: <strong class="text-teal">{{ formatCurrency(meta.grand_total) }}</strong>
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
            items-per-page="25"
            density="compact"
          >
            <!-- Currency formatting for detail mode -->
            <template v-for="col in currencyCols" :key="col" v-slot:[`item.${col}`]="{ item }">
              <span :class="col === 'bersih' || col === 'total_bersih' ? 'font-weight-bold text-teal' : ''">
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
  </v-app>
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
const mode      = ref('summary')   // 'summary' | 'detail'
const activeTab = ref('all')
const selectedMonth = ref(new Date().getMonth() + 1)
const selectedYear  = ref(new Date().getFullYear())
const menu          = ref(false)
const summary       = ref({})

const tabs = [
  { type: 'all',  label: 'Gabungan',          icon: 'mdi-layers-triple',          color: 'teal'   },
  { type: 'pns',  label: 'PNS',               icon: 'mdi-account-tie-outline',    color: 'blue'   },
  { type: 'pppk', label: 'PPPK Penuh Waktu',  icon: 'mdi-account-check-outline',  color: 'green'  },
  { type: 'pw',   label: 'PPPK Paruh Waktu',  icon: 'mdi-account-clock-outline',  color: 'orange' },
]

const currentTab = computed(() => tabs.find(t => t.type === activeTab.value) ?? tabs[0])

// ── Headers ────────────────────────────────────────────────────────────────
const summaryHeaders = [
  { title: 'Kode SKPD',      key: 'kode_skpd',        align: 'start',  width: 140 },
  { title: 'Nama SKPD',      key: 'nama_skpd',         align: 'start'  },
  { title: 'PEG',            key: 'employee_count',    align: 'center', width: 80  },
  { title: 'Gaji Pokok',     key: 'total_gaji_pokok',  align: 'end'   },
  { title: 'Tunjangan',      key: 'total_tunjangan',   align: 'end'   },
  { title: 'Potongan',       key: 'total_potongan',    align: 'end'   },
  { title: 'Bersih',         key: 'total_bersih',      align: 'end'   },
]

const detailHeaders = [
  { title: 'Kode SKPD',   key: 'kode_skpd',       align: 'start',  width: 120 },
  { title: 'Nama SKPD',   key: 'nama_skpd',        align: 'start'  },
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
  { title: 'TBILAT',      key: 'tj_bilat',         align: 'end'   },
  { title: 'KOTOR',       key: 'kotor',            align: 'end'   },
  { title: 'PIWP',        key: 'pot_iwp',          align: 'end'   },
  { title: 'PIWP2',       key: 'pot_iwp2',         align: 'end'   },
  { title: 'PIWP8',       key: 'pot_iwp8',         align: 'end'   },
  { title: 'PPAJAK',      key: 'pot_pajak',        align: 'end'   },
  { title: 'POTONGAN',    key: 'total_potongan',   align: 'end'   },
  { title: 'BERSIH',      key: 'bersih',           align: 'end'   },
]

const currentHeaders = computed(() => mode.value === 'detail' ? detailHeaders : summaryHeaders)

// columns that need currency formatting
const currencyCols = computed(() => {
  if (mode.value === 'detail') {
    return ['gapok','tj_istri','tj_anak','tj_tpp','tj_eselon','tj_fungsi',
            'tj_beras','tj_pajak','tj_umum','tj_bilat','kotor',
            'pot_iwp','pot_iwp2','pot_iwp8','pot_pajak','total_potongan','bersih']
  }
  return ['total_gaji_pokok','total_tunjangan','total_potongan','total_bersih']
})

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
      params: { month: selectedMonth.value, year: selectedYear.value, type: activeTab.value }
    })
    items.value = res.data.data
    meta.value  = res.data.meta
    mode.value  = res.data.mode ?? 'summary'
    summary.value[activeTab.value] = res.data.meta
  } catch (e) {
    console.error('Error fetching data:', e)
  } finally {
    loading.value = false
  }
}

const preloadSummaries = async () => {
  for (const tab of tabs) {
    if (summary.value[tab.type]) continue
    try {
      const res = await api.get('/reports/paid-skpds', {
        params: { month: selectedMonth.value, year: selectedYear.value, type: tab.type }
      })
      summary.value[tab.type] = res.data.meta
    } catch (e) { /* silent */ }
  }
}

const exportData = async (format) => {
  exporting.value = true
  try {
    const res = await api.get('/reports/paid-export', {
      params: { month: selectedMonth.value, year: selectedYear.value, format, type: activeTab.value },
      responseType: 'blob'
    })
    const url  = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href  = url
    const ext  = format === 'pdf' ? 'pdf' : 'xlsx'
    link.setAttribute('download', `laporan_skpd_${activeTab.value}_${selectedMonth.value}_${selectedYear.value}.${ext}`)
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
}
:deep(.v-data-table__td) { white-space: nowrap; font-size: 0.78rem; }
:deep(.v-data-table__tr:hover) { background-color: rgba(0, 150, 136, 0.05) !important; }
</style>
