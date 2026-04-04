<template>
  <div class="modern-dashboard">
    <Navbar />
    <Sidebar />
    <v-main class="bg-light">
      <v-container fluid class="pa-6">
        <!-- Header Section -->
        <div class="d-flex align-center mb-6">
          <div class="icon-box mr-4">
            <v-icon :icon="config.icon" color="primary" size="32"></v-icon>
          </div>
          <div>
            <h1 class="text-h4 font-weight-bold mb-1">Perhitungan {{ config.label }} PPPK-PW</h1>
            <p class="text-subtitle-1 text-medium-emphasis">
              Simulasi dan Laporan {{ config.label }} PPPK Paruh Waktu (Januari 2026 - Sekarang)
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
              v-if="canManage"
              prepend-icon="mdi-sync"
              :color="meta.is_generated ? 'secondary' : 'primary'"
              :variant="meta.is_generated ? 'tonal' : 'flat'"
              rounded="lg"
              @click="generateData"
              :loading="loading"
              class="text-none"
            >
              {{ meta.is_generated ? 'Sinkronkan Ulang' : 'Generate Data' }}
            </v-btn>
            <v-btn
              v-if="canManage && meta.is_generated"
              prepend-icon="mdi-plus"
              color="primary"
              variant="tonal"
              rounded="lg"
              @click="openAddDialog"
              class="text-none"
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
        <v-card class="glass-card mb-4 pa-4" variant="flat">
          <v-row align="center">
            <v-col cols="12" sm="2">
              <v-select
                v-model="selectedMonth"
                :items="months"
                item-title="title"
                item-value="value"
                :label="'Bulan Pembayaran ' + config.label"
                variant="outlined"
                density="comfortable"
                hide-details
                prepend-inner-icon="mdi-calendar-month"
                @update:modelValue="refreshAll"
                rounded="lg"
              ></v-select>
            </v-col>
            <v-col cols="12" sm="2">
              <v-select
                v-model="selectedSumberDana"
                :items="sumberDanaOptions"
                label="Sumber Dana"
                variant="outlined"
                density="comfortable"
                hide-details
                rounded="lg"
                prepend-inner-icon="mdi-bank"
                @update:model-value="refreshAll"
              ></v-select>
            </v-col>
            <v-col cols="12" sm="3">
              <v-text-field
                v-model="searchInput"
                label="Cari Pegawai / SKPD..."
                variant="outlined"
                density="comfortable"
                hide-details
                prepend-inner-icon="mdi-magnify"
                rounded="lg"
                clearable
              ></v-text-field>
            </v-col>
            <v-col cols="12" sm="2" v-if="isSuperadmin">
              <v-text-field
                v-model="multiplierInput"
                label="Parameter n/12"
                type="number"
                variant="outlined"
                density="comfortable"
                hide-details
                prepend-inner-icon="mdi-calculator"
                rounded="lg"
                suffix="/12"
                @update:modelValue="saveMultiplierDebounced"
              ></v-text-field>
            </v-col>
            <v-col cols="12" sm="auto">
              <v-btn
                color="primary"
                size="large"
                class="rounded-xl font-weight-bold"
                @click="refreshAll"
                prepend-icon="mdi-refresh"
                :loading="loading"
              >
                Refresh
              </v-btn>
            </v-col>
          </v-row>
        </v-card>

        <!-- Stats row -->
        <v-row class="mb-4">
          <v-col cols="12" md="6" lg="3">
            <v-card class="glass-card gradient-card-primary" variant="flat">
              <v-card-text class="d-flex align-center py-4">
                <v-icon size="40" color="white" class="mr-4">mdi-account-group</v-icon>
                <div>
                  <div class="text-white text-caption">Total Pegawai</div>
                  <div class="text-white text-h5 font-weight-black">{{ meta.total_employees || 0 }}</div>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" md="6" lg="3">
            <v-card class="glass-card gradient-card-secondary" variant="flat">
              <v-card-text class="d-flex align-center py-4">
                <v-icon size="40" color="white" class="mr-4">mdi-cash-multiple</v-icon>
                <div>
                  <div class="text-white text-caption">Total {{ config.label }}</div>
                  <div class="text-white text-h5 font-weight-black">{{ formatCurrency(meta.total_amount || 0) }}</div>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <!-- Tabs and Table Section -->
        <v-card class="glass-card overflow-hidden" variant="flat">
          <v-tabs v-model="activeTab" color="primary" align-tabs="start" class="border-b px-4">
            <v-tab value="detail" class="text-none font-weight-bold">
              <v-icon start icon="mdi-account-details-outline"></v-icon> Daftar Pegawai
            </v-tab>
            <v-tab value="skpd" class="text-none font-weight-bold">
              <v-icon start icon="mdi-domain"></v-icon> Rekapitulasi per SKPD
            </v-tab>
            <v-tab value="missing" class="text-none font-weight-bold">
              <v-icon start icon="mdi-account-alert-outline" color="warning"></v-icon> Data Belum Terbentuk
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
                <template v-slot:item.payroll_amount="{ item }">
                  <span class="font-weight-bold text-primary">{{ formatCurrency(item.payroll_amount) }}</span>
                </template>
                <template v-slot:item.actions="{ item }">
                  <div class="d-flex ga-1" v-if="canManage">
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
              <v-data-table-server
                :headers="skpdHeaders"
                :items="skpdGroups"
                :loading="loadingSummary"
                class="custom-table"
                hover
                v-model:items-per-page="itemsPerPageSummary"
                :items-length="totalSummary"
                :search="search"
                @update:options="loadSummaryItems"
              >
                <template v-slot:item.sumber_dana="{ item }">
              <v-chip size="x-small" :color="item.sumber_dana === 'APBD' ? 'indigo' : 'teal'" variant="tonal" class="font-weight-bold">
                {{ item.sumber_dana }}
              </v-chip>
            </template>
            <template v-slot:item.total_amount_skpd="{ item }">
                  <span class="font-weight-bold text-primary">{{ formatCurrency(item.total_amount_skpd) }}</span>
                </template>
                <template v-slot:item.total_employees_skpd="{ item }">
                  <v-chip size="small" variant="tonal" color="secondary" rounded="lg">
                    {{ item.total_employees_skpd }} Pegawai
                  </v-chip>
                </template>
                <template v-slot:no-data>
                  <div class="pa-12 text-center">
                    <v-icon icon="mdi-domain-off" size="64" color="disabled" class="mb-4"></v-icon>
                    <p class="text-h6 text-disabled">Belum ada data rekapitulasi.</p>
                  </div>
                </template>
              </v-data-table-server>
            </v-window-item>

            <!-- Missing Data Tab -->
            <v-window-item value="missing">
              <v-alert
                type="warning"
                variant="tonal"
                class="ma-4 rounded-lg"
                density="compact"
                title="Daftar Terdeteksi Terlewat"
                text="Daftar ini berisi pegawai yang memiliki slip gaji pada bulan basis (Peb/Mei) namun BELUM memiliki data di tabel laporan ini."
              >
                <template v-slot:append>
                  <v-btn
                    color="success"
                    prepend-icon="mdi-file-excel-outline"
                    variant="flat"
                    size="small"
                    class="ml-4"
                    @click="exportMissingData"
                    :loading="exportLoading"
                  >
                    Ekspor Daftar Terlewat
                  </v-btn>
                </template>
              </v-alert>
              <v-data-table-server
                :headers="missingHeaders"
                :items="missingItems"
                :loading="loadingMissing"
                class="custom-table"
                hover
                v-model:items-per-page="itemsPerPageMissing"
                :items-length="totalMissing"
                :search="search"
                @update:options="loadMissingItems"
              >
                <template v-slot:item.gapok_basis="{ item }">
                  {{ formatCurrency(item.gapok_basis) }}
                </template>
                <template v-slot:item.action="{ item }">
                  <v-chip color="warning" size="small" variant="flat">Belum Terbentuk</v-chip>
                </template>
                <template v-slot:no-data>
                  <div class="pa-12 text-center">
                    <v-icon icon="mdi-check-circle-outline" size="64" color="success" class="mb-4"></v-icon>
                    <p class="text-h6 text-success">Semua data gaji basis sudah memiliki record laporan.</p>
                  </div>
                </template>
              </v-data-table-server>
            </v-window-item>
          </v-window>
        </v-card>

        <!-- CRUD Dialogs -->
        <v-dialog v-model="dialogEdit" max-width="500px" persistent>
          <v-card class="glass-card rounded-xl shadow-lg">
            <v-card-title class="pa-6 font-weight-bold">Edit Data {{ config.label }}</v-card-title>
            <v-card-text class="pa-6 pt-0">
              <v-text-field v-model="editedItem.nama" label="Nama Pegawai" variant="outlined" readonly disabled density="comfortable"></v-text-field>
              <v-text-field v-model="editedItem.payroll_amount" :label="'Besaran ' + config.label" variant="outlined" type="number" prefix="Rp" density="comfortable"></v-text-field>
              
              <v-divider class="my-4"></v-divider>
              <p class="text-subtitle-2 mb-2">Informasi PPTK</p>
              <v-text-field v-model="editedItem.pptk_nama" label="Nama PPTK" variant="outlined" placeholder="Nama Lengkap & Gelar" density="comfortable"></v-text-field>
              <v-text-field v-model="editedItem.pptk_nip" label="NIP PPTK" variant="outlined" density="comfortable"></v-text-field>
              <v-text-field v-model="editedItem.pptk_jabatan" label="Jabatan/Pangkat PPTK" variant="outlined" density="comfortable"></v-text-field>
              
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
            <v-card-title class="pa-6 font-weight-bold">Tambah Baris {{ config.label }}</v-card-title>
            <v-card-text class="pa-6 pt-0">
              <v-text-field v-model="newItem.nama" label="Nama Pegawai" variant="outlined" placeholder="Masukkan nama" density="comfortable"></v-text-field>
              <v-text-field v-model="newItem.skpd_name" label="SKPD" variant="outlined" placeholder="Masukkan SKPD" density="comfortable" :readonly="isOperator" :disabled="isOperator"></v-text-field>
              <v-text-field v-model="newItem.nama_sub_giat" label="Sub Kegiatan" variant="outlined" placeholder="Input bebas" density="comfortable"></v-text-field>
              <v-text-field v-model="newItem.payroll_amount" :label="'Besaran ' + config.label" variant="outlined" type="number" prefix="Rp" density="comfortable"></v-text-field>
              
              <v-divider class="my-4"></v-divider>
              <p class="text-subtitle-2 mb-2">Informasi PPTK</p>
              <v-text-field v-model="newItem.pptk_nama" label="Nama PPTK" variant="outlined" density="comfortable"></v-text-field>
              <v-text-field v-model="newItem.pptk_nip" label="NIP PPTK" variant="outlined" density="comfortable"></v-text-field>
              <v-text-field v-model="newItem.pptk_jabatan" label="Jabatan/Pangkat PPTK" variant="outlined" density="comfortable"></v-text-field>
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
          <strong>Dasar Perhitungan ({{ meta.method === 'tetap' ? 'Nilai Tetap' : 'Proporsional n/12' }}):</strong> 
          {{ meta.calculation_basis || 'Memuat informasi calculation basis...' }}
        </v-alert>
      </v-container>
    </v-main>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import api from '../api'
import Sidebar from '../components/Sidebar.vue'
import Navbar from '../components/Navbar.vue'

const props = defineProps({
  type: {
    type: String,
    required: true, // 'thr' or 'gaji13'
  }
})

const config = computed(() => {
  if (props.type === 'thr') {
    return {
      label: 'THR',
      icon: 'mdi-cash-fast',
      apiBase: '/thr/pppk-pw',
      defaultMonth: 3
    }
  }
  return {
    label: 'Gaji 13',
    icon: 'mdi-cash-check',
    apiBase: '/gaji13/pppk-pw',
    defaultMonth: 6
  }
})

const loading = ref(false)
const loadingSummary = ref(false)
const loadingMissing = ref(false)
const exportLoading = ref(false)
const selectedMonth = ref(config.value.defaultMonth)
const items = ref([])
const skpdGroups = ref([])
const missingItems = ref([])
const meta = ref({})
const activeTab = ref('detail')
const user = JSON.parse(localStorage.getItem('user') || '{}')
const isSuperadmin = computed(() => user.role === 'superadmin')
const isOperator = computed(() => user.role === 'operator')
const canManage = computed(() => isSuperadmin.value)

// Pagination Refs
const itemsPerPage = ref(15)
const totalItems = ref(0)
const itemsPerPageSummary = ref(15)
const totalSummary = ref(0)
const itemsPerPageMissing = ref(15)
const totalMissing = ref(0)
const page = ref(1)
const search = ref('')
const searchInput = ref('')
const serverOptions = ref({})
const serverOptionsSummary = ref({})
const serverOptionsMissing = ref({})

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
  pptk_nama: '',
  pptk_nip: '',
  pptk_jabatan: '',
  payroll_amount: 0,
  year: 2026,
  month: selectedMonth.value
})

const multiplierInput = ref(2)
const selectedSumberDana = ref('Semua')
const selectedYear = ref(2026)
const sumberDanaOptions = ['Semua', 'APBD', 'BLUD']
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
  { title: 'Jabatan', key: 'jabatan', align: 'start', sortable: true },
  { title: 'SKPD', key: 'skpd', align: 'start', sortable: true },
  { title: 'Sumber Dana', key: 'sumber_dana', align: 'start', width: 120 },
  { title: 'Gaji Pokok', key: 'gapok_basis', align: 'end', sortable: true },
  { title: 'Nama Pegawai', key: 'nama', align: 'start', sortable: true },
  { title: 'NIP', key: 'nip', align: 'start' },
  { title: 'Sub Kegiatan', key: 'sub_giat', align: 'start' },
  { title: 'PPTK', key: 'pptk_nama', align: 'start' },
  { title: 'Gapok (Basis)', key: 'gapok_basis', align: 'end' },
  { title: 'Masa Kerja', key: 'n_months', align: 'center' },
  { title: 'Besaran ' + config.value.label, key: 'payroll_amount', align: 'end' },
  { title: 'Aksi', key: 'actions', align: 'center', sortable: false },
]

const skpdHeaders = [
  { title: 'Satuan Kerja (SKPD)', key: 'skpd_name', align: 'start', sortable: true },
  { title: 'Sumber Dana', key: 'sumber_dana', align: 'start', width: 120 },
  { title: 'Jumlah Pegawai', key: 'total_employees_skpd', align: 'center', sortable: true },
  { title: 'Total Pembayaran ' + config.value.label, key: 'total_amount_skpd', align: 'end', sortable: true },
]

const missingHeaders = [
  { title: 'SKPD', key: 'skpd_name', align: 'start' },
  { title: 'Nama Pegawai', key: 'nama', align: 'start' },
  { title: 'NIP', key: 'nip', align: 'start' },
  { title: 'Jabatan', key: 'jabatan', align: 'start' },
  { title: 'Gapok (Basis)', key: 'gapok_basis', align: 'end' },
  { title: 'Alasan Belum Terbentuk', key: 'reason', align: 'start' },
  { title: 'Status', key: 'action', align: 'center' },
]

const showSnackbar = (text, color = 'success') => {
  snackbarText.value = text
  snackbarColor.value = color
  snackbar.value = true
}

const loadItems = async (options) => {
  serverOptions.value = options
  loading.value = true
  try {
    const { page, itemsPerPage, sortBy, search } = options
    const response = await api.get(config.value.apiBase, {
      params: {
        page,
        per_page: itemsPerPage,
        search,
        sumber_dana: selectedSumberDana.value,
        year: selectedYear.value,
        month: selectedMonth.value
      }
    })
    
    items.value = response.data.data
    totalItems.value = response.data.meta.total
    meta.value = response.data.meta
    if (response.data.meta.multiplier) {
      multiplierInput.value = response.data.meta.multiplier
    }
  } catch (error) {
    console.error('Error loading payroll items:', error)
    showSnackbar('Gagal memuat data pegawai', 'error')
  } finally {
    loading.value = false
  }
}

const loadSummaryItems = async (options) => {
  serverOptionsSummary.value = options
  loadingSummary.value = true
  try {
    const { page, itemsPerPage, search } = options
    const response = await api.get(config.value.apiBase + '/summary', {
      params: { 
        year: selectedYear.value,
        month: selectedMonth.value,
        sumber_dana: selectedSumberDana.value,
        page,
        per_page: itemsPerPage,
        search
      }
    })
    skpdGroups.value = response.data.data
    totalSummary.value = response.data.meta.total
    if (response.data.meta) {
      meta.value = { ...meta.value, ...response.data.meta }
    }
  } catch (error) {
    console.error('Error fetching summary:', error)
    showSnackbar('Gagal memuat rekapitulasi', 'error')
  } finally {
    loadingSummary.value = false
  }
}

const loadMissingItems = async (options) => {
  serverOptionsMissing.value = options
  loadingMissing.value = true
  try {
    const { page, itemsPerPage, search } = options
    const response = await api.get(config.value.apiBase + '/missing', {
      params: { 
        year: selectedYear.value,
        month: selectedMonth.value,
        page,
        per_page: itemsPerPage,
        search
      }
    })
    
    missingItems.value = response.data.data
    totalMissing.value = response.data.meta.total
  } catch (error) {
    console.error('Error loading missing items:', error)
    showSnackbar('Gagal memuat data terlewat', 'error')
  } finally {
    loadingMissing.value = false
  }
}

const refreshAll = () => {
  loadItems({ 
    page: 1, 
    itemsPerPage: serverOptions.value.itemsPerPage || 15, 
    sortBy: serverOptions.value.sortBy || [],
    search: searchInput.value 
  })
  if (activeTab.value === 'skpd') {
    loadSummaryItems({ page: 1, itemsPerPage: 15, search: searchInput.value })
  } else if (activeTab.value === 'missing') {
    loadMissingItems({ page: 1, itemsPerPage: 15, search: searchInput.value })
  }
}

watch(activeTab, (newTab) => {
  if (newTab === 'skpd' && skpdGroups.value.length === 0) {
    loadSummaryItems({ page: 1, itemsPerPage: 15, search: searchInput.value })
  } else if (newTab === 'missing' && missingItems.value.length === 0) {
    loadMissingItems({ page: 1, itemsPerPage: 15, search: searchInput.value })
  }
})

const generateData = async () => {
  if (!confirm(`Hasilkan data ${config.value.label} otomatis berdasarkan gaji basis? Data yang sudah ada di periode ini akan ditimpa.`)) return
  
  loading.value = true
  try {
    const response = await api.post(config.value.apiBase + '/generate', {
      month: selectedMonth.value
    })
    if (response.data.success) {
      showSnackbar(`Data ${config.value.label} berhasil di-generate`)
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
    await api.put(`${config.value.apiBase}/${editedItem.value.id}`, editedItem.value)
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
  if (!confirm(`Hapus data ${config.value.label} untuk ${item.nama}?`)) return
  try {
    await api.delete(`${config.value.apiBase}/${item.id}`)
    showSnackbar('Data berhasil dihapus')
    refreshAll()
  } catch (error) {
    showSnackbar('Gagal menghapus data', 'error')
  }
}

const openAddDialog = () => {
  newItem.value = {
    nama: '',
    skpd_name: isOperator.value ? (user.skpd?.nama_skpd || '') : '',
    nama_sub_giat: '',
    pptk_nama: '',
    pptk_nip: '',
    pptk_jabatan: '',
    payroll_amount: 0,
    year: 2026,
    month: selectedMonth.value
  }
  dialogAdd.value = true
}

const saveMultiplier = async () => {
  try {
    const key = props.type === 'thr' ? 'thr_pppk_pw_multiplier' : 'gaji13_pppk_pw_multiplier'
    await api.post('/settings', {
      settings: [{ key, value: multiplierInput.value }]
    })
    showSnackbar('Parameter berhasil disimpan. Silahkan Generate Ulang data.')
  } catch (error) {
    showSnackbar('Gagal menyimpan parameter', 'error')
  }
}

let multiplierTimeout = null
const saveMultiplierDebounced = () => {
  if (multiplierTimeout) clearTimeout(multiplierTimeout)
  multiplierTimeout = setTimeout(() => {
    saveMultiplier()
  }, 1000)
}

const saveAdd = async () => {
  saving.value = true
  try {
    await api.post(config.value.apiBase + '/store', newItem.value)
    showSnackbar('Baris baru berhasil ditambahkan')
    dialogAdd.value = false
    refreshAll()
  } catch (error) {
    showSnackbar('Gagal menambahkan data', 'error')
  } finally {
    saving.value = false
  }
}

const activeJobId = ref(null)

const queryJobStatus = async (jobId) => {
  try {
    const res = await api.get(`/upload-jobs/${jobId}`)
    const job = res.data.data
    if (job.status === 'completed') {
      showSnackbar('Pembuatan dokumen selesai. Mengunduh...', 'success')
      if (job.result_summary && job.result_summary.download_url) {
        window.open(job.result_summary.download_url, '_blank')
      }
      activeJobId.value = null
      exportLoading.value = false
    } else if (job.status === 'failed') {
      showSnackbar('Gagal membuat dokumen: ' + (job.error_message || ''), 'error')
      activeJobId.value = null
      exportLoading.value = false
    } else {
      setTimeout(() => queryJobStatus(jobId), 3000)
    }
  } catch (error) {
    console.error('Error checking job status', error)
    showSnackbar('Gagal mengecek status tugas (Job)', 'error')
    activeJobId.value = null
    exportLoading.value = false
  }
}

const exportData = async (type) => {
  exportLoading.value = true
  try {
    const url = `${config.value.apiBase}/${type}?month=${selectedMonth.value}`
    
    if (type === 'pdf') {
      const response = await api.get(url)
      if (response.data && response.data.job_id) {
        activeJobId.value = response.data.job_id
        showSnackbar('Permintaan ekspor PDF dikirim ke antrian background. Mohon tunggu...', 'info')
        setTimeout(() => queryJobStatus(response.data.job_id), 3000)
      } else {
        exportLoading.value = false
      }
    } else {
      const response = await api.get(url, { responseType: 'blob' })
      const blob = new Blob([response.data])
      const link = document.createElement('a')
      link.href = window.URL.createObjectURL(blob)
      link.download = `${config.value.label}_PPPK_PW_2026_${selectedMonth.value}.${type === 'excel' ? 'xlsx' : 'pdf'}`
      link.click()
      exportLoading.value = false
    }
  } catch (error) {
    console.error(`Error exporting ${type}:`, error)
    showSnackbar(`Gagal mengekspor ${type}. Silakan coba lagi.`, 'error')
    exportLoading.value = false
  }
}

const exportMissingData = async () => {
  exportLoading.value = true
  try {
    const url = `${config.value.apiBase}/missing/export?month=${selectedMonth.value}`
    const response = await api.get(url, { responseType: 'blob' })
    
    const blob = new Blob([response.data])
    const link = document.createElement('a')
    link.href = window.URL.createObjectURL(blob)
    link.download = `DAFTAR_TERLEWAT_${config.value.label}_2026_${selectedMonth.value}.xlsx`
    link.click()
  } catch (error) {
    console.error('Error exporting missing data:', error)
    alert('Gagal mengekspor daftar terlewat.')
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

.gradient-card-primary {
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%) !important;
  box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3) !important;
}

.gradient-card-secondary {
  background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%) !important;
  box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3) !important;
}
</style>
