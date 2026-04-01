<template>
  <div class="modern-dashboard">
    <Navbar />
    <Sidebar />
    <v-main class="bg-light">
      <v-container fluid class="pa-6">
        <!-- Header -->
        <div class="d-flex align-center mb-6">
          <div class="icon-box mr-4">
            <v-icon icon="mdi-history" color="primary" size="32"></v-icon>
          </div>
          <div>
            <h1 class="text-h4 font-weight-bold mb-1">Riwayat Ekspor</h1>
            <p class="text-subtitle-1 text-medium-emphasis">
              Pantau aktivitas cetak PDF dan unduh Excel dari sistem.
            </p>
          </div>
          <v-spacer></v-spacer>
          <v-btn
            color="error"
            variant="tonal"
            prepend-icon="mdi-delete-sweep"
            class="text-none font-weight-bold rounded-lg"
            @click="showCleanupDialog = true"
          >
            Hapus Log Lama
          </v-btn>
        </div>

        <!-- Filter Card -->
        <v-card class="glass-card mb-6 pa-4 border-0" elevation="0">
          <v-row align="center">
            <v-col cols="12" md="4">
              <v-text-field
                v-model="filters.report_name"
                label="Cari Nama Laporan"
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
                density="comfortable"
                hide-details
                rounded="lg"
                class="bg-white"
                @keyup.enter="fetchLogs"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="3">
              <v-text-field
                v-model="filters.start_date"
                type="date"
                label="Mulai Tanggal"
                variant="outlined"
                density="comfortable"
                hide-details
                rounded="lg"
                class="bg-white"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="3">
              <v-text-field
                v-model="filters.end_date"
                type="date"
                label="Sampai Tanggal"
                variant="outlined"
                density="comfortable"
                hide-details
                rounded="lg"
                class="bg-white"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="2">
              <v-btn
                color="primary"
                height="48"
                block
                rounded="lg"
                prepend-icon="mdi-filter"
                class="text-none font-weight-bold"
                @click="fetchLogs"
                :loading="loading"
              >
                Filter
              </v-btn>
            </v-col>
          </v-row>
        </v-card>

        <!-- Table Card -->
        <v-card class="glass-card border-0 overflow-hidden" elevation="0">
          <v-data-table-server
            v-model:items-per-page="pagination.itemsPerPage"
            :headers="headers"
            :items="logs"
            :items-length="pagination.totalItems"
            :loading="loading"
            @update:options="fetchLogs"
            class="custom-table"
            hover
          >
            <!-- User Column -->
            <template v-slot:item.user="{ item }">
              <div class="d-flex align-center py-2">
                <v-avatar color="primary" variant="tonal" size="36" class="mr-3">
                  <span class="text-body-2 font-weight-bold">{{ item.user?.name?.charAt(0) || '?' }}</span>
                </v-avatar>
                <div>
                  <div class="font-weight-bold text-body-1">{{ item.user?.name || 'Unknown' }}</div>
                  <div class="text-caption text-medium-emphasis">{{ item.user?.username }}</div>
                </div>
              </div>
            </template>

            <!-- SKPD Column -->
            <template v-slot:item.skpd="{ item }">
              <div class="text-body-2 font-weight-medium">
                {{ item.user?.skpd?.nama_skpd || (item.user?.role === 'superadmin' ? 'Provinsi / Superadmin' : 'N/A') }}
              </div>
            </template>

            <!-- Action Column -->
            <template v-slot:item.action="{ item }">
              <v-chip
                :color="item.action.includes('PDF') ? 'error' : 'success'"
                size="small"
                variant="flat"
                class="font-weight-medium px-3 text-uppercase"
              >
                <v-icon start size="small" :icon="item.action.includes('PDF') ? 'mdi-file-pdf-box' : 'mdi-file-excel'"></v-icon>
                {{ item.action }}
              </v-chip>
            </template>

            <!-- Date Column -->
            <template v-slot:item.created_at="{ item }">
              <div class="font-weight-medium">{{ formatDate(item.created_at) }}</div>
              <div class="text-caption text-medium-emphasis">{{ formatTime(item.created_at) }}</div>
            </template>

            <template v-slot:item.ip_address="{ item }">
               <v-chip size="x-small" variant="tonal" color="grey" class="font-monospace">
                 {{ item.ip_address || 'N/A' }}
               </v-chip>
            </template>
            
            <template v-slot:no-data>
              <div class="pa-12 text-center">
                <v-icon icon="mdi-history" size="64" color="disabled" class="mb-4"></v-icon>
                <p class="text-h6 text-disabled">Tidak ada riwayat ekspor ditemukan.</p>
              </div>
            </template>
          </v-data-table-server>
        </v-card>
      </v-container>
    </v-main>

    <!-- Dialog Konfirmasi Cleanup -->
    <v-dialog v-model="showCleanupDialog" max-width="450px" persistent>
      <v-card class="rounded-xl pa-4">
        <v-card-title class="d-flex align-center">
          <v-icon color="error" class="mr-3">mdi-alert-circle</v-icon>
          Hapus Log Riwayat
        </v-card-title>
        <v-card-text>
          Pilih rentang waktu log yang akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.
          <v-select
            v-model="cleanupDays"
            :items="cleanupOptions"
            label="Hapus log yang lebih tua dari"
            variant="outlined"
            class="mt-4"
            density="comfortable"
          ></v-select>
 
          <v-text-field
            v-model="cleanupPassword"
            label="Password Konfirmasi"
            type="password"
            variant="outlined"
            class="mt-2"
            density="comfortable"
            placeholder="Masukkan password akun Anda"
            prepend-inner-icon="mdi-lock"
            hide-details
          ></v-text-field>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="showCleanupDialog = false" class="text-none">Batal</v-btn>
          <v-btn color="error" variant="flat" :loading="cleanupLoading" @click="handleCleanup" class="text-none px-6 rounded-lg">
            Hapus Sekarang
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../api'
import Sidebar from '../../components/Sidebar.vue'
import Navbar from '../../components/Navbar.vue'

const router = useRouter()
const loading = ref(true)
const logs = ref([])
 
// Cleanup Dialog
const showCleanupDialog = ref(false)
const cleanupLoading = ref(false)
const cleanupDays = ref(30)
const cleanupPassword = ref('')
const cleanupOptions = [
  { title: '30 Hari Terakhir', value: 30 },
  { title: '60 Hari Terakhir', value: 60 },
  { title: '90 Hari Terakhir', value: 90 },
  { title: 'Semua Log', value: 0 },
]

const pagination = ref({
  page: 1,
  itemsPerPage: 15,
  totalItems: 0
})

const filters = ref({
  report_name: '',
  start_date: '',
  end_date: ''
})

const headers = [
  { title: 'Waktu Ekspor', key: 'created_at', width: '200px' },
  { title: 'Pengguna', key: 'user', width: '250px' },
  { title: 'SKPD', key: 'skpd', width: '200px' },
  { title: 'Laporan', key: 'report_name', width: '200px' },
  { title: 'Aksi', key: 'action', width: '150px' },
  { title: 'Deskripsi', key: 'description' },
  { title: 'Alamat IP', key: 'ip_address', width: '150px' }
]

const fetchLogs = async (options = {}) => {
  loading.value = true
  
  if (options.page) pagination.value.page = options.page
  if (options.itemsPerPage) pagination.value.itemsPerPage = options.itemsPerPage

  try {
    const response = await api.get('/export-logs', {
      params: {
        page: pagination.value.page,
        per_page: pagination.value.itemsPerPage,
        ...filters.value
      }
    })
    
    if (response.data.success) {
      logs.value = response.data.data
      pagination.value.totalItems = response.data.meta.total
    }
  } catch (error) {
    console.error('Error fetching logs:', error)
    if (error.response && error.response.status === 403) {
      alert('Anda tidak memiliki akses ke halaman ini.')
      router.push('/dashboard')
    }
  } finally {
    loading.value = false
  }
}
 
const handleCleanup = async () => {
  if (!cleanupPassword.value) {
    alert('Silakan masukkan password konfirmasi.')
    return
  }
 
  cleanupLoading.value = true
  try {
    const response = await api.delete('/export-logs/cleanup', {
      params: { 
        days: cleanupDays.value,
        password: cleanupPassword.value
      }
    })
    if (response.data.success) {
      showCleanupDialog.value = false
      cleanupPassword.value = ''
      alert(response.data.message)
      await fetchLogs()
    }
  } catch (error) {
    alert('Gagal menghapus log: ' + (error.response?.data?.message || error.message))
  } finally {
    cleanupLoading.value = false
  }
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  }).format(date)
}

const formatTime = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  }).format(date)
}

onMounted(() => {
  // Let the v-data-table-server trigger the first load
})
</script>

<style scoped>
.modern-dashboard {
  min-height: 100vh;
  background-color: rgb(var(--v-theme-background));
}

.icon-box {
  width: 56px;
  height: 56px;
  background: rgba(var(--v-theme-primary), 0.1);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.glass-card {
  background: rgba(var(--v-theme-surface), 0.85) !important;
  backdrop-filter: blur(12px);
  border: 1px solid rgba(var(--v-border-color), 0.08) !important;
  border-radius: 20px !important;
}

.custom-table :deep(th) {
  font-weight: 700 !important;
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.05em;
  color: rgba(var(--v-theme-surface-variant), 0.7);
  background: rgba(var(--v-theme-surface), 0.5) !important;
}

.custom-table :deep(td) {
  padding: 12px 16px !important;
}

.font-monospace {
  font-family: monospace;
}
</style>
