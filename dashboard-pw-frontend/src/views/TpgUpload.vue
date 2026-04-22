<template>
  <div class="modern-dashboard">
    <Navbar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />
    <Sidebar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />

    <v-main class="bg-light">
      <v-container fluid class="pa-8">
        <!-- Header -->
        <v-row class="mb-6 align-center">
          <v-col cols="12">
            <h1 class="text-h4 font-weight-bold mb-1">
              <v-icon start color="deep-purple" size="36">mdi-school-outline</v-icon>
              Upload Data TPG
            </h1>
            <p class="text-subtitle-1 text-grey-darken-1">Upload data Tunjangan Profesi Guru (TPG) per Bulan via Excel.</p>
          </v-col>
        </v-row>

        <v-row>
          <!-- Upload Form -->
          <v-col cols="12" md="7" lg="6">
            <v-card class="glass-card rounded-xl pa-6">
              <v-form @submit.prevent="submitUpload" v-model="valid">
                <v-row>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="selectedMonth"
                      :items="monthOptions"
                      item-title="title"
                      item-value="value"
                      label="Bulan"
                      variant="outlined"
                      density="comfortable"
                      color="deep-purple"
                      prepend-inner-icon="mdi-calendar-month"
                      :rules="[v => !!v || 'Bulan harus dipilih']"
                    ></v-select>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="selectedYear"
                      :items="years"
                      label="Tahun"
                      variant="outlined"
                      density="comfortable"
                      color="deep-purple"
                      prepend-inner-icon="mdi-calendar"
                      :rules="[v => !!v || 'Tahun harus dipilih']"
                    ></v-select>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="selectedJenis"
                      :items="jenisOptions"
                      item-title="title"
                      item-value="value"
                      label="Jenis Upload"
                      variant="outlined"
                      density="comfortable"
                      color="deep-purple"
                      prepend-inner-icon="mdi-tag-outline"
                      :rules="[v => !!v || 'Jenis harus dipilih']"
                    ></v-select>
                    <v-alert
                      v-if="selectedJenis === 'INDUK'"
                      type="warning"
                      variant="tonal"
                      density="compact"
                      class="mt-1 text-caption"
                    >
                      Upload Induk akan <strong>menimpa</strong> data Induk sebelumnya untuk TW & Tahun yang sama.
                    </v-alert>
                    <v-alert
                      v-if="selectedJenis === 'SUSULAN'"
                      type="info"
                      variant="tonal"
                      density="compact"
                      class="mt-1 text-caption"
                    >
                      Upload Susulan akan <strong>menambahkan</strong> data tanpa menghapus data sebelumnya.
                    </v-alert>
                  </v-col>
                </v-row>

                <v-file-input
                  v-model="file"
                  label="Pilih File Excel (.xlsx)"
                  accept=".xlsx,.xls"
                  variant="outlined"
                  density="comfortable"
                  color="deep-purple"
                  prepend-icon=""
                  prepend-inner-icon="mdi-file-excel-outline"
                  show-size
                  :rules="[
                    v => {
                      const f = Array.isArray(v) ? v[0] : v;
                      return !f || f.size < 10000000 || 'Ukuran file maks 10MB';
                    }
                  ]"
                ></v-file-input>

                <div class="d-flex justify-space-between align-center mt-6">
                  <v-btn
                    variant="text"
                    color="deep-purple"
                    prepend-icon="mdi-eye-outline"
                    @click="viewDashboard"
                  >
                    Lihat Dashboard TPG
                  </v-btn>
                  <v-btn
                    type="submit"
                    color="deep-purple"
                    variant="elevated"
                    prepend-icon="mdi-cloud-upload-outline"
                    size="large"
                    :loading="loading"
                    :disabled="!valid || !file"
                    elevation="2"
                  >
                    Upload Data
                  </v-btn>
                </div>
              </v-form>
            </v-card>
          </v-col>

          <!-- Instructions Panel -->
          <v-col cols="12" md="5" lg="6">
            <v-card class="glass-card rounded-xl pa-6 bg-purple-lighten-5">
              <h3 class="text-h6 font-weight-bold mb-3 text-deep-purple-darken-2">
                <v-icon start size="20">mdi-information-outline</v-icon>
                Petunjuk Upload TPG
              </h3>
              <ul class="text-body-2 text-grey-darken-2" style="list-style: none; padding-left: 0;">
                <li class="mb-3 d-flex align-start">
                  <v-icon size="18" color="deep-purple" class="mr-2 mt-1">mdi-numeric-1-circle</v-icon>
                  <span>Pilih <strong>Bulan</strong> dan <strong>Tahun</strong> data TPG.</span>
                </li>
                <li class="mb-3 d-flex align-start">
                  <v-icon size="18" color="deep-purple" class="mr-2 mt-1">mdi-numeric-2-circle</v-icon>
                  <span>Pilih file Excel (.xlsx) yang berisi data TPG.</span>
                </li>
                <li class="mb-3 d-flex align-start">
                  <v-icon size="18" color="deep-purple" class="mr-2 mt-1">mdi-numeric-3-circle</v-icon>
                  <span>File harus memiliki kolom: <strong>NIP, NAMA, NO. REKENING, SATDIK, SALUR BRUT, POT. JKN, SALUR NETT</strong>.</span>
                </li>
                <li class="mb-3 d-flex align-start">
                  <v-icon size="18" color="deep-purple" class="mr-2 mt-1">mdi-numeric-4-circle</v-icon>
                <span>Upload <strong>Induk</strong> untuk bulan & tahun yang sama akan <strong>menimpa</strong> data Induk sebelumnya. Upload <strong>Susulan</strong> akan <strong>menambahkan</strong> data tanpa menghapus data yang ada.</span>
                </li>
                <li class="mb-2 d-flex align-start">
                  <v-icon size="18" color="deep-purple" class="mr-2 mt-1">mdi-numeric-5-circle</v-icon>
                  <span>Ukuran file maksimal <strong>10 MB</strong>.</span>
                </li>
              </ul>

              <v-divider class="my-4"></v-divider>

              <div class="text-caption text-grey-darken-1 mb-3">
                <v-icon size="14" class="mr-1">mdi-lightbulb-outline</v-icon>
                Baris pertama file Excel harus berisi header kolom.
              </div>

              <v-btn
                block
                color="deep-purple"
                variant="outlined"
                prepend-icon="mdi-download-outline"
                @click="downloadTemplate"
                :loading="downloadingTemplate"
              >
                Download Template Excel
              </v-btn>
            </v-card>
          </v-col>
        </v-row>

        <!-- Upload Result Tracker -->
        <v-row v-if="activeJobId" class="mt-4">
          <v-col cols="12" md="7" lg="6">
            <v-card class="glass-card rounded-xl pa-6 border">
              <div class="text-center mb-4">
                <v-icon :color="jobStatusColor" size="48" class="mb-2">
                  {{ jobStatusIcon }}
                </v-icon>
                <div class="text-h6 font-weight-bold">{{ jobStatusLabel }}</div>
                <div class="text-caption text-grey">{{ activeJobFileName }}</div>
              </div>

              <v-progress-linear
                :model-value="activeJobProgress"
                :color="jobStatusColor"
                height="10"
                rounded
                class="mb-4"
                :indeterminate="activeJobStatus === 'processing' && activeJobProgress < 5"
              ></v-progress-linear>
              <div class="text-caption text-center text-grey mb-4">{{ activeJobProgress }}%</div>

              <v-alert v-if="activeJobStatus === 'completed'" type="success" variant="tonal" class="mb-4">
                <div class="font-weight-bold mb-1">Import Berhasil!</div>
                <div class="text-body-2">{{ activeJobResult?.message || 'Data berhasil diproses.' }}</div>
              </v-alert>

              <v-alert v-if="activeJobStatus === 'failed'" type="error" variant="tonal" class="mb-4">
                <div class="font-weight-bold mb-1">Gagal Import</div>
                <div class="text-body-2">{{ activeJobError }}</div>
              </v-alert>

              <v-btn v-if="activeJobStatus === 'completed' || activeJobStatus === 'failed'"
                block color="deep-purple" variant="tonal" @click="resetUpload">
                Upload File Baru
              </v-btn>
            </v-card>
          </v-col>
        </v-row>

        <!-- Snackbar -->
        <v-snackbar
          v-model="snackbar.show"
          :color="snackbar.color"
          timeout="5000"
          location="top right"
        >
          {{ snackbar.message }}
          <template v-slot:actions>
            <v-btn variant="text" @click="snackbar.show = false">Tutup</v-btn>
          </template>
        </v-snackbar>

      </v-container>
    </v-main>
  </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api'
import Navbar from '../components/Navbar.vue'
import Sidebar from '../components/Sidebar.vue'

const router = useRouter()
const loading = ref(false)
const downloadingTemplate = ref(false)
const valid = ref(false)
const file = ref(null)

const selectedMonth = ref(new Date().getMonth() + 1)
const selectedYear = ref(new Date().getFullYear())
const selectedJenis = ref('INDUK')

const monthOptions = [
  { title: 'Januari', value: 1 },
  { title: 'Februari', value: 2 },
  { title: 'Maret', value: 3 },
  { title: 'April', value: 4 },
  { title: 'Mei', value: 5 },
  { title: 'Juni', value: 6 },
  { title: 'Juli', value: 7 },
  { title: 'Agustus', value: 8 },
  { title: 'September', value: 9 },
  { title: 'Oktober', value: 10 },
  { title: 'November', value: 11 },
  { title: 'Desember', value: 12 },
]

const jenisOptions = [
  { title: 'Induk (Data Utama)', value: 'INDUK' },
  { title: 'Susulan (Tambahan)', value: 'SUSULAN' },
]

const years = [2024, 2025, 2026, 2027]

const snackbar = ref({ show: false, message: '', color: 'success' })

// Queue Job Tracking
const activeJobId = ref(null)
const activeJobStatus = ref('')
const activeJobProgress = ref(0)
const activeJobFileName = ref('')
const activeJobResult = ref(null)
const activeJobError = ref('')
let pollInterval = null

const jobStatusColor = computed(() => {
  switch (activeJobStatus.value) {
    case 'pending': return 'grey'
    case 'processing': return 'deep-purple'
    case 'completed': return 'success'
    case 'failed': return 'error'
    default: return 'grey'
  }
})

const jobStatusIcon = computed(() => {
  switch (activeJobStatus.value) {
    case 'pending': return 'mdi-clock-outline'
    case 'processing': return 'mdi-loading mdi-spin'
    case 'completed': return 'mdi-check-circle'
    case 'failed': return 'mdi-alert-circle'
    default: return 'mdi-clock-outline'
  }
})

const jobStatusLabel = computed(() => {
  switch (activeJobStatus.value) {
    case 'pending': return 'Menunggu Antrian...'
    case 'processing': return 'Sedang Memproses...'
    case 'completed': return 'Selesai!'
    case 'failed': return 'Gagal'
    default: return 'Menunggu...'
  }
})

const viewDashboard = () => {
  router.push('/tpg-dashboard')
}

const submitUpload = async () => {
  const fileToUpload = Array.isArray(file.value) ? file.value[0] : file.value
  if (!fileToUpload) return

  loading.value = true
  try {
    const formData = new FormData()
    formData.append('file', fileToUpload)
    formData.append('type', 'tpg')
    formData.append('month', selectedMonth.value)
    formData.append('tahun', selectedYear.value)
    formData.append('jenis', selectedJenis.value)

    const response = await api.post('/upload-jobs', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    activeJobId.value = response.data.data.job_id
    activeJobFileName.value = response.data.data.file_name
    activeJobStatus.value = 'pending'
    activeJobProgress.value = 0
    activeJobResult.value = null
    activeJobError.value = ''
    
    startPolling()
    showSnackbar('File diterima, sedang diproses di background...', 'info')
  } catch (error) {
    const msg = error.response?.data?.message || 'Terjadi kesalahan saat upload.'
    showSnackbar(msg, 'error')
  } finally {
    loading.value = false
  }
}

const startPolling = () => {
  if (pollInterval) clearInterval(pollInterval)
  pollInterval = setInterval(async () => {
    try {
      const res = await api.get(`/upload-jobs/${activeJobId.value}`)
      const job = res.data.data
      activeJobStatus.value = job.status
      activeJobProgress.value = job.progress
      
      if (job.status === 'completed') {
        clearInterval(pollInterval)
        pollInterval = null
        activeJobResult.value = job.result_summary
      } else if (job.status === 'failed') {
        clearInterval(pollInterval)
        pollInterval = null
        activeJobError.value = job.error_message || 'Proses gagal'
      }
    } catch (e) {
      console.error('Polling error:', e)
    }
  }, 3000)
}

const resetUpload = () => {
  activeJobId.value = null
  activeJobStatus.value = ''
  activeJobProgress.value = 0
  activeJobFileName.value = ''
  activeJobResult.value = null
  activeJobError.value = ''
  file.value = null
}

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})

const showSnackbar = (msg, color = 'success') => {
  snackbar.value = { show: true, message: msg, color }
}

const downloadTemplate = async () => {
  downloadingTemplate.value = true
  try {
    const response = await api.get('/tpg/template', { responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'template_upload_tpg.xlsx')
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    console.error('Failed to download template:', e)
    alert('Gagal mendownload template.')
  } finally {
    downloadingTemplate.value = false
  }
}
</script>

<style scoped>
.modern-dashboard {
  background-color: rgb(var(--v-theme-background)) !important;
  font-family: 'Inter', sans-serif;
}
.bg-light {
  background-color: rgb(var(--v-theme-background)) !important;
}
.glass-card {
  background-color: rgb(var(--v-theme-surface)) !important;
  border: 1px solid rgba(var(--v-border-color), 0.08) !important;
  box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07) !important;
}
</style>
