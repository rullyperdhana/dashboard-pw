<template>
  <div class="tapd-analytics">
    <Navbar />
    <Sidebar />
    
    <v-main>
      <v-container fluid class="pa-8">
        <!-- Header -->
        <div class="d-flex align-center mb-8">
          <div>
            <h1 class="text-h4 font-weight-bold">Analitik Cerdas TAPD</h1>
            <p class="text-body-2 text-medium-emphasis mt-1">Estimasi kebutuhan belanja pegawai berbasis data KGB, KP & Pensiun</p>
          </div>
          <v-spacer></v-spacer>
          <v-btn-toggle v-model="category" mandatory color="primary" variant="outlined" rounded="pill" density="compact">
            <v-btn v-if="user?.role === 'superadmin'" value="pw">PPPK-PW</v-btn>
            <v-btn value="pns">PNS</v-btn>
            <v-btn value="pppk">PPPK-FULL</v-btn>
          </v-btn-toggle>
        </div>

        <v-alert v-if="error" type="info" variant="tonal" closable class="mb-6 rounded-lg border-primary" prepend-icon="mdi-information-outline">
          {{ error }}
        </v-alert>

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
              <div class="text-h4 font-weight-black text-primary mb-2">
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

        <!-- Analysis Tabs/Sections -->
        <v-row>
          <v-col cols="12" md="6">
            <v-card class="glass-card rounded-xl pa-6 mb-6 h-100" elevation="0">
              <div class="d-flex align-center mb-6">
                <h3 class="text-h6 font-weight-bold">Rincian Anggaran (Kode Rekening)</h3>
                <v-spacer></v-spacer>
                <v-btn icon="mdi-download" variant="text" size="small" color="primary"></v-btn>
              </div>
              <v-data-table
                v-if="prediction && prediction.breakdown"
                :headers="breakdownHeaders"
                :items="prediction.breakdown"
                density="comfortable"
                class="bg-transparent"
                hide-default-footer
              >
                <template v-slot:item.amount="{ item }">
                  <div class="font-weight-bold">{{ formatCurrency(item.amount) }}</div>
                </template>
                <template v-slot:bottom>
                  <v-divider></v-divider>
                  <div class="pa-4 d-flex justify-space-between font-weight-black text-primary">
                    <span>TOTAL ESTIMASI</span>
                    <span>{{ formatCurrency(prediction.projection.final_forecast) }}</span>
                  </div>
                </template>
              </v-data-table>
              <div v-else class="text-center py-10 text-disabled">
                <v-icon size="64">mdi-chart-tree</v-icon>
                <p>Data rincian akan muncul setelah simulasi.</p>
              </div>
            </v-card>
          </v-col>

          <v-col cols="12" md="6">
            <v-card class="glass-card rounded-xl pa-6 mb-6 h-100" elevation="0">
              <div class="d-flex align-center mb-6">
                <h3 class="text-h6 font-weight-bold">Daftar Pegawai Pensiun (12 Bulan)</h3>
                <v-spacer></v-spacer>
                <v-chip color="error" size="small" variant="flat" v-if="prediction">{{ prediction.factors.retiring_count }} Orang</v-chip>
              </div>
              <v-data-table
                v-if="prediction"
                :headers="retirementTableHeaders"
                :items="prediction.retiring_list"
                hover
                density="comfortable"
                class="bg-transparent"
                :items-per-page="5"
              >
                <template v-slot:item.nama="{ item }">
                  <div class="text-body-2 font-weight-bold">{{ item.nama }}</div>
                  <div class="text-caption text-grey">{{ item.nip }}</div>
                </template>
                <template v-slot:item.skpd="{ item }">
                  <div class="text-caption text-wrap" style="max-width: 250px">{{ item.skpd || '-' }}</div>
                </template>
              </v-data-table>
              <div v-else class="text-center py-10 text-disabled">
                <v-icon size="64">mdi-table-search</v-icon>
                <p>Klik 'Jalankan Simulasi' untuk melihat data.</p>
              </div>
            </v-card>
          </v-col>
        </v-row>

        <!-- Charts Row -->
        <v-row class="mt-4">
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
import { ref, onMounted, computed, provide, watch } from 'vue'
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

const user = JSON.parse(localStorage.getItem('user') || '{}')
const loading = ref(false)
const error = ref('')
const category = ref(user.role === 'superadmin' ? 'pw' : 'pns')
const growthFactor = ref(5)
const prediction = ref(null)
const healthData = ref(null)

const breakdownHeaders = [
  { title: 'KODE REKENING', key: 'kode', sortable: false },
  { title: 'NAMA REKENING', key: 'nama', sortable: false },
  { title: 'ESTIMASI ANGGARAN', key: 'amount', align: 'end', sortable: false },
]

const retirementTableHeaders = [
  { title: 'NAMA / NIP', key: 'nama', sortable: true },
  { title: 'SKPD', key: 'skpd', sortable: true },
]

const fetchPrediction = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await api.get('/budget-prediction', {
      params: { 
        category: category.value,
        growth_factor: growthFactor.value
      }
    })
    
    if (response.data.success) {
      prediction.value = response.data.data
    } else {
      error.value = response.data.message
      prediction.value = null
    }
  } catch (err) {
    console.error('Error fetching prediction:', err)
    error.value = 'Gagal memuat prediksi anggaran. Silakan coba lagi.'
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

const formatCurrency = (value) => {
  if (!value) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value)
}

const formatCurrencyCompact = (value) => {
  return formatCurrency(value)
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'short',
    year: 'numeric'
  })
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

watch(category, () => {
  prediction.value = null
  error.value = ''
  fetchPrediction()
  fetchHealthData()
})

onMounted(() => {
  fetchPrediction()
  fetchHealthData()
})
</script>

<style scoped>
.glass-card {
  background: rgba(var(--v-theme-surface), 0.7) !important;
  backdrop-filter: blur(12px);
  border: 1px solid rgba(var(--v-border-color), 0.1) !important;
}
.border-primary {
  border: 1px solid rgb(var(--v-theme-primary), 0.3) !important;
}
</style>
