<template>
  <div class="executive-mobile bg-slate-50 min-h-screen pb-20">
    <!-- Navbar Mobile -->
    <v-app-bar color="white" elevation="1" flat class="px-2">
      <v-avatar color="primary" size="32" class="mr-3">
        <v-icon icon="mdi-shield-crown" color="white" size="20"></v-icon>
      </v-avatar>
      <v-toolbar-title class="text-subtitle-1 font-weight-black">SIP-Gaji Executive</v-toolbar-title>
      <v-spacer></v-spacer>
      <v-btn icon @click="fetchData">
        <v-icon>mdi-refresh</v-icon>
      </v-btn>
    </v-app-bar>

    <div class="pa-4 pt-6">
      <!-- High Level Summary -->
      <div class="text-subtitle-2 text-grey mb-4">RINGKASAN EKSEKUTIF - {{ currentMonthName }} {{ currentYear }}</div>
      
      <v-card class="rounded-xl mb-4 bg-primary text-white pa-5 shadow-lg" elevation="0">
        <div class="text-caption opacity-80 mb-1">Total Belanja Pegawai</div>
        <div class="text-h4 font-weight-black">{{ formatCurrencyCompact(stats.monthly_payment) }}</div>
        <div class="d-flex align-center mt-3">
          <v-chip size="x-small" color="white" variant="flat" class="text-primary font-weight-bold mr-2">
            +2.4% vs Mar
          </v-chip>
          <span class="text-caption opacity-70">Tren Bulanan</span>
        </div>
      </v-card>

      <v-row dense>
        <v-col cols="6">
          <v-card class="rounded-xl pa-4 bg-white" elevation="2">
            <div class="text-caption text-grey mb-1">Total Pegawai</div>
            <div class="text-h5 font-weight-bold text-slate-800">{{ stats.total_employees }}</div>
            <div class="text-caption text-success mt-1">Aktif & Terintegrasi</div>
          </v-card>
        </v-col>
        <v-col cols="6">
          <v-card class="rounded-xl pa-4 bg-white" elevation="2">
            <div class="text-caption text-grey mb-1">Instansi</div>
            <div class="text-h5 font-weight-bold text-slate-800">{{ stats.total_skpd }}</div>
            <div class="text-caption text-primary mt-1">Unit Pengelola</div>
          </v-card>
        </v-col>
      </v-row>

      <!-- Charts for Mobile -->
      <div class="text-subtitle-2 text-grey mb-4 mt-6">TREN PENCAIRAN</div>
      <v-card class="rounded-xl pa-4 bg-white mb-6" elevation="2">
        <div style="height: 200px">
          <v-chart v-if="charts.payment_trend" :option="trendOption" autoresize />
        </div>
      </v-card>

      <!-- Quick Action / Alert -->
      <v-alert
        v-if="unpaidCount > 0"
        type="warning"
        variant="tonal"
        class="rounded-xl mb-6"
        density="compact"
      >
        Ada <strong>{{ unpaidCount }} SKPD</strong> belum memproses gaji bulan ini.
      </v-alert>

      <v-btn block color="primary" rounded="xl" size="large" to="/dashboard">
        Lihat Dashboard Lengkap
      </v-btn>
    </div>

    <!-- Bottom Navigation -->
    <v-bottom-navigation grow color="primary">
      <v-btn value="summary">
        <v-icon>mdi-view-dashboard</v-icon>
        Ringkasan
      </v-btn>
      <v-btn value="reports">
        <v-icon>mdi-file-chart</v-icon>
        Laporan
      </v-btn>
      <v-btn value="profile">
        <v-icon>mdi-account</v-icon>
        Profil
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
provide(THEME_KEY, 'light')

const currentYear = new Date().getFullYear()
const currentMonthName = new Intl.DateTimeFormat('id-ID', { month: 'long' }).format(new Date())

const stats = ref({ total_employees: 0, monthly_payment: 0, total_skpd: 0 })
const charts = ref({ payment_trend: [] })
const unpaidCount = ref(0)

const fetchData = async () => {
  try {
    const response = await api.get('/dashboard')
    stats.value = response.data.data.summary
    charts.value = response.data.data.charts
    
    // Check missing count
    const missingRes = await api.get('/reports/thr-pppk-pw/summary')
    unpaidCount.value = missingRes.data.data.length
  } catch (error) {
    console.error('Error fetching data:', error)
  }
}

const formatCurrencyCompact = (value) => {
  if (!value) return 'Rp 0'
  if (value >= 1000000000) return 'Rp ' + (value / 1000000000).toFixed(2) + ' M'
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value)
}

const trendOption = computed(() => ({
  tooltip: { trigger: 'axis' },
  grid: { left: '3%', right: '4%', bottom: '3%', top: '10%', containLabel: true },
  xAxis: { type: 'category', data: charts.value.payment_trend?.map(i => i.month.split(' ')[0]) || [] },
  yAxis: { type: 'value', show: false },
  series: [{
    data: charts.value.payment_trend?.map(i => i.total) || [],
    type: 'line',
    smooth: true,
    areaStyle: {
      color: 'rgba(var(--v-theme-primary), 0.1)'
    },
    lineStyle: { width: 3 }
  }]
}))

onMounted(fetchData)
</script>

<style scoped>
.executive-mobile {
  font-family: 'Inter', sans-serif;
}
</style>
