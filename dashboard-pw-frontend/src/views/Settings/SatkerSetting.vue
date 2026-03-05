<template>
  <v-app class="modern-dashboard">
    <Navbar />
    <Sidebar />
    
    <v-main class="bg-light">
      <v-container fluid class="pa-8">
        <v-row class="mb-6 align-center">
          <v-col cols="12">
            <h1 class="text-h4 font-weight-bold mb-1">
              <v-icon start color="primary" size="36">mdi-office-building-cog</v-icon>
              Referensi Satker & SKPD
            </h1>
            <p class="text-subtitle-1 text-medium-emphasis">Kelola pemetaan kode SKPD/Satker ke nama instansi.</p>
          </v-col>
        </v-row>

        <v-row>
          <v-col cols="12" md="4">
            <v-card class="rounded-xl pa-6 glass-card" elevation="0">
              <h2 class="text-h6 font-weight-bold mb-4">Upload File Referensi</h2>
              <p class="text-body-2 text-grey mb-6">
                Upload file Excel (PemeliharaanTabelSatker.xlsx) untuk memperbarui nama SKPD dan Satker di data pegawai.
              </p>
              
              <v-file-input
                v-model="file"
                label="Pilih File Excel"
                prepend-icon="mdi-file-excel"
                variant="outlined"
                accept=".xlsx, .xls"
                :loading="uploading"
                :disabled="uploading || activeJobStatus === 'processing'"
              ></v-file-input>

              <div v-if="activeJobId" class="mt-4 pa-4 rounded-lg border" style="background: rgba(var(--v-theme-primary), 0.08);">
                <div class="d-flex align-center mb-2">
                  <v-icon :color="jobStatusColor" class="mr-2">{{ jobStatusIcon }}</v-icon>
                  <span class="text-caption font-weight-bold">{{ jobStatusLabel }}</span>
                  <v-spacer></v-spacer>
                  <span class="text-caption font-weight-bold">{{ activeJobProgress }}%</span>
                </div>
                <v-progress-linear
                  :model-value="activeJobProgress"
                  :color="jobStatusColor"
                  height="8"
                  rounded
                  :indeterminate="activeJobStatus === 'processing' && activeJobProgress < 5"
                ></v-progress-linear>
              </div>

              <v-btn
                v-if="!activeJobId || activeJobStatus === 'completed' || activeJobStatus === 'failed'"
                block
                color="primary"
                size="large"
                class="mt-4"
                :loading="uploading"
                :disabled="!file"
                @click="handleUpload"
              >
                {{ activeJobStatus === 'completed' || activeJobStatus === 'failed' ? 'RE-UPLOAD DATA' : 'UPDATE DATA REFERENSI' }}
              </v-btn>

              <v-alert v-if="uploadError" type="error" variant="tonal" class="mt-4 rounded-lg">
                {{ uploadError }}
              </v-alert>

              <v-alert v-if="activeJobStatus === 'completed' && activeJobResult" type="success" variant="tonal" class="mt-4 rounded-lg">
                {{ activeJobResult.message }}
              </v-alert>
            </v-card>
          </v-col>

          <v-col cols="12" md="8">
            <v-card class="rounded-xl glass-card" elevation="0">
              <v-data-table-server
                v-model:items-per-page="itemsPerPage"
                :headers="headers"
                :items="items"
                :items-length="totalItems"
                :loading="loading"
                @update:options="fetchData"
                class="modern-table"
              >
                <template v-slot:item.kdskpd="{ item }">
                  <div class="font-weight-bold">{{ item.kdskpd }}</div>
                  <div class="text-caption text-grey">{{ item.nmskpd }}</div>
                </template>
                <template v-slot:item.kdsatker="{ item }">
                  <div class="font-weight-bold">{{ item.kdsatker }}</div>
                  <div class="text-caption text-grey">{{ item.nmsatker }}</div>
                </template>
              </v-data-table-server>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue'
import api from '../../api'
import Sidebar from '../../components/Sidebar.vue'
import Navbar from '../../components/Navbar.vue'

const file = ref(null)
const uploading = ref(false)
const uploadError = ref(null)
const loading = ref(false)
const items = ref([])
const totalItems = ref(0)
const itemsPerPage = ref(10)

const headers = [
  { title: 'SKPD', key: 'kdskpd', sortable: false },
  { title: 'Satker', key: 'kdsatker', sortable: false },
]

// Job Tracking State
const activeJobId = ref(null)
const activeJobStatus = ref('')
const activeJobProgress = ref(0)
const activeJobResult = ref(null)
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
    case 'completed': return 'Update Selesai!'
    case 'failed': return 'Gagal'
    default: return ''
  }
})

const fetchData = async ({ page, itemsPerPage }) => {
  loading.value = true
  try {
    const response = await api.get('/settings/satker-list', {
      params: { page, limit: itemsPerPage }
    })
    items.value = response.data.data
    totalItems.value = response.data.total
  } catch (error) {
    console.error('Error fetching satker list:', error)
  } finally {
    loading.value = false
  }
}

const handleUpload = async () => {
  if (!file.value) return
  
  uploading.value = true
  uploadError.value = null
  
  const formData = new FormData()
  formData.append('file', file.value)
  formData.append('type', 'satker_ref')
  
  try {
    const response = await api.post('/upload-jobs', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    if (response.data.data?.job_id) {
      activeJobId.value = response.data.data.job_id
      activeJobStatus.value = 'pending'
      activeJobProgress.value = 0
      startPolling(response.data.data.job_id)
    }
  } catch (error) {
    uploadError.value = error.response?.data?.message || 'Gagal mengupload file.'
    uploading.value = false
  }
}

const startPolling = (jobId) => {
  if (pollInterval) clearInterval(pollInterval)
  pollInterval = setInterval(async () => {
    try {
      const response = await api.get(`/upload-jobs/${jobId}`)
      const job = response.data.data
      
      activeJobStatus.value = job.status
      activeJobProgress.value = job.progress
      
      if (job.status === 'completed') {
        clearInterval(pollInterval)
        pollInterval = null
        uploading.value = false
        activeJobResult.value = job.result_summary
        fetchData({ page: 1, itemsPerPage: itemsPerPage.value })
      } else if (job.status === 'failed') {
        clearInterval(pollInterval)
        pollInterval = null
        uploading.value = false
        uploadError.value = job.error_message || 'Proses gagal.'
      }
    } catch (error) {
      console.error('Polling error:', error)
      clearInterval(pollInterval)
      pollInterval = null
      uploading.value = false
    }
  }, 2000)
}
</script>

<style scoped>
.glass-card {
  background: rgba(var(--v-theme-surface), 0.95) !important;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(var(--v-border-color), 0.08) !important;
}
</style>
