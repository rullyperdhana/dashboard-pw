<template>
  <div class="modern-dashboard">
    <Navbar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />
    <Sidebar />

    <v-main class="bg-dashboard">
      <v-container fluid class="pa-6 pa-md-10">
        <!-- Header -->
        <header class="dashboard-header mb-8">
          <v-row align="center">
            <v-col cols="12">
              <div class="d-flex align-center mb-2">
                <v-avatar color="primary-lighten-4" size="48" class="mr-4">
                  <v-icon color="primary" size="28">mdi-account-plus-outline</v-icon>
                </v-avatar>
                <div>
                  <h1 class="text-h4 font-weight-black tracking-tight text-high-emphasis">Update NIK Master Pegawai</h1>
                  <p class="text-subtitle-1 text-medium-emphasis">Memasukan NIK secara massal ke dalam data Master Pegawai berdasarkan NIP.</p>
                </div>
              </div>
            </v-col>
          </v-row>
        </header>

        <v-row>
          <v-col cols="12" md="7">
            <v-card class="glass-panel pa-8" elevation="0">
              <v-form @submit.prevent="submitUpload" v-model="valid">
                <div class="mb-8">
                  <div class="text-overline font-weight-black mb-2 text-primary">UPLOAD FILE SUMBER</div>
                  <p class="text-caption text-medium-emphasis mb-6">
                    Sistem akan mencocokkan **NIP** yang ada di file dengan database dan memperbarui kolom **NIK (No. KTP)**.
                  </p>
                  
                  <v-alert type="info" variant="tonal" class="mb-8 rounded-xl border-0" density="comfortable">
                    <template v-slot:prepend>
                      <v-icon size="24" class="mr-2">mdi-information-outline</v-icon>
                    </template>
                    <div class="text-body-2">
                      Pastikan file Anda memiliki header kolom <strong>nip</strong> dan <strong>nik</strong>.
                    </div>
                  </v-alert>

                  <v-file-input
                    v-model="file"
                    label="Pilih File (Excel atau CSV)"
                    accept=".xlsx,.xls,.csv"
                    variant="filled"
                    flat
                    rounded="lg"
                    prepend-inner-icon="mdi-file-excel"
                    show-size
                    class="custom-file-input"
                    :rules="[
                      v => !!v && (Array.isArray(v) ? v.length > 0 : true) || 'File harus dipilih',
                      v => {
                          const f = Array.isArray(v) ? v[0] : v;
                          return !f || f.size < 5000000 || 'Ukuran file maks 5MB';
                      }
                    ]"
                  ></v-file-input>
                </div>

                <div class="d-flex justify-space-between align-center">
                  <v-btn
                    variant="text"
                    color="primary"
                    prepend-icon="mdi-download"
                    @click="downloadTemplate"
                    class="text-none font-weight-bold"
                  >
                    Download Template
                  </v-btn>

                  <v-btn
                    type="submit"
                    color="primary"
                    size="large"
                    :loading="loading"
                    :disabled="!valid || !file"
                    variant="flat"
                    rounded="pill"
                    class="px-10 font-weight-black"
                  >
                    PROSES UPDATE
                  </v-btn>
                </div>
              </v-form>
            </v-card>
          </v-col>

          <v-col cols="12" md="5">
             <v-card class="glass-panel pa-8 border-dashed" elevation="0">
                <div class="d-flex align-center mb-6">
                  <v-avatar color="info-lighten-4" size="36" class="mr-4">
                    <v-icon color="info" size="20">mdi-script-text-outline</v-icon>
                  </v-avatar>
                  <h3 class="text-h6 font-weight-black text-high-emphasis">Petunjuk Format</h3>
                </div>
                
                <v-list density="compact" class="bg-transparent pa-0">
                  <v-list-item class="px-0 mb-4 align-start">
                    <template v-slot:prepend>
                      <v-avatar color="surface" size="24" class="mr-3 border">
                        <span class="text-caption font-weight-bold">1</span>
                      </v-avatar>
                    </template>
                    <v-list-item-title class="text-body-2 font-weight-bold mb-1">Tipe File Didukung</v-list-item-title>
                    <v-list-item-subtitle class="text-caption text-medium-emphasis text-wrap">Gunakan format **.xlsx** (Excel Modern) atau **.csv**.</v-list-item-subtitle>
                  </v-list-item>

                  <v-list-item class="px-0 mb-4 align-start">
                    <template v-slot:prepend>
                      <v-avatar color="surface" size="24" class="mr-3 border">
                        <span class="text-caption font-weight-bold">2</span>
                      </v-avatar>
                    </template>
                    <v-list-item-title class="text-body-2 font-weight-bold mb-1">Header Wajib</v-list-item-title>
                    <v-list-item-subtitle class="text-caption text-medium-emphasis text-wrap">Baris pertama harus berisi kolom **nip** dan **nik** (Case Sensitive).</v-list-item-subtitle>
                  </v-list-item>

                  <v-list-item class="px-0 mb-4 align-start">
                    <template v-slot:prepend>
                      <v-avatar color="surface" size="24" class="mr-3 border">
                        <span class="text-caption font-weight-bold">3</span>
                      </v-avatar>
                    </template>
                    <v-list-item-title class="text-body-2 font-weight-bold mb-1">Mekanisme Update</v-list-item-title>
                    <v-list-item-subtitle class="text-caption text-medium-emphasis text-wrap">Sistem akan mencocokkan NIP dan memperbarui kolom **noktp** di tabel Master Pegawai.</v-list-item-subtitle>
                  </v-list-item>
                </v-list>
             </v-card>
          </v-col>
        </v-row>
      
        <!-- Upload Status Tracker -->
        <v-expand-transition>
          <v-row v-if="activeJobId" class="mt-8">
            <v-col cols="12" md="7">
              <v-card class="glass-panel pa-8 overflow-hidden" elevation="0">
                <div class="d-flex align-center mb-6">
                  <v-progress-circular
                    :model-value="activeJobProgress"
                    :rotate="-90"
                    :size="64"
                    :width="6"
                    :color="jobStatusColor"
                    class="mr-4"
                  >
                    <v-icon :icon="jobStatusIcon" size="24"></v-icon>
                  </v-progress-circular>
                  <div>
                    <div class="text-h6 font-weight-black text-high-emphasis">{{ jobStatusLabel }}</div>
                    <div class="text-caption text-medium-emphasis">{{ activeJobFileName }}</div>
                  </div>
                </div>

                <v-progress-linear
                  :model-value="activeJobProgress"
                  :color="jobStatusColor"
                  height="8"
                  rounded
                  class="mb-8"
                  :indeterminate="activeJobStatus === 'processing' && activeJobProgress < 5"
                ></v-progress-linear>

                <v-fade-transition mode="out-in">
                  <div :key="activeJobStatus">
                    <v-alert v-if="activeJobStatus === 'completed'" type="success" variant="tonal" class="mb-6 rounded-xl border-0">
                      <div class="font-weight-bold mb-1 d-flex align-center">
                        <v-icon start icon="mdi-check-decagram"></v-icon> Update Berhasil
                      </div>
                      <div class="text-body-2">{{ activeJobResult?.message || 'NIK pegawai telah berhasil diperbarui ke sistem.' }}</div>
                      <div v-if="activeJobResult?.not_found_count > 0" class="text-caption mt-2 font-weight-bold text-error">
                         ⚠️ Terdapat {{ activeJobResult.not_found_count }} NIP yang tidak ditemukan dalam database.
                      </div>
                    </v-alert>

                    <v-alert v-if="activeJobStatus === 'failed'" type="error" variant="tonal" class="mb-6 rounded-xl border-0">
                      <div class="font-weight-bold mb-1">Terjadi Kesalahan</div>
                      <div class="text-body-2">{{ activeJobError }}</div>
                    </v-alert>

                    <v-btn v-if="activeJobStatus === 'completed' || activeJobStatus === 'failed'"
                      block color="primary" variant="flat" size="large" @click="resetUpload" rounded="pill" class="font-weight-black">
                      Import File Lain
                    </v-btn>
                  </div>
                </v-fade-transition>
              </v-card>
            </v-col>
          </v-row>
        </v-expand-transition>
      
        <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="5000" location="top right" class="rounded-lg">
            <div class="d-flex align-center">
              <v-icon start icon="mdi-information" class="mr-2"></v-icon>
              {{ snackbar.message }}
            </div>
            <template v-slot:actions>
              <v-btn variant="text" @click="snackbar.show = false" icon="mdi-close"></v-btn>
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
    case 'pending': return 'warning'
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
    case 'pending': return 'Dalam Antrian...'
    case 'processing': return 'Memproses Update...'
    case 'completed': return 'Selesai!'
    case 'failed': return 'Proses Gagal'
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
  min-height: 100vh;
}

.bg-dashboard {
  background-color: rgb(var(--v-theme-background));
  background-image: 
    radial-gradient(at 0% 0%, rgba(var(--v-theme-primary), 0.05) 0, transparent 50%),
    radial-gradient(at 100% 100%, rgba(var(--v-theme-info), 0.05) 0, transparent 50%);
}

.glass-panel {
  background: rgba(var(--v-theme-surface), 0.7) !important;
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(var(--v-border-color), 0.1) !important;
  border-radius: 28px !important;
  box-shadow: 0 4px 10px -2px rgba(0, 0, 0, 0.03), 0 2px 5px -2px rgba(0, 0, 0, 0.02) !important;
}

.border-dashed {
  border-style: dashed !important;
}

.custom-file-input :deep(.v-field) {
  background: rgba(var(--v-theme-on-surface), 0.03) !important;
  border-radius: 16px !important;
}

.text-wrap {
  white-space: normal !important;
}
</style>
