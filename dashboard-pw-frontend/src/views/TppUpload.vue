<template>
  <v-app class="modern-dashboard">
    <Navbar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />
    <Sidebar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />

    <v-main class="bg-light">
      <v-container fluid class="pa-8">
        <!-- Header -->
        <v-row class="mb-6 align-center">
          <v-col cols="12">
             <h1 class="text-h4 font-weight-bold mb-1">
              <v-icon start color="teal" size="36">mdi-upload-multiple</v-icon>
              Upload Data TPP
            </h1>
            <p class="text-subtitle-1 text-grey-darken-1">Upload data Tunjangan Penghasilan Pegawai (TPP) via Excel.</p>
          </v-col>
        </v-row>

        <v-row>
          <v-col cols="12" md="8" lg="6">
            <v-card class="glass-card rounded-xl pa-6">
              <v-form @submit.prevent="submitUpload" v-model="valid">
                <v-row>
                    <v-col cols="12" md="6">
                        <v-select
                        v-model="selectedMonth"
                        :items="months"
                        item-title="title"
                        item-value="value"
                        label="Bulan"
                        variant="outlined"
                        color="teal"
                        prepend-inner-icon="mdi-calendar"
                        :rules="[v => !!v || 'Bulan harus dipilih']"
                        ></v-select>
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-select
                        v-model="selectedYear"
                        :items="years"
                        label="Tahun"
                        variant="outlined"
                        color="teal"
                        prepend-inner-icon="mdi-calendar"
                        :rules="[v => !!v || 'Tahun harus dipilih']"
                        ></v-select>
                    </v-col>
                </v-row>

                <v-select
                  v-model="employeeType"
                  :items="employeeTypes"
                  item-title="title"
                  item-value="value"
                  label="Tipe Pegawai"
                  variant="outlined"
                  color="teal"
                  class="mt-2"
                  prepend-inner-icon="mdi-account-group"
                  :rules="[v => !!v || 'Tipe pegawai harus dipilih']"
                ></v-select>

                <v-file-input
                  v-model="file"
                  label="Pilih File Excel"
                  accept=".xlsx,.xls,.csv"
                  variant="outlined"
                  color="teal"
                  class="mt-2"
                  prepend-inner-icon="mdi-file-excel"
                  show-size
                  :rules="[
                    v => !!v && (Array.isArray(v) ? v.length > 0 : true) || 'File harus dipilih',
                    v => {
                        const f = Array.isArray(v) ? v[0] : v;
                        return !f || f.size < 5000000 || 'Ukuran file maks 5MB';
                    }
                  ]"
                ></v-file-input>

                <div class="d-flex justify-space-between align-center mt-6">
                   <v-btn
                    variant="text"
                    color="primary"
                    prepend-icon="mdi-download"
                    @click="downloadTemplate"
                  >
                    Download Template
                  </v-btn>

                  <v-btn
                    type="submit"
                    color="teal"
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

          <v-col cols="12" md="4" lg="6">
             <v-card class="glass-card rounded-xl pa-6 bg-teal-lighten-5">
                <h3 class="text-h6 font-weight-bold mb-2 text-teal-darken-2">Petunjuk Upload</h3>
                <ul class="pl-4 text-body-2 text-grey-darken-3">
                    <li class="mb-2">Pastikan file Excel memiliki header: <strong>NIP, NAMA, NILAI</strong>.</li>
                    <li class="mb-2">Kolom <strong>NIP</strong> wajib diisi dan harus sesuai dengan data pegawai.</li>
                    <li class="mb-2">Kolom <strong>NILAI</strong> berisi nominal TPP (contoh: 1500000 atau 1.500.000).</li>
                    <li class="mb-2">Sistem akan otomatis menghitung ulang Total Tunjangan, Gaji Kotor, dan Gaji Bersih.</li>
                    <li class="mb-2">Format file yang didukung: .xlsx, .xls, .csv.</li>
                </ul>
             </v-card>
          </v-col>
        </v-row>
      
        <!-- Upload Status Tracker -->
        <v-row v-if="activeJobId" class="mt-4">
          <v-col cols="12" md="8" lg="6">
            <v-card class="glass-card rounded-xl pa-6">
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
                :indeterminate="activeJobStatus === 'processing' && activeJobProgress < 10"
              ></v-progress-linear>
              <div class="text-caption text-center text-grey mb-4">{{ activeJobProgress }}%</div>

              <v-alert v-if="activeJobStatus === 'completed'" type="success" variant="tonal" class="mb-4">
                <div class="font-weight-bold mb-1">Import Berhasil!</div>
                <div class="text-body-2">{{ activeJobResult?.message || 'Data berhasil diproses.' }}</div>
              </v-alert>

              <v-alert v-if="activeJobStatus === 'failed'" type="error" variant="tonal" class="mb-4">
                <div class="font-weight-bold mb-1">Gagal Import</div>
                <div class="text-body-2">{{ activeJobError }}</div>
                <v-expansion-panels v-if="activeJobErrorDetail" variant="accordion" class="mt-2">
                  <v-expansion-panel>
                    <v-expansion-panel-title class="text-caption">Lihat Detail Error</v-expansion-panel-title>
                    <v-expansion-panel-text>
                      <pre class="text-caption" style="white-space: pre-wrap; font-family: monospace;">{{ activeJobErrorDetail }}</pre>
                    </v-expansion-panel-text>
                  </v-expansion-panel>
                </v-expansion-panels>
              </v-alert>

              <v-btn v-if="activeJobStatus === 'completed' || activeJobStatus === 'failed'"
                block color="teal" variant="tonal" @click="resetUpload">
                Upload File Baru
              </v-btn>
            </v-card>
          </v-col>
        </v-row>
      
        <!-- Snackbar for notifications -->
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
  </v-app>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue'
import api from '../api'
import Navbar from '../components/Navbar.vue'
import Sidebar from '../components/Sidebar.vue'

const loading = ref(false)
const valid = ref(false)
const file = ref([])
const selectedMonth = ref(new Date().getMonth() + 1)
const selectedYear = ref(new Date().getFullYear())
const employeeType = ref('pns')

const snackbar = ref({
    show: false,
    message: '',
    color: 'success'
})

const months = [
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

const years = [2024, 2025, 2026, 2027]

const employeeTypes = [
    { title: 'PNS', value: 'pns' },
    { title: 'PPPK', value: 'pppk' }
]

const downloadTemplate = async () => {
    try {
        const response = await api.get('/tpp/template', { responseType: 'blob' })
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', 'template_upload_tpp.xlsx')
        document.body.appendChild(link)
        link.click()
        link.remove()
    } catch (error) {
        showSnackbar('Gagal download template', 'error')
    }
}

// Queue Job Tracking
const activeJobId = ref(null)
const activeJobStatus = ref('')
const activeJobProgress = ref(0)
const activeJobFileName = ref('')
const activeJobResult = ref(null)
const activeJobError = ref('')
const activeJobErrorDetail = ref('')
let pollInterval = null

const jobStatusColor = computed(() => {
  switch (activeJobStatus.value) {
    case 'pending': return 'grey'
    case 'processing': return 'teal'
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

const submitUpload = async () => {
    const fileToUpload = Array.isArray(file.value) ? file.value[0] : file.value
    if (!fileToUpload) return

    loading.value = true
    const formData = new FormData()
    formData.append('file', fileToUpload)
    formData.append('type', 'tpp')
    formData.append('month', selectedMonth.value)
    formData.append('year', selectedYear.value)
    formData.append('tpp_type', employeeType.value)

    try {
        const response = await api.post('/upload-jobs', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        
        activeJobId.value = response.data.data.job_id
        activeJobFileName.value = response.data.data.file_name
        activeJobStatus.value = 'pending'
        activeJobProgress.value = 0
        activeJobResult.value = null
        activeJobError.value = ''
        activeJobErrorDetail.value = ''
        file.value = []
        
        startPolling()
        showSnackbar('File diterima, sedang diproses di background...', 'info')
    } catch (error) {
        const msg = error.response?.data?.message || 'Gagal mengirim file'
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
        activeJobErrorDetail.value = job.error_detail || ''
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
  activeJobErrorDetail.value = ''
}

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})

const showSnackbar = (msg, color = 'success') => {
    snackbar.value.message = msg
    snackbar.value.color = color
    snackbar.value.show = true
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
