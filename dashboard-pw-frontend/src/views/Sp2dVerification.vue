<template>
  <v-container fluid class="modern-dashboard pa-6">
    <v-row>
      <v-col cols="12">
        <div class="d-flex align-center mb-6">
          <v-btn icon="mdi-arrow-left" variant="text" @click="$router.push('/dashboard')" class="mr-2"></v-btn>
          <div>
            <h1 class="text-h4 font-weight-bold mb-1">Verifikasi Realisasi SP2D</h1>
            <p class="text-subtitle-1 text-medium-emphasis mb-0">Monitor pencairan Gaji & TPP berdasarkan data SIPD</p>
          </div>
        </div>
      </v-col>
    </v-row>

    <v-row>
      <!-- Controls & Upload -->
      <v-col cols="12" lg="4">
        <v-card class="glass-card rounded-xl pa-6 mb-6" elevation="0">
          <h2 class="text-h6 font-weight-bold mb-4">Pengaturan Periode</h2>
          <v-row dense>
            <v-col cols="7">
              <v-select
                v-model="selectedMonth"
                :items="months"
                label="Bulan"
                density="comfortable"
                variant="outlined"
                rounded="lg"
                @update:model-value="fetchData"
              ></v-select>
            </v-col>
            <v-col cols="5">
              <v-select
                v-model="selectedYear"
                :items="years"
                label="Tahun"
                density="comfortable"
                variant="outlined"
                rounded="lg"
                @update:model-value="fetchData"
              ></v-select>
            </v-col>
          </v-row>

          <v-divider class="my-4"></v-divider>

          <h2 class="text-h6 font-weight-bold mb-4">Upload Register SIPD</h2>
          <div 
            class="upload-zone pa-8 text-center rounded-xl border-dashed"
            :class="{ 'is-dragging': isDragging }"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
            @click="$refs.fileInput.click()"
          >
            <input type="file" ref="fileInput" class="d-none" @change="handleFileSelect" accept=".xlsx,.xls">
            <v-icon size="48" color="primary" class="mb-4">mdi-file-excel-outline</v-icon>
            <div class="text-h6 mb-2">Pilih atau Tarik File Excel</div>
            <div class="text-body-2 text-medium-emphasis">Format Laporan Register SIPD (.xlsx)</div>
          </div>
          
          <v-expand-transition>
            <div v-if="uploading" class="mt-4">
              <v-progress-linear indeterminate color="primary" rounded height="6"></v-progress-linear>
              <div class="text-center mt-2 text-caption">Sedang mengimpor data...</div>
            </div>
          </v-expand-transition>
        </v-card>

        <v-card class="glass-card rounded-xl pa-6" elevation="0">
          <div class="d-flex align-center justify-space-between mb-4">
            <h2 class="text-h6 font-weight-bold mb-0">Tampilan</h2>
            <v-btn-toggle
              v-model="viewMode"
              mandatory
              color="primary"
              density="compact"
              rounded="pill"
            >
              <v-btn value="summary" size="small">Ringkasan</v-btn>
              <v-btn value="details" size="small">Detail Data</v-btn>
            </v-btn-toggle>
          </div>
          <v-list density="compact" class="bg-transparent pa-0">
            <v-list-item class="px-0">
              <template v-slot:prepend>
                <v-icon color="success" size="20">mdi-check-circle</v-icon>
              </template>
              <v-list-item-title class="text-body-2">Grup "PNS" -> Gaji Induk/PNS</v-list-item-title>
            </v-list-item>
            <v-list-item class="px-0">
              <template v-slot:prepend>
                <v-icon color="success" size="20">mdi-check-circle</v-icon>
              </template>
              <v-list-item-title class="text-body-2">Grup "PPPK" -> Gaji PPPK</v-list-item-title>
            </v-list-item>
            <v-list-item class="px-0">
              <template v-slot:prepend>
                <v-icon color="success" size="20">mdi-check-circle</v-icon>
              </template>
              <v-list-item-title class="text-body-2">Grup "TPP" -> Tambahan Penghasilan</v-list-item-title>
            </v-list-item>
          </v-list>
        </v-card>
      </v-col>

      <!-- Results Table -->
      <v-col cols="12" lg="8">
        <!-- Summary View -->
        <v-card v-if="viewMode === 'summary'" class="glass-card rounded-xl overflow-hidden" elevation="0">
          <div class="pa-6 border-bottom d-flex align-center justify-space-between bg-surface-variant-light">
            <h2 class="text-h6 font-weight-bold mb-0">Status Realisasi per SKPD</h2>
            <v-text-field
              v-model="search"
              prepend-inner-icon="mdi-magnify"
              label="Cari SKPD..."
              single-line
              hide-details
              density="compact"
              variant="outlined"
              rounded="pill"
              class="max-width-300"
            ></v-text-field>
          </div>
          
          <v-data-table
            :headers="headers"
            :items="items"
            :loading="loading"
            :search="search"
            class="bg-transparent"
            hover
          >
            <template v-slot:item.nama_skpd="{ item }">
              <div class="font-weight-medium text-truncate" style="max-width: 250px;">
                {{ item.nama_skpd }}
              </div>
            </template>

            <template v-slot:item.pns="{ item }">
              <status-chip :status="item.pns" />
            </template>

            <template v-slot:item.pppk="{ item }">
              <status-chip :status="item.pppk" />
            </template>

            <template v-slot:item.tpp="{ item }">
              <status-chip :status="item.tpp" />
            </template>
          </v-data-table>
        </v-card>

        <!-- Detailed View (Raw Transactions) -->
        <v-card v-else class="glass-card rounded-xl overflow-hidden" elevation="0">
          <div class="pa-6 border-bottom d-flex align-center justify-space-between bg-surface-variant-light">
            <h2 class="text-h6 font-weight-bold mb-0">Daftar Transaksi Hasil Impor</h2>
            <div class="d-flex align-center gap-2">
              <v-text-field
                v-model="searchDetail"
                prepend-inner-icon="mdi-magnify"
                label="Cari..."
                single-line
                hide-details
                density="compact"
                variant="outlined"
                rounded="pill"
                class="max-width-200"
              ></v-text-field>
            </div>
          </div>
          
          <v-data-table
            :headers="detailHeaders"
            :items="transactions"
            :loading="loading"
            :search="searchDetail"
            class="bg-transparent"
            hover
          >
            <template v-slot:item.nomor_sp2d="{ item }">
              <div class="text-caption font-weight-bold">{{ item.nomor_sp2d }}</div>
            </template>
            
            <template v-slot:item.tanggal_sp2d="{ item }">
              {{ formatDate(item.tanggal_sp2d) }}
            </template>

            <template v-slot:item.jenis_data="{ item }">
              <v-chip size="x-small" :color="getTypeColor(item.jenis_data)" variant="flat">
                {{ item.jenis_data }}
              </v-chip>
            </template>

            <template v-slot:item.netto="{ item }">
              <div class="text-right font-weight-bold">{{ formatCurrency(item.netto) }}</div>
            </template>
            
            <template v-slot:item.nama_skpd_sipd="{ item }">
              <div class="text-caption" style="max-width: 150px;">{{ item.nama_skpd_sipd }}</div>
            </template>
          </v-data-table>
        </v-card>
      </v-col>
    </v-row>

    <!-- Success Snackbar -->
    <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="3000" rounded="lg">
      {{ snackbarText }}
    </v-snackbar>
  </v-container>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import api from '../api'
import StatusChip from '../components/Sp2dStatusChip.vue'

const selectedMonth = ref(new Date().getMonth() + 1)
const selectedYear = ref(new Date().getFullYear())
const search = ref('')
const searchDetail = ref('')
const loading = ref(false)
const uploading = ref(false)
const isDragging = ref(false)
const viewMode = ref('summary')
const items = ref([])
const transactions = ref([])
const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

const months = [
  { title: 'Januari', value: 1 }, { title: 'Februari', value: 2 },
  { title: 'Maret', value: 3 }, { title: 'April', value: 4 },
  { title: 'Mei', value: 5 }, { title: 'Juni', value: 6 },
  { title: 'Juli', value: 7 }, { title: 'Agustus', value: 8 },
  { title: 'September', value: 9 }, { title: 'Oktober', value: 10 },
  { title: 'November', value: 11 }, { title: 'Desember', value: 12 }
]
const years = [2024, 2025, 2026]

const headers = [
  { title: 'Unit SKPD', key: 'nama_skpd', align: 'start', sortable: true },
  { title: 'Gaji PNS', key: 'pns', align: 'center', sortable: false },
  { title: 'Gaji PPPK', key: 'pppk', align: 'center', sortable: false },
  { title: 'TPP', key: 'tpp', align: 'center', sortable: false },
]

const detailHeaders = [
  { title: 'No. SP2D', key: 'nomor_sp2d', width: '200px' },
  { title: 'Tanggal', key: 'tanggal_sp2d' },
  { title: 'SKPD (SIPD)', key: 'nama_skpd_sipd' },
  { title: 'Kategori', key: 'jenis_data', align: 'center' },
  { title: 'Nilai Netto', key: 'netto', align: 'end' },
]

const fetchData = async () => {
  if (viewMode.value === 'summary') {
    await fetchStatus()
  } else {
    await fetchTransactions()
  }
}

watch(viewMode, () => {
  fetchData()
})

const fetchStatus = async () => {
  loading.value = true
  try {
    const response = await api.get('/sp2d/status', {
      params: { bulan: selectedMonth.value, tahun: selectedYear.value }
    })
    items.value = response.data.data
  } catch (err) {
    console.error(err)
    showSnackbar('Gagal mengambil data status', 'error')
  } finally {
    loading.value = false
  }
}

const fetchTransactions = async () => {
  loading.value = true
  try {
    const response = await api.get('/sp2d/transactions', {
      params: { bulan: selectedMonth.value, tahun: selectedYear.value }
    })
    transactions.value = response.data.data
  } catch (err) {
    console.error(err)
    showSnackbar('Gagal mengambil data transaksi', 'error')
  } finally {
    loading.value = false
  }
}

const handleFileSelect = (e) => {
  const file = e.target.files[0]
  if (file) uploadFile(file)
}

const handleDrop = (e) => {
  isDragging.value = false
  const file = e.dataTransfer.files[0]
  if (file) uploadFile(file)
}

const uploadFile = async (file) => {
  const formData = new FormData()
  formData.append('file', file)
  
  uploading.value = true
  try {
    const response = await api.post('/sp2d/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    showSnackbar(response.data.message || 'Data berhasil diimpor')
    fetchData()
  } catch (err) {
    showSnackbar(err.response?.data?.message || 'Gagal mengunggah file', 'error')
  } finally {
    uploading.value = false
    if (this.$refs.fileInput) this.$refs.fileInput.value = ''
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

const formatCurrency = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(val || 0)
}

const getTypeColor = (type) => {
  switch (type) {
    case 'PNS': return 'blue'
    case 'PPPK': return 'orange'
    case 'TPP': return 'teal'
    default: return 'grey'
  }
}

const showSnackbar = (text, color = 'success') => {
  snackbarText.value = text
  snackbarColor.value = color
  snackbar.value = true
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.modern-dashboard {
  background-color: rgb(var(--v-theme-background)) !important;
  min-height: 100vh;
}

.glass-card {
  background-color: rgb(var(--v-theme-surface)) !important;
  border: 1px solid rgba(var(--v-border-color), 0.08) !important;
}

.upload-zone {
  border: 2px dashed rgba(var(--v-border-color), 0.2);
  cursor: pointer;
  transition: all 0.3s ease;
}

.upload-zone:hover, .upload-zone.is-dragging {
  border-color: rgb(var(--v-theme-primary));
  background-color: rgba(var(--v-theme-primary), 0.05);
}

.max-width-300 {
  max-width: 300px;
}

.max-width-200 {
  max-width: 200px;
}

.gap-2 {
  gap: 8px;
}

.border-bottom {
  border-bottom: 1px solid rgba(var(--v-border-color), 0.08);
}

.bg-surface-variant-light {
  background-color: rgba(var(--v-theme-surface-variant), 0.05);
}
</style>
