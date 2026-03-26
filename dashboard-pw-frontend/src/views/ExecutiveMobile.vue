<template>
  <div class="executive-mobile min-h-screen">
    <!-- Premium Header Area -->
    <div class="header-gradient pt-12 pb-8 px-6 text-white relative">
      <div class="d-flex justify-space-between align-center mb-6">
        <div>
          <div class="text-caption font-weight-bold opacity-70">EXECUTIVE DASHBOARD</div>
          <div class="text-h6 font-weight-black">SIP-Gaji Digital</div>
        </div>
        <v-btn icon color="white" variant="tonal" size="small" @click="fetchData" :loading="loading">
          <v-icon size="18">mdi-refresh</v-icon>
        </v-btn>
      </div>

      <v-card class="glass-card mt-4 mb-2 pa-6 border-0" elevation="0">
        <div class="text-caption text-white opacity-60 mb-1">TOTAL BELANJA PEGAWAI ({{ currentMonthName }})</div>
        <div class="text-h3 font-weight-black mb-3 letter-spacing-tight">
          {{ formatCurrencyCompact(stats.total_expenditure) }}
        </div>
        <div class="d-flex align-center">
          <v-icon icon="mdi-trending-up" color="success-light" size="18" class="mr-1"></v-icon>
          <span class="text-caption font-weight-bold text-success-light">+2.1%</span>
          <span class="text-caption text-white opacity-60 ml-2">vs bulan sebelumnya</span>
        </div>
      </v-card>
    </div>

    <!-- Main Content -->
    <div class="content-area pa-6">
      <!-- Stats Row -->
      <v-row dense class="mb-6 mt-n12">
        <v-col cols="6">
          <v-card class="stat-card pa-4" elevation="4">
            <div class="d-flex align-center mb-1">
              <v-icon color="primary" size="small" class="mr-2">mdi-account-group</v-icon>
              <span class="text-caption text-grey">Total Pegawai</span>
            </div>
            <div class="text-h5 font-weight-black">{{ stats.total_employees.toLocaleString() }}</div>
          </v-card>
        </v-col>
        <v-col cols="6">
          <v-card class="stat-card pa-4" elevation="4">
            <div class="d-flex align-center mb-1">
              <v-icon color="secondary" size="small" class="mr-2">mdi-bank</v-icon>
              <span class="text-caption text-grey">Instansi SKPD</span>
            </div>
            <div class="text-h5 font-weight-black">{{ stats.active_skpd }}</div>
          </v-card>
        </v-col>
      </v-row>

      <!-- Category Breakdown -->
      <div class="text-subtitle-2 font-weight-black mb-4 d-flex align-center">
        RINCIAN BELANJA PEGAWAI
        <v-divider class="ml-4"></v-divider>
      </div>

      <div v-for="cat in categories" :key="cat.label" class="mb-3">
        <v-card class="category-card pa-4" variant="flat" border>
          <div class="d-flex justify-space-between align-center">
            <div>
              <div class="text-subtitle-2 font-weight-bold">{{ cat.label }}</div>
              <div class="text-caption text-grey">{{ cat.employees }} Pegawai Terdaftar</div>
            </div>
            <div class="text-right">
              <div class="text-subtitle-1 font-weight-black">{{ formatCurrencyCompact(cat.amount) }}</div>
            </div>
          </div>
          <v-progress-linear
            :model-value="(cat.amount / stats.total_expenditure) * 100"
            color="primary"
            height="4"
            class="mt-3 rounded-pill"
          ></v-progress-linear>
        </v-card>
      </div>

      <!-- Combined Trend -->
      <div class="text-subtitle-2 font-weight-black mb-4 mt-8 d-flex align-center">
        TREN BELANJA (6 BULAN)
        <v-divider class="ml-4"></v-divider>
      </div>
      <v-card class="chart-card pa-4 mb-20" elevation="2">
        <div style="height: 220px">
          <v-chart v-if="trendData.length" :option="trendOption" autoresize />
        </div>
      </v-card>
    </div>

    <!-- Minimalist Bottom Nav -->
    <v-bottom-navigation grow color="primary" elevation="10" height="72" class="border-top-nav">
      <v-btn value="summary">
        <v-icon>mdi-finance</v-icon>
        <span class="text-caption mt-1">Summary</span>
      </v-btn>
      <v-btn value="skpd" to="/skpd">
        <v-icon>mdi-domain</v-icon>
        <span class="text-caption mt-1">Instansi</span>
      </v-btn>
      <v-btn value="reports" to="/reports/skpd-monthly">
        <v-icon>mdi-file-chart-outline</v-icon>
        <span class="text-caption mt-1">Laporan</span>
      </v-btn>
    </v-bottom-navigation>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, provide } from 'vue'
import { useTheme } from 'vuetify'
import api from '../api'

// ECharts
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { LineChart, BarChart } from 'echarts/charts'
import { TitleComponent, TooltipComponent, GridComponent } from 'echarts/components'
import VChart, { THEME_KEY } from 'vue-echarts'

use([CanvasRenderer, LineChart, BarChart, TitleComponent, TooltipComponent, GridComponent])

const theme = useTheme()
provide(THEME_KEY, computed(() => theme.global.name.value === 'dark' ? 'dark' : 'light'))

const loading = ref(false)
const currentMonthName = new Intl.DateTimeFormat('id-ID', { month: 'long' }).format(new Date())

const stats = ref({ total_expenditure: 0, total_employees: 0, active_skpd: 0, tpp_total: 0 })
const categories = ref([])
const trendData = ref([])

const fetchData = async () => {
  loading.value = true
  try {
    const response = await api.get('/dashboard/executive')
    const { summary, categories: cats, trend } = response.data.data
    stats.value = summary
    categories.value = cats
    trendData.value = trend
  } catch (error) {
    console.error('Error:', error)
  } finally {
    loading.value = false
  }
}

const formatCurrencyCompact = (value) => {
  if (!value) return 'Rp 0'
  if (value >= 1000000000000) return 'Rp ' + (value / 1000000000000).toFixed(2) + ' Triliun'
  if (value >= 1000000000) return 'Rp ' + (value / 1000000000).toFixed(2) + ' Miliar'
  return new Intl.NumberFormat('id-ID', { 
    style: 'currency', 
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(value)
}

const trendOption = computed(() => ({
  tooltip: { trigger: 'axis', backgroundColor: 'rgba(0,0,0,0.8)', textStyle: { color: '#fff' } },
  grid: { left: '0%', right: '0%', bottom: '0%', top: '5%', containLabel: true },
  xAxis: { 
    type: 'category', 
    data: trendData.value.map(i => i.label.split(' ')[0]),
    axisLine: { show: false },
    axisTick: { show: false }
  },
  yAxis: { type: 'value', show: false },
  series: [{
    data: trendData.value.map(i => i.total),
    type: 'line',
    smooth: true,
    showSymbol: false,
    areaStyle: {
      color: {
        type: 'linear',
        x: 0, y: 0, x2: 0, y2: 1,
        colorStops: [
          { offset: 0, color: 'rgba(var(--v-theme-primary), 0.3)' },
          { offset: 1, color: 'rgba(var(--v-theme-primary), 0)' }
        ]
      }
    },
    lineStyle: { width: 4, color: '#1B5E20' }
  }]
}))

onMounted(fetchData)
</script>

<style scoped>
.executive-mobile {
  font-family: 'Outfit', 'Inter', sans-serif;
  background-color: #F8FAFC;
}

.header-gradient {
  background: linear-gradient(135deg, #164e63 0%, #0891b2 100%);
  border-bottom-left-radius: 40px;
  border-bottom-right-radius: 40px;
}

.glass-card {
  background: rgba(255, 255, 255, 0.15) !important;
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.2) !important;
  border-radius: 24px !important;
}

.stat-card {
  border-radius: 20px !important;
  border: none;
}

.category-card {
  border-radius: 16px !important;
  background: white !important;
  transition: transform 0.2s;
}

.chart-card {
  border-radius: 24px !important;
}

.letter-spacing-tight {
  letter-spacing: -1.5px;
}

.text-success-light {
  color: #4ADE80;
}

.border-top-nav {
  border-top: 1px solid #E2E8F0;
}
</style>
