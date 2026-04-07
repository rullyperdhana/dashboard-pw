<template>
  <div class="modern-dashboard">
    <Navbar />
    <Sidebar />
    <v-main class="bg-light min-vh-100">
      <v-container fluid class="pa-8">
        <div class="pa-4">
    <v-row class="mb-4 d-flex align-center">
      <v-col cols="12" md="8">
        <h2 class="text-h5 font-weight-bold">Laporan Realisasi Anggaran</h2>
        <div class="text-subtitle-2 text-medium-emphasis">
          Perbandingan Pagu Anggaran dengan Realisasi SP2D (Brutto).
        </div>
      </v-col>
      <v-col cols="12" md="4" class="text-right">
        <v-btn color="success" prepend-icon="mdi-file-excel-outline" @click="exportExcel">
          Export Excel
        </v-btn>
      </v-col>
    </v-row>

    <!-- Filter Bar -->
    <v-card class="mb-4 rounded-xl" elevation="0" border>
      <v-card-text>
        <v-row align="center">
          <v-col cols="12" md="3">
            <v-select
              v-model="filterTahun"
              :items="tahunOptions"
              label="Tahun Laporan"
              variant="outlined"
              density="compact"
              hide-details
            ></v-select>
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="filterTipe"
              :items="['MURNI', 'PERUBAHAN_1', 'PERUBAHAN_2', 'PERUBAHAN_3', 'TERAKHIR']"
              label="Pilih Tipe Anggaran"
              variant="outlined"
              density="compact"
              hide-details
            ></v-select>
          </v-col>
          <v-col cols="12" md="2">
            <v-select
              v-model="filterBulan"
              :items="bulanOptions"
              label="Realisasi s.d Bulan"
              variant="outlined"
              density="compact"
              hide-details
              clearable
            ></v-select>
          </v-col>
          <v-col cols="12" md="4">
            <v-btn color="primary" variant="tonal" class="mr-2" @click="fetchReport" :loading="loading">
              Tampilkan
            </v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Summary Cards -->
    <v-row class="mb-4">
      <v-col cols="12" md="3">
        <v-card class="rounded-xl border border-opacity-10 py-2" elevation="0" color="primary" variant="tonal">
          <v-card-text>
            <div class="text-caption font-weight-bold text-uppercase mb-1">Total Pagu</div>
            <div class="text-h5 font-weight-bold">Rp {{ formatCurrency(totalAnggaran) }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card class="rounded-xl border border-opacity-10 py-2" elevation="0" color="warning" variant="tonal">
          <v-card-text>
            <div class="text-caption font-weight-bold text-uppercase mb-1">Total Realisasi (Brutto)</div>
            <div class="text-h5 font-weight-bold">Rp {{ formatCurrency(totalRealisasi) }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card class="rounded-xl border border-opacity-10 py-2" elevation="0" color="success" variant="tonal">
          <v-card-text>
            <div class="text-caption font-weight-bold text-uppercase mb-1">Total Sisa Anggaran</div>
            <div class="text-h5 font-weight-bold">Rp {{ formatCurrency(totalSisa) }}</div>
          </v-card-text>
        </v-card>
      </v-col>
      <v-col cols="12" md="3">
        <v-card class="rounded-xl border border-opacity-10 py-2" elevation="0" color="info" variant="tonal">
          <v-card-text>
            <div class="text-caption font-weight-bold text-uppercase mb-1">% Penyerapan</div>
            <div class="text-h5 font-weight-bold">{{ totalPersentase }}%</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Data Table -->
    <v-card class="rounded-xl border border-opacity-10" elevation="0">
      <v-data-table
        :headers="headers"
        :items="reportData"
        :loading="loading"
        class="elevation-0 bg-transparent text-body-2"
        hover
        :items-per-page="50"
      >
        <template v-slot:item.nama_skpd="{ item }">
          <div class="font-weight-medium text-truncate" style="max-width: 300px;">{{ item.nama_skpd }}</div>
        </template>
        
        <template v-slot:item.kategori="{ item }">
          <v-chip size="small" :color="getCategoryColor(item.kategori)" variant="flat" class="font-weight-bold">
            {{ item.kategori }}
          </v-chip>
        </template>
        
        <template v-slot:item.tipe_anggaran="{ item }">
          <span class="text-caption text-medium-emphasis">{{ item.tipe_anggaran.replace('_', ' ') }}</span>
        </template>

        <template v-slot:item.nominal_anggaran="{ item }">
          <div class="font-weight-bold text-primary">Rp {{ formatCurrency(item.nominal_anggaran) }}</div>
        </template>
        
        <template v-slot:item.realisasi_brutto="{ item }">
          <div class="font-weight-bold text-warning">Rp {{ formatCurrency(item.realisasi_brutto) }}</div>
        </template>
        
        <template v-slot:item.sisa_anggaran="{ item }">
          <div class="font-weight-bold" :class="item.sisa_anggaran < 0 ? 'text-error' : 'text-success'">
            Rp {{ formatCurrency(item.sisa_anggaran) }}
          </div>
        </template>
        
        <template v-slot:item.persentase="{ item }">
          <v-progress-linear
            :model-value="item.persentase"
            :color="getPercentageColor(item.persentase)"
            height="15"
            striped
            class="rounded"
          >
            <template v-slot:default="{ value }">
              <span class="text-white" style="font-size: 10px; font-weight: bold">{{ Math.round(value) }}%</span>
            </template>
          </v-progress-linear>
        </template>
      </v-data-table>
    </v-card>
      </div>
    </v-container>
    </v-main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import * as XLSX from 'xlsx'
import api from '../../api'
import Sidebar from '../../components/Sidebar.vue'
import Navbar from '../../components/Navbar.vue'

const loading = ref(false)
const reportData = ref([])

const currentYear = new Date().getFullYear()
const tahunOptions = Array.from({length: 5}, (_, i) => currentYear - 2 + i)
const bulanOptions = [
  { title: 'Januari', value: 1 }, { title: 'Februari', value: 2 }, { title: 'Maret', value: 3 },
  { title: 'April', value: 4 }, { title: 'Mei', value: 5 }, { title: 'Juni', value: 6 },
  { title: 'Juli', value: 7 }, { title: 'Agustus', value: 8 }, { title: 'September', value: 9 },
  { title: 'Oktober', value: 10 }, { title: 'November', value: 11 }, { title: 'Desember', value: 12 }
]

const filterTahun = ref(currentYear)
const filterTipe = ref('TERAKHIR')
const filterBulan = ref(null)

const headers = [
  { title: 'SKPD', key: 'nama_skpd' },
  { title: 'Kategori', key: 'kategori', align: 'center' },
  { title: 'Tipe Pagu', key: 'tipe_anggaran' },
  { title: 'Pagu Anggaran', key: 'nominal_anggaran', align: 'end' },
  { title: 'Realisasi (Brutto)', key: 'realisasi_brutto', align: 'end' },
  { title: 'Sisa Anggaran', key: 'sisa_anggaran', align: 'end' },
  { title: 'Penyerapan', key: 'persentase', align: 'center', width: '120px' }
]

const totalAnggaran = computed(() => {
  return reportData.value.reduce((sum, item) => sum + (parseFloat(item.nominal_anggaran) || 0), 0)
})

const totalRealisasi = computed(() => {
  return reportData.value.reduce((sum, item) => sum + (parseFloat(item.realisasi_brutto) || 0), 0)
})

const totalSisa = computed(() => {
  return reportData.value.reduce((sum, item) => sum + (parseFloat(item.sisa_anggaran) || 0), 0)
})

const totalPersentase = computed(() => {
  if (totalAnggaran.value === 0) return 0
  return ((totalRealisasi.value / totalAnggaran.value) * 100).toFixed(2)
})

const formatCurrency = (val) => {
  if (!val) return '0'
  return parseFloat(val).toLocaleString('id-ID')
}

const getCategoryColor = (cat) => {
  const map = {
    'PNS': 'blue-darken-1',
    'PPPK': 'teal-darken-1',
    'TPP': 'deep-purple-darken-1',
    'PPPK_PW': 'orange-darken-2'
  }
  return map[cat] || 'grey'
}

const getPercentageColor = (val) => {
  if (val < 50) return 'info'
  if (val < 80) return 'warning'
  if (val <= 100) return 'success'
  return 'error' // over budget
}

const fetchReport = async () => {
  loading.value = true
  try {
    const res = await api.get('/budgets/comparison', {
      params: {
        tahun: filterTahun.value,
        tipe_anggaran: filterTipe.value,
        bulan: filterBulan.value || ''
      }
    })
    reportData.value = res.data.data
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

const exportExcel = () => {
  if (!reportData.value.length) return
  
  const formattedData = reportData.value.map(item => ({
    'Nama SKPD': item.nama_skpd,
    'Kategori': item.kategori,
    'Tipe Anggaran': item.tipe_anggaran,
    'Pagu Anggaran': item.nominal_anggaran,
    'Realisasi Brutto': item.realisasi_brutto,
    'Sisa Anggaran': item.sisa_anggaran,
    'Persentase Penyerapan (%)': item.persentase
  }))

  const ws = XLSX.utils.json_to_sheet(formattedData)
  const wb = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(wb, ws, "Realisasi")
  
  const title = `Laporan_Realisasi_Anggaran_${filterTahun.value}_${filterTipe.value}.xlsx`
  XLSX.writeFile(wb, title)
}

onMounted(() => {
  fetchReport()
})
</script>

<style scoped>
.v-data-table {
  background: transparent !important;
}
</style>
