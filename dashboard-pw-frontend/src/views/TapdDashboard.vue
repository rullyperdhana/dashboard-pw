<template>
  <div class="tapd-analytics">
    <Navbar />
    <Sidebar />
    
    <v-main class="bg-light">
      <v-container fluid class="pa-8">
        <!-- Header -->
        <div class="d-flex align-center mb-8">
          <div>
            <h1 class="text-h4 font-weight-bold">Analitik Cerdas TAPD</h1>
            <p class="text-body-2 text-medium-emphasis mt-1">Estimasi kebutuhan belanja pegawai berbasis data KGB, KP & Pensiun</p>
          </div>
          <v-spacer></v-spacer>
          <v-btn-toggle v-model="category" mandatory color="primary" variant="outlined" rounded="pill" density="compact" class="bg-white">
            <v-btn value="pw">PPPK-PW</v-btn>
            <v-btn value="pns">PNS</v-btn>
            <v-btn value="pppk">PPPK-FULL</v-btn>
          </v-btn-toggle>
        </div>

        <!-- Simulation Panel -->
        <v-card class="glass-card rounded-xl pa-6 mb-8 border-primary" elevation="0">
          <v-row align="center">
            <v-col cols="12" md="3">
              <div class="text-subtitle-1 font-weight-bold mb-2">Simulasi Pertumbuhan (%)</div>
              <v-slider
                v-model="growthFactor"
                :min="0"
                :max="15"
                :step="0.5"
                color="primary"
                thumb-label="always"
                hide-details
              ></v-slider>
            </v-col>
            <v-col cols="12" md="3" class="text-center">
              <div class="text-overline text-grey">Status Data</div>
              <v-chip color="success" prepend-icon="mdi-check-circle" variant="tonal">Data Riil Terverifikasi</v-chip>
            </v-col>
            <v-col cols="12" md="6" class="text-right">
              <v-btn color="primary" prepend-icon="mdi-refresh" @click="fetchPrediction" :loading="loading" rounded="lg">
                Jalankan Simulasi
              </v-btn>
            </v-col>
          </v-row>
        </v-card>

        <!-- Main Stats View -->
        <v-row v-if="prediction" class="mb-8">
          <v-col cols="12" md="4">
            <v-card class="glass-card rounded-xl pa-6 h-100" elevation="0">
              <div class="text-overline mb-2">Estimasi Total Tahun Depan</div>
              <div class="text-h3 font-weight-black text-primary mb-2">
                {{ formatCurrencyCompact(prediction.projection.final_forecast) }}
              </div>
              <v-divider class="mb-4"></v-divider>
              <div class="d-flex justify-space-between text-body-2 mb-2">
                <span class="text-grey">Basis Tahun Berjalan</span>
                <span class="font-weight-bold">{{ formatCurrencyCompact(prediction.projection.base_yearly) }}</span>
              </div>
              <div class="d-flex justify-space-between text-body-2 text-success">
                <span>Efisiensi Pensiun</span>
                <span>-{{ formatCurrencyCompact(prediction.factors.retirement_savings) }}</span>
              </div>
            </v-card>
          </v-col>

          <v-col cols="12" md="8">
            <v-row>
              <v-col cols="12" sm="4">
                <v-card variant="tonal" color="orange" class="rounded-xl pa-4">
                  <v-icon icon="mdi-clock-alert" class="mb-2"></v-icon>
                  <div class="text-h4 font-weight-bold">{{ prediction.factors.retiring_count }}</div>
                  <div class="text-caption font-weight-bold text-uppercase">Pegawai Pensiun</div>
                </v-card>
              </v-col>
              <v-col cols="12" sm="4">
                <v-card variant="tonal" color="blue" class="rounded-xl pa-4">
                  <v-icon icon="mdi-trending-up" class="mb-2"></v-icon>
                  <div class="text-h4 font-weight-bold">{{ prediction.factors.kgb_count }}</div>
                  <div class="text-caption font-weight-bold text-uppercase">Kenaikan Berkala (KGB)</div>
                </v-card>
              </v-col>
              <v-col cols="12" sm="4">
                <v-card variant="tonal" color="purple" class="rounded-xl pa-4">
                  <v-icon icon="mdi-seal-variant" class="mb-2"></v-icon>
                  <div class="text-h4 font-weight-bold">{{ prediction.factors.kp_count }}</div>
                  <div class="text-caption font-weight-bold text-uppercase">Kenaikan Pangkat (KP)</div>
                </v-card>
              </v-col>
            </v-row>
          </v-col>
        </v-row>

        <!-- Charts Row -->
        <v-row>
          <v-col cols="12" md="6">
            <v-card class="glass-card rounded-xl pa-6 h-100" elevation="0">
              <h3 class="text-h6 font-weight-bold mb-4">Struktur Usia Pegawai</h3>
              <div style="height: 350px">
                <v-chart v-if="healthData" :option="ageChartOption" autoresize />
              </div>
            </v-card>
          </v-col>
          <v-col cols="12" md="6">
            <v-card class="glass-card rounded-xl pa-6 h-100" elevation="0">
              <h3 class="text-h6 font-weight-bold mb-4">Jadwal Pensiun 5 Tahun</h3>
              <div style="height: 350px">
                <v-chart v-if="healthData" :option="retirementChartOption" autoresize />
              </div>
            </v-card>
          </v-col>
        </v-row>

      </v-container>
    </v-main>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, provide } from 'vue'
import { useTheme } from 'vuetify'
import Navbar from '../components/Navbar.vue'
import Sidebar from '../components/Sidebar.vue'
import api from '../api'

// ECharts
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { BarChart, PieChart, LineChart } from 'echarts/charts'
import { TitleComponent, TooltipComponent, LegendComponent, GridComponent } from 'echarts/components'
import VChart, { THEME_KEY } from 'vue-echarts'

use([CanvasRenderer, BarChart, PieChart, LineChart, TitleComponent, TooltipComponent, LegendComponent, GridComponent])

const theme = useTheme()
provide(THEME_KEY, computed(() => theme.global.name.value === 'dark' ? 'dark' : 'light'))

const loading = ref(false)
const category = ref('pw')
const growthFactor = ref(5)
const prediction = ref(null)
const healthData = ref(null)

const fetchPrediction = async () => {
  loading.value = true
  try {
    const response = await api.get('/budget-prediction', {
      params: { 
        category: category.value,
        growth_factor: growthFactor.value
      }
    })
    prediction.value = response.data.data
  } catch (error) {
    console.error('Error fetching prediction:', error)
  } finally {
    loading.value = false
  }
}

const fetchHealthData = async () => {
  try {
    const response = await api.get('/analytics/health', {
      params: { category: category.value === 'pw' ? 'pw' : 'pns_pppk' }
    })
    healthData.value = response.data.data
  } catch (error) {
    console.error('Error fetching health data:', error)
  }
}

const formatCurrencyCompact = (value) => {
  if (!value) return 'Rp 0'
  if (value >= 1000000000) return 'Rp ' + (value / 1000000000).toFixed(2) + ' M'
  if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(2) + ' Jt'
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' }).format(value)
}

const ageChartOption = computed(() => {
  if (!healthData.value) return {}
  const data = healthData.value.age_distribution[category.value === 'pw' ? 'pw' : 'pns_pppk'] || []
  return {
    tooltip: { trigger: 'item' },
    legend: { bottom: '0' },
    series: [{
      type: 'pie',
      radius: ['40%', '70%'],
      avoidLabelOverlap: false,
      itemStyle: { borderRadius: 10, borderColor: '#fff', borderWidth: 2 },
      label: { show: false },
      data: data.map(i => ({ value: i.value, name: i.label })),
      color: ['#00897B', '#4DB6AC', '#80CBC4', '#B2DFDB']
    }]
  }
})

const retirementChartOption = computed(() => {
  if (!healthData.value) return {}
  const data = healthData.value.retirement_schedule || []
  return {
    tooltip: { trigger: 'axis' },
    grid: { left: '3%', right: '4%', bottom: '3%', containLabel: true },
    xAxis: { type: 'category', data: data.map(i => i.year) },
    yAxis: { type: 'value' },
    series: [{
      data: data.map(i => i.count),
      type: 'bar',
      color: '#FF7043',
      label: { show: true, position: 'top' },
      itemStyle: { borderRadius: [5, 5, 0, 0] }
    }]
  }
})

onMounted(() => {
  fetchPrediction()
  fetchHealthData()
})
</script>

<style scoped>
.glass-card {
  background: rgba(255, 255, 255, 0.7) !important;
  backdrop-filter: blur(12px);
  border: 1px solid rgba(0, 0, 0, 0.05);
}
.border-primary {
  border: 1px solid rgb(var(--v-theme-primary), 0.3) !important;
}
</style>
