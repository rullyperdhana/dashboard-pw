<template>
  <div class="executive-mobile min-h-screen pb-20 overflow-x-hidden">
    <!-- Header -->
    <div class="header-vibrant pa-6 pb-12 text-white">
      <div class="d-flex justify-space-between align-center mb-6">
        <div>
          <h1 class="text-h5 font-weight-black">Analitik Eksekutif</h1>
          <p class="text-caption opacity-80">Realisasi Anggaran & Personil {{ currentYear }}</p>
        </div>
        <v-btn icon color="white" variant="tonal" size="small" @click="fetchData" :loading="loading">
          <v-icon size="20">mdi-refresh</v-icon>
        </v-btn>
      </div>

      <!-- Main KPI Card -->
      <v-card class="glass-card pa-5 mb-2 border-0" elevation="10">
        <div class="text-overline font-weight-black text-slate-500 mb-3">TOTAL REALISASI BELANJA {{ currentYear }}</div>
        
        <!-- PNS Total -->
        <div class="mb-3">
          <div class="d-flex justify-space-between align-center mb-1">
            <span class="text-caption font-weight-bold text-indigo">PNS</span>
            <span class="text-subtitle-1 font-weight-black text-slate-900">{{ formatCurrencyFull(catStats.pns.amount) }}</span>
          </div>
          <v-progress-linear :model-value="(catStats.pns.amount / catStats.total) * 100 || 0" color="indigo" height="4" rounded></v-progress-linear>
        </div>

        <!-- PPPK Total -->
        <div class="mb-3">
          <div class="d-flex justify-space-between align-center mb-1">
            <span class="text-caption font-weight-bold text-teal">PPPK</span>
            <span class="text-subtitle-1 font-weight-black text-slate-900">{{ formatCurrencyFull(catStats.pppk.amount) }}</span>
          </div>
          <v-progress-linear :model-value="(catStats.pppk.amount / catStats.total) * 100 || 0" color="teal" height="4" rounded></v-progress-linear>
        </div>

        <!-- PW Total -->
        <div>
          <div class="d-flex justify-space-between align-center mb-1">
            <span class="text-caption font-weight-bold text-orange">PPPK Paruh Waktu</span>
            <span class="text-subtitle-1 font-weight-black text-slate-900">{{ formatCurrencyFull(catStats.pw.amount) }}</span>
          </div>
          <v-progress-linear :model-value="(catStats.pw.amount / catStats.total) * 100 || 0" color="orange" height="4" rounded></v-progress-linear>
        </div>
      </v-card>
    </div>

    <!-- Stats Grid -->
    <div class="px-6 mt-n8 mb-8">
      <v-row dense>
        <v-col cols="4">
          <v-card class="stat-mini-card pa-3 text-center h-100" elevation="2">
            <v-icon color="indigo" size="20" class="mb-1">mdi-account-tie</v-icon>
            <div class="text-subtitle-2 font-weight-black text-slate-900">{{ catStats.pns.emp.toLocaleString() }}</div>
            <div class="text-xxxs text-slate-500 font-weight-bold">PNS</div>
          </v-card>
        </v-col>
        <v-col cols="4">
          <v-card class="stat-mini-card pa-3 text-center h-100" elevation="2">
            <v-icon color="teal" size="20" class="mb-1">mdi-account-hard-hat</v-icon>
            <div class="text-subtitle-2 font-weight-black text-slate-900">{{ catStats.pppk.emp.toLocaleString() }}</div>
            <div class="text-xxxs text-slate-500 font-weight-bold">PPPK</div>
          </v-card>
        </v-col>
        <v-col cols="4">
          <v-card class="stat-mini-card pa-3 text-center h-100" elevation="2">
            <v-icon color="orange" size="20" class="mb-1">mdi-account-clock</v-icon>
            <div class="text-subtitle-2 font-weight-black text-slate-900">{{ catStats.pw.emp.toLocaleString() }}</div>
            <div class="text-xxxs text-slate-500 font-weight-bold">PW</div>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <!-- Financial Trend Chart -->
    <div class="px-6 mb-4 mt-2">
      <div class="d-flex justify-space-between align-center mb-1">
        <div class="text-subtitle-1 font-weight-black text-slate-800">Financial Trend {{ currentYear }}</div>
        <v-chip size="x-small" color="teal-lighten-4" class="text-teal-darken-3 font-weight-bold">Akumulasi Juta IDR</v-chip>
      </div>
      <div class="text-caption text-slate-500 mb-4">Pertumbuhan pengeluaran gaji dan tunjangan bulanan</div>
      
      <v-card class="stat-mini-card pa-0 elevation-0 border-0 bg-transparent" elevation="0">
        <div style="height: 300px">
          <v-chart v-if="realizationData.length" :option="trendOption" autoresize />
          <div v-else class="h-100 d-flex align-center justify-center">
            <v-progress-circular indeterminate color="primary"></v-progress-circular>
          </div>
        </div>
      </v-card>
    </div>

    <!-- Category Selector / Label -->
    <div class="px-6 mb-4 mt-6">
      <div class="text-subtitle-2 font-weight-black text-slate-800 d-flex align-center">
        REALISASI BULANAN TERINCI
        <v-spacer></v-spacer>
        <v-chip-group mandatory selected-class="text-primary">
          <v-chip size="x-small" variant="tonal" color="indigo" class="font-weight-bold">PNS</v-chip>
          <v-chip size="x-small" variant="tonal" color="teal" class="font-weight-bold">PPPK</v-chip>
          <v-chip size="x-small" variant="tonal" color="orange" class="font-weight-bold">PW</v-chip>
        </v-chip-group>
      </div>
    </div>

    <!-- Realization Table with Breakdown -->
    <div class="px-6 pb-12">
      <v-expansion-panels variant="accordion" class="custom-expansion">
        <v-expansion-panel
          v-for="row in realizationData"
          :key="row.month_num"
          :disabled="row.nominal === 0"
          elevation="1"
          class="mb-2 rounded-lg border"
        >
          <v-expansion-panel-title class="pa-4">
            <v-row no-gutters align="center">
              <v-col cols="4" class="text-subtitle-2 font-weight-bold">
                {{ row.month_name }}
              </v-col>
              <v-col cols="5" class="text-right text-caption font-weight-black pr-4">
                {{ row.nominal > 0 ? formatCurrencyFull(row.nominal) : '—' }}
              </v-col>
              <v-col cols="3" class="text-right">
                <v-chip v-if="row.status === 'paid'" size="x-small" color="success" variant="flat" class="font-weight-bold">
                  {{ row.employees }} P
                </v-chip>
                <div v-else class="text-xxs text-slate-400">PENDING</div>
              </v-col>
            </v-row>
          </v-expansion-panel-title>
          
          <v-expansion-panel-text class="bg-slate-50 pa-0">
            <div class="breakdown-area py-3">
              <!-- PNS Breakdown -->
              <div class="px-4 mb-3">
                <div class="d-flex justify-space-between align-center mb-1">
                  <div class="d-flex align-center">
                    <div class="cat-dot bg-indigo mr-2"></div>
                    <span class="text-caption font-weight-bold text-slate-800">PEGAWAI NEGERI SIPIL (PNS)</span>
                  </div>
                  <div class="text-right">
                    <div class="text-body-2 font-weight-black text-slate-900">{{ formatCurrencyFull(row.breakdown.pns.amount) }}</div>
                    <div class="text-caption text-slate-500">{{ row.breakdown.pns.employees }} Pegawai</div>
                  </div>
                </div>
                <div class="px-3 d-flex flex-column gap-1 text-caption text-slate-600 mt-2">
                  <div class="d-flex justify-space-between">
                    <span>Gaji Induk:</span>
                    <span class="text-slate-800 font-weight-bold">{{ formatCurrencyFull(row.breakdown.pns.gaji) }}</span>
                  </div>
                  <div class="d-flex justify-space-between" v-if="row.breakdown.pns.thr > 0">
                    <span>Tunj. Hari Raya (THR):</span>
                    <span class="text-slate-800 font-weight-bold">{{ formatCurrencyFull(row.breakdown.pns.thr) }}</span>
                  </div>
                  <div class="d-flex justify-space-between" v-if="row.breakdown.pns.gaji13 > 0">
                    <span>Gaji 13:</span>
                    <span class="text-slate-800 font-weight-bold">{{ formatCurrencyFull(row.breakdown.pns.gaji13) }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span>Tunj. Profesi (TPP):</span>
                    <span class="text-slate-800 font-weight-bold">{{ formatCurrencyFull(row.breakdown.pns.tpp) }}</span>
                  </div>
                </div>
              </div>
              <v-divider class="mx-4 opacity-10 my-3"></v-divider>
              
              <!-- PPPK Breakdown -->
              <div class="px-4 my-3">
                <div class="d-flex justify-space-between align-center mb-1">
                  <div class="d-flex align-center">
                    <div class="cat-dot bg-teal mr-2"></div>
                    <span class="text-caption font-weight-bold text-slate-800">PPPK (FULL TIME)</span>
                  </div>
                  <div class="text-right">
                    <div class="text-body-2 font-weight-black text-slate-900">{{ formatCurrencyFull(row.breakdown.pppk.amount) }}</div>
                    <div class="text-caption text-slate-500">{{ row.breakdown.pppk.employees }} Pegawai</div>
                  </div>
                </div>
                <div class="px-3 d-flex flex-column gap-1 text-caption text-slate-600 mt-2">
                  <div class="d-flex justify-space-between">
                    <span>Gaji Induk:</span>
                    <span class="text-slate-800 font-weight-bold">{{ formatCurrencyFull(row.breakdown.pppk.gaji) }}</span>
                  </div>
                  <div class="d-flex justify-space-between" v-if="row.breakdown.pppk.thr > 0">
                    <span>Tunj. Hari Raya (THR):</span>
                    <span class="text-slate-800 font-weight-bold">{{ formatCurrencyFull(row.breakdown.pppk.thr) }}</span>
                  </div>
                  <div class="d-flex justify-space-between" v-if="row.breakdown.pppk.gaji13 > 0">
                    <span>Gaji 13:</span>
                    <span class="text-slate-800 font-weight-bold">{{ formatCurrencyFull(row.breakdown.pppk.gaji13) }}</span>
                  </div>
                  <div class="d-flex justify-space-between">
                    <span>Tunj. Profesi (TPP):</span>
                    <span class="text-slate-800 font-weight-bold">{{ formatCurrencyFull(row.breakdown.pppk.tpp) }}</span>
                  </div>
                </div>
              </div>
              <v-divider class="mx-4 opacity-10 my-3"></v-divider>

              <!-- PW Breakdown -->
              <div class="d-flex justify-space-between align-center px-4 mt-3 mb-2">
                <div class="d-flex align-center">
                  <div class="cat-dot bg-orange mr-2"></div>
                  <span class="text-caption font-weight-bold text-slate-800">PPPK PARUH WAKTU</span>
                </div>
                <div class="text-right">
                  <div class="text-body-2 font-weight-black text-slate-900">{{ formatCurrencyFull(row.breakdown.pw.amount) }}</div>
                  <div class="text-caption text-slate-500">{{ row.breakdown.pw.employees }} Pegawai</div>
                </div>
              </div>
            </div>
          </v-expansion-panel-text>
        </v-expansion-panel>
      </v-expansion-panels>
    </div>

    <!-- No Bottom Nav Here As per Requested -->
  </div>
</template>

<script setup>
import { ref, onMounted, computed, provide } from 'vue'
import { useTheme } from 'vuetify'
import api from '../api'

// ECharts
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { LineChart } from 'echarts/charts'
import { TitleComponent, TooltipComponent, LegendComponent, GridComponent } from 'echarts/components'
import VChart from 'vue-echarts'

use([CanvasRenderer, LineChart, TitleComponent, TooltipComponent, LegendComponent, GridComponent])

const theme = useTheme()
provide('THEME_KEY', 'light')

const loading = ref(false)
const stats = ref({ total_expenditure: 0, total_employees: 0, active_skpd: 0, tpp_total: 0, avg_per_employee: 0 })
const realizationData = ref([])
const categories = ref([])
const currentYear = ref(new Date().getFullYear())

const catStats = computed(() => {
  let pns = { amount: 0, emp: 0 }
  let pppk = { amount: 0, emp: 0 }
  let pw = { amount: 0, emp: 0 }
  
  if (realizationData.value.length) {
      pns.amount = realizationData.value.reduce((sum, r) => sum + r.breakdown.pns.amount, 0)
      pppk.amount = realizationData.value.reduce((sum, r) => sum + r.breakdown.pppk.amount, 0)
      pw.amount = realizationData.value.reduce((sum, r) => sum + r.breakdown.pw.amount, 0)

      const catPns = categories.value.find(c => c.label === 'PNS')
      pns.emp = catPns ? catPns.employees : 0
      
      const catPppk = categories.value.find(c => c.label === 'PPPK')
      pppk.emp = catPppk ? catPppk.employees : 0
      
      const catPw = categories.value.find(c => c.label === 'PPPK-PW')
      pw.emp = catPw ? catPw.employees : 0
  }
  
  const total = pns.amount + pppk.amount + pw.amount
  return { pns, pppk, pw, total }
})

const trendOption = computed(() => {
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']
  const gajiPns = realizationData.value.map(r => r.breakdown.pns.gaji)
  const tppPns = realizationData.value.map(r => r.breakdown.pns.tpp)
  const gajiPppk = realizationData.value.map(r => r.breakdown.pppk.gaji)
  const tppPppk = realizationData.value.map(r => r.breakdown.pppk.tpp)

  return {
    tooltip: {
      trigger: 'axis',
      backgroundColor: 'rgba(255, 255, 255, 0.9)',
      borderColor: '#E2E8F0',
      textStyle: { color: '#0F172A', fontSize: 11 },
      formatter: function(params) {
        let text = `<b style="font-size:12px;">${params[0].axisValue}</b><br/>`;
        params.forEach(p => {
          text += `${p.marker} <span style="font-weight:bold; color:#334155">${p.seriesName}:</span> <b>Rp ${p.value.toLocaleString('id-ID')}</b><br/>`;
        });
        return text;
      }
    },
    legend: {
      bottom: 0,
      icon: 'circle',
      itemWidth: 10,
      itemHeight: 10,
      textStyle: { fontSize: 10, fontWeight: 'bold', color: '#1e293b' }
    },
    grid: { left: '15%', right: '5%', bottom: '15%', top: '5%' },
    xAxis: {
      type: 'category',
      boundaryGap: false,
      data: months,
      axisLabel: { fontSize: 10, fontWeight: 'bold', color: '#64748b' },
      axisLine: { lineStyle: { color: '#cbd5e1' } }
    },
    yAxis: {
      type: 'value',
      axisLabel: { 
        formatter: (value) => { return (value / 1000000).toLocaleString('id-ID') + ' Jt' },
        fontSize: 10, fontWeight: 'bold', color: '#64748b' 
      },
      splitLine: { lineStyle: { color: '#e2e8f0' } }
    },
    series: [
      {
        name: 'Gaji PNS',
        type: 'line',
        data: gajiPns,
        smooth: true,
        symbol: 'circle',
        symbolSize: 6,
        itemStyle: { color: '#10b981' }, // vibrant green
        lineStyle: { width: 3 },
        areaStyle: {
          color: 'rgba(16, 185, 129, 0.1)'
        }
      },
      {
        name: 'TPP PNS',
        type: 'line',
        data: tppPns,
        smooth: true,
        symbol: 'emptyCircle',
        symbolSize: 6,
        itemStyle: { color: '#34d399' }, // light green
        lineStyle: { type: 'dashed', width: 2 }
      },
      {
        name: 'Gaji PPPK',
        type: 'line',
        data: gajiPppk,
        smooth: true,
        symbol: 'circle',
        symbolSize: 6,
        itemStyle: { color: '#f59e0b' }, // orange
        lineStyle: { width: 3 }
      },
      {
        name: 'TPP PPPK',
        type: 'line',
        data: tppPppk,
        smooth: true,
        symbol: 'emptyCircle',
        symbolSize: 6,
        itemStyle: { color: '#fcd34d' }, // yellow
        lineStyle: { type: 'dashed', width: 2 }
      }
    ]
  }
})

const fetchData = async () => {
  loading.value = true
  try {
    const response = await api.get('/dashboard/executive')
    const { summary, yearly_realization, current_year, categories: cats } = response.data.data
    stats.value = summary
    realizationData.value = yearly_realization
    categories.value = cats || []
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

const formatCurrencyCompact = (value) => {
  if (!value) return 'Rp 0'
  if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(1) + ' Jt'
  return formatCurrencyFull(value)
}

onMounted(fetchData)
</script>

<style scoped>
.executive-mobile {
  font-family: 'Outfit', 'Inter', sans-serif;
  background-color: #F8FAFC;
}

.header-vibrant {
  background: linear-gradient(135deg, #4F46E5 0%, #06B6D4 100%);
  border-bottom-left-radius: 48px;
  border-bottom-right-radius: 48px;
}

.glass-card {
  background: rgba(255, 255, 255, 0.95) !important;
  backdrop-filter: blur(10px);
  border-radius: 24px !important;
}

.stat-mini-card {
  border-radius: 16px !important;
  border: 1px solid #E2E8F0;
  background: white !important;
}

.custom-expansion {
  background: transparent !important;
}

.custom-expansion :deep(.v-expansion-panel) {
  background: white !important;
}

.text-xxs { font-size: 0.65rem; }
.text-xxxs { font-size: 0.55rem; }

.cat-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.bg-indigo { background-color: #4F46E5; }
.bg-teal { background-color: #06B6D4; }
.bg-orange { background-color: #F59E0B; }

.border-top {
  border-top: 1px solid #E2E8F0;
}
</style>
