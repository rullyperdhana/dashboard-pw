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
              <v-icon start color="primary" size="36">mdi-account-plus-outline</v-icon>
              Update NIK Master Pegawai
            </h1>
            <p class="text-subtitle-1 text-grey-darken-1">Memasukan NIK (No. KTP) secara massal ke dalam data Master Pegawai berdasarkan NIP.</p>
          </v-col>
        </v-row>

        <v-row>
          <v-col cols="12" md="8" lg="6">
            <v-card class="glass-card rounded-xl pa-6">
              <v-form @submit.prevent="submitUpload" v-model="valid">
                <v-alert type="info" variant="tonal" class="mb-6 rounded-lg" border="start" density="comfortable">
                  Pastikan file Anda memiliki header kolom <strong>nip</strong> dan <strong>nik</strong>.
                  <template v-slot:append>
                    <v-btn
                      variant="text"
                      color="primary"
                      prepend-icon="mdi-download"
                      @click="downloadTemplate"
                      class="text-none"
                    >
                      Download Contoh File
                    </v-btn>
                  </template>
                </v-alert>

                <v-file-input
                  v-model="file"
                  label="Pilih File (Excel atau CSV)"
                  accept=".xlsx,.xls,.csv"
                  variant="outlined"
                  color="primary"
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

                <div class="d-flex justify-end align-center mt-6">
                  <v-btn
                    type="submit"
                    color="primary"
                    size="large"
                    :loading="loading"
                    :disabled="!valid || !file"
                    elevation="2"
                    rounded="xl"
                    min-width="160"
                  >
                    PROSES UPDATE
                  </v-btn>
                </div>
              </v-form>
            </v-card>
          </v-col>

          <v-col cols="12" md="4" lg="6">
             <v-card class="glass-card rounded-xl pa-6 bg-blue-lighten-5">
                <h3 class="text-h6 font-weight-bold mb-2 text-blue-darken-2">Petunjuk Format File</h3>
                <ul class="pl-4 text-body-2 text-grey-darken-3">
                    <li class="mb-2">File dapat berupa <strong>Excel (.xlsx)</strong> atau <strong>CSV</strong>.</li>
                    <li class="mb-2">Baris pertama harus berisi header: <strong>nip, nik</strong>.</li>
                    <li class="mb-2">Kolom <strong>nip</strong> digunakan sebagai kunci pencarian di database.</li>
                    <li class="mb-2">Sistem akan mengupdate kolom <strong>noktp</strong> di tabel Master Pegawai.</li>
                    <li class="mb-2">Pastikan kolom <strong>nip</strong> sudah terdaftar di Master Pegawai.</li>
                </ul>
             </v-card>
          </v-col>
        </v-row>
      
        <!-- Upload Status Tracker -->
        <v-row v-if="activeJobId" class="mt-4">
          <v-col cols="12" md="8" lg="6">
            <v-card class="glass-card rounded-xl pa-6 border-primary">
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
                <div class="font-weight-bold mb-1">Berhasil!</div>
                <div class="text-body-2">{{ activeJobResult?.message || 'NIK berhasil diperbarui.' }}</div>
                <div v-if="activeJobResult?.not_found_count > 0" class="text-caption mt-2 text-error">
                   {{ activeJobResult.not_found_count }} NIP tidak ditemukan.
                </div>
              </v-alert>

              <v-alert v-if="activeJobStatus === 'failed'" type="error" variant="tonal" class="mb-4">
                <div class="font-weight-bold mb-1">Gagal</div>
                <div class="text-body-2">{{ activeJobError }}</div>
              </v-alert>

              <v-btn v-if="activeJobStatus === 'completed' || activeJobStatus === 'failed'"
                block color="primary" variant="tonal" @click="resetUpload" rounded="xl">
                Upload Data Lain
              </v-btn>
            </v-card>
          </v-col>
        </v-row>
      
        <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="5000" location="top right">
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
import api from '../../api'
import Navbar from '../../components/Navbar.vue'
import Sidebar from '../../components/Sidebar.vue'

const loading = ref(false)
const valid = ref(false)
const file = ref([])

const snackbar = ref({
    show: false,
    message: '',
    color: 'success'
})

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
    case 'processing': return 'primary'
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
    formData.append('type', 'nik_update')

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
        file.value = []
        
        startPolling()
        showSnackbar('Pesan diterima, sedang memproses di background...', 'info')
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
      }
    } catch (e) {
      console.error('Polling error:', e)
    }
  }, 2000)
}

const resetUpload = () => {
  activeJobId.value = null
  activeJobStatus.value = ''
  activeJobProgress.value = 0
  activeJobFileName.value = ''
  activeJobResult.value = null
  activeJobError.value = ''
}

const downloadTemplate = async () => {
  try {
    const response = await api.get('/master/pegawai/template-nik', {
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'template_update_nik.xlsx')
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } catch (error) {
    showSnackbar('Gagal mendownload template', 'error')
  }
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
  background-color: #f8fafc;
  font-family: 'Inter', sans-serif;
}

.glass-card {
  background: white !important;
  border: 1px solid rgba(0,0,0,0.05) !important;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06) !important;
}

.border-primary {
  border-top: 4px solid rgb(var(--v-theme-primary)) !important;
}
</style>
