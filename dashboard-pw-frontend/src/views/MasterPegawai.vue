<template>
  <v-app class="modern-dashboard">
    <Navbar />
    <Sidebar />
    
    <v-main class="bg-light">
      <v-container fluid class="pa-8">
        <v-row class="mb-6 align-center">
          <v-col cols="12" md="6">
            <h1 class="text-h4 font-weight-bold mb-1">
              <v-icon start color="primary" size="36">mdi-database-import</v-icon>
              Master Pegawai & Keluarga
            </h1>
            <p class="text-subtitle-1 text-medium-emphasis">Kelola data master pegawai dari file DBF.</p>
          </v-col>
          <v-col cols="12" md="6" class="text-right">
            <v-btn color="primary" prepend-icon="mdi-upload" @click="uploadDialog = true">
              Upload DBF
            </v-btn>
          </v-col>
        </v-row>

    <!-- Status Distribution Ribbon -->
    <v-card class="mb-6 rounded-xl overflow-hidden stat-ribbon-card" elevation="0" border>
      <v-card-text class="pa-6">
        <v-row align="center">
          <v-col cols="12" md="3" class="border-md-right">
            <div class="text-overline text-grey-darken-1 mb-1">TOTAL PEGAWAI</div>
            <div class="d-flex align-baseline">
              <span class="text-h3 font-weight-black primary--text">{{ (stats.summary?.total || 0).toLocaleString() }}</span>
              <span class="text-caption text-grey ml-2">Orang</span>
            </div>
            <div class="mt-2 d-flex align-center cursor-pointer" @click="clearCardFilter">
              <v-icon size="small" :color="activeFilter === 'all' ? 'primary' : 'grey'">
                {{ activeFilter === 'all' ? 'mdi-filter-check' : 'mdi-filter-off-outline' }}
              </v-icon>
              <span class="text-caption ml-1" :class="activeFilter === 'all' ? 'text-primary font-weight-bold' : 'text-grey'">
                {{ activeFilter === 'all' ? 'Semua Data Terpilih' : 'Klik untuk Reset Filter' }}
              </span>
            </div>
          </v-col>
          
          <v-col cols="12" md="9" class="pl-md-8">
            <div class="text-overline text-grey-darken-1 mb-4 d-flex justify-space-between align-center">
              <span>DISTRIBUSI STATUS PEGAWAI</span>
              <span v-if="activeFilter !== 'all'" class="text-primary font-weight-bold">
                Filter Aktif: {{ activeFilterLabel }}
              </span>
            </div>
            
            <!-- The Ribbon Bar -->
            <div class="ribbon-container mb-6">
              <div 
                v-for="(seg, idx) in ribbonSegments" 
                :key="idx"
                class="ribbon-segment"
                :style="{ 
                  width: seg.percent + '%', 
                  backgroundColor: seg.color,
                  opacity: activeFilter === 'all' || activeFilter === seg.filter ? 1 : 0.3
                }"
                @click="applyCardFilter(seg.filter, seg.label)"
              >
                <v-tooltip activator="parent" location="top">
                  <div class="text-center pa-1">
                    <div class="font-weight-bold">{{ seg.label }}</div>
                    <div>{{ seg.count }} Orang ({{ seg.percent }}%)</div>
                  </div>
                </v-tooltip>
              </div>
            </div>

            <!-- Legend Grid -->
            <div class="legend-grid">
              <div 
                v-for="(seg, idx) in ribbonSegments" 
                :key="'leg-'+idx"
                class="legend-item"
                :class="{ 'legend-active': activeFilter === seg.filter }"
                @click="applyCardFilter(seg.filter, seg.label)"
              >
                <div class="legend-dot" :style="{ backgroundColor: seg.color }"></div>
                <div class="legend-info">
                  <div class="legend-label">{{ seg.label }}</div>
                  <div class="legend-count text-grey">{{ seg.count }}</div>
                </div>
              </div>
            </div>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Search and Filter -->
    <v-row class="mb-6">
      <v-col cols="12" md="8">
        <v-text-field
          v-model="search"
          prepend-inner-icon="mdi-magnify"
          label="Cari berdasarkan Nama atau NIP..."
          variant="outlined"
          hide-details
          clearable
          @keyup.enter="fetchData"
          @click:clear="fetchData"
        ></v-text-field>
      </v-col>
      <v-col cols="12" md="4">
        <v-select
          v-model="filterJenis"
          :items="[
            { title: 'Semua Tipe', value: null },
            { title: 'PNS', value: 2 },
            { title: 'PPPK', value: 4 },
            { title: 'Pejabat Negara', value: 1 }
          ]"
          item-title="title"
          item-value="value"
          label="Tipe Pegawai"
          variant="outlined"
          hide-details
          @update:model-value="fetchData"
        ></v-select>
      </v-col>
    </v-row>

    <!-- Table -->
    <v-card class="rounded-xl" elevation="0" border>
      <v-data-table-server
        v-model:items-per-page="itemsPerPage"
        :headers="headers"
        :items="items"
        :items-length="totalItems"
        :loading="loading"
        @update:options="loadOptions"
        hover
        class="bg-transparent"
      >
        <template v-slot:item.nama="{ item }">
          <div class="font-weight-bold">{{ item.nama }}</div>
          <div class="text-caption text-grey">{{ item.nip }}</div>
        </template>
        <template v-slot:item.kdskpd="{ item }">
          <div v-if="item.nmskpd" class="text-caption font-weight-bold text-truncate" style="max-width: 250px;">{{ item.nmskpd }}</div>
          <div class="text-caption text-grey">{{ item.kdskpd }}</div>
        </template>
        <template v-slot:item.kdsatker="{ item }">
          <div v-if="item.nmsatker" class="text-caption font-weight-bold text-truncate" style="max-width: 250px;">{{ item.nmsatker }}</div>
          <div class="text-caption text-grey">{{ item.kdsatker }}</div>
        </template>
        <template v-slot:item.kd_jns_peg="{ item }">
          <v-chip v-if="item.kd_jns_peg == 2" size="small" color="teal" variant="tonal">PNS</v-chip>
          <v-chip v-else-if="item.kd_jns_peg == 4" size="small" color="purple" variant="tonal">PPPK</v-chip>
          <v-chip v-else-if="item.kd_jns_peg == 1" size="small" color="orange" variant="tonal">Pejabat Negara</v-chip>
          <v-chip v-else size="small" color="grey" variant="tonal">Lainnya ({{ item.kd_jns_peg }})</v-chip>
        </template>
        <template v-slot:item.kdpangkat="{ item }">
          {{ item.kdpangkat }}
          <span class="text-caption text-grey ml-1">({{ item.blgolt }}/{{ item.mkgolt }})</span>
        </template>
        <template v-slot:item.kdstapeg="{ item }">
          <v-chip v-if="item.nmstapeg" size="small" :color="getStapegColor(item.kdstapeg)" variant="tonal">
            {{ item.nmstapeg }}
          </v-chip>
          <span v-else class="text-grey">{{ item.kdstapeg || '-' }}</span>
        </template>
        <template v-slot:item.actions="{ item }">
          <v-btn icon size="small" variant="text" color="primary" @click="showDetail(item)">
            <v-icon>mdi-eye-outline</v-icon>
            <v-tooltip activator="parent" location="top">Lihat Detail & Keluarga</v-tooltip>
          </v-btn>
        </template>
      </v-data-table-server>
    </v-card>

    <!-- Upload Dialog -->
    <v-dialog v-model="uploadDialog" max-width="500">
      <v-card class="rounded-xl pa-4">
        <v-card-title class="font-weight-bold">Upload File DBF</v-card-title>
        <v-card-text>
          <v-form @submit.prevent="handleUpload">
            <v-select
              v-model="uploadType"
              :items="[
                { title: 'Master Pegawai (MST_PGW.DBF)', value: 'master_pegawai' },
                { title: 'Data Keluarga (KEL.DBF)', value: 'master_keluarga' },
                { title: 'Riwayat Gaji Pokok (HIS_GPOK.DBF)', value: 'history_gpok' },
                { title: 'Jabatan Fungsional Ref (Excel)', value: 'jabfung_ref' }
              ]"
              label="Jenis File"
              variant="outlined"
              class="mb-4"
            ></v-select>

            <v-text-field
              v-model="uploadBatch"
              label="Batch / Periode (e.g. 2026-3)"
              variant="outlined"
              class="mb-4"
            ></v-text-field>

            <v-file-input
              v-model="uploadFile"
              label="Pilih File .DBF"
              variant="outlined"
              accept=".dbf,.DBF"
              prepend-icon="mdi-database-outline"
            ></v-file-input>

            <v-alert v-if="uploadError" type="error" variant="tonal" class="mt-4">
              {{ uploadError }}
            </v-alert>

            <!-- Loading & Progress State -->
            <div v-if="uploading && !uploadError" class="mt-4">
              <div v-if="uploadStage === 'uploading'">
                <div class="text-caption mb-1 d-flex justify-space-between">
                  <span>Tahap 1: Mengupload file ke server...</span>
                  <span class="font-weight-bold">{{ uploadProgress }}%</span>
                </div>
                <v-progress-linear
                  v-model="uploadProgress"
                  color="info"
                  height="10"
                  rounded
                  striped
                ></v-progress-linear>
              </div>

              <div v-else-if="uploadStage === 'processing'">
                <div class="text-caption mb-1 d-flex justify-space-between">
                  <span>Tahap 2: Memasukkan data ke database...</span>
                  <span class="font-weight-bold">{{ processingProgress }}%</span>
                </div>
                <v-progress-linear
                  v-model="processingProgress"
                  color="success"
                  height="10"
                  rounded
                  indeterminate
                  v-if="processingProgress === 0"
                ></v-progress-linear>
                <v-progress-linear
                  v-model="processingProgress"
                  color="success"
                  height="10"
                  rounded
                  v-else
                ></v-progress-linear>
                <div class="text-caption text-info mt-1 d-flex align-center" v-if="processingProgress === 0">
                  <v-icon size="small" class="mr-1">mdi-timer-sand</v-icon>
                  Menunggu antrian server... (Sesuai antrian)
                </div>
              </div>
            </div>

            <!-- Recent Jobs List -->
            <div v-if="recentJobs.length > 0" class="mt-6">
              <div class="text-subtitle-2 mb-2 text-grey">Pekerjaan Terakhir:</div>
              <v-list density="compact" class="rounded-lg" style="background: rgba(var(--v-border-color), 0.05);">
                <v-list-item v-for="job in recentJobs" :key="job.id" class="px-2">
                  <template v-slot:prepend>
                    <v-icon :color="getStatusColor(job.status)" size="small">
                      {{ getStatusIcon(job.status) }}
                    </v-icon>
                  </template>
                  <v-list-item-title class="text-caption">
                    {{ job.type }} (Batch: {{ job.params?.batch || '-' }})
                  </v-list-item-title>
                  <template v-slot:append>
                    <span class="text-caption text-grey">{{ job.status }}</span>
                  </template>
                </v-list-item>
              </v-list>
            </div>

            <v-btn
              v-if="!uploading"
              block
              color="primary"
              size="large"
              type="submit"
              class="mt-6"
              :disabled="!uploadFile || !uploadBatch"
            >
              MULAI IMPORT
            </v-btn>
            
            <v-btn
              v-else
              block
              variant="tonal"
              size="large"
              class="mt-6"
              disabled
            >
              {{ uploadStage === 'uploading' ? 'SEDANG MENGUPLOAD...' : 'SEDANG MEMPROSES...' }}
            </v-btn>
          </v-form>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Detail Dialog -->
    <v-dialog v-model="detailDialog" max-width="900">
      <v-card v-if="selectedItem" class="rounded-xl overflow-hidden">
        <v-toolbar color="primary" dark flat>
          <v-toolbar-title>Detail Pegawai: {{ selectedItem.nama }}</v-toolbar-title>
          <v-spacer></v-spacer>
          <v-btn icon @click="detailDialog = false"><v-icon>mdi-close</v-icon></v-btn>
        </v-toolbar>
        
        <v-card-text class="pa-0">
          <v-tabs v-model="detailTab" color="primary">
            <v-tab value="info">Info Personal</v-tab>
            <v-tab value="family">Keluarga ({{ selectedItem.keluarga?.length || 0 }})</v-tab>
            <v-tab value="job">Info Pekerjaan</v-tab>
          </v-tabs>

          <v-window v-model="detailTab" class="pa-6">
            <v-window-item value="info">
              <v-row>
                <v-col cols="12" md="6">
                  <div class="text-subtitle-2 text-grey mb-1">NIP</div>
                  <div class="text-body-1 font-weight-bold mb-4">{{ selectedItem.nip }}</div>
                  
                  <div class="text-subtitle-2 text-grey mb-1">NIK</div>
                  <div class="text-body-1 font-weight-bold mb-4">{{ selectedItem.noktp || '-' }}</div>
                  
                  <div class="text-subtitle-2 text-grey mb-1">Tempat/Tgl Lahir</div>
                  <div class="text-body-1 mb-4">{{ selectedItem.tempatlhr || '-' }}, {{ selectedItem.tgllhr || '-' }}</div>
                  
                  <div class="text-subtitle-2 text-grey mb-1">Alamat</div>
                  <div class="text-body-1 mb-4">{{ selectedItem.alamat || '-' }}</div>
                </v-col>
                <v-col cols="12" md="6">
                  <div class="text-subtitle-2 text-grey mb-1">WhatsApp / HP</div>
                  <div class="text-body-1 font-weight-bold mb-4">{{ selectedItem.nohandphon || '-' }}</div>

                  <div class="text-subtitle-2 text-grey mb-1">Jenis Kelamin</div>
                  <div class="text-body-1 mb-4">{{ selectedItem.kdjenkel == 1 ? 'Laki-laki' : 'Perempuan' }}</div>
                  
                  <div class="text-subtitle-2 text-grey mb-1">Pendidikan</div>
                  <div class="text-body-1 mb-4">{{ selectedItem.pendidikan || '-' }}</div>

                  <div class="text-subtitle-2 text-grey mb-1">No. Rekening</div>
                  <div class="text-body-1 mb-4">{{ selectedItem.norek || '-' }} ({{ selectedItem.induk_bank || 'Bank' }})</div>
                </v-col>
              </v-row>
            </v-window-item>

            <v-window-item value="family">
              <v-table v-if="selectedItem.keluarga && selectedItem.keluarga.length">
                <thead>
                  <tr>
                    <th>Nama</th>
                    <th>Hubungan</th>
                    <th>Tgl Lahir</th>
                    <th>Tunjangan?</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="fam in selectedItem.keluarga" :key="fam.id">
                    <td>{{ fam.nmkel }}</td>
                    <td>{{ getHubungan(fam.kdhubkel) }}</td>
                    <td>{{ fam.tgllhr }}</td>
                    <td>
                      <v-chip size="x-small" :color="fam.kdtunjang == 1 ? 'success' : 'grey'">
                        {{ fam.kdtunjang == 1 ? 'Ya' : 'Tidak' }}
                      </v-chip>
                    </td>
                  </tr>
                </tbody>
              </v-table>
              <div v-else class="text-center py-8 text-grey">
                <v-icon size="48">mdi-account-group-outline</v-icon>
                <div class="mt-2">Tidak ada data keluarga ditemukan.</div>
              </div>
            </v-window-item>

            <v-window-item value="job">
              <v-row>
                <v-col cols="12" md="6">
                  <div class="text-subtitle-2 text-grey mb-1">Gaji Pokok</div>
                  <div class="text-h6 font-weight-bold text-success mb-4">{{ formatCurrency(selectedItem.gapok) }}</div>
                  
                  <div class="text-subtitle-2 text-grey mb-1">Pangkat/Golonagan</div>
                  <div class="text-body-1 mb-4">{{ selectedItem.kdpangkat }} ({{ selectedItem.blgolt }}/{{ selectedItem.mkgolt }})</div>

                  <div class="text-subtitle-2 text-grey mb-1">Tunjangan Eselon</div>
                  <div class="text-body-1 font-weight-bold text-primary mb-4">{{ formatCurrency(selectedItem.tjeselon) }}</div>
                </v-col>
                <v-col cols="12" md="6">
                  <div class="text-subtitle-2 text-grey mb-1">Unit Kerja / SKPD</div>
                  <div class="text-body-1 font-weight-bold mb-1" v-if="selectedItem.nmskpd">{{ selectedItem.nmskpd }}</div>
                  <div class="text-body-2 text-grey mb-4">{{ selectedItem.kdskpd }}</div>
                  
                  <div class="text-subtitle-2 text-grey mb-1">Satuan Kerja</div>
                  <div class="text-body-1 font-weight-bold mb-1" v-if="selectedItem.nmsatker">{{ selectedItem.nmsatker }}</div>
                  <div class="text-body-2 text-grey mb-4">{{ selectedItem.kdsatker }}</div>
                  
                  <div class="text-subtitle-2 text-grey mb-1">Status Pegawai</div>
                  <div class="text-body-1 mb-4">
                    <v-chip v-if="selectedItem.nmstapeg" size="small" :color="getStapegColor(selectedItem.kdstapeg)" variant="tonal">
                      {{ selectedItem.nmstapeg }}
                    </v-chip>
                    <span v-else>{{ selectedItem.kdstapeg || '-' }}</span>
                  </div>

                  <div class="text-subtitle-2 text-grey mb-1">Jabatan</div>
                  <div class="text-body-1 mb-1" v-if="selectedItem.nama_jabatan">{{ selectedItem.nama_jabatan }}</div>
                  <div class="text-body-2 text-grey mb-4" v-else>{{ selectedItem.kdfungsi || '-' }}</div>

                  <div class="text-subtitle-2 text-grey mb-1">TMT Capeg</div>
                  <div class="text-body-1 mb-4">{{ selectedItem.tmtcapeg || '-' }}</div>
                </v-col>
              </v-row>
            </v-window-item>
          </v-window>
        </v-card-text>
      </v-card>
    </v-dialog>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import api from '../api'
import Sidebar from '../components/Sidebar.vue'
import Navbar from '../components/Navbar.vue'

const loading = ref(false)
const items = ref([])
const totalItems = ref(0)
const itemsPerPage = ref(20)
const search = ref('')
const filterJenis = ref(null)
const stats = ref({
    summary: {},
    by_code: []
})
const activeFilter = ref('all')
const activeFilterLabel = ref('')
const statusGroup = ref(null)
const statusCode = ref(null)

const ribbonSegments = computed(() => {
  const total = stats.value.summary?.total || 0
  if (total === 0) return []
  
  const colors = [
    '#1e40af', '#7e22ce', '#d97706', '#dc2626', '#ea580c', '#0284c7', '#64748b',
    '#059669', '#0891b2', '#4f46e5', '#7c3aed', '#c026d3', '#db2777', '#dc2626',
    '#facc15', '#4ade80', '#22d3ee', '#818cf8', '#fb7185', '#a78bfa'
  ]
  
  return stats.value.by_code.map((s, idx) => ({
    label: s.nmstapeg || `Status ${s.kdstapeg}`,
    count: s.count,
    color: colors[idx % colors.length],
    filter: s.kdstapeg,
    percent: ((s.count / total) * 100).toFixed(1)
  })).sort((a, b) => b.count - a.count)
})

const headers = [
  { title: 'Nama / NIP', key: 'nama', align: 'start', width: '250', sortable: false },
  { title: 'Tipe', key: 'kd_jns_peg', align: 'center', sortable: false },
  { title: 'Status', key: 'kdstapeg', align: 'start', sortable: false },
  { title: 'SKPD', key: 'kdskpd', align: 'start', sortable: false },
  { title: 'Satker', key: 'kdsatker', align: 'start', sortable: false },
  { title: 'Jabatan', key: 'nama_jabatan', align: 'start', sortable: false },
  { title: 'Golongan', key: 'kdpangkat', align: 'start', sortable: false },
  { title: 'Gaji Pokok', key: 'gapok', align: 'end', sortable: false },
  { title: 'Aksi', key: 'actions', align: 'center', sortable: false },
]

const uploadDialog = ref(false)
const uploading = ref(false)
const uploadType = ref('master_pegawai')
const uploadBatch = ref('2026-3')
const uploadFile = ref(null)
const uploadError = ref(null)
const uploadStage = ref('idle') // idle, uploading, processing
const uploadProgress = ref(0)
const processingProgress = ref(0)
const pollInterval = ref(null)
const recentJobs = ref([])

const detailDialog = ref(false)
const selectedItem = ref(null)
const detailTab = ref('info')

const loadOptions = ({ page, itemsPerPage, sortBy }) => {
  fetchData(page, itemsPerPage)
}

const fetchData = async (page = 1, limit = itemsPerPage.value) => {
  loading.value = true
  try {
    const params = {
      page,
      per_page: limit,
      search: search.value,
      kd_jns_peg: filterJenis.value,
      status_group: statusGroup.value,
      status_code: statusCode.value
    }

    const response = await api.get('/master/pegawai', { params })
    items.value = response.data.data.data
    totalItems.value = response.data.data.total
  } catch (error) {
    console.error('Error fetching master pegawai:', error)
  } finally {
    loading.value = false
  }
}

const fetchStats = async () => {
    try {
        const response = await api.get('/master/pegawai/stats')
        stats.value = response.data.data
    } catch (e) {
        console.error('Failed to fetch stats', e)
    }
}

const applyCardFilter = (filter, label) => {
    activeFilter.value = filter
    activeFilterLabel.value = label
    
    // Map card filter to params
    if (filter === 'all') {
        statusGroup.value = null
        statusCode.value = null
        filterJenis.value = null
    } else {
        // Individual status code
        statusCode.value = filter
        statusGroup.value = null
        filterJenis.value = null
    }
    
    fetchData(1)
}

const clearCardFilter = () => {
    applyCardFilter('all', '')
}

const fetchRecentJobs = async () => {
    try {
        const response = await api.get('/upload-jobs?limit=3')
        recentJobs.value = response.data.data || []
    } catch (e) {
        console.error('Failed to fetch recent jobs', e)
    }
}

watch(uploadDialog, (val) => {
    if (val) {
        fetchRecentJobs()
        uploadStage.value = 'idle'
        uploadProgress.value = 0
        processingProgress.value = 0
        uploadError.value = null
    }
})

const getStatusColor = (status) => {
    switch(status) {
        case 'completed': return 'success'
        case 'failed': return 'error'
        case 'processing': return 'info'
        default: return 'warning'
    }
}

const getStatusIcon = (status) => {
    switch(status) {
        case 'completed': return 'mdi-check-circle'
        case 'failed': return 'mdi-alert-circle'
        case 'processing': return 'mdi-cached'
        default: return 'mdi-clock-outline'
    }
}

const handleUpload = async () => {
  if (!uploadFile.value) return
  
  uploading.value = true
  uploadError.value = null
  uploadStage.value = 'uploading'
  uploadProgress.value = 0
  
  const formData = new FormData()
  formData.append('file', uploadFile.value)
  formData.append('batch', uploadBatch.value)
  formData.append('type', uploadType.value)
  
  const endpoint = '/upload-jobs';

  try {
    const response = await api.post(endpoint, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      onUploadProgress: (progressEvent) => {
          uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total)
      }
    })
    
    if (response.data.data?.job_id) {
        startPolling(response.data.data.job_id)
    } else {
        uploading.value = false
        uploadDialog.value = false
        uploadFile.value = null
        alert(response.data.message || 'Import berhasil!')
        fetchData()
    }
  } catch (error) {
    console.error('Error uploading file:', error)
    uploadError.value = error.response?.data?.message || 'Gagal mengupload file ke server.'
    uploading.value = false
  }
}

const startPolling = (jobId) => {
    uploadStage.value = 'processing'
    processingProgress.value = 0
    
    pollInterval.value = setInterval(async () => {
        try {
            const response = await api.get(`/upload-jobs/${jobId}`)
            const job = response.data.data
            
            if (job.status === 'completed') {
                clearInterval(pollInterval.value)
                uploading.value = false
                uploadDialog.value = false
                uploadFile.value = null
                alert(job.result_summary?.message || 'Import selesai!')
                fetchData()
            } else if (job.status === 'failed') {
                clearInterval(pollInterval.value)
                uploading.value = false
                uploadError.value = job.error_message || 'Proses di server gagal.'
            } else {
                processingProgress.value = job.progress || 0
            }
        } catch (error) {
            console.error('Polling error:', error)
            clearInterval(pollInterval.value)
            uploading.value = false
        }
    }, 2000)
}

const showDetail = async (item) => {
  try {
    const response = await api.get(`/master/pegawai/${item.id}`)
    selectedItem.value = response.data.data
    detailDialog.value = true
    detailTab.value = 'info'
  } catch (error) {
    console.error('Error fetching detail:', error)
  }
}

const getHubungan = (code) => {
  const map = {
    '10': 'Istri',
    '11': 'Suami',
    '01': 'Anak Kandung',
    '02': 'Anak Tiri',
    '03': 'Anak Angkat'
  }
  return map[code] || code || 'Lainnya'
}

const getStapegColor = (code) => {
  const activeStatuses = [1, 2, 3, 4, 5, 11, 12]
  const inactiveStatuses = [6, 7, 8, 9, 10, 22]
  const terminatedStatuses = [23, 24, 27, 28, 30]
  
  const numCode = parseInt(code)
  if (activeStatuses.includes(numCode)) return 'success'
  if (inactiveStatuses.includes(numCode)) return 'warning'
  if (terminatedStatuses.includes(numCode)) return 'error'
  return 'grey'
}

const formatCurrency = (value) => {
  if (!value) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(value)
}

onMounted(() => {
  fetchData()
  fetchRecentJobs()
  fetchStats()
})
</script>

<style scoped>
.v-data-table-server {
  border-radius: 12px;
}

.stat-ribbon-card {
  background: rgba(var(--v-theme-surface), 0.9) !important;
  backdrop-filter: blur(10px);
}

.border-md-right {
  border-right: 1px solid rgba(var(--v-border-color), 0.1);
}

@media (max-width: 960px) {
  .border-md-right {
    border-right: none;
    border-bottom: 1px solid rgba(var(--v-border-color), 0.1);
    padding-bottom: 24px;
    margin-bottom: 24px;
  }
}

.ribbon-container {
  display: flex;
  height: 40px;
  width: 100%;
  border-radius: 12px;
  overflow: hidden;
  background: rgba(var(--v-border-color), 0.08);
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);
}

.ribbon-segment {
  height: 100%;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
  position: relative;
}

.ribbon-segment:hover {
  transform: scaleY(1.15);
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  z-index: 2;
  filter: brightness(1.1);
}

.legend-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 16px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s;
}

.legend-item:hover {
  background: rgba(var(--v-border-color), 0.08);
}

.legend-active {
  background: rgba(var(--v-theme-primary), 0.08) !important;
  border: 1px solid rgba(var(--v-theme-primary), 0.2);
}

.legend-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  flex-shrink: 0;
}

.legend-label {
  font-size: 0.75rem;
  font-weight: 600;
  line-height: 1.2;
}

.legend-count {
  font-size: 0.875rem;
  font-weight: 700;
}

.cursor-pointer {
  cursor: pointer;
}
</style>
