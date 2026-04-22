<template>
  <div class="modern-dashboard">
    <Navbar />
    <Sidebar />

    <v-main class="bg-light">
      <v-container fluid class="pa-8">
        <!-- Header -->
        <v-row class="mb-6 align-center">
          <v-col cols="12" md="6">
            <h1 class="text-h4 font-weight-black mb-1">Laporan Konsolidasi</h1>
            <p class="text-subtitle-1 text-grey-darken-1">Gabungan Gaji, TPP, dan TPG per Pegawai</p>
          </v-col>
          <v-col cols="12" md="6" class="text-md-right">
            <v-btn
              color="success"
              prepend-icon="mdi-microsoft-excel"
              class="rounded-lg px-6"
              elevation="2"
              @click="exportExcel"
              :loading="exporting"
            >
              EKSPOR EXCEL
            </v-btn>
          </v-col>
        </v-row>

        <!-- Filters -->
        <v-card class="glass-card rounded-xl pa-6 mb-8 shadow-premium" elevation="0">
          <v-row align="center">
            <v-col cols="12" sm="3">
              <v-select
                v-model="selectedMonth"
                :items="months"
                item-title="title"
                item-value="value"
                label="Bulan"
                prepend-inner-icon="mdi-calendar-month"
                variant="outlined"
                hide-details
                rounded="lg"
                @update:model-value="fetchData"
              ></v-select>
            </v-col>
            <v-col cols="12" sm="2">
              <v-select
                v-model="selectedYear"
                :items="years"
                label="Tahun"
                prepend-inner-icon="mdi-calendar-range"
                variant="outlined"
                hide-details
                rounded="lg"
                @update:model-value="fetchData"
              ></v-select>
            </v-col>
            <v-col cols="12" sm="4">
              <v-text-field
                v-model="search"
                label="Cari NIP atau Nama..."
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
                hide-details
                rounded="lg"
                clearable
              ></v-text-field>
            </v-col>
            <v-col cols="12" sm="3" class="text-right">
              <v-btn
                variant="tonal"
                color="primary"
                prepend-icon="mdi-refresh"
                rounded="lg"
                @click="fetchData"
                block
              >
                REFRESH DATA
              </v-btn>
            </v-col>
          </v-row>
        </v-card>

        <!-- Stats Table -->
        <v-card class="glass-card rounded-xl overflow-hidden shadow-premium" elevation="0">
          <v-data-table-server
            v-model:items-per-page="perPage"
            v-model:page="page"
            :headers="headers"
            :items="items"
            :items-length="totalItems"
            :loading="loading"
            :search="search"
            class="modern-table"
            hover
            @update:options="fetchData"
          >
            <!-- Custom Cell for NAMA -->
            <template v-slot:item.nama="{ item }">
              <div class="d-flex flex-column">
                <span class="font-weight-bold">{{ item.nama }}</span>
                <span class="text-caption text-grey">{{ item.nip }}</span>
              </div>
            </template>

            <!-- Custom Cell for SKPD -->
            <template v-slot:item.skpd="{ item }">
              <span class="text-caption">{{ item.skpd }}</span>
            </template>

            <!-- Currency Formatting -->
            <template v-slot:item.gaji_bruto="{ item }">
              <span class="font-weight-medium">{{ formatCurrency(item.gaji_bruto) }}</span>
            </template>
            <template v-slot:item.tpp="{ item }">
              <span class="font-weight-medium text-purple-darken-1">{{ formatCurrency(item.tpp) }}</span>
            </template>
            <template v-slot:item.tpg="{ item }">
              <span class="font-weight-medium text-success">{{ formatCurrency(item.tpg) }}</span>
            </template>
            <template v-slot:item.total_bruto="{ item }">
              <span class="font-weight-black text-primary">{{ formatCurrency(item.total_bruto) }}</span>
            </template>

            <!-- Footer for Summary -->
            <template v-slot:tfoot>
              <tr class="bg-grey-lighten-4 font-weight-black">
                <td colspan="4" class="text-right">TOTAL HALAMAN INI</td>
                <td>{{ formatCurrency(pageTotalGaji) }}</td>
                <td>{{ formatCurrency(pageTotalTpp) }}</td>
                <td>{{ formatCurrency(pageTotalTpg) }}</td>
                <td class="text-primary">{{ formatCurrency(pageTotalBruto) }}</td>
              </tr>
            </template>
          </v-data-table-server>
        </v-card>
      </v-container>
    </v-main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Navbar from '../components/Navbar.vue'
import Sidebar from '../components/Sidebar.vue'
import api from '../api'

const loading = ref(false)
const exporting = ref(false)
const items = ref([])
const totalItems = ref(0)
const page = ref(1)
const perPage = ref(25)
const search = ref('')

const selectedMonth = ref(new Date().getMonth() + 1)
const selectedYear = ref(new Date().getFullYear())

const months = [
  { title: 'Januari', value: 1 }, { title: 'Februari', value: 2 }, { title: 'Maret', value: 3 },
  { title: 'April', value: 4 }, { title: 'Mei', value: 5 }, { title: 'Juni', value: 6 },
  { title: 'Juli', value: 7 }, { title: 'Agustus', value: 8 }, { title: 'September', value: 9 },
  { title: 'Oktober', value: 10 }, { title: 'November', value: 11 }, { title: 'Desember', value: 12 }
]

const years = computed(() => {
  const current = new Date().getFullYear()
  return [current, current - 1, current - 2]
})

const headers = [
  { title: 'NAMA / NIP', key: 'nama', sortable: false },
  { title: 'SKPD', key: 'skpd', sortable: false },
  { title: 'GAJI BRUTO', key: 'gaji_bruto', align: 'end', sortable: false },
  { title: 'TPP', key: 'tpp', align: 'end', sortable: false },
  { title: 'TPG', key: 'tpg', align: 'end', sortable: false },
  { title: 'TOTAL BRUTO', key: 'total_bruto', align: 'end', sortable: false },
]

const fetchData = async () => {
  loading.value = true
  try {
    const params = {
      month: selectedMonth.value,
      year: selectedYear.value,
      page: page.value,
      per_page: perPage.value,
      search: search.value
    }
    const { data } = await api.get('/reports/consolidated', { params })
    items.value = data.data.data
    totalItems.value = data.data.total
  } catch (e) {
    console.error('Failed to fetch consolidated data:', e)
  } finally {
    loading.value = false
  }
}

const exportExcel = async () => {
  exporting.value = true
  try {
    const params = {
      month: selectedMonth.value,
      year: selectedYear.value
    }
    const response = await api.get('/reports/consolidated-export', {
      params,
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `laporan_konsolidasi_${selectedMonth.value}_${selectedYear.value}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (e) {
    console.error('Failed to export consolidated report:', e)
    alert('Gagal mengekspor laporan.')
  } finally {
    exporting.value = false
  }
}

const formatCurrency = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(val || 0)
}

// Page Totals
const pageTotalGaji = computed(() => items.value.reduce((sum, i) => sum + parseFloat(i.gaji_bruto || 0), 0))
const pageTotalTpp = computed(() => items.value.reduce((sum, i) => sum + parseFloat(i.tpp || 0), 0))
const pageTotalTpg = computed(() => items.value.reduce((sum, i) => sum + parseFloat(i.tpg || 0), 0))
const pageTotalBruto = computed(() => items.value.reduce((sum, i) => sum + parseFloat(i.total_bruto || 0), 0))

onMounted(() => {
  fetchData()
})
</script>

<style scoped>
.modern-dashboard {
  background-color: rgb(var(--v-theme-background)) !important;
  font-family: 'Inter', sans-serif;
}
.bg-light {
  background-color: rgb(var(--v-theme-background)) !important;
}
.glass-card {
  background-color: rgb(var(--v-theme-surface)) !important;
  border: 1px solid rgba(var(--v-border-color), 0.08) !important;
  box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07) !important;
}
.modern-table :deep(thead th) {
  background-color: rgba(var(--v-theme-primary), 0.04) !important;
  font-weight: 700 !important;
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.05em;
}
</style>
