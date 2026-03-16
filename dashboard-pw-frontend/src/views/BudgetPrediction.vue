<template>
  <div class="modern-dashboard">
    <Navbar />
    <Sidebar />
    
    <v-main>
      <v-container fluid class="pa-8">
        <!-- Header -->
        <div class="d-flex align-center mb-6">
          <div>
            <h1 class="text-h4 font-weight-bold">Prediksi Anggaran Gaji</h1>
            <p class="text-body-2 text-medium-emphasis mt-1">Estimasi kebutuhan anggaran tahun depan berbasis AI & Tren</p>
          </div>
          <v-spacer></v-spacer>
          <v-chip color="primary" variant="tonal" class="pa-4 rounded-lg">
            <v-icon start>mdi-chart-line</v-icon> Smart Forecast
          </v-chip>
        </div>

        <v-row>
          <!-- Configuration Card -->
          <v-col cols="12" md="4">
            <v-card class="glass-card rounded-xl pa-6 h-100" elevation="0">
              <div class="text-h6 font-weight-bold mb-4">Parameter Prediksi</div>
              
              <v-select
                v-model="category"
                :items="categories"
                label="Kategori Pegawai"
                variant="outlined"
                density="compact"
                rounded="lg"
                class="mb-4"
              ></v-select>

              <div class="mb-6">
                <div class="d-flex justify-space-between mb-2">
                  <span class="text-body-2 text-medium-emphasis">Faktor Kenaikan (%)</span>
                  <span class="text-body-2 font-weight-bold text-primary">{{ growthFactor }}%</span>
                </div>
                <v-slider
                  v-model="growthFactor"
                  min="0"
                  max="20"
                  step="0.5"
                  color="primary"
                  hide-details
                ></v-slider>
                <div class="text-caption text-medium-emphasis mt-2">
                  Asumsi kenaikan gaji berkala, inflasi, atau penyesuaian pimpinan.
                </div>
              </div>

              <v-divider class="mb-6"></v-divider>

              <div class="text-body-2 font-weight-bold mb-2">Dasar Perhitungan (Rata-rata 3 Bulan):</div>
              <div class="text-h5 font-weight-black text-primary mb-1" v-if="data">
                {{ formatCurrency(data.parameters.avg_monthly_base) }}
              </div>
              <div class="text-caption text-medium-emphasis mb-6">/ bulan</div>

              <v-btn
                block
                color="primary"
                rounded="lg"
                height="48"
                class="font-weight-bold"
                @click="fetchData"
                :loading="loading"
              >
                PROSES PREDIKSI
              </v-btn>
            </v-card>
          </v-col>

          <!-- Result Card -->
          <v-col cols="12" md="8">
            <v-card class="glass-card rounded-xl pa-8 shadow-premium h-100" elevation="0">
              <div v-if="loading" class="d-flex flex-column align-center justify-center h-100 py-12">
                <v-progress-circular indeterminate color="primary" size="64" width="6"></v-progress-circular>
                <div class="mt-4 text-h6 font-weight-medium">Menganalisis Tren Data...</div>
              </div>
              
              <div v-else-if="data" class="h-100">
                <div class="text-h6 font-weight-bold mb-6">Hasil Proyeksi Anggaran Tahun Depan ({{ categoryLabel }})</div>
                
                <v-row class="mb-8">
                  <v-col cols="12" md="6">
                    <v-card variant="tonal" color="primary" class="pa-6 rounded-xl border-dashed">
                      <div class="text-overline text-primary font-weight-bold">TOTAL ESTIMASI TAHUNAN</div>
                      <div class="text-h3 font-weight-black">{{ formatCurrency(data.projection.final_forecast) }}</div>
                      <div class="text-caption text-medium-emphasis mt-2">Sudah termasuk faktor kenaikan {{ growthFactor }}%</div>
                    </v-card>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-card variant="tonal" color="teal" class="pa-6 rounded-xl border-dashed">
                      <div class="text-overline text-teal font-weight-bold">RATA-RATA BULANAN</div>
                      <div class="text-h3 font-weight-black">{{ formatCurrency(data.projection.monthly_avg_forecast) }}</div>
                      <div class="text-caption text-medium-emphasis mt-2">Beban pengeluaran per bulan</div>
                    </v-card>
                  </v-col>
                </v-row>

                <div class="text-subtitle-1 font-weight-bold mb-4">Faktor Penyesuaian:</div>
                <v-row>
                  <v-col cols="12" sm="6">
                    <v-list-item class="bg-surface-variant rounded-xl pa-4 mb-3 border">
                      <template v-slot:prepend>
                        <v-avatar color="error" variant="tonal" rounded="lg">
                          <v-icon>mdi-account-minus-outline</v-icon>
                        </v-avatar>
                      </template>
                      <v-list-item-title class="font-weight-bold">Pensiun</v-list-item-title>
                      <v-list-item-subtitle>{{ data.factors.retiring_count }} Pegawai akan pensiun</v-list-item-subtitle>
                      <template v-slot:append>
                        <div class="text-right">
                          <div class="text-body-2 font-weight-bold text-error">- {{ formatCurrency(data.factors.retirement_savings) }}</div>
                          <div class="text-caption">Efisiensi Tahunan</div>
                        </div>
                      </template>
                    </v-list-item>
                  </v-col>
                  <v-col cols="12" sm="6">
                    <v-list-item class="bg-surface-variant rounded-xl pa-4 border">
                      <template v-slot:prepend>
                        <v-avatar color="success" variant="tonal" rounded="lg">
                          <v-icon>mdi-trending-up</v-icon>
                        </v-avatar>
                      </template>
                      <v-list-item-title class="font-weight-bold">Growth Factor</v-list-item-title>
                      <v-list-item-subtitle>Kenaikan Berkala & Inflasi</v-list-item-subtitle>
                      <template v-slot:append>
                        <div class="text-right">
                          <div class="text-body-2 font-weight-bold text-success">+ {{ growthFactor }}%</div>
                          <div class="text-caption">Penyesuaian</div>
                        </div>
                      </template>
                    </v-list-item>
                  </v-col>
                </v-row>
              </div>

              <div v-else class="d-flex flex-column align-center justify-center h-100 py-12 text-disabled">
                <v-icon size="80">mdi-creation-outline</v-icon>
                <div class="mt-4">Klik 'Proses Prediksi' untuk memulai simulasi.</div>
              </div>
            </v-card>
          </v-col>
        </v-row>

        <!-- Retiring List Table -->
        <v-row class="mt-8" v-if="data && data.retiring_list.length">
          <v-col cols="12">
            <v-card class="glass-card rounded-xl pa-6 shadow-premium" elevation="0">
              <div class="text-h6 font-weight-bold mb-6">Daftar Pegawai Pensiun (1 Tahun Mendatang)</div>
              <v-table hover density="comfortable">
                <thead>
                  <tr>
                    <th class="text-left">NIP</th>
                    <th class="text-left">NAMA</th>
                    <th class="text-left">SKPD</th>
                    <th class="text-center">TGL LAHIR</th>
                    <th class="text-center">ESTIMASI PENSIUN</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="emp in data.retiring_list" :key="emp.nip">
                    <td class="text-caption">{{ emp.nip }}</td>
                    <td class="text-body-2 font-weight-bold">{{ emp.nama }}</td>
                    <td class="text-caption">{{ emp.skpd || '-' }}</td>
                    <td class="text-center text-caption">{{ formatDate(emp.tgl_lahir) }}</td>
                    <td class="text-center">
                      <v-chip color="error" size="x-small" variant="flat" label>
                        BUP {{ emp.bup }} TAHUN
                      </v-chip>
                    </td>
                  </tr>
                </tbody>
              </v-table>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Sidebar from '../components/Sidebar.vue'
import Navbar from '../components/Navbar.vue'
import api from '../api'

const loading = ref(false)
const growthFactor = ref(5)
const category = ref('pw')
const data = ref(null)

const categories = [
  { title: 'PPPK Paruh Waktu (PW)', value: 'pw' },
  { title: 'PNS', value: 'pns' },
  { title: 'PPPK Penuh Waktu', value: 'pppk' }
]

const categoryLabel = computed(() => {
  return categories.find(c => c.value === category.value)?.title || ''
})

const fetchData = async () => {
  loading.value = true
  try {
    const response = await api.get('/budget-prediction', {
      params: { 
        growth_factor: growthFactor.value,
        category: category.value
      }
    })
    if (response.data.success) {
      data.value = response.data.data
    }
  } catch (err) {
    console.error('Failed to fetch prediction:', err)
  } finally {
    loading.value = false
  }
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(value || 0)
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.modern-dashboard {
  min-height: 100vh;
}
.glass-card {
  background: rgba(var(--v-theme-surface), 0.7) !important;
  backdrop-filter: blur(12px);
  border: 1px solid rgba(var(--v-border-color), 0.1) !important;
}
.shadow-premium {
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.05) !important;
}
.border-dashed {
  border: 1px dashed currentColor !important;
}
</style>
