<template>
  <div class="modern-dashboard">
    <Navbar />
    <Sidebar />

    <v-main class="bg-light">
      <v-container fluid class="pa-8">
        <!-- Header -->
        <v-row class="mb-6 align-center">
          <v-col cols="12">
            <h1 class="text-h4 font-weight-bold mb-1">
              <v-icon start color="warning" size="36">mdi-history</v-icon>
              Riwayat Selisih TPP
            </h1>
            <p class="text-subtitle-1 text-grey-darken-1">Lihat dan download laporan selisih TPP dari periode sebelumnya.</p>
          </v-col>
        </v-row>

        <!-- Filters -->
        <v-row class="mb-6">
          <v-col cols="12">
            <v-card class="glass-card rounded-xl pa-6">
              <v-row align="center">
                <v-col cols="12" md="3">
                  <v-select
                    v-model="filters.month"
                    :items="months"
                    item-title="title"
                    item-value="value"
                    label="Bulan"
                    variant="outlined"
                    color="teal"
                    density="compact"
                    hide-details
                    prepend-inner-icon="mdi-calendar"
                  ></v-select>
                </v-col>
                <v-col cols="12" md="2">
                  <v-select
                    v-model="filters.year"
                    :items="years"
                    label="Tahun"
                    variant="outlined"
                    color="teal"
                    density="compact"
                    hide-details
                    prepend-inner-icon="mdi-calendar"
                  ></v-select>
                </v-col>
                <v-col cols="12" md="3">
                  <v-select
                    v-model="filters.type"
                    :items="employeeTypes"
                    item-title="title"
                    item-value="value"
                    label="Tipe Pegawai"
                    variant="outlined"
                    color="teal"
                    density="compact"
                    hide-details
                    prepend-inner-icon="mdi-account-group"
                  ></v-select>
                </v-col>
                <v-col cols="12" md="4" class="d-flex gap-2">
                  <v-btn
                    color="teal"
                    prepend-icon="mdi-magnify"
                    :loading="loading"
                    @click="fetchDiscrepancies"
                  >
                    Tampilkan
                  </v-btn>
                  <v-btn
                    v-if="logs.length > 0"
                    color="warning"
                    variant="tonal"
                    prepend-icon="mdi-file-export-outline"
                    @click="exportLogs"
                  >
                    Export
                  </v-btn>
                </v-col>
              </v-row>
            </v-card>
          </v-col>
        </v-row>

        <!-- Data Table -->
        <v-row>
          <v-col cols="12">
            <v-card class="glass-card rounded-xl overflow-hidden">
               <v-text-field
                v-model="search"
                prepend-inner-icon="mdi-magnify"
                label="Cari NIP atau Nama..."
                variant="solo"
                flat
                hide-details
                class="px-4 py-2 border-bottom"
              ></v-text-field>

              <v-data-table
                :headers="headers"
                :items="logs"
                :loading="loading"
                :search="search"
                class="bg-transparent"
                hover
              >
                <template v-slot:no-data>
                   <v-alert v-if="!loading" type="info" variant="tonal" density="compact" class="ma-4">
                        Pilih periode dan klik tombol "Tampilkan" untuk melihat riwayat selisih.
                   </v-alert>
                </template>
                
                <template v-slot:item.nip="{ item }">
                  <span class="font-weight-medium text-teal">{{ item.nip }}</span>
                </template>

                <template v-slot:item.reason="{ item }">
                  <v-chip size="small" color="warning" variant="tonal">
                    {{ item.reason }}
                  </v-chip>
                </template>
              </v-data-table>
            </v-card>
          </v-col>
        </v-row>

        <!-- Snackbar -->
        <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000">
          {{ snackbar.message }}
        </v-snackbar>
      </v-container>
    </v-main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../api'
import Navbar from '../components/Navbar.vue'
import Sidebar from '../components/Sidebar.vue'

const loading = ref(false)
const search = ref('')
const logs = ref([])

const filters = ref({
  month: new Date().getMonth() + 1,
  year: new Date().getFullYear(),
  type: 'pns'
})

const snackbar = ref({
  show: false,
  message: '',
  color: 'success'
})

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

const years = [2024, 2025, 2026, 2027]
const employeeTypes = [
  { title: 'PNS', value: 'pns' },
  { title: 'PPPK', value: 'pppk' }
]

const headers = [
  { title: 'NIP', key: 'nip', sortable: true },
  { title: 'NAMA', key: 'nama', sortable: true },
  { title: 'SKPD', key: 'skpd', sortable: true },
  { title: 'KETERANGAN', key: 'reason', sortable: true },
  { title: 'TGL LOG', key: 'created_at', sortable: true },
]

const fetchDiscrepancies = async () => {
  loading.value = true
  try {
    const res = await api.get('/tpp/discrepancies', { params: filters.value })
    logs.value = res.data.data
    if (logs.value.length === 0) {
      showSnackbar('Tidak ada data selisih untuk periode ini.', 'info')
    }
  } catch (error) {
    showSnackbar('Gagal mengambil data riwayat.', 'error')
  } finally {
    loading.value = false
  }
}

const exportLogs = () => {
  const headerArr = ['NIP', 'NAMA', 'SKPD', 'KETERANGAN', 'TANGGAL']
  const rows = logs.value.map(l => [
    l.nip, 
    l.nama, 
    l.skpd, 
    l.reason, 
    new Date(l.created_at).toLocaleString()
  ])
  
  let csvContent = "data:text/csv;charset=utf-8," 
    + headerArr.join(",") + "\n"
    + rows.map(e => e.join(",")).join("\n");

  const encodedUri = encodeURI(csvContent);
  const link = document.createElement("a");
  link.setAttribute("href", encodedUri);
  link.setAttribute("download", `Riwayat_Selisih_TPP_${filters.value.month}_${filters.value.year}.csv`);
  document.body.appendChild(link);
  link.click();
  link.remove();
}

const showSnackbar = (msg, color = 'success') => {
  snackbar.value.message = msg
  snackbar.value.color = color
  snackbar.value.show = true
}

onMounted(() => {
  fetchDiscrepancies()
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

.border-bottom {
  border-bottom: 1px solid rgba(var(--v-border-color), 0.08) !important;
}

.gap-2 {
  gap: 8px;
}
</style>
