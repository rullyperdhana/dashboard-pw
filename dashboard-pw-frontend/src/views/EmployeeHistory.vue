<template>
  <v-app class="modern-bg">
    <Navbar @show-coming-soon="(msg) => {}" />
    <Sidebar @show-coming-soon="(msg) => {}" />

    <v-main>
      <v-container fluid class="pa-8">
        <v-row>
          <v-col cols="12">
            <v-card class="glass-card rounded-xl mb-4" elevation="0">
              <v-card-title class="d-flex align-center py-4 bg-primary text-white">
                <v-icon start icon="mdi-account-search" class="mr-2"></v-icon>
                Trace Daftar Penggajian Per Orang
              </v-card-title>
              
              <v-card-text class="pt-6">
                <v-row>
                  <v-col cols="12" md="8">
                    <v-autocomplete
                      v-model="selectedEmployee"
                      :items="employees"
                      item-title="nama"
                      item-value="id"
                      label="Cari Nama Pegawai PPPK Paruh Waktu"
                      prepend-inner-icon="mdi-account"
                      variant="outlined"
                      clearable
                      :loading="searching"
                      @update:search="onSearch"
                      return-object
                      placeholder="Ketik minimal 3 karakter untuk mencari..."
                    >
                      <template v-slot:item="{ props, item }">
                        <v-list-item v-bind="props" :subtitle="item.raw.nip + ' - ' + item.raw.jabatan"></v-list-item>
                      </template>
                    </v-autocomplete>
                  </v-col>
                  <v-col cols="12" md="4" class="d-flex align-center">
                    <v-btn
                      color="secondary"
                      prepend-icon="mdi-file-pdf-box"
                      :disabled="!selectedEmployee || !history.length"
                      @click="exportPdf"
                      block
                      height="56"
                      rounded="lg"
                    >
                      Cetak Trace PDF
                    </v-btn>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-card>

            <v-card v-if="selectedEmployee" elevation="0" class="glass-card rounded-xl">
              <v-card-text class="pa-6">
                <div class="d-flex justify-space-between align-center mb-4">
                  <div>
                    <h3 class="text-h6 mb-1 font-weight-bold">{{ selectedEmployee.nama }}</h3>
                    <div class="text-subtitle-2 text-grey-darken-1">
                      NIP: {{ selectedEmployee.nip }} | Jabatan: {{ selectedEmployee.jabatan }}
                    </div>
                  </div>
                  <v-chip color="info" label variant="tonal">
                    {{ history.length }} Transaksi Ditemukan
                  </v-chip>
                </div>

                <v-divider class="mb-4 border-opacity-10"></v-divider>

                <v-data-table
                  :headers="headers"
                  :items="history"
                  :loading="loadingHistory"
                  class="modern-table"
                  hover
                >
                  <template v-slot:item.period="{ item }">
                    {{ getMonthName(item.month) }} {{ item.year }}
                  </template>
                  
                  <template v-slot:item.gaji_pokok="{ item }">
                    {{ formatCurrency(item.gaji_pokok) }}
                  </template>
                  
                  <template v-slot:item.tunjangan="{ item }">
                    {{ formatCurrency(item.tunjangan) }}
                  </template>
                  
                  <template v-slot:item.potongan="{ item }">
                    {{ formatCurrency(item.potongan) }}
                  </template>

                  <template v-slot:item.total_bersih="{ item }">
                    <span class="font-weight-bold text-success">
                      {{ formatCurrency(item.total_bersih) }}
                    </span>
                  </template>
                  
                  <template v-slot:no-data>
                    <div class="pa-10 text-center">
                      <v-icon icon="mdi-database-off" size="48" color="grey-lighten-3" class="mb-2"></v-icon>
                      <div class="text-grey">Belum ada riwayat penggajian untuk pegawai ini di sistem.</div>
                    </div>
                  </template>
                </v-data-table>
              </v-card-text>
            </v-card>
            
            <v-card v-else class="text-center pa-16 glass-card rounded-xl" elevation="0">
              <v-icon icon="mdi-account-question" size="64" color="grey-lighten-2" class="mb-4"></v-icon>
              <div class="text-h6 text-grey-darken-1">Pilih pegawai untuk melihat riwayat penggajian</div>
              <div class="text-caption text-grey">Data akan muncul otomatis setelah Anda memilih pegawai di atas.</div>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref, watch } from 'vue'
import api from '../api'
import Navbar from '../components/Navbar.vue'
import Sidebar from '../components/Sidebar.vue'

const employees = ref([])
const selectedEmployee = ref(null)
const history = ref([])
const searching = ref(false)
const loadingHistory = ref(false)

const headers = [
  { title: 'Periode', key: 'period', align: 'start' },
  { title: 'Gaji Pokok', key: 'gaji_pokok', align: 'end' },
  { title: 'Tunjangan', key: 'tunjangan', align: 'end' },
  { title: 'Potongan', key: 'potongan', align: 'end' },
  { title: 'Total Bersih', key: 'total_bersih', align: 'end' },
]

const onSearch = async (val) => {
  if (!val || val.length < 3) return
  
  searching.value = true
  try {
    const response = await api.get('/employees', {
      params: { search: val, per_page: 10 }
    })
    employees.value = response.data.data.data
  } catch (error) {
    console.error('Error searching employees:', error)
  } finally {
    searching.value = false
  }
}

watch(selectedEmployee, async (newVal) => {
  if (newVal) {
    fetchHistory(newVal.id)
  } else {
    history.value = []
  }
})

const fetchHistory = async (id) => {
  loadingHistory.value = true
  try {
    const response = await api.get(`/employees/${id}/history`)
    history.value = response.data.data
  } catch (error) {
    console.error('Error fetching history:', error)
  } finally {
    loadingHistory.value = false
  }
}

const exportPdf = () => {
  if (!selectedEmployee.value) return
  
  const url = `/api/employees/${selectedEmployee.value.id}/history-export`
  const token = localStorage.getItem('token')
  window.open(`${url}?token=${token}`, '_blank')
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(value)
}

const getMonthName = (month) => {
  const months = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
  ]
  return months[month - 1] || month
}
</script>

<style scoped>
.modern-bg {
  background-color: rgb(var(--v-theme-background)) !important;
}

.glass-card {
  background-color: rgb(var(--v-theme-surface)) !important;
  border: 1px solid rgba(var(--v-border-color), 0.08) !important;
}

.bg-primary {
  background-color: #4338ca !important;
}

:deep(.modern-table) {
  background: transparent !important;
}

:deep(.v-data-table-header) {
  background: rgba(var(--v-border-color), 0.05) !important;
}

:deep(.v-data-table-header th) {
  font-weight: 700 !important;
  text-transform: uppercase;
  font-size: 0.7rem !important;
  letter-spacing: 0.05em;
}
</style>
