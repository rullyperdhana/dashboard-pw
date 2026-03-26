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
      <v-card class="glass-card pa-6 mb-2 border-0" elevation="10">
        <div class="text-overline font-weight-black text-slate-500 mb-1">TOTAL REALISASI BELANJA</div>
        <div class="text-h4 font-weight-black text-slate-900 mb-2">
          {{ formatCurrencyFull(stats.total_expenditure) }}
        </div>
        <div class="d-flex align-center">
          <v-chip size="x-small" color="success" class="font-weight-bold mr-2">+2.1%</v-chip>
          <span class="text-xxs text-slate-500">Dibanding periode sebelumnya</span>
        </div>
      </v-card>
    </div>

    <!-- Stats Grid -->
    <div class="px-6 mt-n8 mb-8">
      <v-row dense>
        <v-col cols="4">
          <v-card class="stat-mini-card pa-3 text-center h-100" elevation="2">
            <v-icon color="indigo" size="20" class="mb-1">mdi-account-group</v-icon>
            <div class="text-subtitle-2 font-weight-black text-slate-900">{{ stats.total_employees.toLocaleString() }}</div>
            <div class="text-xxxs text-slate-500 font-weight-bold">PEGAWAI</div>
          </v-card>
        </v-col>
        <v-col cols="4">
          <v-card class="stat-mini-card pa-3 text-center h-100" elevation="2">
            <v-icon color="teal" size="20" class="mb-1">mdi-wallet</v-icon>
            <div class="text-subtitle-2 font-weight-black text-slate-900">{{ stats.active_skpd }}</div>
            <div class="text-xxxs text-slate-500 font-weight-bold">INSTANSI</div>
          </v-card>
        </v-col>
        <v-col cols="4">
          <v-card class="stat-mini-card pa-3 text-center h-100" elevation="2">
            <v-icon color="orange" size="20" class="mb-1">mdi-calculator</v-icon>
            <div class="text-xxxs font-weight-black text-slate-900 mt-1">{{ formatCurrencyCompact(stats.avg_per_employee) }}</div>
            <div class="text-xxxs text-slate-500 font-weight-bold">AVG/STAFF</div>
          </v-card>
        </v-col>
      </v-row>
    </div>

    <!-- Category Selector / Label -->
    <div class="px-6 mb-4">
      <div class="text-subtitle-2 font-weight-black text-slate-800 d-flex align-center">
        TREN & REALISASI BULANAN
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
              <div class="d-flex justify-space-between align-center px-4 mb-2">
                <div class="d-flex align-center">
                  <div class="cat-dot bg-indigo mr-2"></div>
                  <span class="text-xxs font-weight-bold text-slate-600">PEGAWAI NEGERI SIPIL (PNS)</span>
                </div>
                <div class="text-right">
                  <div class="text-xxs font-weight-black text-slate-900">{{ formatCurrencyFull(row.breakdown.pns.amount) }}</div>
                  <div class="text-xxxs text-slate-500">{{ row.breakdown.pns.employees }} Pegawai</div>
                </div>
              </div>
              <v-divider class="mx-4 opacity-10"></v-divider>
              <!-- PPPK Breakdown -->
              <div class="d-flex justify-space-between align-center px-4 my-2">
                <div class="d-flex align-center">
                  <div class="cat-dot bg-teal mr-2"></div>
                  <span class="text-xxs font-weight-bold text-slate-600">PPPK (FULL TIME)</span>
                </div>
                <div class="text-right">
                  <div class="text-xxs font-weight-black text-slate-900">{{ formatCurrencyFull(row.breakdown.pppk.amount) }}</div>
                  <div class="text-xxxs text-slate-500">{{ row.breakdown.pppk.employees }} Pegawai</div>
                </div>
              </div>
              <v-divider class="mx-4 opacity-10"></v-divider>
              <!-- PW Breakdown -->
              <div class="d-flex justify-space-between align-center px-4 mt-2">
                <div class="d-flex align-center">
                  <div class="cat-dot bg-orange mr-2"></div>
                  <span class="text-xxs font-weight-bold text-slate-600">PPPK PARUH WAKTU</span>
                </div>
                <div class="text-right">
                  <div class="text-xxs font-weight-black text-slate-900">{{ formatCurrencyFull(row.breakdown.pw.amount) }}</div>
                  <div class="text-xxxs text-slate-500">{{ row.breakdown.pw.employees }} Pegawai</div>
                </div>
              </div>
            </div>
          </v-expansion-panel-text>
        </v-expansion-panel>
      </v-expansion-panels>
    </div>

    <!-- Bottom Nav -->
    <v-bottom-navigation grow color="primary" elevation="10" height="72" class="border-top">
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
import { useTheme } from 'vuetify'
import api from '../api'

const theme = useTheme()
provide('THEME_KEY', 'light')

const loading = ref(false)
const stats = ref({ total_expenditure: 0, total_employees: 0, active_skpd: 0, tpp_total: 0, avg_per_employee: 0 })
const realizationData = ref([])
const currentYear = ref(new Date().getFullYear())

const fetchData = async () => {
  loading.value = true
  try {
    const response = await api.get('/dashboard/executive')
    const { summary, yearly_realization, current_year } = response.data.data
    stats.value = summary
    realizationData.value = yearly_realization
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
