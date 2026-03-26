<template>
  <div class="executive-mobile min-h-screen pb-20 overflow-x-hidden">
    <!-- Header -->
    <div class="header-area pa-6 pb-2 mt-4">
      <div class="d-flex justify-space-between align-center mb-6">
        <div>
          <h1 class="text-h5 font-weight-black text-white">Analitik Eksekutif</h1>
          <p class="text-caption text-slate-400">Realisasi Anggaran & Personil {{ currentYear }}</p>
        </div>
        <v-btn icon color="slate-700" variant="flat" size="small" @click="fetchData" :loading="loading">
          <v-icon color="teal-lighten-2" size="20">mdi-refresh</v-icon>
        </v-btn>
      </div>

      <!-- Top Summary Cards (2x2) -->
      <v-row dense class="mb-6">
        <v-col cols="6">
          <v-card class="analytics-card pa-4 h-100" elevation="0">
            <div class="d-flex align-center mb-2">
              <v-avatar color="indigo-darken-4" size="28" class="mr-2">
                <v-icon size="14" color="indigo-lighten-3">mdi-account-group</v-icon>
              </v-avatar>
              <span class="text-xxs font-weight-bold text-slate-400">TOTAL PEGAWAI</span>
            </div>
            <div class="text-h5 font-weight-black text-white">{{ stats.total_employees.toLocaleString() }}</div>
            <div class="text-xxs text-slate-500 mt-1">Data aktif {{ currentYear }}</div>
          </v-card>
        </v-col>
        <v-col cols="6">
          <v-card class="analytics-card pa-4 h-100" elevation="0">
            <div class="d-flex align-center mb-2">
              <v-avatar color="teal-darken-4" size="28" class="mr-2">
                <v-icon size="14" color="teal-lighten-3">mdi-cash-multiple</v-icon>
              </v-avatar>
              <span class="text-xxs font-weight-bold text-slate-400">TOTAL REALISASI</span>
            </div>
            <div class="text-subtitle-1 font-weight-black text-teal-accent-3 line-height-tight">
              {{ formatCurrencyFull(stats.total_expenditure) }}
            </div>
            <div class="text-xxs text-slate-500 mt-1">Gaji & TPP Gabungan</div>
          </v-card>
        </v-col>
        <v-col cols="6">
          <v-card class="analytics-card pa-4 h-100" elevation="0">
            <div class="d-flex align-center mb-2">
              <v-avatar color="amber-darken-4" size="28" class="mr-2">
                <v-icon size="14" color="amber-lighten-3">mdi-calculator</v-icon>
              </v-avatar>
              <span class="text-xxs font-weight-bold text-slate-400">RATA-RATA / PERSONIL</span>
            </div>
            <div class="text-subtitle-1 font-weight-black text-white line-height-tight">
              {{ formatCurrencyFull(stats.avg_per_employee) }}
            </div>
            <div class="text-xxs text-slate-500 mt-1">Remunerasi bulanan</div>
          </v-card>
        </v-col>
        <v-col cols="6">
          <v-card class="analytics-card pa-4 h-100" elevation="0">
            <div class="d-flex align-center mb-2">
              <v-avatar color="purple-darken-4" size="28" class="mr-2">
                <v-icon size="14" color="purple-lighten-3">mdi-office-building</v-icon>
              </v-avatar>
              <span class="text-xxs font-weight-bold text-slate-400">INSTANSI</span>
            </div>
            <div class="text-h5 font-weight-black text-white">{{ stats.active_skpd }}</div>
            <div class="text-xxs text-slate-500 mt-1">SKPD Pengelola</div>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <!-- Realization Progress Bar -->
    <div class="px-6 mb-8">
      <div class="d-flex justify-space-between align-center mb-2">
        <div class="text-caption font-weight-bold text-white d-flex align-center">
          <v-icon size="16" class="mr-2" color="teal">mdi-trending-up</v-icon>
          Realisasi {{ currentYear }}
        </div>
        <div class="text-caption text-slate-400">
          <span class="text-teal-accent-3">{{ paidMonthsCount }} bln</span> / 12 bln
        </div>
      </div>
      <v-progress-linear
        :model-value="(paidMonthsCount / 12) * 100"
        height="12"
        rounded
        color="teal-accent-3"
        bg-color="slate-800"
        bg-opacity="1"
      >
        <template v-slot:default="{ value }">
          <div class="text-xxs font-weight-black text-white">{{ Math.ceil(value) }}%</div>
        </template>
      </v-progress-linear>
      <div class="d-flex gap-4 mt-2 justify-end">
        <div class="d-flex align-center">
          <div class="dot mr-1 bg-teal"></div>
          <span class="text-xxxs text-slate-500">Terbayar</span>
        </div>
        <div class="d-flex align-center">
          <div class="dot mr-1 bg-slate-700"></div>
          <span class="text-xxxs text-slate-500">Mendatang</span>
        </div>
      </div>
    </div>

    <!-- Trend Chart -->
    <div class="px-6 mb-8">
      <v-card class="chart-card pa-4" elevation="0">
        <div class="text-caption font-weight-bold text-slate-300 mb-4 d-flex align-center">
          <div class="bar-accent mr-2"></div>
          TREN REALISASI PEMBAYARAN PER BULAN
        </div>
        <div style="height: 220px">
          <v-chart v-if="realizationData.length" :option="chartOption" autoresize />
        </div>
      </v-card>
    </div>

    <!-- Detailed Table -->
    <div class="px-6 pb-12">
      <v-card class="table-card" elevation="0">
        <div class="pa-4 d-flex align-center justify-space-between border-b-slate">
          <div class="text-caption font-weight-black text-white">REALISASI PER BULAN</div>
          <div class="text-xxs text-slate-500">{{ currentYear }}</div>
        </div>
        
        <div class="table-responsive">
          <v-table theme="dark" density="compact" class="executive-table">
            <thead>
              <tr>
                <th class="text-xxs text-slate-500">BULAN</th>
                <th class="text-xxs text-slate-500 text-right">NOMINAL</th>
                <th class="text-xxs text-slate-500 text-right">PEGAWAI</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in realizationData" :key="row.month_num" :class="{ 'row-active': row.month_num === currentMonth }">
                <td class="text-xxs font-weight-bold py-3">
                  <div class="d-flex align-center">
                    <v-icon v-if="row.status === 'paid'" size="14" color="teal" class="mr-1">mdi-check-circle</v-icon>
                    <v-icon v-else size="14" color="slate-600" class="mr-1">mdi-clock-outline</v-icon>
                    {{ row.month_name }}
                  </div>
                </td>
                <td class="text-xxs font-weight-black text-right">
                  {{ row.nominal > 0 ? formatCurrencyFull(row.nominal) : '—' }}
                </td>
                <td class="text-xxs text-right text-slate-400">
                  {{ row.employees > 0 ? row.employees.toLocaleString() : '—' }}
                </td>
              </tr>
            </tbody>
          </v-table>
        </div>
      </v-card>
    </div>

    <!-- Nav Support -->
    <v-bottom-navigation grow color="teal-accent-3" elevation="24" height="72" class="bottom-nav">
      <v-btn value="summary" active>
        <v-icon>mdi-finance</v-icon>
        <span class="text-xxs mt-1">Summary</span>
      </v-btn>
      <v-btn value="skpd" to="/skpd">
        <v-icon>mdi-domain</v-icon>
        <span class="text-xxs mt-1">Instansi</span>
      </v-btn>
      <v-btn value="reports" to="/reports/skpd-monthly">
        <v-icon>mdi-file-chart-outline</v-icon>
        <span class="text-xxs mt-1">Laporan</span>
      </v-btn>
    </v-bottom-navigation>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, provide } from 'vue'
import api from '../api'

// ECharts
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { LineChart, BarChart } from 'echarts/charts'
import { TitleComponent, TooltipComponent, GridComponent } from 'echarts/components'
import VChart, { THEME_KEY } from 'vue-echarts'

use([CanvasRenderer, LineChart, BarChart, TitleComponent, TooltipComponent, GridComponent])

provide(THEME_KEY, 'dark')

const loading = ref(false)
const stats = ref({ total_expenditure: 0, total_employees: 0, active_skpd: 0, tpp_total: 0, avg_per_employee: 0 })
const realizationData = ref([])
const currentMonth = ref(new Date().getMonth() + 1)
const currentYear = ref(new Date().getFullYear())

const paidMonthsCount = computed(() => {
  return realizationData.value.filter(m => m.status === 'paid').length
})

const fetchData = async () => {
  loading.value = true
  try {
    const response = await api.get('/dashboard/executive')
    const { summary, yearly_realization, current_month, current_year } = response.data.data
    stats.value = summary
    realizationData.value = yearly_realization
    currentMonth.value = current_month
    currentYear.value = current_year
  } catch (error) {
    console.error('Error:', error)
  } finally {
    loading.value = false
  }
}

const formatCurrencyFull = (value) => {
  if (!value) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', { 
    style: 'currency', 
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(value)
}

const chartOption = computed(() => ({
  backgroundColor: 'transparent',
  tooltip: { trigger: 'axis', backgroundColor: 'rgba(15, 23, 42, 0.9)', borderColor: '#1e293b', textStyle: { color: '#f8fafc' } },
  grid: { left: '0%', right: '0%', bottom: '5%', top: '5%', containLabel: true },
  xAxis: { 
    type: 'category', 
    data: realizationData.value.map(i => i.month_name.substring(0, 3)),
    axisLine: { lineStyle: { color: '#334155' } },
    axisTick: { show: false },
    axisLabel: { color: '#64748b', fontSize: 10 }
  },
  yAxis: { type: 'value', show: false },
  series: [
    {
      data: realizationData.value.map(i => i.nominal),
      type: 'bar',
      barWidth: '50%',
      itemStyle: {
        color: {
          type: 'linear', x: 0, y: 0, x2: 0, y2: 1,
          colorStops: [{ offset: 0, color: '#2dd4bf' }, { offset: 1, color: '#115e59' }]
        },
        borderRadius: [4, 4, 0, 0]
      }
    },
    {
      data: realizationData.value.map(i => i.nominal),
      type: 'line',
      smooth: true,
      symbolSize: 6,
      lineStyle: { color: '#4ade80', width: 2 },
      itemStyle: { color: '#4ade80' }
    }
  ]
}))

onMounted(fetchData)
</script>

<style scoped>
.executive-mobile {
  font-family: 'Outfit', 'Inter', sans-serif;
  background-color: #0f172a; /* Slate 900 */
}

.analytics-card {
  background: #1e293b !important; /* Slate 800 */
  border: 1px solid #334155 !important; /* Slate 700 */
  border-radius: 16px !important;
}

.chart-card {
  background: #1e293b !important;
  border-radius: 20px !important;
  border: 1px solid #334155 !important;
}

.table-card {
  background: #1e293b !important;
  border-radius: 20px !important;
  border: 1px solid #334155 !important;
  overflow: hidden;
}

.text-xxs { font-size: 0.65rem; }
.text-xxxs { font-size: 0.55rem; }

.line-height-tight { line-height: 1.2; }

.dot { width: 8px; height: 8px; border-radius: 50%; }
.bg-teal { background-color: #2dd4bf; }
.bg-slate-700 { background-color: #334155; }

.bar-accent { width: 4px; height: 16px; background: #2dd4bf; border-radius: 2px; }

.border-b-slate { border-bottom: 1px solid #334155; }

.row-active {
  background-color: rgba(45, 212, 191, 0.05);
}

.executive-table :deep(th) {
  border-bottom: 1px solid #334155 !important;
  height: 48px !important;
}

.executive-table :deep(td) {
  border-bottom: 1px solid #1e293b !important;
}

.bottom-nav {
  background-color: #1e293b !important;
  border-top: 1px solid #334155;
}

.v-btn--active { color: #2dd4bf !important; }

/* Colors from implementation */
.text-teal-accent-3 { color: #5eead4 !important; }
.text-slate-400 { color: #94a3b8 !important; }
.text-slate-500 { color: #64748b !important; }
.text-slate-600 { color: #475569 !important; }
</style>
