<template>
  <div>
    <Navbar />
    <Sidebar />

    <v-main>
      <v-container fluid class="pa-6 pa-md-8">
        <!-- Header -->
        <div class="d-flex align-center mb-6 flex-wrap ga-2">
          <div>
            <h1 class="text-h5 font-weight-bold">Rekonsiliasi Data BKD</h1>
            <p class="text-body-2 text-medium-emphasis mt-1">Bandingkan data pegawai BKD dengan data SimGaji</p>
          </div>
          <v-spacer></v-spacer>
          <v-btn color="success" prepend-icon="mdi-download" variant="tonal" rounded="lg" @click="doExport" :loading="exporting" class="mr-2">
            Export XLS
          </v-btn>
          <v-btn color="primary" prepend-icon="mdi-upload" variant="flat" rounded="lg" @click="uploadDialog = true">
            Upload Data BKD
          </v-btn>
        </div>

        <!-- Summary Cards -->
        <v-row v-if="summary" class="mb-4" dense>
          <v-col cols="6" sm="4" md="2">
            <v-card variant="tonal" color="primary" class="rounded-xl pa-3 text-center" @click="filter = 'all'" style="cursor: pointer">
              <div class="text-h6 font-weight-black">{{ summary.bkd_total }}</div>
              <div class="text-caption font-weight-bold mt-1">Data BKD</div>
            </v-card>
          </v-col>
          <v-col cols="6" sm="4" md="2">
            <v-card variant="tonal" color="success" class="rounded-xl pa-3 text-center" @click="filter = 'all'" style="cursor: pointer">
              <div class="text-h6 font-weight-black">{{ summary.identical }}</div>
              <div class="text-caption font-weight-bold mt-1">Identik</div>
            </v-card>
          </v-col>
          <v-col cols="6" sm="4" md="2">
            <v-card variant="tonal" color="warning" class="rounded-xl pa-3 text-center" @click="filter = 'diff'" style="cursor: pointer">
              <div class="text-h6 font-weight-black">{{ summary.with_differences }}</div>
              <div class="text-caption font-weight-bold mt-1">Selisih</div>
            </v-card>
          </v-col>
          <v-col cols="6" sm="4" md="2">
            <v-card variant="tonal" color="orange" class="rounded-xl pa-3 text-center" @click="filter = 'bkd_only'" style="cursor: pointer">
              <div class="text-h6 font-weight-black">{{ summary.bkd_only }}</div>
              <div class="text-caption font-weight-bold mt-1">Hanya BKD</div>
            </v-card>
          </v-col>
          <v-col cols="6" sm="4" md="2">
            <v-card variant="tonal" color="error" class="rounded-xl pa-3 text-center" @click="filter = 'simgaji_only'" style="cursor: pointer">
              <div class="text-h6 font-weight-black">{{ summary.simgaji_only }}</div>
              <div class="text-caption font-weight-bold mt-1">Hanya SimGaji</div>
            </v-card>
          </v-col>
        </v-row>

        <!-- Diff breakdown chips -->
        <div v-if="summary && summary.with_differences > 0" class="mb-4 d-flex align-center ga-2 flex-wrap">
          <span class="text-caption text-medium-emphasis mr-1">Detail Selisih:</span>
          <v-chip size="small" :color="filter === 'diff_nik' ? 'primary' : 'default'" :variant="filter === 'diff_nik' ? 'flat' : 'outlined'" @click="filter = filter === 'diff_nik' ? 'diff' : 'diff_nik'" style="cursor: pointer">
            NIK: {{ summary.diff_nik }}
          </v-chip>
          <v-chip size="small" :color="filter === 'diff_golongan' ? 'primary' : 'default'" :variant="filter === 'diff_golongan' ? 'flat' : 'outlined'" @click="filter = filter === 'diff_golongan' ? 'diff' : 'diff_golongan'" style="cursor: pointer">
            Golongan: {{ summary.diff_golongan }}
          </v-chip>
          <v-chip size="small" :color="filter === 'diff_jabatan' ? 'primary' : 'default'" :variant="filter === 'diff_jabatan' ? 'flat' : 'outlined'" @click="filter = filter === 'diff_jabatan' ? 'diff' : 'diff_jabatan'" style="cursor: pointer">
            Jabatan: {{ summary.diff_jabatan }}
          </v-chip>
        </div>

        <v-alert v-if="summary && summary.last_upload" type="info" variant="tonal" density="compact" class="mb-4 rounded-lg" closable>
          Data BKD terakhir diupload pada: <strong>{{ formatDate(summary.last_upload) }}</strong>
        </v-alert>

        <!-- Filter & Search -->
        <v-card class="glass-card rounded-xl pa-4 mb-6" elevation="0">
          <v-row align="center" dense>
            <v-col cols="12" sm="6" md="4">
              <v-text-field
                v-model="search"
                prepend-inner-icon="mdi-magnify"
                label="Cari NIP / Nama..."
                variant="outlined"
                density="compact"
                rounded="lg"
                clearable
                hide-details
                @keyup.enter="fetchData"
              ></v-text-field>
            </v-col>
            <v-col cols="12" sm="6" md="5">
              <v-btn-toggle v-model="filter" mandatory color="primary" variant="outlined" rounded="pill" density="compact">
                <v-btn value="all" size="small">Semua</v-btn>
                <v-btn value="diff" size="small">Selisih</v-btn>
                <v-btn value="bkd_only" size="small">BKD Only</v-btn>
                <v-btn value="simgaji_only" size="small">SG Only</v-btn>
              </v-btn-toggle>
            </v-col>
            <v-col cols="auto">
              <v-btn color="primary" variant="text" prepend-icon="mdi-refresh" @click="fetchData" :loading="loading" size="small">Refresh</v-btn>
            </v-col>
          </v-row>
        </v-card>

        <!-- Data Table -->
        <v-card class="glass-card rounded-xl" elevation="0">
          <v-data-table-server
            :headers="headers"
            :items="items"
            :items-length="totalItems"
            :loading="loading"
            :items-per-page="perPage"
            :page="page"
            @update:page="page = $event; fetchData()"
            @update:items-per-page="perPage = $event; page = 1; fetchData()"
            density="compact"
            class="bg-transparent"
            :items-per-page-options="[25, 50, 100]"
          >
            <template v-slot:item.nip="{ item }">
              <span class="font-weight-bold text-body-2">{{ item.nip }}</span>
            </template>

            <template v-slot:item.bkd_nama="{ item }">
              <span class="text-body-2">{{ item.bkd_nama || '-' }}</span>
            </template>
            <template v-slot:item.sg_nama="{ item }">
              <span class="text-body-2">{{ item.sg_nama || '-' }}</span>
            </template>

            <template v-slot:item.bkd_nik="{ item }">
              <span :class="{ 'diff-highlight': item.differences?.includes('nik') }" class="text-caption">{{ cleanNik(item.bkd_nik) || '-' }}</span>
            </template>
            <template v-slot:item.sg_nik="{ item }">
              <span :class="{ 'diff-highlight': item.differences?.includes('nik') }" class="text-caption">{{ item.sg_nik || '-' }}</span>
            </template>

            <template v-slot:item.bkd_golongan="{ item }">
              <span :class="{ 'diff-highlight': item.differences?.includes('golongan') }">{{ item.bkd_golongan || '-' }}</span>
            </template>
            <template v-slot:item.sg_golongan="{ item }">
              <span :class="{ 'diff-highlight': item.differences?.includes('golongan') }">{{ item.sg_golongan || '-' }}</span>
            </template>

            <template v-slot:item.bkd_jabatan="{ item }">
              <span class="text-caption" :class="{ 'diff-highlight': item.differences?.includes('jabatan') }">{{ item.bkd_jabatan || '-' }}</span>
            </template>
            <template v-slot:item.sg_jabatan="{ item }">
              <span class="text-caption" :class="{ 'diff-highlight': item.differences?.includes('jabatan') }">{{ item.sg_jabatan || '-' }}</span>
            </template>

            <template v-slot:item.match_status="{ item }">
              <v-chip
                :color="getStatusColor(item)"
                size="x-small"
                variant="flat"
                label
              >
                {{ getStatusLabel(item) }}
              </v-chip>
            </template>

            <template v-slot:no-data>
              <div class="text-center py-10 text-disabled">
                <v-icon size="64">mdi-database-search</v-icon>
                <p class="mt-2">{{ summary ? 'Tidak ada data yang cocok dengan filter.' : 'Upload file data_bkd.xlsx terlebih dahulu.' }}</p>
              </div>
            </template>
          </v-data-table-server>
        </v-card>

        <!-- Upload Dialog -->
        <v-dialog v-model="uploadDialog" max-width="500px" persistent>
          <v-card class="rounded-xl pa-2">
            <v-card-title class="pa-4 font-weight-bold">Upload Data BKD</v-card-title>
            <v-card-text>
              <v-alert type="warning" variant="tonal" density="compact" class="mb-4 rounded-lg">
                Data BKD sebelumnya akan <strong>ditimpa</strong> dengan file baru.
              </v-alert>
              <v-file-input
                v-model="uploadFile"
                label="Pilih file data_bkd.xlsx"
                accept=".xlsx,.xls,.csv"
                prepend-icon="mdi-file-excel"
                variant="outlined"
                density="compact"
                rounded="lg"
                show-size
                :rules="[v => !!v || 'File wajib dipilih']"
              ></v-file-input>
            </v-card-text>
            <v-card-actions class="pa-4 pt-0">
              <v-spacer></v-spacer>
              <v-btn color="grey" variant="text" @click="uploadDialog = false" :disabled="uploading">Batal</v-btn>
              <v-btn color="primary" variant="flat" rounded="lg" @click="doUpload" :loading="uploading" :disabled="!uploadFile">Upload & Proses</v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>

        <!-- Snackbar -->
        <v-snackbar v-model="snackbar" :color="snackColor" rounded="lg" location="top">
          {{ snackText }}
        </v-snackbar>
      </v-container>
    </v-main>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import Navbar from '../../components/Navbar.vue'
import Sidebar from '../../components/Sidebar.vue'
import api from '../../api'

const loading = ref(false)
const items = ref([])
const totalItems = ref(0)
const page = ref(1)
const perPage = ref(50)
const search = ref('')
const filter = ref('all')
const summary = ref(null)
const exporting = ref(false)

const uploadDialog = ref(false)
const uploadFile = ref(null)
const uploading = ref(false)

const snackbar = ref(false)
const snackText = ref('')
const snackColor = ref('success')

const headers = [
  { title: 'NIP', key: 'nip', sortable: false, width: '160px' },
  { title: 'Nama (BKD)', key: 'bkd_nama', sortable: false },
  { title: 'Nama (SG)', key: 'sg_nama', sortable: false },
  { title: 'NIK (BKD)', key: 'bkd_nik', sortable: false, width: '140px' },
  { title: 'NIK (SG)', key: 'sg_nik', sortable: false, width: '140px' },
  { title: 'Gol (BKD)', key: 'bkd_golongan', sortable: false, width: '75px' },
  { title: 'Gol (SG)', key: 'sg_golongan', sortable: false, width: '75px' },
  { title: 'Jabatan (BKD)', key: 'bkd_jabatan', sortable: false },
  { title: 'Jabatan (SG)', key: 'sg_jabatan', sortable: false },
  { title: 'Status', key: 'match_status', sortable: false, width: '90px' },
]

const fetchSummary = async () => {
  try {
    const res = await api.get('/bkd-recon/summary')
    if (res.data.success) {
      summary.value = res.data.data
    }
  } catch (err) {
    console.error('Error fetching summary:', err)
  }
}

const fetchData = async () => {
  loading.value = true
  try {
    const res = await api.get('/bkd-recon', {
      params: {
        filter: filter.value,
        search: search.value || undefined,
        page: page.value,
        per_page: perPage.value,
      }
    })
    if (res.data.success) {
      items.value = res.data.data
      totalItems.value = res.data.meta?.total || 0
    } else {
      items.value = []
      totalItems.value = 0
    }
  } catch (err) {
    console.error('Error:', err)
    items.value = []
  } finally {
    loading.value = false
  }
}

const doUpload = async () => {
  if (!uploadFile.value) return
  uploading.value = true
  try {
    const formData = new FormData()
    formData.append('file', uploadFile.value)
    const res = await api.post('/bkd-recon/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    if (res.data.success) {
      showSnack(res.data.message, 'success')
      uploadDialog.value = false
      uploadFile.value = null
      await fetchSummary()
      await fetchData()
    }
  } catch (err) {
    showSnack(err.response?.data?.message || 'Gagal mengupload file.', 'error')
  } finally {
    uploading.value = false
  }
}

const doExport = async () => {
  exporting.value = true
  try {
    const res = await api.get('/bkd-recon/export', {
      params: { filter: filter.value },
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([res.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `rekon_bkd_${filter.value}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
    showSnack('File berhasil didownload!', 'success')
  } catch (err) {
    showSnack('Gagal mengexport file.', 'error')
  } finally {
    exporting.value = false
  }
}

const showSnack = (text, color = 'success') => {
  snackText.value = text
  snackColor.value = color
  snackbar.value = true
}

const getStatusColor = (item) => {
  if (item.match_status === 'bkd_only') return 'orange'
  if (item.match_status?.startsWith('simgaji_only')) return 'error'
  if (item.has_diff) return 'warning'
  return 'success'
}

const getStatusLabel = (item) => {
  if (item.match_status === 'bkd_only') return 'BKD Only'
  if (item.match_status?.startsWith('simgaji_only')) return 'SG Only'
  if (item.has_diff) return 'Selisih'
  return 'Identik'
}

const cleanNik = (nik) => {
  if (!nik) return ''
  return nik.replace(/'/g, '')
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

watch(filter, () => {
  page.value = 1
  fetchData()
})

onMounted(() => {
  fetchSummary()
  fetchData()
})
</script>

<style scoped>
.glass-card {
  background: rgba(var(--v-theme-surface), 0.7) !important;
  backdrop-filter: blur(12px);
  border: 1px solid rgba(var(--v-border-color), 0.1) !important;
}
.diff-highlight {
  background: rgba(255, 152, 0, 0.15);
  color: rgb(var(--v-theme-warning));
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 4px;
}
</style>
