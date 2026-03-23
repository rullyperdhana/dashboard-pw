<template>
  <div class="modern-dashboard">
    <Navbar @show-coming-soon="showComingSoon" />
    <Sidebar @show-coming-soon="showComingSoon" />
    
    <v-main class="bg-light">
      <v-container fluid class="pa-8">
        <!-- ═══════════════════════════════════════════ -->
        <!-- HEADER: SUBMISSION PROGRESS GAUGE          -->
        <!-- ═══════════════════════════════════════════ -->
        <div class="d-flex align-center mb-8">
          <div class="d-flex align-center">
            <v-progress-circular
              :model-value="submissionProgress"
              :rotate="360"
              :size="80"
              :width="8"
              color="primary"
              class="mr-4"
            >
              <template v-slot:default>
                <div class="text-caption font-weight-black">{{ submissionProgress }}%</div>
              </template>
            </v-progress-circular>
            <div>
              <h1 class="text-h4 font-weight-bold">Dashboard PPPK-PW</h1>
              <div class="text-body-2 text-medium-emphasis d-flex align-center">
                <v-icon size="16" color="success" class="mr-1">mdi-check-circle-outline</v-icon>
                {{ submissionProgress }}% SKPD Sudah Setor ({{ unpaidMonthName }} {{ unpaidYear }})
              </div>
            </div>
          </div>
          <v-spacer></v-spacer>
          <div class="d-none d-md-flex align-center">
            <v-btn variant="tonal" color="primary" rounded="pill" class="mr-3" @click="fetchAllData" :loading="loading">
              <v-icon start>mdi-refresh</v-icon> Refresh
            </v-btn>
            <v-btn variant="tonal" color="primary" rounded="pill">
              <v-icon start>mdi-calendar</v-icon> {{ currentYear }}
            </v-btn>
          </div>
        </div>
        
        <v-alert v-if="error" type="error" variant="tonal" closable class="mb-6 rounded-lg">
          {{ error }}
        </v-alert>
        
        <!-- ═══════════════════════════════════════════ -->
        <!-- SECTION 1: CONSOLIDATED KPI CARDS          -->
        <!-- ═══════════════════════════════════════════ -->
        <v-row v-if="!loading" class="mb-8">
          <!-- Total Anggaran (Main KPI) -->
          <v-col cols="12" md="4">
            <v-card class="glass-card rounded-xl pa-5 stat-card-premium blue-glow shadow-premium" elevation="0">
              <div class="d-flex align-center mb-4">
                <v-avatar color="blue-lighten-5" rounded="lg" size="48">
                  <v-icon color="blue">mdi-currency-usd</v-icon>
                </v-avatar>
                <v-spacer></v-spacer>
                <v-chip color="blue" size="x-small" variant="flat" class="font-weight-bold">ANGGARAN TAHUNAN</v-chip>
              </div>
              <div class="text-h4 font-weight-black mb-1">{{ reportData ? formatCurrencyCompact(reportData.summary.annual_budget) : formatCurrencyCompact(stats.monthly_payment * 12) }}</div>
              <div class="d-flex align-center text-caption text-success font-weight-bold">
                <v-icon size="14" class="mr-1">mdi-trending-up</v-icon> +2.4% vs Tahun Lalu
              </div>
            </v-card>
          </v-col>

          <!-- Total Pegawai Info -->
          <v-col cols="12" sm="6" md="2">
            <v-card class="glass-card rounded-xl pa-5 stat-card shadow-premium" elevation="0" to="/employees">
              <div class="text-overline text-grey-darken-1 mb-1">Personnel</div>
              <div class="text-h5 font-weight-bold mb-1">{{ stats.total_employees?.toLocaleString() || 0 }}</div>
              <div class="text-caption text-medium-emphasis">Pegawai PW</div>
              <v-sparkline :model-value="sparklineData.employees" color="blue" height="30" padding="4" smooth line-width="2" class="mt-2"></v-sparkline>
            </v-card>
          </v-col>

          <!-- Units Info -->
          <v-col cols="12" sm="6" md="2">
            <v-card class="glass-card rounded-xl pa-5 stat-card shadow-premium" elevation="0" to="/skpd">
              <div class="text-overline text-grey-darken-1 mb-1">Units (SKPD)</div>
              <div class="text-h5 font-weight-bold mb-1">{{ stats.total_skpd || 0 }}</div>
              <div class="text-caption text-medium-emphasis">Instansi Aktif</div>
              <div class="mt-4">
                <v-progress-linear :model-value="submissionProgress" color="primary" height="6" rounded></v-progress-linear>
              </div>
            </v-card>
          </v-col>

          <!-- Average Cost -->
          <v-col cols="12" md="4">
            <v-card class="glass-card rounded-xl pa-5 stat-card-premium purple-glow shadow-premium" elevation="0">
              <div class="d-flex align-center mb-4">
                <v-avatar color="purple-lighten-5" rounded="lg" size="48">
                  <v-icon color="purple">mdi-account-cash-outline</v-icon>
                </v-avatar>
                <v-spacer></v-spacer>
                <v-chip color="purple" size="x-small" variant="flat" class="font-weight-bold">RATA-RATA / ORANG</v-chip>
              </div>
              <div class="text-h4 font-weight-black mb-1">{{ reportData ? formatCurrency(reportData.summary.avg_per_employee) : formatCurrency(stats.monthly_payment / stats.total_employees) }}</div>
              <div class="text-caption text-grey">Biaya gaji rata-rata per pegawai</div>
            </v-card>
          </v-col>
        </v-row>

        <!-- ═══════════════════════════════════════════ -->
        <!-- SECTION 2: ANALYTICS & SMART ALERTS        -->
        <!-- ═══════════════════════════════════════════ -->
        <v-row class="mb-8">
          <!-- Main Trend Chart -->
          <v-col cols="12" md="8">
            <v-card class="glass-card rounded-xl pa-6 shadow-premium h-100" elevation="0">
              <div class="d-flex align-center mb-6">
                <v-icon color="primary" class="mr-2">mdi-chart-line</v-icon>
                <h2 class="text-h6 font-weight-bold">Tren Pengeluaran Gaji</h2>
                <v-spacer></v-spacer>
                <v-chip color="success" size="small" variant="tonal" prepend-icon="mdi-trending-up">Live Analytics</v-chip>
              </div>
              <apexchart v-if="reportData" type="area" height="320" :options="trendChartOptions" :series="trendSeries"></apexchart>
              <div v-else class="text-center py-12">
                <v-progress-circular indeterminate color="primary" v-if="loadingReport"></v-progress-circular>
                <div v-else class="text-grey">Menunggu data analitik...</div>
              </div>
            </v-card>
          </v-col>
          
          <!-- Smart Alerts Panel -->
          <v-col cols="12" md="4">
            <v-card class="glass-card rounded-xl pa-6 shadow-premium h-100 d-flex flex-column" elevation="0">
              <div class="d-flex align-center mb-6">
                <v-icon color="warning" class="mr-2">mdi-lamp-outline</v-icon>
                <h2 class="text-h6 font-weight-bold">Smart Insights</h2>
              </div>
              
              <div class="flex-grow-1 overflow-y-auto pr-1" style="max-height: 320px">
                <template v-if="smartAlerts.length">
                  <v-alert
                    v-for="(alert, idx) in smartAlerts"
                    :key="idx"
                    :type="alert.type"
                    :icon="alert.icon"
                    variant="tonal"
                    density="compact"
                    class="mb-3 rounded-lg border-opacity-25"
                    style="border-left: 4px solid"
                  >
                    <div class="text-caption font-weight-bold">{{ alert.title }}</div>
                    <div class="text-caption opacity-80" style="font-size: 10px !important;">{{ alert.text }}</div>
                  </v-alert>
                </template>
                <div v-else class="text-center py-12">
                  <v-icon color="grey-lighten-2" size="48" class="mb-2">mdi-shield-check-outline</v-icon>
                  <div class="text-caption text-grey">Sistem dalam kondisi optimal</div>
                </div>
              </div>

              <v-divider class="my-4 border-opacity-10"></v-divider>
              <v-btn block variant="tonal" color="primary" rounded="lg" size="small" append-icon="mdi-arrow-right">Lihat Detail Laporan</v-btn>
            </v-card>
          </v-col>
        </v-row>

        <!-- ═══════════════════════════════════════════ -->
        <!-- SECTION 3: DISTRIBUTION & STATUS           -->
        <!-- ═══════════════════════════════════════════ -->
        <v-row class="mb-8">
          <!-- Pegawai per Instansi -->
          <v-col cols="12" md="7">
            <v-card class="glass-card rounded-xl pa-6 shadow-premium h-100" elevation="0">
              <div class="d-flex align-center mb-6">
                <h2 class="text-h6 font-weight-bold">Sebaran Pegawai (Top 6)</h2>
                <v-spacer></v-spacer>
                <v-btn variant="text" size="small" color="primary" rounded="pill" to="/skpd">Lihat Semua</v-btn>
              </div>
              <v-row dense>
                <v-col v-for="(item, index) in charts.employees_per_skpd?.slice(0, 6)" :key="index" cols="12" sm="6" class="mb-2">
                  <div class="d-flex justify-space-between mb-1 px-1">
                    <span class="text-caption font-weight-bold text-truncate" style="max-width: 140px">{{ item.nama_skpd }}</span>
                    <span class="text-caption font-weight-bold text-primary">{{ item.total }}</span>
                  </div>
                  <v-progress-linear
                    :model-value="(item.total / stats.total_employees) * 100"
                    color="primary" height="6" rounded class="bg-blue-lighten-5"
                  ></v-progress-linear>
                </v-col>
              </v-row>
            </v-card>
          </v-col>

          <!-- Status Kepegawaian -->
          <v-col cols="12" md="5">
            <v-card class="glass-card rounded-xl pa-6 shadow-premium h-100" elevation="0">
              <h2 class="text-h6 font-weight-bold mb-6">Status Pegawai</h2>
              <v-row dense>
                <v-col v-for="(s, idx) in distribution.status" :key="idx" cols="6">
                  <div class="pa-3 rounded-lg border-opacity-10 mb-2" :class="`bg-${getStatusColor(s.status)}-lighten-5`" style="border: 1px solid currentColor">
                    <div class="text-h5 font-weight-black" :class="`text-${getStatusColor(s.status)}`">{{ s.total }}</div>
                    <div class="text-caption font-weight-bold opacity-70">{{ s.status || 'Aktif' }}</div>
                  </div>
                </v-col>
              </v-row>
            </v-card>
          </v-col>
        </v-row>

        <!-- ═══════════════════════════════════════════ -->
        <!-- SECTION 4: DETAILED REPORTS (COLLAPSIBLE)  -->
        <!-- ═══════════════════════════════════════════ -->
        
        <!-- Missing Payrolls -->
        <v-card class="glass-card rounded-xl overflow-hidden shadow-premium mb-6" elevation="0">
          <v-toolbar color="error-lighten-5" flat class="px-6 py-3 cursor-pointer" @click="isUnpaidVisible = !isUnpaidVisible">
            <v-icon color="error" class="mr-3">mdi-alert-octagon-outline</v-icon>
            <v-toolbar-title class="font-weight-bold text-subtitle-1 text-error">Gaji Belum Masuk (Pending)</v-toolbar-title>
            <v-spacer></v-spacer>
            <v-chip color="error" size="small" variant="flat" class="mr-4">{{ unpaidSkpds.length }} SKPD</v-chip>
            <v-btn :icon="isUnpaidVisible ? 'mdi-chevron-up' : 'mdi-chevron-down'" variant="text"></v-btn>
          </v-toolbar>
          
          <v-expand-transition>
            <div v-if="isUnpaidVisible">
              <v-divider></v-divider>
              <div class="pa-4 bg-error-lighten-5 border-b-sm border-error border-opacity-10">
                <div class="d-flex align-center">
                  <v-btn-toggle v-model="viewBy" mandatory density="compact" color="error" variant="outlined" class="rounded-lg bg-white">
                    <v-btn value="skpd" size="small">PER SKPD</v-btn>
                    <v-btn value="upt" size="small">PER UPT</v-btn>
                    <v-btn value="employees" size="small">PER PEGAWAI</v-btn>
                  </v-btn-toggle>
                  <v-spacer></v-spacer>
                  <v-btn color="success" variant="flat" size="small" class="mr-2" prepend-icon="mdi-microsoft-excel" @click="exportUnpaid('excel')" :loading="exportLoading === 'excel'">Export Excel</v-btn>
                  <v-menu v-model="unpaidMenu" :close-on-content-click="false">
                    <template v-slot:activator="{ props }">
                      <v-btn color="error" variant="tonal" size="small" v-bind="props" prepend-icon="mdi-calendar">
                        {{ unpaidMonthName }} {{ unpaidYear }}
                      </v-btn>
                    </template>
                    <v-card min-width="300" class="pa-4 rounded-xl shadow-premium">
                      <v-row dense>
                        <v-col cols="6">
                          <v-select v-model="unpaidMonth" :items="monthList" item-title="title" item-value="value" label="Bulan" density="compact" variant="outlined" hide-details></v-select>
                        </v-col>
                        <v-col cols="6">
                          <v-select v-model="unpaidYear" :items="yearList" label="Tahun" density="compact" variant="outlined" hide-details></v-select>
                        </v-col>
                        <v-col cols="12" class="mt-2 text-right">
                          <v-btn block color="primary" @click="fetchUnpaidData(); unpaidMenu = false">Filter</v-btn>
                        </v-col>
                      </v-row>
                    </v-card>
                  </v-menu>
                </div>
              </div>

              <div class="pa-2" style="max-height: 400px; overflow-y: auto;">
                <v-list v-if="viewBy === 'skpd'" class="bg-transparent" lines="one">
                  <v-list-item v-for="skpd in unpaidSkpds" :key="skpd.id_skpd" class="mb-1 rounded-lg">
                    <template v-slot:prepend><v-icon color="error" size="18">mdi-office-building-remove</v-icon></template>
                    <v-list-item-title class="text-caption font-weight-bold">{{ skpd.nama_skpd }}</v-list-item-title>
                    <template v-slot:append><v-chip size="x-small" color="error" variant="tonal">BELUM LAPOR</v-chip></template>
                  </v-list-item>
                </v-list>
                <div v-if="!unpaidSkpds.length" class="text-center py-8 text-grey text-caption">Semua SKPD sudah mengunggah data gaji.</div>
              </div>
            </div>
          </v-expand-transition>
        </v-card>

        <!-- Paid Payrolls -->
        <v-card class="glass-card rounded-xl overflow-hidden shadow-premium mb-8" elevation="0">
          <v-toolbar color="success-lighten-5" flat class="px-6 py-3 cursor-pointer" @click="isPaidVisible = !isPaidVisible">
            <v-icon color="success" class="mr-3">mdi-check-decagram-outline</v-icon>
            <v-toolbar-title class="font-weight-bold text-subtitle-1 text-success">Daftar Gaji Terbayar (Verified)</v-toolbar-title>
            <v-spacer></v-spacer>
            <v-chip color="success" size="small" variant="flat" class="mr-4">{{ paidSkpds.length }} SKPD</v-chip>
            <v-btn :icon="isPaidVisible ? 'mdi-chevron-up' : 'mdi-chevron-down'" variant="text"></v-btn>
          </v-toolbar>

          <v-expand-transition>
            <div v-if="isPaidVisible">
              <v-divider></v-divider>
              <div class="pa-4 bg-success-lighten-5 border-b-sm border-success border-opacity-10">
                <div class="d-flex align-center">
                  <v-btn color="success" variant="flat" size="small" prepend-icon="mdi-microsoft-excel" @click="exportPaid('excel')" :loading="paidExportLoading === 'excel'">Export Excel</v-btn>
                  <v-spacer></v-spacer>
                  <v-menu v-model="paidMenu" :close-on-content-click="false">
                    <template v-slot:activator="{ props }">
                      <v-btn color="success" variant="tonal" size="small" v-bind="props" prepend-icon="mdi-calendar">
                        {{ paidMonthName }} {{ paidYear }}
                      </v-btn>
                    </template>
                    <v-card min-width="300" class="pa-4 rounded-xl shadow-premium">
                      <v-row dense>
                        <v-col cols="6"><v-select v-model="paidMonth" :items="monthList" item-title="title" item-value="value" label="Bulan" density="compact" variant="outlined" hide-details></v-select></v-col>
                        <v-col cols="6"><v-select v-model="paidYear" :items="yearList" label="Tahun" density="compact" variant="outlined" hide-details></v-select></v-col>
                        <v-col cols="12" class="mt-2 text-right"><v-btn block color="success" @click="fetchPaidData(); paidMenu = false">Filter</v-btn></v-col>
                      </v-row>
                    </v-card>
                  </v-menu>
                </div>
              </div>
              <v-data-table
                :headers="paidHeaders"
                :items="paidSkpds"
                :loading="paidLoading"
                density="comfortable"
                class="modern-report-table"
                max-height="400"
              >
                <template v-slot:item.total_bersih="{ item }">
                  <span class="font-weight-black text-primary">{{ formatCurrencyCompact(item.total_bersih) }}</span>
                </template>
              </v-data-table>
            </div>
          </v-expand-transition>
        </v-card>

        <!-- ═══════════════════════════════════════════ -->
        <!-- SECTION 5: TOP EARNERS                     -->
        <!-- ═══════════════════════════════════════════ -->
        <v-row class="mb-6" v-if="reportData">
          <v-col cols="12">
            <v-card class="glass-card rounded-xl overflow-hidden shadow-premium" elevation="0">
              <v-toolbar color="transparent" flat class="px-6 py-4">
                <v-toolbar-title class="font-weight-bold text-subtitle-1">Peringkat Gaji Tertinggi</v-toolbar-title>
                <v-spacer></v-spacer>
                <v-chip color="amber-darken-2" variant="flat" size="small" prepend-icon="mdi-medal-outline">TOP 10</v-chip>
              </v-toolbar>
              <v-data-table
                :headers="topEarnersHeaders"
                :items="reportData.top_earners"
                class="modern-report-table"
                hover
                :items-per-page="10"
              >
                <template v-slot:item.rank="{ index }">
                  <v-avatar v-if="index < 3" :color="index === 0 ? 'amber' : index === 1 ? 'grey-lighten-2' : 'brown-lighten-2'" size="28" class="font-weight-black text-caption">
                    {{ index + 1 }}
                  </v-avatar>
                  <span v-else class="text-grey font-weight-bold">{{ index + 1 }}</span>
                </template>
                <template v-slot:item.nama="{ item }">
                  <div class="font-weight-bold">{{ item.nama }}</div>
                  <div class="text-caption text-grey">{{ item.nip }}</div>
                </template>
                <template v-slot:item.jabatan_info="{ item }">
                  <div class="text-body-2">{{ item.jabatan }}</div>
                  <div class="text-caption text-primary">{{ item.nama_skpd }}</div>
                </template>
                <template v-slot:item.total_amoun="{ item }">
                  <span class="font-weight-black text-primary">{{ formatCurrency(item.total_amoun) }}</span>
                </template>
              </v-data-table>
            </v-card>
          </v-col>
        </v-row>
        
        <!-- ═══════════════════════════════════════════ -->
        <!-- SECTION 6: RETIREMENT MONITOR              -->
        <!-- ═══════════════════════════════════════════ -->
        <v-row v-if="reportData">
          <v-col cols="12">
            <v-card class="glass-card rounded-xl overflow-hidden shadow-premium" elevation="0">
              <v-toolbar color="transparent" flat class="px-6 py-4">
                <v-toolbar-title class="font-weight-bold text-subtitle-1">Monitoring Pensiun (Batas: 58 Tahun)</v-toolbar-title>
                <v-spacer></v-spacer>
                <v-chip color="error" variant="flat" size="small" prepend-icon="mdi-clock-alert-outline">PERENCANAAN</v-chip>
              </v-toolbar>
              <v-data-table
                :headers="retirementHeaders"
                :items="reportData.retirement_monitor"
                class="modern-report-table"
                hover
                :items-per-page="12"
              >
                <template v-slot:item.nama="{ item }">
                  <div class="font-weight-bold">{{ item.nama }}</div>
                  <div class="text-caption text-grey">{{ item.jabatan }}</div>
                  <div class="text-caption text-primary">{{ item.nama_skpd }}</div>
                </template>
                <template v-slot:item.age="{ item }">
                  <v-chip :color="item.age >= 58 ? 'red' : 'orange'" variant="tonal" size="small" class="font-weight-bold">
                    {{ item.age }} Tahun
                  </v-chip>
                </template>
                <template v-slot:item.retirement_date="{ item }">
                  <span class="font-weight-medium">{{ formatDate(item.retirement_date) }}</span>
                </template>
                <template v-slot:item.status="{ item }">
                  <v-btn v-if="item.age >= 58" color="error" size="x-small" variant="flat" class="font-weight-bold">PROSES</v-btn>
                  <v-chip v-else-if="item.is_critical" color="warning" size="x-small" variant="flat">MENDEKATI</v-chip>
                  <v-chip v-else color="info" size="x-small" variant="tonal">MONITORING</v-chip>
                </template>
                <template v-slot:no-data>
                  <div class="text-center py-8 text-grey">Tidak ada pegawai yang mendekati usia pensiun (55+).</div>
                </template>
              </v-data-table>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>

    <!-- Global Feedback Snackbar -->
    <v-snackbar v-model="snackbar" :timeout="3000" color="primary" rounded="lg" elevation="24">
      <div class="d-flex align-center">
        <v-icon class="mr-3">mdi-information-outline</v-icon>
        <div>
          <div class="font-weight-bold">{{ snackbarTitle }}</div>
          <div class="text-caption">Fitur ini akan segera hadir.</div>
        </div>
      </div>
      <template v-slot:actions>
        <v-btn variant="text" @click="snackbar = false">TUTUP</v-btn>
      </template>
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch, provide } from 'vue'
import { useRouter } from 'vue-router'
import { useTheme } from 'vuetify'
import api from '../api'

// ECharts
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { PieChart, BarChart, LineChart } from 'echarts/charts'
import {
  TitleComponent,
  TooltipComponent,
  LegendComponent,
  GridComponent
} from 'echarts/components'
import VChart, { THEME_KEY } from 'vue-echarts'

use([
  CanvasRenderer,
  PieChart,
  BarChart,
  LineChart,
  TitleComponent,
  TooltipComponent,
  LegendComponent,
  GridComponent
])

import Sidebar from '../components/Sidebar.vue'
import Navbar from '../components/Navbar.vue'

const theme = useTheme()
provide(THEME_KEY, computed(() => theme.global.name.value === 'dark' ? 'dark' : 'light'))
const router = useRouter()
const user = ref(null)
const loading = ref(true)
const loadingReport = ref(true)
const error = ref('')
const snackbar = ref(false)
const snackbarTitle = ref('')
const currentYear = new Date().getFullYear()

const showComingSoon = (feature) => {
  snackbarTitle.value = feature
  snackbar.value = true
}

// ═══════════════════════════════════════════
// DASHBOARD OVERVIEW DATA
// ═══════════════════════════════════════════
const stats = ref({
  total_employees: 0,
  active_employees: 0,
  monthly_payment: 0,
  total_skpd: 0,
})
const distribution = ref({ gender: [] })
const charts = ref({
  employees_per_skpd: [],
  payment_trend: [],
})

const sparklineData = computed(() => ({
  employees: [stats.value.total_employees * 0.9, stats.value.total_employees * 0.95, stats.value.total_employees],
  payments: charts.value.payment_trend?.slice(-5).map(p => p.total) || [0, 0, 0, 0, 0]
}))

const fetchDashboardData = async () => {
  try {
    const response = await api.get('/dashboard')
    if (response.data.success) {
      stats.value = response.data.data.summary
      distribution.value = response.data.data.distribution || { gender: [], composition: [] }
      charts.value = response.data.data.charts
    }
  } catch (err) {
    error.value = 'Gagal memuat data dashboard: ' + (err.response?.data?.message || err.message)
  } finally {
    loading.value = false
  }
}

// ECharts Options
const compositionOption = computed(() => {
  const data = distribution.value.composition || []
  return {
    tooltip: {
      trigger: 'item',
      formatter: '{b}: {c} ({d}%)'
    },
    legend: {
      orient: 'vertical',
      left: 'left',
      bottom: '0',
      textStyle: {
        color: theme.global.name.value === 'dark' ? '#fff' : '#000'
      }
    },
    series: [
      {
        name: 'Komposisi',
        type: 'pie',
        radius: ['40%', '70%'],
        avoidLabelOverlap: false,
        itemStyle: {
          borderRadius: 10,
          borderColor: '#fff',
          borderWidth: 2
        },
        label: {
          show: false,
          position: 'center'
        },
        emphasis: {
          label: {
            show: true,
            fontSize: 20,
            fontWeight: 'bold'
          }
        },
        labelLine: {
          show: false
        },
        data: data.map(item => ({
          value: item.value,
          name: item.label
        })),
        color: ['#00897B', '#4DB6AC', '#80CBC4']
      }
    ]
  }
})

const skpdComparisonOption = computed(() => {
  const data = charts.value.employees_per_skpd || []
  return {
    tooltip: {
      trigger: 'axis',
      axisPointer: { type: 'shadow' }
    },
    grid: {
      left: '3%',
      right: '4%',
      bottom: '3%',
      containLabel: true
    },
    xAxis: {
      type: 'value',
      show: false
    },
    yAxis: {
      type: 'category',
      data: data.map(item => item.nama_skpd.length > 20 ? item.nama_skpd.substring(0, 17) + '...' : item.nama_skpd),
      axisLine: { show: false },
      axisTick: { show: false }
    },
    series: [
      {
        name: 'Pegawai',
        type: 'bar',
        data: data.map(item => item.total),
        itemStyle: {
          color: '#00897B',
          borderRadius: [0, 5, 5, 0]
        },
        label: {
          show: true,
          position: 'right'
        }
      }
    ]
  }
})

const submissionProgress = computed(() => {
  if (!stats.value.total_skpd || stats.value.total_skpd === 0) return 0
  const submittedCount = stats.value.total_skpd - unpaidSkpds.value.length
  return Math.round((submittedCount / stats.value.total_skpd) * 100)
})

const smartAlerts = computed(() => {
  const alerts = []
  if (unpaidSkpds.value.length > 5) {
    alerts.push({
      type: 'error',
      icon: 'mdi-alert-circle',
      title: `${unpaidSkpds.value.length} SKPD Belum Setor`,
      text: 'Beberapa instansi besar belum mengunggah laporan gaji.'
    })
  }
  if (unpaidEmployees.value.length > 0) {
    alerts.push({
      type: 'warning',
      icon: 'mdi-account-alert',
      title: `${unpaidEmployees.value.length} Pegawai Pending`,
      text: 'Data gaji individu masih dalam antrian verifikasi.'
    })
  }
  if (reportData.value?.summary?.annual_budget > 10000000000) {
    alerts.push({
      type: 'info',
      icon: 'mdi-shield-check',
      title: 'Audit Anggaran Aktif',
      text: 'Total pencairan tahunan telah melewati batas pemantauan.'
    })
  }
  return alerts
})

const isUnpaidVisible = ref(false)
const isPaidVisible = ref(false)

// ═══════════════════════════════════════════
// REPORT / ANALYTICS DATA
// ═══════════════════════════════════════════
const reportData = ref(null)

const fetchReportData = async () => {
  loadingReport.value = true
  try {
    const response = await api.get('/reports')
    reportData.value = response.data.data
  } catch (err) {
    console.error('Error fetching reports:', err)
  } finally {
    loadingReport.value = false
  }
}

// ═══════════════════════════════════════════
// UNPAID (MISSING) PAYROLLS
// ═══════════════════════════════════════════
const unpaidLoading = ref(true)
const unpaidSkpds = ref([])
const unpaidUpts = ref([])
const unpaidEmployees = ref([])
const exportLoading = ref(null)
const unpaidMenu = ref(false)
const viewBy = ref('skpd')
const unpaidMonth = ref(new Date().getMonth() + 1)
const unpaidYear = ref(new Date().getFullYear())

const monthList = [
  { title: 'Januari', value: 1 }, { title: 'Februari', value: 2 }, { title: 'Maret', value: 3 },
  { title: 'April', value: 4 }, { title: 'Mei', value: 5 }, { title: 'Juni', value: 6 },
  { title: 'Juli', value: 7 }, { title: 'Agustus', value: 8 }, { title: 'September', value: 9 },
  { title: 'Oktober', value: 10 }, { title: 'November', value: 11 }, { title: 'Desember', value: 12 }
]
const yearList = computed(() => {
  const current = new Date().getFullYear()
  return [current, current - 1, current - 2]
})
const unpaidMonthName = computed(() => monthList.find(m => m.value === unpaidMonth.value)?.title)

const fetchUnpaidData = async () => {
  unpaidLoading.value = true
  try {
    const params = { month: unpaidMonth.value, year: unpaidYear.value }
    const [skpdsRes, uptsRes, employeesRes] = await Promise.all([
      api.get('/reports/unpaid-skpds', { params }),
      api.get('/reports/unpaid-upts', { params }),
      api.get('/reports/unpaid-employees', { params })
    ])
    if (skpdsRes.data.success) unpaidSkpds.value = skpdsRes.data.data
    if (uptsRes.data.success) unpaidUpts.value = uptsRes.data.data
    if (employeesRes.data.success) unpaidEmployees.value = employeesRes.data.data
  } catch (err) {
    console.error('Failed to fetch unpaid data:', err)
  } finally {
    unpaidLoading.value = false
  }
}

watch(viewBy, () => { fetchUnpaidData() })

const exportUnpaid = async (format) => {
  exportLoading.value = format
  try {
    const params = { month: unpaidMonth.value, year: unpaidYear.value, view_by: viewBy.value, format }
    const response = await api.get('/reports/unpaid-export', { params, responseType: 'blob' })
    downloadBlob(response.data, format, `missing_payrolls_${viewBy.value}_${unpaidMonth.value}_${unpaidYear.value}`)
  } catch (err) {
    console.error('Export failed:', err)
  } finally {
    exportLoading.value = null
  }
}

// ═══════════════════════════════════════════
// PAID PAYROLLS
// ═══════════════════════════════════════════
const paidLoading = ref(true)
const paidSkpds = ref([])
const paidEmployees = ref([])
const paidExportLoading = ref(null)
const paidMenu = ref(false)
const paidMonth = ref(new Date().getMonth() + 1)
const paidYear = ref(new Date().getFullYear())
const paidViewBy = ref('skpd')
const paidMonthName = computed(() => monthList.find(m => m.value === paidMonth.value)?.title)

const paidHeaders = [
  { title: 'SKPD', key: 'nama_skpd', sortable: true },
  { title: 'KODE', key: 'kode_skpd', sortable: true, width: '100px' },
  { title: 'PEGAWAI', key: 'employee_count', sortable: true, align: 'center', width: '100px' },
  { title: 'GAJI POKOK', key: 'total_gaji_pokok', sortable: true, align: 'end' },
  { title: 'TOTAL BERSIH', key: 'total_bersih', sortable: true, align: 'end' },
]

const paidEmployeesHeaders = [
  { title: 'PEGAWAI', key: 'nama', sortable: true },
  { title: 'JABATAN / SKPD', key: 'jabatan', sortable: true },
  { title: 'GAJI POKOK', key: 'gaji_pokok', sortable: true, align: 'end' },
  { title: 'PAJAK', key: 'pajak', sortable: true, align: 'end' },
  { title: 'IWP', key: 'iwp', sortable: true, align: 'end' },
  { title: 'TUNJANGAN', key: 'tunjangan', sortable: true, align: 'end' },
  { title: 'TOTAL BERSIH', key: 'total_bersih', sortable: true, align: 'end' },
]

const fetchPaidData = async () => {
  paidLoading.value = true
  try {
    const params = { month: paidMonth.value, year: paidYear.value, type: 'pw' }
    const [skpdsRes, employeesRes] = await Promise.all([
      api.get('/reports/paid-skpds', { params }),
      api.get('/reports/paid-employees', { params })
    ])
    if (skpdsRes.data.success) paidSkpds.value = skpdsRes.data.data
    if (employeesRes.data.success) paidEmployees.value = employeesRes.data.data
  } catch (err) {
    console.error('Failed to fetch paid data:', err)
  } finally {
    paidLoading.value = false
  }
}

const exportPaid = async (format) => {
  paidExportLoading.value = format
  try {
    const params = { month: paidMonth.value, year: paidYear.value, format }
    const endpoint = paidViewBy.value === 'employees' ? '/reports/paid-employees-export' : '/reports/paid-export'
    const response = await api.get(endpoint, { params, responseType: 'blob' })
    downloadBlob(response.data, format, `skpd_daftar_gaji_${paidMonth.value}_${paidYear.value}`)
  } catch (err) {
    console.error('Export failed:', err)
  } finally {
    paidExportLoading.value = null
  }
}

// ═══════════════════════════════════════════
// TABLE HEADERS
// ═══════════════════════════════════════════
const topEarnersHeaders = [
  { title: 'PERINGKAT', key: 'rank', sortable: false, align: 'center', width: '80px' },
  { title: 'PEGAWAI', key: 'nama', sortable: true },
  { title: 'JABATAN / INSTANSI', key: 'jabatan_info', sortable: false },
  { title: 'PENGHASILAN', key: 'total_amoun', sortable: true, align: 'end' },
]

const retirementHeaders = [
  { title: 'PEGAWAI', key: 'nama', sortable: true },
  { title: 'USIA', key: 'age', sortable: true, align: 'center' },
  { title: 'ESTIMASI PENSIUNAN', key: 'retirement_date', sortable: true },
  { title: 'STATUS', key: 'status', sortable: false, align: 'center' },
]

// ═══════════════════════════════════════════
// CHART OPTIONS
// ═══════════════════════════════════════════
const trendSeries = computed(() => {
  if (!reportData.value) return []
  return [{ name: 'Total Anggaran', data: reportData.value.growth.map(i => i.value) }]
})

const trendChartOptions = computed(() => ({
  chart: {
    height: 350, type: 'area', toolbar: { show: false }, zoom: { enabled: false },
    foreColor: theme.global.name.value === 'dark' ? '#94a3b8' : '#64748b'
  },
  theme: { mode: theme.global.name.value },
  colors: ['#1867C0'],
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 3 },
  xaxis: {
    categories: reportData.value?.growth.map(i => i.label) || [],
    axisBorder: { show: false }, axisTicks: { show: false }
  },
  yaxis: {
    labels: { formatter: (val) => formatCurrencyCompact(val) }
  },
  fill: {
    type: 'gradient',
    gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100] }
  },
  grid: { borderColor: '#f1f1f1', strokeDashArray: 4 }
}))

// ═══════════════════════════════════════════
// UTILITIES
// ═══════════════════════════════════════════
const formatCurrency = (value) => {
  if (!value && value !== 0) return '-'
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value || 0)
}

const formatCurrencyShort = (value) => {
  if (!value) return 'Rp 0'
  if (value >= 1000000000) return 'Rp ' + (value / 1000000000).toFixed(2) + ' M'
  if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(2) + ' Jt'
  return formatCurrency(value)
}

const formatCurrencyCompact = (val) => {
  if (!val) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val)
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' })
}

const getProgressColor = (index) => {
  const colors = ['primary', 'success', 'info', 'warning', 'purple', 'teal', 'cyan', 'indigo']
  return colors[index % colors.length]
}

const getStatusColor = (status) => {
  switch (status) {
    case 'Aktif': return 'success'
    case 'Pensiun': return 'warning'
    case 'Keluar': return 'error'
    case 'Diberhentikan': return 'deep-orange'
    case 'Meninggal': return 'grey-darken-2'
    default: return 'primary'
  }
}

const downloadBlob = (data, format, filename) => {
  const blob = new Blob([data], {
    type: format === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
  })
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `${filename}.${format === 'pdf' ? 'pdf' : 'xlsx'}`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  window.URL.revokeObjectURL(url)
}

// ═══════════════════════════════════════════
// LIFECYCLE
// ═══════════════════════════════════════════
const fetchAllData = async () => {
  loading.value = true
  loadingReport.value = true
  await Promise.all([fetchDashboardData(), fetchReportData()])
  fetchUnpaidData()
  fetchPaidData()
}

onMounted(async () => {
  const userData = localStorage.getItem('user')
  if (userData) user.value = JSON.parse(userData)
  
  await Promise.all([fetchDashboardData(), fetchReportData()])
  fetchUnpaidData()
  fetchPaidData()
})
</script>

<style scoped>
.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.08) !important;
}

.stat-card-premium {
  position: relative;
  overflow: hidden;
  transition: transform 0.3s;
}

.stat-card-premium:hover {
  transform: translateY(-4px);
}

.blue-glow { border-top: 4px solid #1867C0; }
.purple-glow { border-top: 4px solid #9C27B0; }
.teal-glow { border-top: 4px solid #009688; }

.shadow-premium {
  box-shadow: 0 10px 30px rgba(0,0,0,0.03) !important;
}

.custom-progress {
  border-radius: 99px;
  background: rgba(var(--v-border-color), 0.1);
}

.trend-item {
  background: rgba(var(--v-border-color), 0.02);
  transition: background 0.2s;
}

.trend-item:hover {
  background: rgba(var(--v-theme-primary), 0.1);
}

.gender-block {
  transition: transform 0.2s;
}

.gender-block:hover {
  transform: scale(1.1);
}

.modern-report-table {
  background: transparent !important;
}

:deep(.v-table__wrapper) {
  background: transparent !important;
}

.modern-report-table th {
  background: rgba(var(--v-border-color), 0.05);
  color: rgb(var(--v-theme-on-surface)) !important;
  opacity: 0.7;
  text-transform: uppercase;
  font-size: 0.7rem !important;
  height: 48px !important;
}

.modern-report-table td {
  height: 60px !important;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.05) !important;
}
</style>
