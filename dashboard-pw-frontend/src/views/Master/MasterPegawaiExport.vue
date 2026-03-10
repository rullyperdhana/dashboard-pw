<template>
  <div class="modern-bg">
    <Navbar />
    <Sidebar />

    <v-main>
      <v-container fluid class="pa-8">
        <!-- Header -->
        <v-row class="mb-6">
          <v-col cols="12">
            <div class="d-flex align-center justify-space-between">
              <div>
                <h1 class="text-h4 font-weight-bold mb-1">
                  <v-icon start color="indigo" size="36">mdi-account-arrow-right</v-icon>
                  Export Master Pegawai
                </h1>
                <p class="text-subtitle-1 text-medium-emphasis">Kelola dan ekspor data induk pegawai untuk rekonsiliasi data.</p>
              </div>
            </div>
          </v-col>
        </v-row>

        <v-row>
          <v-col cols="12" md="6" lg="5">
            <v-card class="glass-card rounded-xl pa-6" elevation="0">
              <v-card-title class="pa-0 mb-4 text-h6 font-weight-bold">
                Filter Data Export
              </v-card-title>
              
              <v-row dense>
                <v-col cols="12">
                  <v-select
                    v-model="filters.kdskpd"
                    :items="skpdList"
                    item-title="nmskpd"
                    item-value="kdskpd"
                    label="SKPD"
                    variant="outlined"
                    density="comfortable"
                    color="indigo"
                    prepend-inner-icon="mdi-office-building"
                    clearable
                  ></v-select>
                </v-col>

                <v-col cols="12" md="6">
                  <v-select
                    v-model="filters.kd_jns_peg"
                    :items="[
                      { title: 'Semua Tipe', value: null },
                      { title: 'PNS', value: 2 },
                      { title: 'PPPK', value: 4 }
                    ]"
                    label="Tipe Pegawai"
                    variant="outlined"
                    density="comfortable"
                    color="indigo"
                    prepend-inner-icon="mdi-account-group"
                  ></v-select>
                </v-col>

                <v-col cols="12" md="6">
                  <v-select
                    v-model="filters.kdstapeg"
                    :items="statusList"
                    item-title="nmstapeg"
                    item-value="kdstapeg"
                    label="Status Pegawai"
                    variant="outlined"
                    density="comfortable"
                    color="indigo"
                    prepend-inner-icon="mdi-account-check"
                    clearable
                  ></v-select>
                </v-col>

                <v-col cols="12">
                  <v-text-field
                    v-model="filters.search"
                    label="Cari NIP atau Nama (Opsional)"
                    variant="outlined"
                    density="comfortable"
                    color="indigo"
                    prepend-inner-icon="mdi-magnify"
                    clearable
                    hide-details
                  ></v-text-field>
                </v-col>
              </v-row>

              <v-divider class="my-6"></v-divider>

              <div class="d-flex align-center justify-space-between">
                <div class="text-caption text-medium-emphasis">
                  Format File: <strong>.xlsx (Excel)</strong>
                </div>
                <v-btn
                  color="indigo"
                  size="large"
                  prepend-icon="mdi-microsoft-excel"
                  :loading="exporting"
                  @click="exportData"
                  class="rounded-lg px-6"
                >
                  Download Excel
                </v-btn>
              </div>
            </v-card>
          </v-col>

          <v-col cols="12" md="6" lg="7">
            <v-card class="glass-card rounded-xl pa-6" elevation="0">
              <v-card-title class="pa-0 mb-4 text-h6 font-weight-bold">
                <v-icon start color="amber-darken-2">mdi-information</v-icon>
                Informasi Export
              </v-card-title>
              
              <v-list bg-color="transparent" density="compact">
                <v-list-item class="px-0">
                  <template v-slot:prepend>
                    <v-icon color="indigo" size="small">mdi-check-circle</v-icon>
                  </template>
                  <v-list-item-title class="text-body-2">
                    Gunakan filter SKPD jika ingin mengeksport data per instansi saja.
                  </v-list-item-title>
                </v-list-item>
                
                <v-list-item class="px-0">
                  <template v-slot:prepend>
                    <v-icon color="indigo" size="small">mdi-check-circle</v-icon>
                  </template>
                  <v-list-item-title class="text-body-2">
                    Data yang dieksport adalah data master terbaru yang bersumber dari file DBF.
                  </v-list-item-title>
                </v-list-item>

                <v-list-item class="px-0">
                  <template v-slot:prepend>
                    <v-icon color="indigo" size="small">mdi-check-circle</v-icon>
                  </template>
                  <v-list-item-title class="text-body-2">
                    Kolom yang disertakan meliputi: NIP, NIK, NPWP, SKPD, Jabatan, dan Alamat.
                  </v-list-item-title>
                </v-list-item>

                <v-list-item class="px-0">
                  <template v-slot:prepend>
                    <v-icon color="indigo" size="small">mdi-check-circle</v-icon>
                  </template>
                  <v-list-item-title class="text-body-2">
                    Proses mungkin memakan waktu beberapa detik tergantung jumlah data.
                  </v-list-item-title>
                </v-list-item>
              </v-list>

              <v-alert
                type="info"
                variant="tonal"
                class="mt-4 rounded-lg"
                density="compact"
              >
                Data ini dapat digunakan sebagai referensi untuk rekonsiliasi dengan Simpeg BKD.
              </v-alert>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>

    <!-- Snackbar -->
    <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="3000">
      {{ snackbarText }}
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'
import Navbar from '@/components/Navbar.vue'
import Sidebar from '@/components/Sidebar.vue'

const skpdList = ref([])
const statusList = ref([])
const exporting = ref(false)
const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

const filters = ref({
  kdskpd: null,
  kd_jns_peg: null,
  kdstapeg: null,
  search: ''
})

const fetchInitialData = async () => {
  try {
    const [skpdRes, statsRes] = await Promise.all([
      api.get('/settings/satker-list'),
      api.get('/master/pegawai/stats')
    ])
    
    if (skpdRes.data.success) {
      skpdList.value = skpdRes.data.data
    }
    
    if (statsRes.data.success) {
      statusList.value = statsRes.data.data.by_code
    }
  } catch (error) {
    console.error('Error fetching initial data:', error)
    showSnackbar('Gagal memuat data filter', 'error')
  }
}

const exportData = async () => {
  exporting.value = true
  try {
    const response = await api.get('/master/pegawai/export', {
      params: filters.value,
      responseType: 'blob'
    })
    
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-')
    link.setAttribute('download', `master_pegawai_${timestamp}.xlsx`)
    
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    
    showSnackbar('Export berhasil diunduh')
  } catch (error) {
    console.error('Export error:', error)
    showSnackbar('Gagal melakukan export data', 'error')
  } finally {
    exporting.value = false
  }
}

const showSnackbar = (text, color = 'success') => {
  snackbarText.value = text
  snackbarColor.value = color
  snackbar.value = true
}

onMounted(fetchInitialData)
</script>

<style scoped>
</style>
