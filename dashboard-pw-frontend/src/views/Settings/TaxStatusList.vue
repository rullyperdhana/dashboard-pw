<template>
  <div>
    <Navbar />
    <Sidebar />
    <v-main class="bg-light">
      <v-container fluid class="pa-6">
        <!-- Header/Toolbar -->
        <v-row class="mb-6 align-center">
          <v-col cols="12" md="6">
            <h1 class="text-h4 font-weight-bold mb-1 text-primary">Status Pajak (PTKP)</h1>
            <p class="text-subtitle-1 text-medium-emphasis mb-0">Manajemen status pajak statis tahunan untuk PNS & PPPK</p>
          </v-col>
          <v-col cols="12" md="6" class="d-flex justify-md-end gap-2">
            <v-select
              v-model="selectedYear"
              :items="yearOptions"
              label="Tahun"
              variant="outlined"
              density="compact"
              hide-details
              style="max-width: 120px"
              @update:model-value="() => { page = 1; fetchData(); }"
            ></v-select>
            <v-btn
              v-if="isSuperAdmin"
              color="success"
              prepend-icon="mdi-plus"
              variant="flat"
              @click="openAddDialog"
            >
              Tambah Data
            </v-btn>
            <v-btn
              v-if="isSuperAdmin"
              color="secondary"
              prepend-icon="mdi-refresh"
              variant="outlined"
              @click="initYearDialog = true"
            >
              Inisialisasi Tahun
            </v-btn>
            <v-btn
              color="primary"
              prepend-icon="mdi-export"
              variant="flat"
              @click="exportData"
            >
              Export
            </v-btn>
            <v-btn
              v-if="isSuperAdmin"
              color="primary"
              prepend-icon="mdi-import"
              variant="flat"
              @click="importDialog = true"
            >
              Import
            </v-btn>
          </v-col>
        </v-row>

        <!-- Content Card -->
        <v-card class="glass-card rounded-xl overflow-hidden" elevation="0">
          <v-toolbar color="transparent" class="px-4 py-2 border-b">
            <v-text-field
              v-model="search"
              prepend-inner-icon="mdi-magnify"
              label="Cari NIP atau Nama..."
              variant="solo-filled"
              flat
              density="compact"
              hide-details
              class="mr-4"
              @keyup.enter="fetchData"
              style="max-width: 400px"
            ></v-text-field>
            <v-tabs v-model="selectedType" color="primary" @update:model-value="() => { page = 1; fetchData(); }">
              <v-tab value="">Semua</v-tab>
              <v-tab value="pns">PNS</v-tab>
              <v-tab value="pppk">PPPK</v-tab>
            </v-tabs>
            <v-spacer></v-spacer>
            <v-btn
              icon="mdi-reload"
              variant="text"
              @click="fetchData"
              :loading="loading"
            ></v-btn>
          </v-toolbar>

          <v-table class="tax-table font-weight-medium">
            <thead>
              <tr>
                <th class="text-uppercase text-caption font-weight-bold">NIP</th>
                <th class="text-uppercase text-caption font-weight-bold">Nama Pegawai</th>
                <th class="text-uppercase text-caption font-weight-bold">Tipe</th>
                <th class="text-uppercase text-caption font-weight-bold">Status Pajak</th>
                <th class="text-uppercase text-caption font-weight-bold text-center">Status Data</th>
                <th class="text-uppercase text-caption font-weight-bold text-right">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <v-progress-linear
                v-if="loading"
                color="primary"
                indeterminate
                absolute
                top
              ></v-progress-linear>
              
              <tr v-for="item in tableData" :key="item.id" class="hover-row">
                <td>
                  <span class="text-body-2 font-weight-bold">{{ item.nip }}</span>
                </td>
                <td>{{ item.nama }}</td>
                <td>
                  <v-chip
                    :color="item.employee_type === 'pns' ? 'info' : 'warning'"
                    size="x-small"
                    variant="flat"
                    class="text-uppercase"
                  >
                    {{ item.employee_type }}
                  </v-chip>
                </td>
                <td>
                  <v-chip
                    :color="getStatusColor(item.tax_status)"
                    size="small"
                    variant="outlined"
                    class="font-weight-bold"
                  >
                    {{ item.tax_status }}
                  </v-chip>
                </td>
                <td class="text-center">
                  <v-tooltip :text="item.is_manual ? 'Data Diubah Manual' : 'Data Default/Impor'">
                    <template v-slot:activator="{ props }">
                      <v-icon
                        v-bind="props"
                        :icon="item.is_manual ? 'mdi-account-edit' : 'mdi-database'"
                        :color="item.is_manual ? 'primary' : 'grey-lighten-1'"
                        size="small"
                      ></v-icon>
                    </template>
                  </v-tooltip>
                </td>
                <td class="text-right">
                  <v-btn
                    icon="mdi-pencil"
                    variant="text"
                    density="compact"
                    color="primary"
                    @click="editItem(item)"
                  ></v-btn>
                </td>
              </tr>
              
              <tr v-if="tableData.length === 0 && !loading">
                <td colspan="6" class="text-center py-10">
                  <v-icon icon="mdi-account-off" size="large" color="grey-lighten-1" class="mb-2"></v-icon>
                  <div class="text-subtitle-1 text-medium-emphasis">Tidak ada data ditemukan</div>
                </td>
              </tr>
            </tbody>
          </v-table>

          <v-divider></v-divider>
          
          <!-- Custom Pagination -->
          <div class="pa-4 d-flex align-center">
            <span class="text-caption text-medium-emphasis">
              Menampilkan {{ pagination.from || 0 }} - {{ pagination.to || 0 }} dari {{ pagination.total || 0 }} data
            </span>
            <v-spacer></v-spacer>
            <v-pagination
              v-model="page"
              :length="pagination.last_page || 1"
              total-visible="5"
              density="compact"
              @update:model-value="fetchData"
            ></v-pagination>
          </div>
        </v-card>

        <!-- Edit Dialog -->
        <v-dialog v-model="editDialog" max-width="450px">
          <v-card class="rounded-xl pa-4">
            <v-card-title class="px-0 pt-0 font-weight-bold d-flex align-center">
              Update Status Pajak
              <v-spacer></v-spacer>
              <v-btn icon="mdi-close" variant="text" size="small" @click="editDialog = false"></v-btn>
            </v-card-title>
            <v-card-text class="px-0">
              <div class="mb-4">
                <div class="text-caption text-medium-emphasis">Pegawai</div>
                <div class="text-body-1 font-weight-bold">{{ currentItem.nama }}</div>
                <div class="text-caption">{{ currentItem.nip }}</div>
              </div>
              <v-select
                v-model="currentItem.tax_status"
                :items="taxStatusOptions"
                label="Pilih Status Pajak (PTKP)"
                variant="outlined"
                density="comfortable"
                class="mb-2"
              ></v-select>
              <v-alert
                type="info"
                variant="tonal"
                density="compact"
                class="text-caption"
                icon="mdi-information-outline"
              >
                Satus pajaka ini bersifat statis untuk tahun {{ selectedYear }}. Menekan simpan akan menandai data ini sebagai update manual.
              </v-alert>
            </v-card-text>
            <v-card-actions class="px-0 pb-0">
              <v-btn block color="primary" size="large" @click="saveItem" :loading="saving">Simpan Perubahan</v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>

        <!-- Add Dialog -->
        <v-dialog v-model="addDialog" max-width="550px" persistent>
          <v-card class="rounded-xl pa-4">
            <v-card-title class="px-0 pt-0 font-weight-bold d-flex align-center">
              Tambah Status Pajak
              <v-spacer></v-spacer>
              <v-btn icon="mdi-close" variant="text" size="small" @click="closeAddDialog"></v-btn>
            </v-card-title>
            <v-card-text class="px-0">
              <p class="text-body-2 mb-4">Cari pegawai dari database master (PNS/PPPK) untuk ditambahkan ke daftar status pajak tahun {{ selectedYear }}.</p>
              
              <v-autocomplete
                v-model="selectedEmployee"
                v-model:search="employeeSearch"
                :items="employeeOptions"
                :loading="loadingEmployees"
                item-title="search_label"
                item-value="nip"
                label="Cari NIP atau Nama Pegawai..."
                placeholder="Ketik minimal 3 karakter..."
                variant="outlined"
                density="comfortable"
                hide-no-data
                hide-details
                return-object
                clearable
                class="mb-4"
              >
                <template v-slot:item="{ props, item }">
                  <v-list-item v-bind="props" :subtitle="item.raw.nip">
                    <template v-slot:prepend>
                      <v-chip size="x-small" :color="item.raw.type === 'pns' ? 'info' : 'warning'" class="mr-2">
                        {{ item.raw.type.toUpperCase() }}
                      </chip>
                    </template>
                  </v-list-item>
                </template>
              </v-autocomplete>

              <v-expand-transition>
                <div v-if="selectedEmployee">
                  <v-select
                    v-model="newTaxStatus"
                    :items="taxStatusOptions"
                    label="Pilih Status Pajak (PTKP)"
                    variant="outlined"
                    density="comfortable"
                    class="mt-2"
                  ></v-select>
                </div>
              </v-expand-transition>
            </v-card-text>
            <v-card-actions class="px-0 pb-0">
              <v-btn 
                block 
                color="primary" 
                size="large" 
                @click="handleAdd" 
                :loading="saving"
                :disabled="!selectedEmployee || !newTaxStatus"
              >
                Tambahkan Data
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>

        <!-- Inisialisasi Tahun Dialog -->
        <v-dialog v-model="initYearDialog" max-width="500px">
          <v-card class="rounded-xl pa-4">
            <v-card-title class="px-0 pt-0 font-weight-bold">Inisialisasi Data Tahun {{ selectedYear }}</v-card-title>
            <v-card-text class="px-0">
              <p class="text-body-2 mb-4">Fitur ini membantu Anda mengisi data status pajak untuk tahun <b>{{ selectedYear }}</b> berdasarkan data yang sudah ada.</p>
              
              <v-radio-group v-model="initMode" class="mb-4">
                <v-radio label="Salin data dari tahun sebelumnya" value="copy"></v-radio>
                <v-select
                  v-if="initMode === 'copy'"
                  v-model="sourceYear"
                  :items="previousYears"
                  label="Tahun Sumber"
                  variant="outlined"
                  density="compact"
                  class="mt-2 ml-8"
                ></v-select>
                <v-radio label="Tarik data pegawai baru (Sinkronisasi dari Database)" value="sync" class="mt-4"></v-radio>
              </v-radio-group>

              <v-alert type="warning" variant="tonal" density="compact" class="text-caption">
                <b>Perhatian:</b> Data yang sudah terbentuk untuk tahun {{ selectedYear }} (PTKP yang sudah ada) <u>tidak akan ditimpa atau diubah</u> secara massal. Hanya data pegawai baru yang belum terdaftar yang akan ditambahkan.
              </v-alert>
            </v-card-text>
            <v-card-actions class="px-0 pb-0">
              <v-spacer></v-spacer>
              <v-btn color="grey" variant="text" @click="initYearDialog = false">Batal</v-btn>
              <v-btn color="primary" @click="handleInitialize" :loading="initializing">Mulai Proses</v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>

        <!-- Import Dialog -->
        <v-dialog v-model="importDialog" max-width="450px">
          <v-card class="rounded-xl pa-4">
            <v-card-title class="px-0 pt-0 font-weight-bold">Import Status Pajak</v-card-title>
            <v-card-text class="px-0">
              <p class="text-body-2 mb-4">Unggah file Excel (.xlsx) untuk memperbarui status pajak secara massal untuk tahun {{ selectedYear }}.</p>
              <v-file-input
                v-model="importFile"
                label="Pilih File Excel"
                accept=".xlsx,.xls,.csv"
                variant="outlined"
                density="comfortable"
                prepend-icon="mdi-microsoft-excel"
                hide-details
                class="mb-4"
              ></v-file-input>
              <v-alert type="info" variant="tonal" density="compact" class="text-caption">
                <b>Keamanan Data:</b> Proses impor ini hanya akan menambah data baru. Menghindari perubahan data yang sudah ada (tidak akan menimpa data NIP yang sudah ada di database tahun ini).
              </v-alert>
            </v-card-text>
            <v-card-actions class="px-0 pb-0">
              <v-spacer></v-spacer>
              <v-btn color="grey" variant="text" @click="importDialog = false">Batal</v-btn>
              <v-btn color="primary" @click="handleImport" :loading="importing" :disabled="!importFile">Import Data</v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>

        <!-- Snackbar for Notifications -->
        <v-snackbar v-model="snackbar.show" :color="snackbar.color" location="top">
          {{ snackbar.text }}
          <template v-slot:actions>
            <v-btn icon="mdi-close" variant="text" @click="snackbar.show = false"></v-btn>
          </template>
        </v-snackbar>
      </v-container>
    </v-main>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import Sidebar from '@/components/Sidebar.vue'
import Navbar from '@/components/Navbar.vue'
import api from '@/api'

const loading = ref(false)
const saving = ref(false)
const initializing = ref(false)
const importing = ref(false)
const search = ref('')
const selectedYear = ref(new Date().getFullYear())
const selectedType = ref('')
const page = ref(1)
const tableData = ref([])
const pagination = ref({})

const editDialog = ref(false)
const currentItem = ref({})

const initYearDialog = ref(false)
const initMode = ref('copy')
const sourceYear = ref(new Date().getFullYear() - 1)

const importDialog = ref(false)
const importFile = ref(null)

const snackbar = ref({
  show: false,
  text: '',
  color: 'success'
})
 
const addDialog = ref(false)
const selectedEmployee = ref(null)
const employeeSearch = ref('')
const employeeOptions = ref([])
const loadingEmployees = ref(false)
const newTaxStatus = ref('TK/0')
 
const isSuperAdmin = computed(() => {
  const user = JSON.parse(localStorage.getItem('user') || '{}')
  // Allow superadmin and admin (role 1 & 2)
  return user.role_id === 1 || user.role_id === 2 || user.role === 'admin' || user.role === 'superadmin'
})

const yearOptions = computed(() => {
  const current = new Date().getFullYear()
  return [current - 1, current, current + 1]
})

const previousYears = computed(() => {
  const current = new Date().getFullYear()
  return [current - 1, current - 2, current - 3]
})

const taxStatusOptions = [
  'TK/0', 'TK/1', 'TK/2', 'TK/3',
  'K/0', 'K/1', 'K/2', 'K/3',
  'K/I/0', 'K/I/1', 'K/I/2', 'K/I/3',
  '-'
]

const fetchData = async () => {
  loading.value = true
  try {
    const response = await api.get('/tax-status', {
      params: {
        year: selectedYear.value,
        type: selectedType.value,
        search: search.value,
        page: page.value,
        per_page: 25 // Increased default per_page
      }
    })
    tableData.value = response.data.data.data
    pagination.value = response.data.data
  } catch (error) {
    console.error('Error fetching tax status:', error)
    showSnackbar('Gagal mengambil data', 'error')
  } finally {
    loading.value = false
  }
}

// Debounce search
let searchTimer = null
watch(search, () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    page.value = 1
    fetchData()
  }, 500)
})
 
// Search employees for Add Dialog
let employeeSearchTimer = null
watch(employeeSearch, (val) => {
  if (!val || val.length < 3) {
    employeeOptions.value = []
    return
  }
  
  if (employeeSearchTimer) clearTimeout(employeeSearchTimer)
  employeeSearchTimer = setTimeout(() => {
    fetchEmployeesSearch(val)
  }, 500)
})
 
const fetchEmployeesSearch = async (val) => {
  loadingEmployees.value = true
  try {
    // Search both PNS and PPPK
    const [pnsRes, pppkRes] = await Promise.all([
      api.get('/master-pegawai', { params: { search: val, per_page: 10 } }),
      api.get('/employees', { params: { search: val, per_page: 10 } })
    ])
 
    const pns = (pnsRes.data.data.data || []).map(p => ({
      nip: p.nip,
      nama: p.nama,
      type: 'pns',
      search_label: `${p.nip} - ${p.nama} (PNS)`
    }))
 
    const pppk = (pppkRes.data.data.data || []).map(p => ({
      nip: p.nip,
      nama: p.nama,
      type: 'pppk',
      search_label: `${p.nip} - ${p.nama} (PPPK)`
    }))
 
    employeeOptions.value = [...pns, ...pppk]
  } catch (error) {
    console.error('Error searching employees:', error)
  } finally {
    loadingEmployees.value = false
  }
}
 
const openAddDialog = () => {
  addDialog.value = true
  selectedEmployee.value = null
  employeeSearch.value = ''
  newTaxStatus.value = 'TK/0'
}
 
const closeAddDialog = () => {
  addDialog.value = false
}
 
const handleAdd = async () => {
  if (!selectedEmployee.value) return
  
  saving.value = true
  try {
    await api.post('/tax-status', {
      nip: selectedEmployee.value.nip,
      nama: selectedEmployee.value.nama,
      employee_type: selectedEmployee.value.type,
      tax_status: newTaxStatus.value,
      year: selectedYear.value
    })
    showSnackbar('Status pajak berhasil ditambahkan')
    closeAddDialog()
    fetchData()
  } catch (error) {
    showSnackbar(error.response?.data?.message || 'Gagal menambahkan data', 'error')
  } finally {
    saving.value = false
  }
}

const getStatusColor = (status) => {
  if (!status || status === '-') return 'grey'
  if (status.startsWith('K')) return 'indigo'
  return 'teal'
}

const editItem = (item) => {
  currentItem.value = { ...item }
  editDialog.value = true
}

const saveItem = async () => {
  saving.value = true
  try {
    await api.post('/tax-status', {
      ...currentItem.value,
      year: selectedYear.value
    })
    showSnackbar('Status pajak berhasil diperbarui')
    editDialog.value = false
    fetchData()
  } catch (error) {
    showSnackbar('Gagal menyimpan data', 'error')
  } finally {
    saving.value = false
  }
}

const handleInitialize = async () => {
  initializing.value = true
  try {
    const params = {
      target_year: selectedYear.value
    }
    if (initMode.value === 'copy') {
      params.source_year = sourceYear.value
    }

    const response = await api.post('/tax-status/initialize', params)
    showSnackbar(response.data.message)
    initYearDialog.value = false
    fetchData()
  } catch (error) {
    showSnackbar('Gagal melakukan inisialisasi', 'error')
  } finally {
    initializing.value = false
  }
}

const exportData = async () => {
  try {
    const response = await api.get('/tax-status/export', {
      params: {
        year: selectedYear.value,
        type: selectedType.value
      },
      responseType: 'blob'
    })
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `status_pajak_${selectedYear.value}.xlsx`)
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    showSnackbar('Export berhasil diunduh')
  } catch (error) {
    console.error('Export error:', error)
    showSnackbar('Gagal mengunduh export', 'error')
  }
}

const handleImport = async () => {
  if (!importFile.value) return

  importing.value = true
  const formData = new FormData()
  formData.append('file', importFile.value)
  formData.append('year', selectedYear.value)

  try {
    const response = await api.post('/tax-status/import', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    showSnackbar(response.data.message)
    importDialog.value = false
    importFile.value = null
    fetchData()
  } catch (error) {
    showSnackbar(error.response?.data?.message || 'Gagal mengimpor data', 'error')
  } finally {
    importing.value = false
  }
}

const showSnackbar = (text, color = 'success') => {
  snackbar.value = { show: true, text, color }
}

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.glass-card {
  background: rgba(var(--v-theme-surface), 0.8) !important;
  backdrop-filter: blur(12px);
  border: 1px solid rgba(var(--v-border-color), 0.05) !important;
}

.tax-table {
  background: transparent !important;
}

.tax-table :deep(thead tr th) {
  background: rgba(var(--v-theme-primary), 0.03) !important;
  height: 48px !important;
}

.hover-row:hover {
  background: rgba(var(--v-theme-primary), 0.02) !important;
}

.gap-2 {
  gap: 8px;
}
</style>
