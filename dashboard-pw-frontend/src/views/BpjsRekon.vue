<template>
  <v-app class="modern-bg">
    <Navbar @show-coming-soon="() => {}" />
    <Sidebar @show-coming-soon="() => {}" />

    <v-main>
      <v-container fluid class="pa-8">
        <!-- Header -->
        <v-row class="mb-6">
          <v-col cols="12">
            <div class="d-flex align-center justify-space-between">
              <div>
                <h1 class="text-h4 font-weight-bold mb-1">
                  <v-icon start color="teal" size="36">mdi-hospital-box</v-icon>
                  Rekon BPJS 4% - PPPK Paruh Waktu
                </h1>
                <p class="text-subtitle-1 text-medium-emphasis">Rekonsiliasi pembayaran BPJS Kesehatan 4% berdasarkan data gaji.</p>
              </div>
              <v-btn variant="tonal" color="teal" prepend-icon="mdi-cog" @click="showUmpDialog = true">
                Setting UMP
              </v-btn>
            </div>
          </v-col>
        </v-row>

        <!-- UMP Info Banner -->
        <v-row v-if="umpValue" class="mb-2">
          <v-col cols="12">
            <v-alert type="info" variant="tonal" density="compact" class="rounded-lg">
              <strong>Rumus BPJS 4%:</strong>
              Jika gaji pokok ≥ UMP ({{ formatCurrency(umpValue) }}) → BPJS = Gaji Pokok × 4%.
              Jika gaji pokok &lt; UMP → BPJS = {{ formatCurrency(bpjsUmpValue) }} ({{ formatCurrency(umpValue) }} × 4%).
            </v-alert>
          </v-col>
        </v-row>

        <!-- Filter -->
        <v-row>
          <v-col cols="12" md="10" lg="8">
            <v-card class="glass-card rounded-xl pa-4" elevation="0">
              <v-row>
                <v-col cols="4">
                  <v-select
                    v-model="selectedMonth"
                    :items="months"
                    item-title="title"
                    item-value="value"
                    label="Bulan"
                    variant="outlined"
                    density="compact"
                    color="teal"
                    prepend-inner-icon="mdi-calendar"
                    hide-details
                  ></v-select>
                </v-col>
                <v-col cols="3">
                  <v-select
                    v-model="selectedYear"
                    :items="years"
                    label="Tahun"
                    variant="outlined"
                    density="compact"
                    color="teal"
                    prepend-inner-icon="mdi-calendar"
                    hide-details
                  ></v-select>
                </v-col>
                <v-col cols="3">
                  <v-select
                    v-model="selectedSumberDana"
                    :items="sumberDanaOptions"
                    label="Sumber Dana"
                    variant="outlined"
                    density="compact"
                    color="teal"
                    prepend-inner-icon="mdi-cash-multiple"
                    hide-details
                  ></v-select>
                </v-col>
                <v-col cols="2" class="d-flex align-center">
                  <v-btn color="teal" block @click="fetchData" :loading="loading" height="40">
                    <v-icon start>mdi-magnify</v-icon> Cari
                  </v-btn>
                </v-col>
              </v-row>
            </v-card>
          </v-col>
        </v-row>

        <!-- Grand Total Summary -->
        <v-row v-if="grandTotal" class="mt-4">
          <v-col cols="6" md="2">
            <v-card class="glass-card rounded-xl pa-4 text-center" elevation="0">
              <div class="text-caption text-medium-emphasis mb-1">Jumlah Pegawai</div>
              <div class="text-h5 font-weight-bold text-teal">{{ grandTotal.jumlah_pegawai?.toLocaleString() }}</div>
            </v-card>
          </v-col>
          <v-col cols="6" md="2">
            <v-card class="glass-card rounded-xl pa-4 text-center" elevation="0">
              <div class="text-caption text-medium-emphasis mb-1">Total Gaji Pokok</div>
              <div class="text-h6 font-weight-bold">{{ formatCurrency(grandTotal.total_gaji_pokok) }}</div>
            </v-card>
          </v-col>
          <v-col cols="6" md="2">
            <v-card class="glass-card rounded-xl pa-4 text-center" elevation="0">
              <div class="text-caption text-medium-emphasis mb-1">Total BPJS 4%</div>
              <div class="text-h5 font-weight-bold text-red-darken-1">{{ formatCurrency(grandTotal.total_bpjs_4_persen) }}</div>
            </v-card>
          </v-col>
          <v-col cols="6" md="2">
            <v-card class="glass-card rounded-xl pa-4 text-center" elevation="0">
              <div class="text-caption text-medium-emphasis mb-1">Total Gaji Bersih</div>
              <div class="text-h6 font-weight-bold text-green">{{ formatCurrency(grandTotal.total_gaji_bersih) }}</div>
            </v-card>
          </v-col>
          <v-col cols="6" md="2">
            <v-card class="glass-card rounded-xl pa-4 text-center" elevation="0">
              <div class="text-caption text-medium-emphasis mb-1">Gaji &lt; UMP</div>
              <div class="text-h6 font-weight-bold text-orange">{{ grandTotal.pegawai_bawah_ump || 0 }} orang</div>
            </v-card>
          </v-col>
          <v-col cols="6" md="2">
            <v-card class="glass-card rounded-xl pa-4 text-center" elevation="0">
              <div class="text-caption text-medium-emphasis mb-1">Gaji ≥ UMP</div>
              <div class="text-h6 font-weight-bold text-blue">{{ grandTotal.pegawai_atas_ump || 0 }} orang</div>
            </v-card>
          </v-col>
        </v-row>

        <!-- View Mode Toggle -->
        <v-row v-if="grandTotal" class="mt-4">
          <v-col cols="12">
            <v-btn-toggle v-model="viewMode" mandatory color="teal" density="compact" class="rounded-lg">
              <v-btn value="skpd" variant="outlined">
                <v-icon start>mdi-office-building</v-icon> Per SKPD
              </v-btn>
              <v-btn value="detail" variant="outlined">
                <v-icon start>mdi-format-list-bulleted</v-icon> Detail Per Orang
              </v-btn>
            </v-btn-toggle>
          </v-col>
        </v-row>

        <!-- SKPD Summary Table -->
        <v-row v-if="viewMode === 'skpd' && skpdSummary.length" class="mt-2">
          <v-col cols="12">
            <v-card class="glass-card rounded-xl" elevation="0">
              <v-card-title class="d-flex align-center pa-4">
                <span class="text-body-1 font-weight-bold">Rekap Per SKPD</span>
                <v-spacer></v-spacer>
                <v-btn size="small" color="teal" variant="tonal" prepend-icon="mdi-microsoft-excel" @click="exportExcel('skpd')">
                  Export Excel
                </v-btn>
              </v-card-title>
              <v-data-table
                :headers="skpdHeaders"
                :items="skpdSummary"
                class="modern-table"
                hover
                density="compact"
                :items-per-page="-1"
              >
                <template v-slot:item.no="{ index }">{{ index + 1 }}</template>
                <template v-slot:item.total_gaji_pokok="{ item }">{{ formatCurrency(item.total_gaji_pokok) }}</template>
                <template v-slot:item.total_bpjs_4_persen="{ item }">
                  <span class="font-weight-bold text-red-darken-1">{{ formatCurrency(item.total_bpjs_4_persen) }}</span>
                </template>
                <template v-slot:item.total_gaji_bersih="{ item }">{{ formatCurrency(item.total_gaji_bersih) }}</template>
                <template v-slot:item.pegawai_bawah_ump="{ item }">
                  <v-chip v-if="item.pegawai_bawah_ump > 0" color="orange" size="x-small" variant="tonal">{{ item.pegawai_bawah_ump }}</v-chip>
                  <span v-else class="text-medium-emphasis">-</span>
                </template>

                <template v-slot:body.append>
                  <tr class="font-weight-bold bg-grey-lighten-4">
                    <td></td>
                    <td>TOTAL</td>
                    <td class="text-end">{{ grandTotal.jumlah_pegawai?.toLocaleString() }}</td>
                    <td class="text-end">{{ formatCurrency(grandTotal.total_gaji_pokok) }}</td>
                    <td class="text-end text-red-darken-1">{{ formatCurrency(grandTotal.total_bpjs_4_persen) }}</td>
                    <td class="text-end">{{ formatCurrency(grandTotal.total_gaji_bersih) }}</td>
                    <td class="text-center">{{ grandTotal.pegawai_bawah_ump || 0 }}</td>
                  </tr>
                </template>
              </v-data-table>
            </v-card>
          </v-col>
        </v-row>

        <!-- Detail Per Orang Table -->
        <v-row v-if="viewMode === 'detail' && detail.length" class="mt-2">
          <v-col cols="12">
            <v-card class="glass-card rounded-xl" elevation="0">
              <v-card-title class="d-flex align-center pa-4">
                <span class="text-body-1 font-weight-bold">Detail Per Pegawai</span>
                <v-spacer></v-spacer>
                <v-text-field
                  v-model="searchDetail"
                  density="compact"
                  variant="outlined"
                  label="Cari NIP/Nama"
                  prepend-inner-icon="mdi-magnify"
                  hide-details
                  class="mr-4"
                  style="max-width: 300px;"
                ></v-text-field>
                <v-btn size="small" color="teal" variant="tonal" prepend-icon="mdi-microsoft-excel" @click="exportExcel('detail')">
                  Export Excel
                </v-btn>
              </v-card-title>
              <v-data-table
                :headers="detailHeaders"
                :items="detail"
                :search="searchDetail"
                class="modern-table"
                hover
                density="compact"
                :items-per-page="25"
              >
                <template v-slot:item.no="{ index }">{{ index + 1 }}</template>
                <template v-slot:item.skpd_display="{ item }">{{ item.skpd || item.upt || '-' }}</template>
                <template v-slot:item.gaji_pokok="{ item }">{{ formatCurrency(item.gaji_pokok) }}</template>
                <template v-slot:item.bpjs_4_persen="{ item }">
                  <span class="font-weight-bold text-red-darken-1">{{ formatCurrency(item.bpjs_4_persen) }}</span>
                </template>
                <template v-slot:item.basis_hitung="{ item }">
                  <v-chip :color="item.basis_hitung === 'UMP' ? 'orange' : 'blue'" size="x-small" variant="tonal">
                    {{ item.basis_hitung }}
                  </v-chip>
                </template>
                <template v-slot:item.total_amoun="{ item }">{{ formatCurrency(item.total_amoun) }}</template>
              </v-data-table>
            </v-card>
          </v-col>
        </v-row>

        <!-- Empty State -->
        <v-row v-if="!loading && !grandTotal" class="mt-8">
          <v-col cols="12" class="text-center">
            <v-icon size="64" color="grey-lighten-2" class="mb-4">mdi-hospital-box-outline</v-icon>
            <div class="text-h6 text-medium-emphasis">Pilih bulan dan tahun, lalu klik Cari</div>
            <div class="text-caption text-medium-emphasis">Data BPJS 4% akan ditampilkan berdasarkan record pembayaran.</div>
          </v-col>
        </v-row>
      </v-container>
    </v-main>

    <!-- UMP Settings Dialog -->
    <v-dialog v-model="showUmpDialog" max-width="480">
      <v-card class="rounded-xl">
        <v-card-title class="d-flex align-center pa-6 pb-2">
          <v-icon start color="teal">mdi-cog</v-icon>
          Setting UMP Provinsi
        </v-card-title>
        <v-card-text class="pa-6 pt-2">
          <v-alert type="info" variant="tonal" density="compact" class="mb-4 rounded-lg">
            <div class="text-caption">UMP digunakan sebagai batas minimum perhitungan BPJS 4%.</div>
            <div class="text-caption">Jika gaji pokok &lt; UMP, maka BPJS 4% = UMP × 4%.</div>
          </v-alert>
          <v-text-field
            v-model.number="editUmpValue"
            label="UMP Provinsi Kalimantan Selatan"
            type="number"
            variant="outlined"
            color="teal"
            prefix="Rp"
            hint="Contoh: 3725000"
            persistent-hint
          ></v-text-field>
          <div v-if="editUmpValue" class="mt-2 text-body-2">
            BPJS 4% dari UMP = <strong class="text-red-darken-1">{{ formatCurrency(Math.round(editUmpValue * 0.04)) }}</strong>
          </div>
        </v-card-text>
        <v-card-actions class="pa-6 pt-0">
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="showUmpDialog = false">Batal</v-btn>
          <v-btn color="teal" variant="flat" :loading="savingUmp" @click="saveUmp">Simpan</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-app>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../api'
import Navbar from '../components/Navbar.vue'
import Sidebar from '../components/Sidebar.vue'

const loading = ref(false)
const viewMode = ref('skpd')
const searchDetail = ref('')

const selectedMonth = ref(new Date().getMonth() + 1)
const selectedYear = ref(new Date().getFullYear())
const selectedSumberDana = ref('Semua')
const sumberDanaOptions = ['Semua', 'APBD', 'BLUD']

const detail = ref([])
const skpdSummary = ref([])
const grandTotal = ref(null)
const umpValue = ref(null)
const bpjsUmpValue = ref(null)

// UMP Dialog
const showUmpDialog = ref(false)
const editUmpValue = ref(null)
const savingUmp = ref(false)

const months = [
  { title: 'Januari', value: 1 }, { title: 'Februari', value: 2 }, { title: 'Maret', value: 3 },
  { title: 'April', value: 4 }, { title: 'Mei', value: 5 }, { title: 'Juni', value: 6 },
  { title: 'Juli', value: 7 }, { title: 'Agustus', value: 8 }, { title: 'September', value: 9 },
  { title: 'Oktober', value: 10 }, { title: 'November', value: 11 }, { title: 'Desember', value: 12 },
]
const years = [2024, 2025, 2026, 2027]

const skpdHeaders = [
  { title: 'No', key: 'no', width: '50px', sortable: false },
  { title: 'SKPD', key: 'skpd', width: '30%' },
  { title: 'Jml Pegawai', key: 'jumlah_pegawai', align: 'end' },
  { title: 'Total Gaji Pokok', key: 'total_gaji_pokok', align: 'end' },
  { title: 'BPJS 4%', key: 'total_bpjs_4_persen', align: 'end' },
  { title: 'Total Gaji Bersih', key: 'total_gaji_bersih', align: 'end' },
  { title: '< UMP', key: 'pegawai_bawah_ump', align: 'center', width: '80px' },
]

const detailHeaders = [
  { title: 'No', key: 'no', width: '50px', sortable: false },
  { title: 'NIP', key: 'nip', width: '160px' },
  { title: 'Nama', key: 'nama', width: '200px' },
  { title: 'SKPD', key: 'skpd_display', width: '200px' },
  { title: 'Jabatan', key: 'jabatan' },
  { title: 'Gaji Pokok', key: 'gaji_pokok', align: 'end' },
  { title: 'BPJS 4%', key: 'bpjs_4_persen', align: 'end' },
  { title: 'Basis', key: 'basis_hitung', align: 'center', width: '80px' },
  { title: 'Gaji Bersih', key: 'total_amoun', align: 'end' },
]

const fetchUmp = async () => {
  try {
    const res = await api.get('/bpjs-rekon/ump')
    if (res.data.success) {
      umpValue.value = res.data.data.ump
      bpjsUmpValue.value = Math.round(res.data.data.ump * 0.04)
      editUmpValue.value = res.data.data.ump
    }
  } catch (e) {
    console.error('Error fetching UMP:', e)
  }
}

const fetchData = async () => {
  loading.value = true
  try {
    const params = { month: selectedMonth.value, year: selectedYear.value }
    if (selectedSumberDana.value !== 'Semua') {
      params.sumber_dana = selectedSumberDana.value
    }
    const res = await api.get('/bpjs-rekon', { params })
    if (res.data.success) {
      detail.value = res.data.data.detail
      skpdSummary.value = res.data.data.skpd_summary
      grandTotal.value = res.data.data.grand_total
      umpValue.value = res.data.data.ump
      bpjsUmpValue.value = res.data.data.bpjs_ump
    }
  } catch (e) {
    console.error('Error fetching BPJS rekon:', e)
    alert('Gagal memuat data: ' + (e.response?.data?.message || e.message))
  } finally {
    loading.value = false
  }
}

const saveUmp = async () => {
  savingUmp.value = true
  try {
    const res = await api.put('/bpjs-rekon/ump', { ump: editUmpValue.value })
    if (res.data.success) {
      umpValue.value = res.data.data.ump
      bpjsUmpValue.value = res.data.data.bpjs_4_persen
      showUmpDialog.value = false
      alert(res.data.message)
      // Refresh data if already loaded
      if (grandTotal.value) fetchData()
    }
  } catch (e) {
    alert('Gagal menyimpan: ' + (e.response?.data?.message || e.message))
  } finally {
    savingUmp.value = false
  }
}

const formatCurrency = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', minimumFractionDigits: 0
  }).format(val || 0)
}

const exportExcel = (type) => {
  const monthName = months.find(m => m.value === selectedMonth.value)?.title || ''
  let csvContent = ''
  let fileName = ''

  if (type === 'skpd') {
    fileName = `Rekon_BPJS_4persen_PerSKPD_${monthName}_${selectedYear.value}.csv`
    csvContent = 'No,SKPD,Jumlah Pegawai,Total Gaji Pokok,BPJS 4%,Total Gaji Bersih,Pegawai < UMP\n'
    skpdSummary.value.forEach((row, i) => {
      csvContent += `${i+1},"${row.skpd}",${row.jumlah_pegawai},${row.total_gaji_pokok},${row.total_bpjs_4_persen},${row.total_gaji_bersih},${row.pegawai_bawah_ump}\n`
    })
    csvContent += `,"TOTAL",${grandTotal.value.jumlah_pegawai},${grandTotal.value.total_gaji_pokok},${grandTotal.value.total_bpjs_4_persen},${grandTotal.value.total_gaji_bersih},${grandTotal.value.pegawai_bawah_ump}\n`
  } else {
    fileName = `Rekon_BPJS_4persen_Detail_${monthName}_${selectedYear.value}.csv`
    csvContent = 'No,NIP,Nama,SKPD,Jabatan,Gaji Pokok,BPJS 4%,Basis Hitung,Gaji Bersih\n'
    detail.value.forEach((row, i) => {
      csvContent += `${i+1},"=""${row.nip}""","${row.nama}","${row.skpd || row.upt || ''}","${row.jabatan || ''}",${row.gaji_pokok},${row.bpjs_4_persen},${row.basis_hitung},${row.total_amoun}\n`
    })
  }

  const BOM = '\uFEFF'
  const blob = new Blob([BOM + csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = fileName
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

onMounted(fetchUmp)
</script>

<style scoped>
.modern-bg {
  background-color: rgb(var(--v-theme-background)) !important;
}
.glass-card {
  background-color: rgb(var(--v-theme-surface)) !important;
  border: 1px solid rgba(var(--v-border-color), 0.08) !important;
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
