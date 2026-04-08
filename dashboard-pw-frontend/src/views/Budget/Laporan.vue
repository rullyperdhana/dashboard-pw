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
          <v-col cols="12" md="2">
            <v-select
              v-model="filterTahun"
              :items="tahunOptions"
              label="Tahun Laporan"
              variant="outlined"
              density="compact"
              hide-details
            ></v-select>
          </v-col>
          <v-col cols="12" md="2">
            <v-select
              v-model="filterTipe"
              :items="['MURNI', 'PERUBAHAN_1', 'PERUBAHAN_2', 'PERUBAHAN_3', 'TERAKHIR']"
              label="Tipe Anggaran"
              variant="outlined"
              density="compact"
              hide-details
            ></v-select>
          </v-col>
          <v-col cols="12" md="2">
            <v-select
              v-model="periodMode"
              :items="periodModeOptions"
              item-title="title"
              item-value="value"
              label="Periode Realisasi"
              variant="outlined"
              density="compact"
              hide-details
            ></v-select>
          </v-col>

          <!-- Kumulatif: single month -->
          <v-col cols="12" md="2" v-if="periodMode === 'kumulatif'">
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

          <!-- Triwulan -->
          <v-col cols="12" md="2" v-if="periodMode === 'triwulan'">
            <v-select
              v-model="selectedTriwulan"
              :items="triwulanOptions"
              item-title="title"
              item-value="value"
              label="Triwulan"
              variant="outlined"
              density="compact"
              hide-details
            ></v-select>
          </v-col>

          <!-- Semester -->
          <v-col cols="12" md="2" v-if="periodMode === 'semester'">
            <v-select
              v-model="selectedSemester"
              :items="semesterOptions"
              item-title="title"
              item-value="value"
              label="Semester"
              variant="outlined"
              density="compact"
              hide-details
            ></v-select>
          </v-col>

          <!-- Custom: dari - sampai -->
          <template v-if="periodMode === 'custom'">
            <v-col cols="6" md="1">
              <v-select
                v-model="customBulanDari"
                :items="bulanOptions"
                label="Dari"
                variant="outlined"
                density="compact"
                hide-details
              ></v-select>
            </v-col>
            <v-col cols="6" md="1">
              <v-select
                v-model="customBulanSampai"
                :items="bulanOptions"
                label="Sampai"
                variant="outlined"
                density="compact"
                hide-details
              ></v-select>
            </v-col>
          </template>

          <v-col cols="12" md="2">
            <v-btn color="primary" variant="tonal" class="mr-2" @click="fetchReport" :loading="loading">
              Tampilkan
            </v-btn>
          </v-col>
        </v-row>

        <!-- Period label chip -->
        <div class="mt-3" v-if="periodMode !== 'kumulatif'">
          <v-chip color="primary" variant="tonal" label size="small" class="font-weight-bold">
            <v-icon start size="14">mdi-calendar-range</v-icon>
            {{ periodLabel }}
          </v-chip>
        </div>
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

// Period mode
const periodMode = ref('kumulatif')
const selectedTriwulan = ref(getCurrentQuarter())
const selectedSemester = ref(getCurrentSemester())
const customBulanDari = ref(1)
const customBulanSampai = ref(new Date().getMonth() + 1)

function getCurrentQuarter() {
  const m = new Date().getMonth() + 1
  if (m <= 3) return 1
  if (m <= 6) return 2
  if (m <= 9) return 3
  return 4
}

function getCurrentSemester() {
  return new Date().getMonth() + 1 <= 6 ? 1 : 2
}

const periodModeOptions = [
  { title: 'Kumulatif (s.d.)', value: 'kumulatif' },
  { title: 'Triwulan', value: 'triwulan' },
  { title: 'Semester', value: 'semester' },
  { title: 'Tahunan', value: 'tahunan' },
  { title: 'Custom', value: 'custom' },
]

const triwulanOptions = [
  { title: 'TW I (Jan-Mar)', value: 1 },
  { title: 'TW II (Apr-Jun)', value: 2 },
  { title: 'TW III (Jul-Sep)', value: 3 },
  { title: 'TW IV (Okt-Des)', value: 4 },
]

const semesterOptions = [
  { title: 'Semester I (Jan-Jun)', value: 1 },
  { title: 'Semester II (Jul-Des)', value: 2 },
]

const bulanNameMap = {
  1: 'Januari', 2: 'Februari', 3: 'Maret', 4: 'April',
  5: 'Mei', 6: 'Juni', 7: 'Juli', 8: 'Agustus',
  9: 'September', 10: 'Oktober', 11: 'November', 12: 'Desember'
}

const periodRange = computed(() => {
  if (periodMode.value === 'triwulan') {
    const q = selectedTriwulan.value
    return { dari: (q - 1) * 3 + 1, sampai: q * 3 }
  }
  if (periodMode.value === 'semester') {
    const s = selectedSemester.value
    return { dari: s === 1 ? 1 : 7, sampai: s === 1 ? 6 : 12 }
  }
  if (periodMode.value === 'tahunan') {
    return { dari: 1, sampai: 12 }
  }
  if (periodMode.value === 'custom') {
    return { dari: customBulanDari.value, sampai: customBulanSampai.value }
  }
  return { dari: null, sampai: null }
})

const periodLabel = computed(() => {
  if (periodMode.value === 'kumulatif') return ''
  const { dari, sampai } = periodRange.value
  if (periodMode.value === 'triwulan') return `Triwulan ${['I','II','III','IV'][selectedTriwulan.value - 1]} ${filterTahun.value}`
  if (periodMode.value === 'semester') return `Semester ${selectedSemester.value === 1 ? 'I' : 'II'} ${filterTahun.value}`
  if (periodMode.value === 'tahunan') return `Tahunan ${filterTahun.value}`
  return `${bulanNameMap[dari]} - ${bulanNameMap[sampai]} ${filterTahun.value}`
})

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
    'GAJI': 'indigo-darken-1',
    'TPP': 'deep-purple-darken-1',
    'PPPK_PW': 'orange-darken-2',
    'LAINNYA': 'grey-darken-1'
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
    const params = {
      tahun: filterTahun.value,
      tipe_anggaran: filterTipe.value,
    }

    if (periodMode.value === 'kumulatif') {
      params.bulan = filterBulan.value || ''
    } else {
      const { dari, sampai } = periodRange.value
      params.bulan_dari = dari
      params.bulan = sampai
    }

    const res = await api.get('/budgets/comparison', { params })
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
