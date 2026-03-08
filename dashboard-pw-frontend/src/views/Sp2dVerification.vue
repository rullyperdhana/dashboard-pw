<template>
  <div class="modern-dashboard">
    <Navbar @show-coming-soon="showComingSoon" />
    <Sidebar @show-coming-soon="showComingSoon" />
    
    <v-main class="bg-light min-vh-100">
      <v-container fluid class="pa-8">
        <v-row>
          <v-col cols="12">
            <div class="d-flex align-center mb-6">
              <v-btn icon="mdi-arrow-left" variant="text" @click="$router.push('/dashboard')" class="mr-2"></v-btn>
              <div>
                <h1 class="text-h4 font-weight-bold mb-1">Verifikasi Realisasi SP2D</h1>
                <p class="text-subtitle-1 text-medium-emphasis mb-0">Monitor pencairan Gaji & TPP berdasarkan data SIPD</p>
              </div>
            </div>
          </v-col>
        </v-row>

    <!-- Top Controls Bar -->
    <v-row>
      <v-col cols="12">
        <v-card class="glass-card rounded-xl pa-4 mb-6" elevation="0">
          <v-row align="center">
            <!-- Period & Type Selection -->
            <v-col cols="12" md="6">
              <div class="d-flex gap-2">
                <v-select
                  v-model="selectedMonth"
                  :items="months"
                  label="Bulan"
                  density="compact"
                  variant="outlined"
                  rounded="lg"
                  hide-details
                  class="flex-grow-1"
                  @update:model-value="fetchData"
                ></v-select>
                <v-select
                  v-model="selectedYear"
                  :items="years"
                  label="Tahun"
                  density="compact"
                  variant="outlined"
                  rounded="lg"
                  hide-details
                  style="width: 100px"
                  @update:model-value="fetchData"
                ></v-select>
                <v-select
                  v-model="selectedJenisGaji"
                  :items="['Induk', 'Susulan', 'Kekurangan', 'Terusan']"
                  label="Jenis Gaji"
                  density="compact"
                  variant="outlined"
                  rounded="lg"
                  hide-details
                  clearable
                  class="flex-grow-1"
                  @update:model-value="fetchData"
                ></v-select>
              </div>
            </v-col>

            <!-- Upload Zone -->
            <v-col cols="12" md="3">
              <div 
                class="upload-zone-compact pa-2 px-4 d-flex align-center rounded-xl border-dashed cursor-pointer"
                :class="{ 'is-dragging': isDragging }"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop"
                @click="$refs.fileInput.click()"
              >
                <input type="file" ref="fileInput" class="d-none" @change="handleFileSelect" accept=".xlsx,.xls">
                <v-icon size="24" color="primary" class="mr-3">mdi-file-excel-outline</v-icon>
                <div class="flex-grow-1">
                  <div class="text-caption font-weight-bold">Impor Register SIPD</div>
                  <div class="text-overline" style="font-size: 8px !important; line-height: 1">Tarik file ke sini atau klik</div>
                </div>
                <v-progress-circular v-if="uploading" indeterminate size="16" width="2" color="primary" class="ml-2"></v-progress-circular>
              </div>
            </v-col>

            <!-- View Mode Switcher -->
            <v-col cols="12" md="3" class="d-flex justify-end">
              <v-btn-toggle
                v-model="viewMode"
                mandatory
                color="primary"
                density="compact"
                rounded="pill"
                variant="tonal"
              >
                <v-btn value="summary" size="small" prepend-icon="mdi-view-list">Ringkasan</v-btn>
                <v-btn value="details" size="small" prepend-icon="mdi-table">Detail Data</v-btn>
                <v-btn value="recon" size="small" prepend-icon="mdi-compare">Table Rekon</v-btn>
              </v-btn-toggle>
            </v-col>
          </v-row>
        </v-card>
      </v-col>
    </v-row>

    <!-- Results Table (Full Width) -->
    <v-row>
      <v-col cols="12">
        <!-- Summary View -->
        <v-card v-if="viewMode === 'summary'" class="glass-card rounded-xl overflow-hidden" elevation="0">
          <div class="pa-6 border-bottom d-flex align-center justify-space-between bg-surface-variant-light">
            <h2 class="text-h6 font-weight-bold mb-0">Status Realisasi per SKPD</h2>
              <v-text-field
                v-model="search"
                prepend-inner-icon="mdi-magnify"
                label="Cari SKPD..."
                single-line
                hide-details
                density="compact"
                variant="outlined"
                rounded="pill"
                class="search-bar-300"
              ></v-text-field>
          </div>
          
          <v-data-table
            :headers="headers"
            :items="items"
            :loading="loading"
            :search="search"
            class="bg-transparent"
            hover
          >
            <template v-slot:item.nama_skpd="{ item }">
              <div class="font-weight-medium text-truncate" style="max-width: 250px;">
                {{ item.nama_skpd }}
              </div>
            </template>

            <template v-slot:item.pns="{ item }">
              <status-chip :status="item.pns" />
            </template>

            <template v-slot:item.pppk="{ item }">
              <status-chip :status="item.pppk" />
            </template>

            <template v-slot:item.tpp="{ item }">
              <status-chip :status="item.tpp" />
            </template>
          </v-data-table>
        </v-card>

        <!-- Detailed View (Raw Transactions) -->
        <v-card v-else-if="viewMode === 'details'" class="glass-card rounded-xl overflow-hidden" elevation="0">
          <div class="pa-6 border-bottom d-flex align-center justify-space-between bg-surface-variant-light">
            <h2 class="text-h6 font-weight-bold mb-0">Daftar Transaksi Hasil Impor</h2>
            <div class="d-flex align-center gap-2">
              <v-text-field
                v-model="searchDetail"
                prepend-inner-icon="mdi-magnify"
                label="Cari..."
                single-line
                hide-details
                density="compact"
                variant="outlined"
                rounded="pill"
                class="search-bar-300"
              ></v-text-field>
            </div>
          </div>
          
          <v-data-table
            :headers="detailHeaders"
            :items="transactions"
            :loading="loading"
            :search="searchDetail"
            class="bg-transparent"
            hover
          >
            <template v-slot:item.nomor_sp2d="{ item }">
              <div class="text-caption font-weight-bold">{{ item.nomor_sp2d }}</div>
            </template>
            
            <template v-slot:item.tanggal_sp2d="{ item }">
              {{ formatDate(item.tanggal_sp2d) }}
            </template>

            <template v-slot:item.jenis_data="{ item }">
              <v-chip size="x-small" :color="getTypeColor(item.jenis_data)" variant="flat">
                {{ item.jenis_data }}
              </v-chip>
            </template>

            <template v-slot:item.netto="{ item }">
              <div class="text-right font-weight-bold">{{ formatCurrency(item.netto) }}</div>
            </template>
            
            <template v-slot:item.nama_skpd_sipd="{ item }">
              <div class="text-caption" style="max-width: 150px;">{{ item.nama_skpd_sipd }}</div>
            </template>

            <template v-slot:item.actions="{ item }">
              <div class="d-flex justify-end gap-1">
                <v-btn icon="mdi-pencil" size="x-small" variant="text" color="primary" @click="openEditDialog(item)"></v-btn>
                <v-btn icon="mdi-delete" size="x-small" variant="text" color="error" @click="confirmDelete(item)"></v-btn>
              </div>
            </template>
          </v-data-table>
        </v-card>

        <!-- Reconciliation Table View -->
        <v-card v-else-if="viewMode === 'recon'" class="glass-card rounded-xl overflow-hidden" elevation="0">
          <div class="pa-6 border-bottom d-flex align-center justify-space-between bg-surface-variant-light">
            <h2 class="text-h6 font-weight-bold mb-0">Tabel Rekonsiliasi SIMGAJI vs SIPD</h2>
            <div class="d-flex align-center gap-2">
              <v-text-field
                v-model="searchRecon"
                prepend-inner-icon="mdi-magnify"
                label="Cari SKPD..."
                single-line
                hide-details
                density="compact"
                variant="outlined"
                rounded="pill"
                class="search-bar-300"
              ></v-text-field>
              <v-btn 
                color="primary" 
                variant="tonal" 
                prepend-icon="mdi-export" 
                size="small" 
                rounded="pill"
                @click="exportExcel"
                :loading="exporting"
              >Export Excel</v-btn>
            </div>
          </div>
          
          <div class="recon-table-container">
            <v-table density="compact" class="recon-table" fixed-header hover>
              <thead>
                <tr class="header-group-row">
                  <th colspan="10" class="text-center simgaji-header">SIMGAJI</th>
                  <th colspan="8" class="text-center sipd-header">SIPD</th>
                </tr>
                <tr class="header-main-row">
                  <th rowspan="2" class="border-right">No</th>
                  <th rowspan="2" class="border-right cursor-pointer" style="min-width: 200px" @click="toggleSort('nama_skpd')">
                    SKPD SIMGAJI <v-icon size="14">{{ getSortIcon('nama_skpd') }}</v-icon>
                  </th>
                  <th rowspan="2" class="border-right cursor-pointer" style="min-width: 100px" @click="toggleSort('jenis_gaji')">
                    Kategori <v-icon size="14">{{ getSortIcon('jenis_gaji') }}</v-icon>
                  </th>
                  <th rowspan="2" class="border-right cursor-pointer" style="min-width: 120px" @click="toggleSort('brutto')">
                    Brutto <v-icon size="14">{{ getSortIcon('brutto') }}</v-icon>
                  </th>
                  <th rowspan="2" class="border-right cursor-pointer" style="min-width: 120px" @click="toggleSort('potongan')">
                    Potongan <v-icon size="14">{{ getSortIcon('potongan') }}</v-icon>
                  </th>
                  <th rowspan="2" class="border-right cursor-pointer" style="min-width: 120px" @click="toggleSort('netto')">
                    Netto <v-icon size="14">{{ getSortIcon('netto') }}</v-icon>
                  </th>
                  <th colspan="2" class="text-center border-right">GAJI</th>
                  <th colspan="2" class="text-center border-right">TPP</th>
                  <th colspan="2" class="text-center border-right">Tanggal SP2D</th>
                  <th rowspan="2" class="border-right" style="min-width: 180px">Nomor SP2D</th>
                  <th rowspan="2" class="border-right" style="min-width: 180px">SKPD SIPD</th>
                  <th rowspan="2" class="border-right" style="min-width: 250px">Keterangan</th>
                  <th rowspan="2" class="border-right" style="min-width: 120px">Brutto</th>
                  <th rowspan="2" class="border-right" style="min-width: 120px">Potongan</th>
                  <th rowspan="2" class="border-right" style="min-width: 120px">Netto</th>
                </tr>
                <tr class="header-sub-row">
                  <th class="text-center border-right">PNS</th>
                  <th class="text-center border-right">PPPK</th>
                  <th class="text-center border-right">PNS</th>
                  <th class="text-center border-right">PPPK</th>
                  <th class="text-center border-right">Pembuatan</th>
                  <th class="text-center border-right">Pencairan</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading" class="text-center">
                  <td colspan="17" class="pa-10">
                    <v-progress-circular indeterminate color="primary"></v-progress-circular>
                  </td>
                </tr>
                <tr v-else-if="filteredReconData.length === 0" class="text-center">
                  <td colspan="17" class="pa-10 text-medium-emphasis">Tidak ada data yang cocok dengan pencarian</td>
                </tr>
                <tr v-for="(row, idx) in filteredReconData" :key="idx">
                  <td class="text-center border-right">{{ idx + 1 }}</td>
                  <td class="border-right text-caption truncate">{{ row.simgaji.nama_skpd }}</td>
                  <td class="border-right text-center">
                    <v-chip v-if="row.simgaji.jenis_gaji" size="x-small" :color="getTypeColor(row.simgaji.jenis_gaji)" variant="tonal">
                      {{ row.simgaji.jenis_gaji }}
                    </v-chip>
                  </td>
                  <td class="border-right text-right text-caption">{{ formatCurrency(row.simgaji.brutto) }}</td>
                  <td class="border-right text-right text-caption">{{ formatCurrency(row.simgaji.potongan) }}</td>
                  <td class="border-right text-right text-caption font-weight-bold">{{ formatCurrency(row.simgaji.netto) }}</td>
                  <td class="border-right text-right text-caption">{{ formatCurrency(row.simgaji.gaji_pns) }}</td>
                  <td class="border-right text-right text-caption">{{ formatCurrency(row.simgaji.gaji_pppk) }}</td>
                  <td class="border-right text-right text-caption">{{ formatCurrency(row.simgaji.tpp_pns) }}</td>
                  <td class="border-right text-right text-caption">{{ formatCurrency(row.simgaji.tpp_pppk) }}</td>
                  
                  <td class="border-right text-center text-caption">{{ formatDate(row.sipd.tanggal_sp2d) }}</td>
                  <td class="border-right text-center text-caption">{{ formatDate(row.sipd.tanggal_cair) }}</td>
                  <td class="border-right text-caption font-weight-bold">{{ row.sipd.nomor_sp2d }}</td>
                  <td class="border-right text-caption overflow-hidden" style="max-width: 15rem">{{ row.sipd.nama_skpd }}</td>
                  <td class="border-right text-caption truncate">{{ row.sipd.keterangan }}</td>
                  <td class="border-right text-right text-caption">{{ formatCurrency(row.sipd.brutto) }}</td>
                  <td class="border-right text-right text-caption">{{ formatCurrency(row.sipd.potongan) }}</td>
                  <td class="text-right text-caption font-weight-bold">{{ formatCurrency(row.sipd.netto) }}</td>
                </tr>
              </tbody>
            </v-table>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Edit Dialog -->
    <v-dialog v-model="editDialog" max-width="500px">
      <v-card class="rounded-xl pa-4">
        <v-card-title class="text-h5 font-weight-bold">Atur Ulang Nilai SP2D</v-card-title>
        <v-card-text>
          <p class="text-body-2 text-medium-emphasis mb-4">
            Sesuaikan nilai SP2D jika data register SIPD tergabung dengan kegiatan lain.
          </p>
          <v-form ref="editForm" v-model="isFormValid">
            <v-text-field
              v-model="editItem.nomor_sp2d"
              label="Nomor SP2D"
              variant="outlined"
              density="comfortable"
              rounded="lg"
              class="mb-2"
            ></v-text-field>
            
            <v-select
              v-model="editItem.jenis_data"
              :items="['PNS', 'PPPK', 'TPP']"
              label="Kategori Data"
              variant="outlined"
              density="comfortable"
              rounded="lg"
              class="mb-2"
            ></v-select>

            <v-text-field
              v-model.number="editItem.netto"
              label="Nilai Netto (Nominal)"
              variant="outlined"
              density="comfortable"
              rounded="lg"
              type="number"
              prefix="Rp"
              :rules="[v => !!v || 'Wajib diisi']"
            ></v-text-field>
          </v-form>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn variant="text" rounded="pill" @click="editDialog = false">Batal</v-btn>
          <v-btn color="primary" variant="flat" rounded="pill" :loading="saving" :disabled="!isFormValid" @click="updateTransaction">
            Simpan Perubahan
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Success Snackbar -->
    <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="3000" rounded="lg">
      <div class="d-flex align-center">
        <v-icon class="mr-3" v-if="snackbarTitle">mdi-information-outline</v-icon>
        <div>
          <div class="font-weight-bold" v-if="snackbarTitle">{{ snackbarTitle }}</div>
          <div class="text-body-2">{{ snackbarText }}</div>
        </div>
      </div>
    </v-snackbar>
    </v-container>
    </v-main>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import api from '../api'
import StatusChip from '../components/Sp2dStatusChip.vue'
import Sidebar from '../components/Sidebar.vue'
import Navbar from '../components/Navbar.vue'

const selectedMonth = ref(new Date().getMonth() + 1)
const selectedYear = ref(new Date().getFullYear())
const search = ref('')
const searchDetail = ref('')
const searchRecon = ref('')
const selectedJenisGaji = ref(null)
const sortBy = ref('nama_skpd')
const sortDesc = ref(false)
const loading = ref(false)
const uploading = ref(false)
const isDragging = ref(false)
const viewMode = ref('summary')
const items = ref([])
const transactions = ref([])
const reconData = ref([])

const toggleSort = (key) => {
  if (sortBy.value === key) {
    sortDesc.value = !sortDesc.value
  } else {
    sortBy.value = key
    sortDesc.value = false
  }
}

const getSortIcon = (key) => {
  if (sortBy.value !== key) return 'mdi-minus-variant'
  return sortDesc.value ? 'mdi-sort-descending' : 'mdi-sort-ascending'
}

const filteredReconData = computed(() => {
  let data = [...reconData.value]
  
  if (searchRecon.value) {
    const s = searchRecon.value.toLowerCase()
    data = data.filter(row => 
      (row.simgaji?.nama_skpd?.toLowerCase().includes(s)) ||
      (row.sipd?.nama_skpd?.toLowerCase().includes(s))
    )
  }

  // Apply Sorting
  data.sort((a, b) => {
    let valA, valB
    
    if (sortBy.value === 'nama_skpd') {
      valA = a.simgaji.nama_skpd
      valB = b.simgaji.nama_skpd
    } else if (sortBy.value === 'jenis_gaji') {
      valA = a.simgaji.jenis_gaji || ''
      valB = b.simgaji.jenis_gaji || ''
    } else {
      // Numbers (brutto, netto, etc)
      valA = a.simgaji[sortBy.value] || 0
      valB = b.simgaji[sortBy.value] || 0
    }

    if (valA < valB) return sortDesc.value ? 1 : -1
    if (valA > valB) return sortDesc.value ? -1 : 1
    return 0
  })

  return data
})
const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')
const snackbarTitle = ref('')
const exporting = ref(false)

const exportExcel = async () => {
    exporting.value = true
    try {
        const response = await api.get('/sp2d/export-recon', {
            params: {
                bulan: selectedMonth.value,
                tahun: selectedYear.value,
                jenis_gaji: selectedJenisGaji.value || undefined
            },
            responseType: 'blob'
        })
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `rekon-sp2d-${selectedMonth.value}-${selectedYear.value}.xlsx`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        showSnackbar('Excel berhasil di-unduh')
    } catch (err) {
        console.error(err)
        showSnackbar('Gagal mengunduh Excel', 'error')
    } finally {
        exporting.value = false
    }
}

const showComingSoon = (feature) => {
  snackbarTitle.value = feature
  snackbarText.value = 'Fitur ini akan segera hadir.'
  snackbarColor.value = 'primary'
  snackbar.value = true
}

// Edit Logic
const editDialog = ref(false)
const saving = ref(false)
const isFormValid = ref(false)
const editItem = ref({ id: null, nomor_sp2d: '', netto: 0, jenis_data: '' })

const months = [
  { title: 'Januari', value: 1 }, { title: 'Februari', value: 2 },
  { title: 'Maret', value: 3 }, { title: 'April', value: 4 },
  { title: 'Mei', value: 5 }, { title: 'Juni', value: 6 },
  { title: 'Juli', value: 7 }, { title: 'Agustus', value: 8 },
  { title: 'September', value: 9 }, { title: 'Oktober', value: 10 },
  { title: 'November', value: 11 }, { title: 'Desember', value: 12 }
]
const years = [2024, 2025, 2026]

const headers = [
  { title: 'Unit SKPD', key: 'nama_skpd', align: 'start', sortable: true },
  { title: 'Gaji PNS', key: 'pns', align: 'center', sortable: false },
  { title: 'Gaji PPPK', key: 'pppk', align: 'center', sortable: false },
  { title: 'TPP', key: 'tpp', align: 'center', sortable: false },
]

const detailHeaders = [
  { title: 'No. SP2D', key: 'nomor_sp2d', width: '180px' },
  { title: 'Tanggal', key: 'tanggal_sp2d' },
  { title: 'SKPD (SIPD)', key: 'nama_skpd_sipd' },
  { title: 'Kategori', key: 'jenis_data', align: 'center' },
  { title: 'Nilai Netto', key: 'netto', align: 'end' },
  { title: 'Aksi', key: 'actions', align: 'end', sortable: false },
]

const fetchData = async () => {
  if (viewMode.value === 'summary') {
    await fetchStatus()
  } else if (viewMode.value === 'details') {
    await fetchTransactions()
  } else if (viewMode.value === 'recon') {
    await fetchRecon()
  }
}

watch(viewMode, () => {
  fetchData()
})

const fetchStatus = async () => {
  loading.value = true
  try {
    const response = await api.get('/sp2d/status', {
      params: { 
        bulan: selectedMonth.value, 
        tahun: selectedYear.value,
        jenis_gaji: selectedJenisGaji.value || undefined
      }
    })
    items.value = response.data.data
  } catch (err) {
    console.error(err)
    showSnackbar('Gagal mengambil data status', 'error')
  } finally {
    loading.value = false
  }
}

const fetchTransactions = async () => {
  loading.value = true
  try {
    const response = await api.get('/sp2d/transactions', {
      params: { 
        bulan: selectedMonth.value, 
        tahun: selectedYear.value,
        jenis_gaji: selectedJenisGaji.value || undefined
      }
    })
    transactions.value = response.data.data
  } catch (err) {
    console.error(err)
    showSnackbar('Gagal mengambil data transaksi', 'error')
  } finally {
    loading.value = false
  }
}

const fetchRecon = async () => {
  loading.value = true
  try {
    const response = await api.get('/sp2d/recon', {
      params: { 
        bulan: selectedMonth.value, 
        tahun: selectedYear.value,
        jenis_gaji: selectedJenisGaji.value || undefined
      }
    })
    reconData.value = response.data.data
  } catch (err) {
    console.error(err)
    showSnackbar('Gagal mengambil data rekonsiliasi', 'error')
  } finally {
    loading.value = false
  }
}

const openEditDialog = (item) => {
  editItem.value = { ...item }
  editDialog.value = true
}

const updateTransaction = async () => {
  saving.value = true
  try {
    await api.put(`/sp2d/realizations/${editItem.value.id}`, {
      netto: editItem.value.netto,
      nomor_sp2d: editItem.value.nomor_sp2d,
      jenis_data: editItem.value.jenis_data
    })
    showSnackbar('Data berhasil diperbarui')
    editDialog.value = false
    fetchData()
    if (viewMode.value === 'details') fetchStatus() // Update summary silently if in details view
  } catch (err) {
    showSnackbar('Gagal memperbarui data', 'error')
  } finally {
    saving.value = false
  }
}

const confirmDelete = async (item) => {
  if (confirm('Apakah Anda yakin ingin menghapus data realisasi ini?')) {
    try {
      await api.delete(`/sp2d/realizations/${item.id}`)
      showSnackbar('Data berhasil dihapus')
      fetchData()
    } catch (err) {
      showSnackbar('Gagal menghapus data', 'error')
    }
  }
}

const handleFileSelect = (e) => {
  const file = e.target.files[0]
  if (file) uploadFile(file)
}

const handleDrop = (e) => {
  isDragging.value = false
  const file = e.dataTransfer.files[0]
  if (file) uploadFile(file)
}

const uploadFile = async (file) => {
  const formData = new FormData()
  formData.append('file', file)
  formData.append('bulan', selectedMonth.value)
  formData.append('tahun', selectedYear.value)
  
  uploading.value = true
  try {
    const response = await api.post('/sp2d/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    showSnackbar(response.data.message || 'Data berhasil diimpor')
    fetchData()
  } catch (err) {
    showSnackbar(err.response?.data?.message || 'Gagal mengunggah file', 'error')
  } finally {
    uploading.value = false
    // Clear input
    const input = document.querySelector('input[type="file"]')
    if (input) input.value = ''
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

const formatCurrency = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(val || 0)
}

const getTypeColor = (type) => {
  if (!type) return 'grey'
  const t = type.toUpperCase()
  if (t.includes('PNS')) return 'blue'
  if (t.includes('PPPK') || t.includes('P3K')) return 'orange'
  if (t.includes('TPP')) return 'teal'
  if (t.includes('INDUK')) return 'indigo'
  if (t.includes('SUSULAN')) return 'deep-purple'
  if (t.includes('KEKURANGAN')) return 'amber'
  if (t.includes('TERUSAN')) return 'brown'
  return 'grey'
}

const showSnackbar = (text, color = 'success') => {
  snackbarTitle.value = ''
  snackbarText.value = text
  snackbarColor.value = color
  snackbar.value = true
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.modern-dashboard {
  background-color: rgb(var(--v-theme-background)) !important;
  min-height: 100vh;
}

.glass-card {
  background-color: rgb(var(--v-theme-surface)) !important;
  border: 1px solid rgba(var(--v-border-color), 0.08) !important;
}

.upload-zone {
  border: 2px dashed rgba(var(--v-border-color), 0.2);
  cursor: pointer;
  transition: all 0.3s ease;
}

.cursor-pointer {
  cursor: pointer;
}

.upload-zone:hover, .upload-zone.is-dragging, .upload-zone-compact:hover, .upload-zone-compact.is-dragging {
  border-color: rgb(var(--v-theme-primary));
  background-color: rgba(var(--v-theme-primary), 0.05);
}
/*
- **Full-Screen Table**: Redesigned the layout to be full screen. Controls (Period, Upload, and View Mode) are now placed in a compact top-bar, allowing the table to expand to the full width of the container.
- **Dark Mode Support**: Replaced hardcoded light colors with theme-aware CSS. Table headers now automatically adapt their background and text colors for perfect readability in both light and dark modes.
- **Sidebar Integration**: The page is now correctly wrapped in the main application layout (`v-app` and `v-main`).
*/
.upload-zone-compact {
  border: 1px dashed rgba(var(--v-border-color), 0.3);
  transition: all 0.2s ease;
  min-height: 48px;
}

.search-bar-300 {
  width: 450px !important;
  max-width: 50% !important;
  flex: none !important;
}

.gap-1 {
  gap: 4px;
}

.gap-2 {
  gap: 8px;
}

.border-bottom {
  border-bottom: 1px solid rgba(var(--v-border-color), 0.08);
}

.bg-surface-variant-light {
  background-color: rgba(var(--v-theme-surface-variant), 0.05);
}

.recon-table-container {
  max-width: 100%;
  overflow-x: auto;
}

.recon-table {
  border-collapse: collapse;
}

.recon-table th, .recon-table td {
  border: 1px solid rgba(var(--v-border-color), 0.1) !important;
  white-space: nowrap !important;
}

.header-group-row th {
  font-size: 0.875rem !important;
  font-weight: 800 !important;
  letter-spacing: 0.05rem;
}

.v-theme--light .simgaji-header {
  background-color: #e8f5e9 !important;
  color: #1b5e20 !important;
}

.v-theme--dark .simgaji-header {
  background-color: #1b5e20 !important;
  color: #c8e6c9 !important;
}

.v-theme--light .sipd-header {
  background-color: #e3f2fd !important;
  color: #0d47a1 !important;
}

.v-theme--dark .sipd-header {
  background-color: #0d47a1 !important;
  color: #bbdefb !important;
}

.header-main-row th, .header-sub-row th {
  background-color: rgba(var(--v-theme-on-surface), 0.05) !important;
  color: rgb(var(--v-theme-on-surface)) !important;
  font-size: 0.75rem !important;
  font-weight: 700 !important;
}

.border-right {
  border-right: 1px solid rgba(var(--v-border-color), 0.1) !important;
}

.truncate {
  max-width: 250px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
