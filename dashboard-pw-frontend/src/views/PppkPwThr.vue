<template>
  <div class="modern-dashboard">
    <Navbar />
    <Sidebar />
    <v-main class="bg-light">
      <v-container fluid class="pa-6">
    <!-- Header Section -->
    <div class="d-flex align-center mb-6">
      <div class="icon-box mr-4">
        <v-icon icon="mdi-cash-fast" color="primary" size="32"></v-icon>
      </div>
      <div>
        <h1 class="text-h4 font-weight-bold mb-1">Perhitungan THR PPPK-PW</h1>
        <p class="text-subtitle-1 text-medium-emphasis">
          Simulasi dan Laporan THR PPPK Paruh Waktu (Januari 2026 - Sekarang)
        </p>
      </div>
      <v-spacer></v-spacer>
      <div class="d-flex ga-2">
        <v-btn
          v-if="isSuperadmin"
          prepend-icon="mdi-cog-outline"
          color="secondary"
          variant="tonal"
          rounded="lg"
          to="/settings/pppk"
        >
          Pengaturan
        </v-btn>
        <v-btn
          v-if="isSuperadmin && !meta.is_generated"
          prepend-icon="mdi-sync"
          color="primary"
          rounded="lg"
          @click="generateData"
          :loading="loading"
        >
          Generate Data
        </v-btn>
        <v-btn
          v-else-if="isSuperadmin && meta.is_generated"
          prepend-icon="mdi-plus"
          color="primary"
          variant="tonal"
          rounded="lg"
          @click="openAddDialog"
        >
          Tambah Baris
        </v-btn>
        <v-btn
          prepend-icon="mdi-file-excel-outline"
          color="success"
          variant="tonal"
          rounded="lg"
          @click="exportData('excel')"
          :loading="exportLoading"
        >
          Ekspor Excel
        </v-btn>
        <v-btn
          prepend-icon="mdi-file-pdf-box"
          color="error"
          variant="tonal"
          rounded="lg"
          @click="exportData('pdf')"
          :loading="exportLoading"
        >
          Cetak PDF
        </v-btn>
      </div>
    </div>

    <!-- Filter Card -->
    <v-card class="glass-card mb-6 pa-4" variant="flat">
      <v-row align="center">
        <v-col cols="12" sm="4">
          <v-select
            v-model="selectedMonth"
            :items="months"
            item-title="title"
            item-value="value"
            label="Bulan Pembayaran THR"
            variant="outlined"
            density="comfortable"
            hide-details
            prepend-inner-icon="mdi-calendar-month"
            @update:modelValue="refreshAll"
            rounded="lg"
          ></v-select>
        </v-col>
        <v-col cols="12" sm="4">
          <v-text-field
            v-model="searchInput"
            label="Cari Nama / NIP / SKPD..."
            variant="outlined"
            density="comfortable"
            hide-details
            prepend-inner-icon="mdi-magnify"
            rounded="lg"
            clearable
          ></v-text-field>
        </v-col>
        <v-col cols="12" sm="8">
          <div class="d-flex ga-4 justify-end">
            <v-chip color="primary" variant="flat" size="large" class="px-4 py-6 rounded-xl">
              <v-icon start icon="mdi-account-group"></v-icon>
              <div class="d-flex flex-column align-start ml-2">
                <span class="text-caption" style="line-height: 1">Total Pegawai</span>
                <span class="text-h6 font-weight-black">{{ meta.total_employees || 0 }}</span>
              </div>
            </v-chip>
            <v-chip color="secondary" variant="flat" size="large" class="px-4 py-6 rounded-xl">
              <v-icon start icon="mdi-cash-multiple"></v-icon>
              <div class="d-flex flex-column align-start ml-2">
                <span class="text-caption" style="line-height: 1">Total THR</span>
                <span class="text-h6 font-weight-black">{{ formatCurrency(meta.total_thr_amount || 0) }}</span>
              </div>
            </v-chip>
          </div>
        </v-col>
      </v-row>
    </v-card>

    <!-- Tabs and Table Section -->
    <v-card class="glass-card overflow-hidden" variant="flat">
      <v-tabs v-model="activeTab" color="primary" align-tabs="start" class="border-b px-4">
        <v-tab value="detail" class="text-none font-weight-bold">
          <v-icon start icon="mdi-account-details-outline"></v-icon> Daftar Pegawai
        </v-tab>
        <v-tab value="skpd" class="text-none font-weight-bold">
          <v-icon start icon="mdi-domain"></v-icon> Rekapitulasi per SKPD
        </v-tab>
      </v-tabs>

      <v-window v-model="activeTab">
        <!-- Detail Tab -->
        <v-window-item value="detail">
          <v-data-table-server
            :headers="headers"
            :items="items"
            :loading="loading"
            class="custom-table"
            hover
            v-model:items-per-page="itemsPerPage"
            :items-length="totalItems"
            :search="search"
            @update:options="loadItems"
          >
            <template v-slot:item.gapok_basis="{ item }">
              {{ formatCurrency(item.gapok_basis) }}
            </template>
            <template v-slot:item.n_months="{ item }">
              <v-chip size="small" variant="tonal" color="info" rounded="lg">
                {{ item.n_months }}/12
              </v-chip>
            </template>
            <template v-slot:item.thr_amount="{ item }">
              <span class="font-weight-bold text-primary">{{ formatCurrency(item.thr_amount) }}</span>
            </template>
            <template v-slot:item.actions="{ item }">
              <div class="d-flex ga-1" v-if="isSuperadmin">
                <v-btn icon="mdi-pencil" size="x-small" variant="text" color="blue" @click="editItem(item)"></v-btn>
                <v-btn icon="mdi-delete" size="x-small" variant="text" color="error" @click="deleteItem(item)"></v-btn>
              </div>
            </template>
            
            <template v-slot:no-data>
              <div class="pa-12 text-center">
                <v-icon icon="mdi-account-search-outline" size="64" color="disabled" class="mb-4"></v-icon>
                <p class="text-h6 text-disabled">Gagal memuat data atau data masih kosong.</p>
              </div>
            </template>
          </v-data-table-server>
        </v-window-item>

        <!-- SKPD Tab -->
        <v-window-item value="skpd">
          <v-data-table
            :headers="skpdHeaders"
            :items="skpdGroups"
            :loading="loadingSummary"
            class="custom-table"
            hover
            :items-per-page="-1"
            hide-default-footer
          >
            <template v-slot:item.total_thr_skpd="{ item }">
              <span class="font-weight-bold text-primary">{{ formatCurrency(item.total_thr_skpd) }}</span>
            </template>
            <template v-slot:item.total_employees_skpd="{ item }">
              <v-chip size="small" variant="tonal" color="secondary" rounded="lg">
                {{ item.total_employees_skpd }} Pegawai
              </v-chip>
            </template>
          </v-data-table>
        </v-window-item>
      </v-window>
    </v-card>

    <!-- CRUD Dialogs -->
    <v-dialog v-model="dialogEdit" max-width="500px" persistent>
      <v-card class="glass-card rounded-xl shadow-lg">
        <v-card-title class="pa-6 font-weight-bold">Edit Data THR</v-card-title>
        <v-card-text class="pa-6 pt-0">
          <v-text-field v-model="editedItem.nama" label="Nama Pegawai" variant="outlined" readonly disabled density="comfortable"></v-text-field>
          <v-text-field v-model="editedItem.thr_amount" label="Besaran THR" variant="outlined" type="number" prefix="Rp" density="comfortable"></v-text-field>
          <v-textarea v-model="editedItem.notes" label="Keterangan" variant="outlined" rows="2" density="comfortable"></v-textarea>
        </v-card-text>
        <v-card-actions class="pa-6 pt-0">
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="dialogEdit = false" class="text-none">Batal</v-btn>
          <v-btn color="primary" variant="flat" rounded="lg" @click="saveEdit" :loading="saving" class="text-none px-6">Simpan</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="dialogAdd" max-width="500px" persistent>
      <v-card class="glass-card rounded-xl shadow-lg">
        <v-card-title class="pa-6 font-weight-bold">Tambah Baris THR</v-card-title>
        <v-card-text class="pa-6 pt-0">
          <v-text-field v-model="newItem.nama" label="Nama Pegawai" variant="outlined" placeholder="Masukkan nama" density="comfortable"></v-text-field>
          <v-text-field v-model="newItem.skpd_name" label="SKPD" variant="outlined" placeholder="Masukkan SKPD" density="comfortable"></v-text-field>
          <v-text-field v-model="newItem.nama_sub_giat" label="Sub Kegiatan" variant="outlined" placeholder="Input bebas" density="comfortable"></v-text-field>
          <v-text-field v-model="newItem.thr_amount" label="Besaran THR" variant="outlined" type="number" prefix="Rp" density="comfortable"></v-text-field>
        </v-card-text>
        <v-card-actions class="pa-6 pt-0">
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="dialogAdd = false" class="text-none">Batal</v-btn>
          <v-btn color="primary" variant="flat" rounded="lg" @click="saveAdd" :loading="saving" class="text-none px-6">Tambah</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar" :color="snackbarColor" rounded="xl" elevation="24">
      {{ snackbarText }}
    </v-snackbar>

    <!-- Formula Info -->
    <v-alert
      type="info"
      variant="tonal"
      class="mt-6 rounded-lg"
      icon="mdi-calculator-variant"
    >
      <strong>Dasar Perhitungan ({{ meta.thr_method === 'tetap' ? 'Nilai Tetap' : 'Proporsional n/12' }}):</strong> 
      {{ meta.calculation_basis || 'Memuat informasi calculation basis...' }}
    </v-alert>
      </v-container>
    </v-main>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '../api'
import Sidebar from '../components/Sidebar.vue'
import Navbar from '../components/Navbar.vue'

const loading = ref(false)
const loadingSummary = ref(false)
const exportLoading = ref(false)
const selectedMonth = ref(4) // Default to April
const items = ref([])
const skpdGroups = ref([])
const meta = ref({})
const activeTab = ref('detail')
const user = JSON.parse(localStorage.getItem('user') || '{}')
const isSuperadmin = computed(() => user.role === 'superadmin')

// Pagination Refs
const itemsPerPage = ref(15)
const totalItems = ref(0)
const page = ref(1)
const search = ref('')
const searchInput = ref('')
const serverOptions = ref({})

// Debounce search
let searchTimeout = null
watch(searchInput, (val) => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    search.value = val
  }, 500)
})

// CRUD Refs
const dialogEdit = ref(false)
const dialogAdd = ref(false)
const saving = ref(false)
const editedItem = ref({})
const newItem = ref({
  nama: '',
  skpd_name: '',
  nama_sub_giat: '',
  thr_amount: 0,
  year: 2026,
  month: 4
})

const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

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

const headers = [
  { title: 'SKPD', key: 'skpd', align: 'start', sortable: true },
  { title: 'Nama Pegawai', key: 'nama', align: 'start', sortable: true },
  { title: 'NIP', key: 'nip', align: 'start' },
  { title: 'Jabatan', key: 'jabatan', align: 'start' },
  { title: 'Sub Kegiatan', key: 'sub_giat', align: 'start' },
  { title: 'Gapok (Basis)', key: 'gapok_basis', align: 'end' },
  { title: 'Masa Kerja', key: 'n_months', align: 'center' },
  { title: 'Besaran THR', key: 'thr_amount', align: 'end' },
  { title: 'Aksi', key: 'actions', align: 'center', sortable: false },
]

const skpdHeaders = [
  { title: 'Satuan Kerja (SKPD)', key: 'skpd_name', align: 'start', sortable: true },
  { title: 'Jumlah Pegawai', key: 'total_employees_skpd', align: 'center', sortable: true },
  { title: 'Total Pembayaran THR', key: 'total_thr_skpd', align: 'end', sortable: true },
]

const showSnackbar = (text, color = 'success') => {
  snackbarText.value = text
  snackbarColor.value = color
  snackbar.value = true
}

const refreshAll = () => {
  if (activeTab.value === 'detail') {
    loadItems(serverOptions.value)
  } else {
    fetchSummary()
  }
}

const loadItems = async (options) => {
  serverOptions.value = options
  loading.value = true
  try {
    const { page, itemsPerPage, sortBy, search } = options
    const response = await api.get('/thr/pppk-pw', {
      params: { 
        month: selectedMonth.value,
        page,
        per_page: itemsPerPage,
        search
      }
    })
    
    items.value = response.data.data
    totalItems.value = response.data.meta.total
    meta.value = response.data.meta
  } catch (error) {
    console.error('Error loading THR items:', error)
    showSnackbar('Gagal memuat data pegawai', 'error')
  } finally {
    loading.value = false
  }
}

const fetchSummary = async () => {
  loadingSummary.value = true
  try {
    const response = await api.get('/thr/pppk-pw/summary', {
      params: { month: selectedMonth.value }
    })
    skpdGroups.value = response.data.data
  } catch (error) {
    console.error('Error fetching summary:', error)
    showSnackbar('Gagal memuat rekapitulasi', 'error')
  } finally {
    loadingSummary.value = false
  }
}

// Watch for tab change
import { watch } from 'vue'
watch(activeTab, (newTab) => {
  if (newTab === 'skpd' && skpdGroups.value.length === 0) {
    fetchSummary()
  }
})

const generateData = async () => {
  if (!confirm('Hasilkan data THR otomatis berdasarkan gaji Februari? Data yang sudah ada di periode ini akan ditimpa.')) return
  
  loading.value = true
  try {
    const response = await api.post('/thr/pppk-pw/generate', {
      month: selectedMonth.value
    })
    if (response.data.success) {
      showSnackbar('Data THR berhasil di-generate')
      refreshAll()
    }
  } catch (error) {
    showSnackbar('Gagal generate data: ' + error.message, 'error')
  } finally {
    loading.value = false
  }
}

const editItem = (item) => {
  editedItem.value = { ...item }
  dialogEdit.value = true
}

const saveEdit = async () => {
  saving.value = true
  try {
    await api.put(`/thr/pppk-pw/${editedItem.value.id}`, editedItem.value)
    showSnackbar('Data berhasil diperbarui')
    dialogEdit.value = false
    refreshAll()
  } catch (error) {
    showSnackbar('Gagal menyimpan perubahan', 'error')
  } finally {
    saving.value = false
  }
}

const deleteItem = async (item) => {
  if (!confirm(`Hapus data THR untuk ${item.nama}?`)) return
  try {
    await api.delete(`/thr/pppk-pw/${item.id}`)
    showSnackbar('Data berhasil dihapus')
    refreshAll()
  } catch (error) {
    showSnackbar('Gagal menghapus data', 'error')
  }
}

const openAddDialog = () => {
  newItem.value = {
    nama: '',
    skpd_name: '',
    nama_sub_giat: '',
    thr_amount: 0,
    year: 2026,
    month: selectedMonth.value
  }
  dialogAdd.value = true
}

const saveAdd = async () => {
  saving.value = true
  try {
    await api.post('/thr/pppk-pw/store', newItem.value)
    showSnackbar('Baris baru berhasil ditambahkan')
    dialogAdd.value = false
    refreshAll()
  } catch (error) {
    showSnackbar('Gagal menambahkan data', 'error')
  } finally {
    saving.value = false
  }
}

const exportData = async (type) => {
  exportLoading.value = true
  try {
    const url = `/thr/pppk-pw/${type}?month=${selectedMonth.value}`
    const response = await api.get(url, { responseType: 'blob' })
    
    const blob = new Blob([response.data])
    const link = document.createElement('a')
    link.href = window.URL.createObjectURL(blob)
    link.download = `THR_PPPK_PW_2026_${selectedMonth.value}.${type === 'excel' ? 'xlsx' : 'pdf'}`
    link.click()
  } catch (error) {
    console.error(`Error exporting ${type}:`, error)
    alert(`Gagal mengekspor ${type}. Silakan coba lagi.`)
  } finally {
    exportLoading.value = false
  }
}

const formatCurrency = (value) => {
  if (!value) return '-'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(value)
}

onMounted(() => {
  // refreshAll() // v-data-table-server will call loadItems automatically on mount
})
</script>

<style scoped>
.glass-card {
  background: rgba(var(--v-theme-surface), 0.85) !important;
  backdrop-filter: blur(12px);
  border: 1px solid rgba(var(--v-border-color), 0.08) !important;
  border-radius: 20px !important;
}

.icon-box {
  width: 56px;
  height: 56px;
  background: rgb(var(--v-theme-primary), 0.1);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
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
  font-size: 0.875rem;
  padding: 12px 16px !important;
}

.transition-all {
  transition: all 0.3s ease;
}
</style>
