<template>
  <div class="modern-dashboard">
    <Navbar @show-coming-soon="snack = true" />
    <Sidebar />

    <v-main class="bg-dashboard">
      <v-container fluid class="pa-6">
        <!-- Header -->
        <div class="d-flex align-center mb-8">
          <div>
            <h1 class="text-h4 font-weight-black text-high-emphasis mb-1">Pajak TER & Bukti Potong A2</h1>
            <p class="text-subtitle-1 text-medium-emphasis">Perhitungan PPh 21 sistem TER (Jan-Nov) & Pasal 17 (Desember)</p>
          </div>
          <v-spacer></v-spacer>
          
          <!-- Calculation Dialog with Activator -->
          <v-dialog v-if="isSuperAdmin" v-model="calcDialog" max-width="450px">
            <template v-slot:activator="{ props }">
              <v-btn
                v-bind="props"
                color="primary"
                prepend-icon="mdi-calculator"
                rounded="xl"
                elevation="0"
                class="px-6 font-weight-black"
                :loading="calculating"
              >
                HITUNG PAJAK
              </v-btn>
            </template>

            <v-card class="glass-modal rounded-xl pa-4">
              <v-card-title class="pa-4 font-weight-black text-h5">Hitung PPh 21 TER</v-card-title>
              <v-card-text>
                <v-select
                  v-model="calcParams.month"
                  label="Pilih Masa Pajak (Bulan)"
                  :items="months"
                  item-title="name"
                  item-value="value"
                  variant="outlined"
                  rounded="lg"
                ></v-select>
                <v-select
                  v-model="calcParams.type"
                  label="Jenis Pegawai"
                  :items="[{title:'PNS', value:'pns'}, {title:'PPPK', value:'pppk'}]"
                  item-title="title"
                  item-value="value"
                  variant="outlined"
                  rounded="lg"
                ></v-select>
                <v-autocomplete
                  v-model="calcParams.skpd"
                  :items="skpdList"
                  item-title="nama_skpd"
                  item-value="id_skpd"
                  label="Pilih SKPD (Opsional)"
                  variant="outlined"
                  rounded="lg"
                  clearable
                  placeholder="Semua SKPD"
                ></v-autocomplete>
                <p class="text-caption text-error mb-4">
                  <v-icon size="14">mdi-alert-circle</v-icon>
                  <b>PENTING:</b> Perhitungan ulang akan menimpa data yang sudah ada untuk bulan, tahun, dan SKPD terpilih.
                </p>
              </v-card-text>
              <v-card-actions class="pa-4 pt-0">
                <v-spacer></v-spacer>
                <v-btn color="grey" variant="text" @click="calcDialog = false">Batal</v-btn>
                <v-btn
                  color="primary"
                  variant="flat"
                  class="px-8 font-weight-black"
                  rounded="lg"
                  :loading="calculating"
                  @click="runCalculation"
                >
                  MULAI HITUNG
                </v-btn>
              </v-card-actions>
            </v-card>
          </v-dialog>
        </div>

        <!-- Filters & Stats -->
        <v-row class="mb-6">
          <v-col cols="12" md="4">
            <v-card class="glass-panel text-white premium-gradient" elevation="0">
              <v-card-text class="pa-6">
                <div class="text-overline font-weight-bold opacity-70 mb-1">Tahun Pajak</div>
                <div class="d-flex align-center">
                  <div class="text-h3 font-weight-black">{{ selectedYear }}</div>
                  <v-btn icon="mdi-calendar-edit" variant="text" color="white" size="small" class="ml-2" @click="yearDialog = true"></v-btn>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" md="8">
            <v-card class="glass-panel pa-2" elevation="0">
              <v-card-text>
                <v-autocomplete
                  v-model="selectedSkpd"
                  :items="skpdList"
                  item-title="nama_skpd"
                  item-value="id_skpd"
                  label="Filter SKPD untuk Laporan & Download"
                  prepend-inner-icon="mdi-office-building"
                  variant="outlined"
                  rounded="lg"
                  hide-details
                  clearable
                  placeholder="Semua SKPD"
                ></v-autocomplete>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <!-- Report Table -->
        <v-card class="glass-panel" elevation="0">
          <v-table class="bg-transparent modern-report-table">
            <thead>
              <tr>
                <th v-if="isSuperAdmin" class="text-center px-2" style="width: 50px;">
                  <v-checkbox
                    :model-value="isAllSelected"
                    :indeterminate="isSomeSelected"
                    @update:model-value="toggleSelectAll"
                    hide-details
                    density="compact"
                  ></v-checkbox>
                </th>
                <th class="text-left font-weight-bold text-medium-emphasis">Masa Pajak</th>
                <th class="text-left font-weight-bold text-medium-emphasis">SKPD</th>
                <th class="text-center font-weight-bold text-medium-emphasis">Pegawai</th>
                <th class="text-right font-weight-bold text-medium-emphasis">Total Bruto</th>
                <th class="text-right font-weight-bold text-medium-emphasis">Total PPh 21</th>
                <th class="text-center font-weight-bold text-medium-emphasis">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <v-progress-linear v-if="loading" indeterminate color="primary" class="position-absolute"></v-progress-linear>
              <template v-else>
                <tr v-for="(item, idx) in reports" :key="idx" class="table-row-hover" :class="{'selected-row': isSelected(item)}">
                  <td v-if="isSuperAdmin" class="text-center px-2">
                    <v-checkbox
                      :model-value="isSelected(item)"
                      @update:model-value="toggleSelection(item)"
                      hide-details
                      density="compact"
                    ></v-checkbox>
                  </td>
                  <td class="font-weight-bold text-high-emphasis">{{ getMonthName(item.bulan) }}</td>
                  <td class="text-caption font-weight-medium" style="max-width: 250px;">{{ item.nama_skpd || 'Unknown' }}</td>
                  <td class="text-center">
                    <v-chip size="x-small" variant="tonal" color="primary">{{ item.total_records }}</v-chip>
                  </td>
                  <td class="text-right font-weight-medium">{{ formatCurrency(item.total_gross) }}</td>
                  <td class="text-right font-weight-black text-error">{{ formatCurrency(item.total_tax) }}</td>
                  <td class="text-center">
                    <v-btn
                      variant="tonal"
                      color="success"
                      density="compact"
                      prepend-icon="mdi-file-excel-outline"
                      class="text-caption font-weight-bold px-4"
                      rounded="lg"
                      @click="downloadA2Specific(item.bulan, item.skpd_id)"
                      title="Download Bukti Potong A2"
                    >
                      A2
                    </v-btn>

                    <v-btn
                      v-if="isSuperAdmin"
                      variant="tonal"
                      color="error"
                      density="compact"
                      icon="mdi-trash-can-outline"
                      class="ml-2"
                      rounded="lg"
                      @click="confirmDelete(item)"
                      title="Hapus Data Perhitungan"
                    ></v-btn>
                  </td>
                </tr>
                <tr v-if="reports.length === 0">
                  <td :colspan="isSuperAdmin ? 7 : 6" class="text-center pa-8 text-disabled">Belum ada data perhitungan untuk tahun {{ selectedYear }}</td>
                </tr>
              </template>
            </tbody>
          </v-table>
          
          <!-- Bulk Action Bar -->
          <v-fade-transition>
            <div v-if="selectedItems.length > 0" class="bulk-action-bar pa-4 d-flex align-center">
              <span class="text-subtitle-2 font-weight-bold">{{ selectedItems.length }} item terpilih</span>
              <v-spacer></v-spacer>
              <v-btn
                color="error"
                prepend-icon="mdi-trash-can"
                variant="flat"
                rounded="lg"
                class="px-4"
                @click="confirmBulkDelete"
              >
                HAPUS TERPILIH
              </v-btn>
              <v-btn icon="mdi-close" variant="text" class="ml-2" @click="clearSelection"></v-btn>
            </div>
          </v-fade-transition>
        </v-card>
      </v-container>
    </v-main>

    <!-- Year Picker Dialog -->
    <v-dialog v-model="yearDialog" max-width="300px">
      <v-card class="glass-modal rounded-xl pa-4 text-center">
        <v-card-title class="font-weight-black">Pilih Tahun Pajak</v-card-title>
        <v-card-text>
          <v-btn-toggle v-model="selectedYear" mandatory vertical block color="primary" variant="tonal">
            <v-btn :value="2024">2024</v-btn>
            <v-btn :value="2025">2025</v-btn>
            <v-btn :value="2026">2026</v-btn>
          </v-btn-toggle>
        </v-card-text>
        <v-card-actions>
          <v-btn block color="primary" variant="flat" rounded="lg" @click="yearDialog = false">Pilih</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="deleteDialog" max-width="400px">
      <v-card class="glass-modal rounded-xl pa-4 text-center">
        <v-card-title class="font-weight-black text-error">
          {{ isBulkDelete ? 'Konfirmasi Hapus Massal' : 'Konfirmasi Hapus' }}
        </v-card-title>
        <v-card-text>
          <template v-if="isBulkDelete">
            Apakah Anda yakin ingin menghapus <b>{{ selectedItems.length }}</b> data perhitungan terpilih?
          </template>
          <template v-else>
            Apakah Anda yakin ingin menghapus data perhitungan PPh21 untuk <b>{{ selectedItem?.nama_skpd }}</b> pada bulan <b>{{ getMonthName(selectedItem?.bulan) }} {{ selectedYear }}</b>?
          </template>
          <p class="text-caption text-error mt-4">Tindakan ini tidak dapat dibatalkan.</p>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn color="grey" variant="text" @click="deleteDialog = false">Batal</v-btn>
          <v-btn color="error" variant="flat" rounded="lg" :loading="deleting" @click="executeDelete">Hapus</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snack" :color="snackColor" rounded="lg">{{ snackText }}</v-snackbar>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import api from '../api'
import Sidebar from '../components/Sidebar.vue'
import Navbar from '../components/Navbar.vue'

const currentUser = JSON.parse(localStorage.getItem('user') || '{}')
const isSuperAdmin = computed(() => currentUser.role === 'superadmin')

const selectedYear = ref(2026)
const selectedSkpd = ref(null)
const skpdList = ref([])
const loading = ref(false)
const calculating = ref(false)
const reports = ref([])
const calcDialog = ref(false)
const yearDialog = ref(false)
const deleteDialog = ref(false)
const selectedItem = ref(null)
const selectedItems = ref([])
const isBulkDelete = ref(false)
const deleting = ref(false)
const snack = ref(false)
const snackText = ref('')
const snackColor = ref('info')

const isSelected = (item) => selectedItems.value.some(x => x.bulan === item.bulan && x.skpd_id === item.skpd_id)
const toggleSelection = (item) => {
  const idx = selectedItems.value.findIndex(x => x.bulan === item.bulan && x.skpd_id === item.skpd_id)
  if (idx > -1) selectedItems.value.splice(idx, 1)
  else selectedItems.value.push({ bulan: item.bulan, skpd_id: item.skpd_id })
}
const isAllSelected = computed(() => reports.value.length > 0 && selectedItems.value.length === reports.value.length)
const isSomeSelected = computed(() => selectedItems.value.length > 0 && selectedItems.value.length < reports.value.length)
const toggleSelectAll = () => {
  if (isAllSelected.value) selectedItems.value = []
  else selectedItems.value = reports.value.map(x => ({ bulan: x.bulan, skpd_id: x.skpd_id }))
}
const clearSelection = () => selectedItems.value = []

const showSnack = (text, color = 'info') => {
  snackText.value = text
  snackColor.value = color
  snack.value = true
}

const calcParams = ref({
  month: new Date().getMonth() + 1,
  type: 'pns',
  skpd: null
})

const months = [
  { name: 'Januari', value: 1 }, { name: 'Februari', value: 2 }, { name: 'Maret', value: 3 },
  { name: 'April', value: 4 }, { name: 'Mei', value: 5 }, { name: 'Juni', value: 6 },
  { name: 'Juli', value: 7 }, { name: 'Agustus', value: 8 }, { name: 'September', value: 9 },
  { name: 'Oktober', value: 10 }, { name: 'November', value: 11 }, { name: 'Desember', value: 12 },
]

const getMonthName = (m) => months.find(x => x.value == m)?.name || m

const fetchReport = async () => {
  loading.value = true
  try {
    const res = await api.get('/pph21/report', { 
      params: { 
        year: selectedYear.value,
        skpd: selectedSkpd.value
      } 
    })
    reports.value = res.data.data
  } catch (err) {
    console.error('Report Error:', err)
  } finally {
    loading.value = false
  }
}

const fetchSkpds = async () => {
  try {
    const res = await api.get('/skpd')
    // Fix: access .data branch correctly based on backend response pattern
    skpdList.value = res.data.data || res.data
  } catch (err) {
    console.error('SKPD Fetch Error:', err)
  }
}

const runCalculation = async () => {
  calculating.value = true
  try {
    const payload = {
      year: selectedYear.value,
      month: calcParams.value.month,
      type: calcParams.value.type,
      skpd: calcParams.value.skpd
    }
    console.log('Calculation Payload:', payload)
    await api.post('/pph21/calculate', payload)
    calcDialog.value = false
    fetchReport()
  } catch (err) {
    console.error('Calc Error:', err)
    alert('Gagal menghitung pajak: ' + (err.response?.data?.message || err.message))
  } finally {
    calculating.value = false
  }
}

const downloadA2Specific = async (month, skpdId) => {
  try {
    const response = await api.get('/pph21/export-a2', {
      params: { 
        year: selectedYear.value, 
        month,
        skpd: skpdId
      },
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `Bukti_Potong_A2_${selectedYear.value}_M${month}_SKPD${skpdId}.xlsx`)
    document.body.appendChild(link)
    link.click()
  } catch (err) {
    console.error('Download Error:', err)
    if (err.response?.data instanceof Blob) {
      const reader = new FileReader()
      reader.onload = () => {
        const errorData = JSON.parse(reader.result)
        alert('Gagal mengunduh: ' + (errorData.message || 'Error tidak diketahui'))
      }
      reader.readAsText(err.response.data)
    } else {
      alert('Gagal mengunduh file Excel.')
    }
  }
}

const confirmDelete = (item) => {
  selectedItem.value = item
  isBulkDelete.value = false
  deleteDialog.value = true
}

const confirmBulkDelete = () => {
  isBulkDelete.value = true
  deleteDialog.value = true
}

const executeDelete = async () => {
  deleting.value = true
  try {
    const payload = {
      year: selectedYear.value
    }

    if (isBulkDelete.value) {
      payload.items = selectedItems.value.map(x => ({ month: x.bulan, skpd: x.skpd_id }))
    } else {
      payload.month = selectedItem.value.bulan
      payload.skpd = selectedItem.value.skpd_id
    }

    await api.delete('/pph21', { params: payload })
    
    showSnack('Data perhitungan berhasil dihapus', 'success')
    deleteDialog.value = false
    selectedItems.value = []
    fetchReport()
  } catch (err) {
    console.error('Delete Error:', err)
    showSnack('Gagal menghapus data: ' + (err.response?.data?.message || err.message), 'error')
  } finally {
    deleting.value = false
  }
}

const formatCurrency = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(val || 0)
}

onMounted(() => {
  fetchSkpds()
  fetchReport()
})

watch([selectedYear, selectedSkpd], fetchReport)
</script>

<style scoped>
.modern-dashboard {
  min-height: 100vh;
}
.bg-dashboard {
  background: rgb(var(--v-theme-background));
  background-image: radial-gradient(at 0% 0%, rgba(var(--v-theme-primary), 0.05) 0, transparent 50%);
  min-height: 100vh;
}
.glass-panel {
  background: rgba(var(--v-theme-surface), 0.7) !important;
  backdrop-filter: blur(16px);
  border: 1px solid rgba(var(--v-border-color), 0.1) !important;
  border-radius: 28px !important;
}
.glass-modal {
  background: rgba(var(--v-theme-surface), 0.95) !important;
  backdrop-filter: blur(24px);
}
.premium-gradient {
  background: linear-gradient(135deg, rgb(var(--v-theme-primary)) 0%, #312e81 100%) !important;
}
.table-row-hover:hover {
  background: rgba(var(--v-theme-primary), 0.02);
}
.selected-row {
  background: rgba(var(--v-theme-primary), 0.05) !important;
}
.bulk-action-bar {
  background: rgb(var(--v-theme-surface));
  border-top: 1px solid rgba(var(--v-border-color), 0.1);
  border-bottom-left-radius: 28px;
  border-bottom-right-radius: 28px;
  position: sticky;
  bottom: 0;
  z-index: 2;
}
.modern-report-table :deep(th) {
  padding: 16px !important;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.modern-report-table :deep(td) {
  padding: 16px !important;
}
</style>
