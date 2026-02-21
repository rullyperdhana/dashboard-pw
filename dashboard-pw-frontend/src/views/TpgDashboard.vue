<template>
  <v-app class="modern-dashboard">
    <Navbar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />
    <Sidebar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />

    <v-main class="bg-light">
      <v-container fluid class="pa-8">
        <!-- Header -->
        <v-row class="mb-2 align-center">
          <v-col>
            <h1 class="text-h4 font-weight-bold mb-1">
              <v-icon start color="deep-purple" size="36">mdi-school-outline</v-icon>
              Dashboard TPG
            </h1>
            <p class="text-subtitle-1 text-grey-darken-1">Monitoring Tunjangan Profesi Guru per Triwulan</p>
          </v-col>
          <v-col cols="auto" class="d-flex align-center ga-3">
            <v-select
              v-model="selectedYear"
              :items="availableYears.length ? availableYears : defaultYears"
              label="Tahun"
              variant="outlined"
              density="compact"
              color="deep-purple"
              hide-details
              style="min-width: 120px"
              @update:model-value="fetchDashboard"
            ></v-select>
            <v-btn
              color="deep-purple"
              variant="tonal"
              prepend-icon="mdi-upload-outline"
              @click="$router.push('/tpg-upload')"
            >
              Upload TPG
            </v-btn>
          </v-col>
        </v-row>

        <!-- Loading -->
        <v-row v-if="loading" class="justify-center py-12">
          <v-progress-circular indeterminate color="deep-purple" size="48"></v-progress-circular>
        </v-row>

        <template v-else>
          <!-- Empty State -->
          <v-row v-if="!hasData" class="justify-center py-12">
            <v-col cols="12" md="6" class="text-center">
              <v-icon size="80" color="grey-lighten-1" class="mb-4">mdi-database-off-outline</v-icon>
              <h3 class="text-h6 text-grey-darken-1 mb-2">Belum Ada Data TPG</h3>
              <p class="text-body-2 text-grey mb-4">Upload file Excel TPG untuk mulai monitoring.</p>
              <v-btn color="deep-purple" variant="elevated" prepend-icon="mdi-upload-outline" @click="$router.push('/tpg-upload')">
                Upload Data TPG
              </v-btn>
            </v-col>
          </v-row>

          <template v-if="hasData">
            <!-- Yearly Summary Cards -->
            <v-row class="mb-6">
              <v-col cols="12" sm="6" lg="3">
                <v-card class="glass-card rounded-xl pa-5 text-center stat-card" elevation="0">
                  <v-icon size="32" color="deep-purple" class="mb-2">mdi-account-group-outline</v-icon>
                  <div class="text-h5 font-weight-bold text-deep-purple">{{ formatNumber(yearlyTotals.total_guru) }}</div>
                  <div class="text-caption text-grey-darken-1 mt-1">Total Guru Penerima</div>
                </v-card>
              </v-col>
              <v-col cols="12" sm="6" lg="3">
                <v-card class="glass-card rounded-xl pa-5 text-center stat-card" elevation="0">
                  <v-icon size="32" color="blue" class="mb-2">mdi-cash-multiple</v-icon>
                  <div class="text-h5 font-weight-bold text-blue">{{ formatCurrency(yearlyTotals.total_brut) }}</div>
                  <div class="text-caption text-grey-darken-1 mt-1">Total Salur Bruto</div>
                </v-card>
              </v-col>
              <v-col cols="12" sm="6" lg="2">
                <v-card class="glass-card rounded-xl pa-5 text-center stat-card" elevation="0">
                  <v-icon size="32" color="red-darken-1" class="mb-2">mdi-percent-outline</v-icon>
                  <div class="text-h5 font-weight-bold text-red-darken-1">{{ formatCurrency(yearlyTotals.total_pph) }}</div>
                  <div class="text-caption text-grey-darken-1 mt-1">Total PPH</div>
                </v-card>
              </v-col>
              <v-col cols="12" sm="6" lg="2">
                <v-card class="glass-card rounded-xl pa-5 text-center stat-card" elevation="0">
                  <v-icon size="32" color="orange-darken-1" class="mb-2">mdi-minus-circle-outline</v-icon>
                  <div class="text-h5 font-weight-bold text-orange-darken-1">{{ formatCurrency(yearlyTotals.total_pot_jkn) }}</div>
                  <div class="text-caption text-grey-darken-1 mt-1">Total Pot. JKN</div>
                </v-card>
              </v-col>
              <v-col cols="12" sm="6" lg="2">
                <v-card class="glass-card rounded-xl pa-5 text-center stat-card" elevation="0">
                  <v-icon size="32" color="green-darken-1" class="mb-2">mdi-wallet-outline</v-icon>
                  <div class="text-h5 font-weight-bold text-green-darken-1">{{ formatCurrency(yearlyTotals.total_nett) }}</div>
                  <div class="text-caption text-grey-darken-1 mt-1">Total Salur Netto</div>
                </v-card>
              </v-col>
            </v-row>

            <!-- Triwulan Summary Chart + Per-TW Cards -->
            <v-row class="mb-6">
              <!-- Bar Chart Triwulan -->
              <v-col cols="12" md="7">
                <v-card class="glass-card rounded-xl pa-5" elevation="0">
                  <div class="d-flex justify-space-between align-center mb-4">
                    <h3 class="text-subtitle-1 font-weight-bold">Penyaluran TPG per Triwulan</h3>
                  </div>
                  <apexchart
                    type="bar"
                    height="300"
                    :options="triwulanChartOptions"
                    :series="triwulanChartSeries"
                  ></apexchart>
                </v-card>
              </v-col>

              <!-- Triwulan Cards -->
              <v-col cols="12" md="5">
                <v-row>
                  <v-col v-for="tw in [1,2,3,4]" :key="tw" cols="6">
                    <v-card
                      class="rounded-xl pa-4 text-center tw-card"
                      :class="getTriwulanData(tw) ? 'glass-card' : 'bg-grey-lighten-4'"
                      elevation="0"
                      :style="getTriwulanData(tw) ? 'border-left: 4px solid ' + twColors[tw-1] : 'opacity: 0.6'"
                    >
                      <div class="text-overline font-weight-bold" :style="{ color: twColors[tw-1] }">TW {{ tw }}</div>
                      <template v-if="getTriwulanData(tw)">
                        <div class="text-h6 font-weight-bold mt-1">{{ formatNumber(getTriwulanData(tw).total_penerima) }}</div>
                        <div class="text-caption text-grey">penerima</div>
                        <div class="text-body-2 font-weight-bold mt-1 text-green-darken-1">
                          {{ formatCurrencyShort(getTriwulanData(tw).total_nett) }}
                        </div>
                        <div class="text-caption text-grey">netto</div>
                      </template>
                      <template v-else>
                        <div class="text-body-2 text-grey mt-2">Belum ada data</div>
                      </template>
                    </v-card>
                  </v-col>
                </v-row>
              </v-col>
            </v-row>

            <!-- Per-SATDIK Breakdown + Filter -->
            <v-row class="mb-6">
              <v-col cols="12">
                <v-card class="glass-card rounded-xl pa-5" elevation="0">
                  <div class="d-flex justify-space-between align-center mb-4">
                    <h3 class="text-subtitle-1 font-weight-bold">
                      <v-icon start size="20" color="deep-purple">mdi-office-building-outline</v-icon>
                      Breakdown per SATDIK
                    </h3>
                    <div class="d-flex align-center ga-3">
                      <v-btn-toggle
                        v-model="selectedTw"
                        mandatory
                        density="compact"
                        color="deep-purple"
                        variant="outlined"
                        divided
                        @update:model-value="fetchDashboard"
                      >
                        <v-btn value="all" size="small">Semua</v-btn>
                        <v-btn :value="1" size="small">TW1</v-btn>
                        <v-btn :value="2" size="small">TW2</v-btn>
                        <v-btn :value="3" size="small">TW3</v-btn>
                        <v-btn :value="4" size="small">TW4</v-btn>
                      </v-btn-toggle>
                      <v-btn
                        color="green"
                        variant="tonal"
                        size="small"
                        prepend-icon="mdi-microsoft-excel"
                        @click="exportData"
                        :loading="exporting"
                      >
                        Export
                      </v-btn>
                    </div>
                  </div>
                  <v-data-table
                    :headers="satdikHeaders"
                    :items="satdikBreakdown"
                    :items-per-page="25"
                    density="comfortable"
                    class="rounded-lg"
                    hover
                  >
                    <template v-slot:item.satdik="{ item }">
                      <a
                        href="#"
                        class="text-deep-purple font-weight-medium text-decoration-none satdik-link"
                        @click.prevent="filterBySatdik(item.satdik)"
                      >
                        {{ item.satdik }}
                        <v-icon size="14" class="ml-1">mdi-filter-outline</v-icon>
                      </a>
                    </template>
                    <template v-slot:item.jumlah_guru="{ item }">
                      <v-chip size="small" color="deep-purple" variant="tonal">{{ item.jumlah_guru }}</v-chip>
                    </template>
                    <template v-slot:item.total_brut="{ item }">
                      {{ formatCurrency(item.total_brut) }}
                    </template>
                    <template v-slot:item.total_pph="{ item }">
                      <span class="text-red-darken-1">{{ formatCurrency(item.total_pph) }}</span>
                    </template>
                    <template v-slot:item.total_pot_jkn="{ item }">
                      <span class="text-orange-darken-1">{{ formatCurrency(item.total_pot_jkn) }}</span>
                    </template>
                    <template v-slot:item.total_nett="{ item }">
                      <span class="font-weight-bold text-green-darken-1">{{ formatCurrency(item.total_nett) }}</span>
                    </template>
                    <template v-slot:tfoot>
                      <tr>
                        <td :colspan="satdikHeaders.length">
                          <div class="d-flex justify-end pa-3 font-weight-bold text-body-2">
                            <span class="mr-6">Total: {{ satdikBreakdown.length }} SATDIK</span>
                            <span class="mr-6 text-blue">Bruto: {{ formatCurrency(satdikTotalBrut) }}</span>
                            <span class="mr-6 text-red-darken-1">PPH: {{ formatCurrency(satdikTotalPph) }}</span>
                            <span class="mr-6 text-orange-darken-1">Pot. JKN: {{ formatCurrency(satdikTotalPotJkn) }}</span>
                            <span class="text-green-darken-1">Netto: {{ formatCurrency(satdikTotalNett) }}</span>
                          </div>
                        </td>
                      </tr>
                    </template>
                  </v-data-table>
                </v-card>
              </v-col>
            </v-row>

            <!-- Individual Data Table -->
            <v-row id="data-penerima-tpg">
              <v-col cols="12">
                <v-card class="glass-card rounded-xl pa-5" elevation="0">
                  <div class="d-flex justify-space-between align-center mb-4">
                    <div class="d-flex align-center ga-2">
                      <h3 class="text-subtitle-1 font-weight-bold">
                        <v-icon start size="20" color="deep-purple">mdi-format-list-bulleted</v-icon>
                        Data Penerima TPG
                      </h3>
                      <v-chip
                        v-if="selectedSatdik"
                        color="deep-purple"
                        variant="tonal"
                        closable
                        size="small"
                        @click:close="clearSatdikFilter"
                      >
                        <v-icon start size="14">mdi-filter</v-icon>
                        {{ selectedSatdik }}
                      </v-chip>
                    </div>
                    <v-text-field
                      v-model="dataSearch"
                      prepend-inner-icon="mdi-magnify"
                      label="Cari NIP, Nama, SATDIK..."
                      variant="outlined"
                      density="compact"
                      hide-details
                      color="deep-purple"
                      style="max-width: 300px"
                      clearable
                      @update:model-value="debouncedFetchData"
                    ></v-text-field>
                  </div>
                  <v-data-table-server
                    :headers="dataHeaders"
                    :items="dataItems"
                    :items-length="dataTotalItems"
                    :items-per-page="dataPerPage"
                    :page="dataPage"
                    :loading="dataLoading"
                    @update:page="dataPage = $event; fetchData()"
                    @update:items-per-page="dataPerPage = $event; dataPage = 1; fetchData()"
                    density="comfortable"
                    class="rounded-lg"
                    hover
                  >
                    <template v-slot:item.no="{ index }">
                      {{ (dataPage - 1) * dataPerPage + index + 1 }}
                    </template>
                    <template v-slot:item.triwulan="{ item }">
                      <v-chip size="x-small" :color="twColors[item.triwulan - 1]" variant="tonal">
                        TW {{ item.triwulan }}
                      </v-chip>
                    </template>
                    <template v-slot:item.jenis="{ item }">
                      <v-chip size="x-small" :color="item.jenis === 'INDUK' ? 'blue' : 'orange'" variant="tonal">
                        {{ item.jenis }}
                      </v-chip>
                    </template>
                    <template v-slot:item.salur_brut="{ item }">
                      {{ formatCurrency(item.salur_brut) }}
                    </template>
                    <template v-slot:item.pph="{ item }">
                      <span class="text-red-darken-1">{{ formatCurrency(item.pph) }}</span>
                    </template>
                    <template v-slot:item.pot_jkn="{ item }">
                      <span class="text-orange-darken-1">{{ formatCurrency(item.pot_jkn) }}</span>
                    </template>
                    <template v-slot:item.salur_nett="{ item }">
                      <span class="font-weight-bold text-green-darken-1">{{ formatCurrency(item.salur_nett) }}</span>
                    </template>
                  </v-data-table-server>
                </v-card>
              </v-col>
            </v-row>
          </template>
        </template>

      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../api'
import Navbar from '../components/Navbar.vue'
import Sidebar from '../components/Sidebar.vue'

// --- State ---
const loading = ref(true)
const exporting = ref(false)

const selectedYear = ref(new Date().getFullYear())
const selectedTw = ref('all')
const defaultYears = [2024, 2025, 2026, 2027]
const availableYears = ref([])

const yearlyTotals = ref({ total_guru: 0, total_brut: 0, total_pph: 0, total_pot_jkn: 0, total_nett: 0 })
const triwulanSummary = ref([])
const satdikBreakdown = ref([])

const twColors = ['#7C4DFF', '#2979FF', '#00BFA5', '#FF6D00']

// Data table
const dataSearch = ref('')
const selectedSatdik = ref('')
const dataItems = ref([])
const dataTotalItems = ref(0)
const dataPage = ref(1)
const dataPerPage = ref(15)
const dataLoading = ref(false)

let debounceTimer = null

// --- Computed ---
const hasData = computed(() => triwulanSummary.value.length > 0)

const satdikTotalBrut = computed(() => satdikBreakdown.value.reduce((sum, s) => sum + parseFloat(s.total_brut || 0), 0))
const satdikTotalPph = computed(() => satdikBreakdown.value.reduce((sum, s) => sum + parseFloat(s.total_pph || 0), 0))
const satdikTotalPotJkn = computed(() => satdikBreakdown.value.reduce((sum, s) => sum + parseFloat(s.total_pot_jkn || 0), 0))
const satdikTotalNett = computed(() => satdikBreakdown.value.reduce((sum, s) => sum + parseFloat(s.total_nett || 0), 0))

// Chart
const triwulanChartOptions = computed(() => ({
  chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
  plotOptions: { bar: { borderRadius: 6, columnWidth: '50%' } },
  colors: twColors,
  xaxis: {
    categories: ['TW 1', 'TW 2', 'TW 3', 'TW 4'],
  },
  yaxis: {
    labels: {
      formatter: v => formatCurrencyShort(v)
    }
  },
  dataLabels: { enabled: false },
  tooltip: {
    y: { formatter: v => formatCurrency(v) }
  },
  grid: { borderColor: '#f0f0f0' },
  legend: { position: 'top' },
}))

const triwulanChartSeries = computed(() => {
  const brut = [0, 0, 0, 0]
  const nett = [0, 0, 0, 0]
  triwulanSummary.value.forEach(tw => {
    brut[tw.triwulan - 1] = parseFloat(tw.total_brut || 0)
    nett[tw.triwulan - 1] = parseFloat(tw.total_nett || 0)
  })
  return [
    { name: 'Salur Bruto', data: brut },
    { name: 'Salur Netto', data: nett },
  ]
})

// Table headers
const satdikHeaders = [
  { title: 'SATDIK', key: 'satdik', sortable: true },
  { title: 'Jumlah Guru', key: 'jumlah_guru', align: 'center', sortable: true },
  { title: 'Salur Bruto', key: 'total_brut', align: 'end', sortable: true },
  { title: 'PPH', key: 'total_pph', align: 'end', sortable: true },
  { title: 'Pot. JKN', key: 'total_pot_jkn', align: 'end', sortable: true },
  { title: 'Salur Netto', key: 'total_nett', align: 'end', sortable: true },
]

const dataHeaders = [
  { title: 'No', key: 'no', sortable: false, width: 60 },
  { title: 'NIP', key: 'nip', sortable: false },
  { title: 'Nama', key: 'nama', sortable: false },
  { title: 'SATDIK', key: 'satdik', sortable: false },
  { title: 'TW', key: 'triwulan', sortable: false, align: 'center' },
  { title: 'Jenis', key: 'jenis', sortable: false, align: 'center' },
  { title: 'Salur Bruto', key: 'salur_brut', sortable: false, align: 'end' },
  { title: 'PPH', key: 'pph', sortable: false, align: 'end' },
  { title: 'Pot. JKN', key: 'pot_jkn', sortable: false, align: 'end' },
  { title: 'Salur Netto', key: 'salur_nett', sortable: false, align: 'end' },
]

// --- Methods ---
const getTriwulanData = (tw) => triwulanSummary.value.find(t => t.triwulan === tw)

const fetchDashboard = async () => {
  loading.value = true
  try {
    const params = { tahun: selectedYear.value }
    if (selectedTw.value !== 'all') {
      params.triwulan = selectedTw.value
    }
    const { data } = await api.get('/tpg/dashboard', { params })

    yearlyTotals.value = data.data.yearly_totals || { total_guru: 0, total_brut: 0, total_pph: 0, total_pot_jkn: 0, total_nett: 0 }
    triwulanSummary.value = data.data.triwulan_summary || []
    satdikBreakdown.value = data.data.satdik_breakdown || []

    if (data.data.available_years?.length) {
      availableYears.value = data.data.available_years
    }
  } catch (e) {
    console.error('Failed to fetch TPG dashboard:', e)
  } finally {
    loading.value = false
  }

  fetchData()
}

const fetchData = async () => {
  dataLoading.value = true
  try {
    const params = {
      tahun: selectedYear.value,
      per_page: dataPerPage.value,
      page: dataPage.value,
    }
    if (selectedTw.value !== 'all') {
      params.triwulan = selectedTw.value
    }
    if (selectedSatdik.value) {
      params.satdik = selectedSatdik.value
    }
    if (dataSearch.value) {
      params.search = dataSearch.value
    }
    const { data } = await api.get('/tpg/data', { params })
    dataItems.value = data.data.data || []
    dataTotalItems.value = data.data.total || 0
  } catch (e) {
    console.error('Failed to fetch TPG data:', e)
  } finally {
    dataLoading.value = false
  }
}

const filterBySatdik = (satdikName) => {
  selectedSatdik.value = satdikName
  dataSearch.value = ''
  dataPage.value = 1
  fetchData()
  // Scroll to data table
  const el = document.getElementById('data-penerima-tpg')
  if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

const clearSatdikFilter = () => {
  selectedSatdik.value = ''
  dataPage.value = 1
  fetchData()
}

const debouncedFetchData = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    dataPage.value = 1
    fetchData()
  }, 400)
}

const exportData = async () => {
  exporting.value = true
  try {
    const params = { tahun: selectedYear.value }
    if (selectedTw.value !== 'all') {
      params.triwulan = selectedTw.value
    }
    const response = await api.get('/tpg/export', {
      params,
      responseType: 'blob'
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `data_tpg_${selectedYear.value}${selectedTw.value !== 'all' ? '_tw' + selectedTw.value : ''}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    console.error('Export failed:', e)
  } finally {
    exporting.value = false
  }
}

const formatCurrency = (val) => {
  const n = parseFloat(val) || 0
  return 'Rp ' + n.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 })
}

const formatCurrencyShort = (val) => {
  const n = parseFloat(val) || 0
  if (n >= 1e12) return 'Rp ' + (n / 1e12).toFixed(1) + ' T'
  if (n >= 1e9) return 'Rp ' + (n / 1e9).toFixed(1) + ' M'
  if (n >= 1e6) return 'Rp ' + (n / 1e6).toFixed(1) + ' Jt'
  if (n >= 1e3) return 'Rp ' + (n / 1e3).toFixed(0) + ' Rb'
  return 'Rp ' + n.toFixed(0)
}

const formatNumber = (val) => {
  return (parseInt(val) || 0).toLocaleString('id-ID')
}

// --- Lifecycle ---
onMounted(() => {
  fetchDashboard()
})
</script>

<style scoped>
.modern-dashboard {
  background-color: rgb(var(--v-theme-background)) !important;
  font-family: 'Inter', sans-serif;
}
.bg-light {
  background-color: rgb(var(--v-theme-background)) !important;
}
.glass-card {
  background-color: rgb(var(--v-theme-surface)) !important;
  border: 1px solid rgba(var(--v-border-color), 0.08) !important;
  box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07) !important;
}
.stat-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 40px rgba(31, 38, 135, 0.12) !important;
}
.tw-card {
  transition: transform 0.15s ease;
}
.tw-card:hover {
  transform: scale(1.03);
}
.satdik-link {
  cursor: pointer;
}
.satdik-link:hover {
  text-decoration: underline !important;
}
</style>
