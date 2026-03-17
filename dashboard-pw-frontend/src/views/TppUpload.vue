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

                <v-select
                  v-model="selectedJenisGaji"
                  :items="jenisGajiOptions"
                  label="Jenis TPP"
                  variant="outlined"
                  color="teal"
                  class="mt-2"
                  prepend-inner-icon="mdi-cash-multiple"
                  :rules="[v => !!v || 'Jenis TPP harus dipilih']"
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
                    v-if="!validationResult"
                    color="teal"
                    size="large"
                    :loading="validating"
                    :disabled="!valid || !file"
                    elevation="2"
                    @click="validateFile"
                  >
                    Validasi File
                  </v-btn>

                  <v-btn
                    v-else
                    type="submit"
                    color="success"
                    size="large"
                    :loading="loading"
                    elevation="2"
                  >
                    Konfirmasi & Upload
                  </v-btn>
                </div>
              </v-form>
            </v-card>

            <!-- Preview Data -->
            <v-card v-if="validationResult && validationResult.success" class="glass-card rounded-xl pa-6 mt-4 animate__animated animate__fadeIn">
              <div class="d-flex align-center justify-space-between mb-4">
                <h3 class="text-h6 font-weight-bold text-teal">
                  <v-icon start>mdi-table-eye</v-icon>
                  Preview Data (5 Baris Pertama)
                </h3>
                <v-btn icon="mdi-close" variant="text" size="small" @click="validationResult = null"></v-btn>
              </div>
              <v-table density="compact" class="preview-table">
                <thead>
                  <tr>
                    <th v-for="h in validationResult.preview[0]" :key="h">{{ h }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, i) in validationResult.preview.slice(1)" :key="i">
                    <td v-for="(col, j) in row" :key="j">{{ col }}</td>
                  </tr>
                </tbody>
              </v-table>
              <v-alert type="success" variant="tonal" density="compact" class="mt-4 text-caption">
                Format Header Sesuai. Klik tombol hijau di atas untuk memproses seluruh data.
              </v-alert>
            </v-card>

            <!-- Error Validation -->
            <v-alert v-if="validationResult && !validationResult.success" type="error" variant="tonal" class="mt-4 rounded-xl">
              <div class="font-weight-bold">Format File Tidak Sesuai</div>
              <div>{{ validationResult.message }}</div>
              <v-btn variant="text" size="small" class="mt-2" @click="validationResult = null">Ganti File</v-btn>
            </v-alert>
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
                <v-divider class="my-4"></v-divider>
                <div class="text-caption mb-2">Ingin melihat laporan sebelumnya?</div>
                <v-btn
                  block
                  color="warning"
                  variant="outlined"
                  size="small"
                  prepend-icon="mdi-history"
                  to="/tpp/discrepancy-history"
                >
                  Riwayat Selisih TPP
                </v-btn>
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

        <!-- Discrepancy Report -->
        <v-row v-if="discrepancies.length > 0 || (activeJobStatus === 'completed' && !loadingDiscrepancies)" class="mt-8">
          <v-col cols="12">
            <v-card class="glass-card rounded-xl pa-6 border-warning">
              <div class="d-flex align-center justify-space-between mb-4">
                <div>
                  <h2 class="text-h5 font-weight-bold text-warning">
                    <v-icon start color="warning" size="32">mdi-alert-circle-outline</v-icon>
                    Laporan Selisih TPP
                  </h2>
                  <p class="text-caption text-grey-darken-1">Pegawai yang ada di data Gaji tapi TIDAK DITEMUKAN di file Excel TPP periode ini.</p>
                </div>
                <v-btn
                  v-if="discrepancies.length > 0"
                  color="warning"
                  variant="tonal"
                  prepend-icon="mdi-file-export-outline"
                  @click="exportDiscrepancies"
                >
                  Export Laporan
                </v-btn>
              </div>

              <v-data-table
                :headers="discrepancyHeaders"
                :items="discrepancies"
                density="comfortable"
                class="bg-transparent"
                :loading="loadingDiscrepancies"
              >
                <template v-slot:no-data>
                   <v-alert type="success" variant="tonal" density="compact" class="mx-4 my-2">
                        Tidak ada selisih. Semua pegawai di database gaji terdaftar dalam file TPP.
                   </v-alert>
                </template>
              </v-data-table>
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
  </div>
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
const selectedJenisGaji = ref('Induk')
const jenisGajiOptions = ['Induk', 'THR', 'Gaji 13']

const discrepancies = ref([])
const loadingDiscrepancies = ref(false)
const discrepancyHeaders = [
  { title: 'NIP', key: 'nip', sortable: true },
  { title: 'NAMA', key: 'nama', sortable: true },
  { title: 'SKPD', key: 'skpd', sortable: true },
  { title: 'KETERANGAN', key: 'reason' },
]

const fetchDiscrepancies = async () => {
  loadingDiscrepancies.value = true
  try {
    const res = await api.get('/tpp/discrepancies', {
      params: {
        month: selectedMonth.value,
        year: selectedYear.value,
        type: employeeType.value
      }
    })
    discrepancies.value = res.data.data
  } catch (error) {
    console.error('Failed to fetch discrepancies', error)
  } finally {
    loadingDiscrepancies.value = false
  }
}

const exportDiscrepancies = () => {
  // Simple CSV export
  const headers = ['NIP', 'NAMA', 'SKPD', 'KETERANGAN']
  const rows = discrepancies.value.map(d => [d.nip, d.nama, d.skpd, d.reason])
  
  let csvContent = "data:text/csv;charset=utf-8," 
    + headers.join(",") + "\n"
    + rows.map(e => e.join(",")).join("\n");

  const encodedUri = encodeURI(csvContent);
  const link = document.createElement("a");
  link.setAttribute("href", encodedUri);
  link.setAttribute("download", `Laporan_Selisih_TPP_${selectedMonth.value}_${selectedYear.value}.csv`);
  document.body.appendChild(link);
  link.click();
  link.remove();
}

const validating = ref(false)
const validationResult = ref(null)

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

const validateFile = async () => {
    const fileToValidate = Array.isArray(file.value) ? file.value[0] : file.value
    if (!fileToValidate) return

    validating.value = true
    const formData = new FormData()
    formData.append('file', fileToValidate)

    try {
        const response = await api.post('/tpp/validate-upload', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        validationResult.value = response.data
        if (response.data.success) {
            showSnackbar('File valid! Silakan cek preview sebelum upload.', 'success')
        } else {
            showSnackbar(response.data.message, 'error')
        }
    } catch (error) {
        showSnackbar('Gagal memvalidasi file', 'error')
    } finally {
        validating.value = false
    }
}

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
    formData.append('jenis_gaji', selectedJenisGaji.value)

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
        validationResult.value = null
        
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
        fetchDiscrepancies()
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
  discrepancies.value = []
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

.preview-table {
  background: transparent !important;
}

.preview-table :deep(th) {
  font-weight: bold !important;
  color: rgb(var(--v-theme-primary)) !important;
  text-transform: uppercase;
  font-size: 0.75rem;
}

.preview-table :deep(td) {
  font-size: 0.85rem;
}

.glass-card {
  background-color: rgb(var(--v-theme-surface)) !important;
  border: 1px solid rgba(var(--v-border-color), 0.08) !important;
  box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07) !important;
}

.border-warning {
  border-left: 6px solid rgb(var(--v-theme-warning)) !important;
}
</style>
