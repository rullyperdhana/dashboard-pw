<template>
  <div class="modern-dashboard">
    <Navbar />
    <Sidebar />

    <v-main class="bg-dashboard">
      <v-container fluid class="pa-6 pa-md-10">
        <!-- Header -->
        <header class="dashboard-header mb-8">
          <v-row align="center">
            <v-col cols="12">
              <div class="d-flex align-center mb-2">
                <v-avatar color="primary-lighten-4" size="48" class="mr-4">
                  <v-icon color="primary" size="28">mdi-account-arrow-right</v-icon>
                </v-avatar>
                <div>
                  <h1 class="text-h4 font-weight-black tracking-tight text-high-emphasis">Export Master Pegawai</h1>
                  <p class="text-subtitle-1 text-medium-emphasis">Kelola dan ekspor data induk pegawai untuk rekonsiliasi data.</p>
                </div>
              </div>
            </v-col>
          </v-row>
        </header>

        <v-row>
          <v-col cols="12" md="6" lg="5">
            <v-card class="glass-panel pa-8" elevation="0">
              <div class="text-overline font-weight-black mb-4 text-primary">KONFIGURASI EXPORT</div>
              
              <v-row dense>
                <v-col cols="12">
                  <v-select
                    v-model="filters.kdskpd"
                    :items="skpdList"
                    item-title="nmskpd"
                    item-value="kdskpd"
                    label="SKPD"
                    variant="filled"
                    flat
                    rounded="lg"
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
                    variant="filled"
                    flat
                    rounded="lg"
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
                    variant="filled"
                    flat
                    rounded="lg"
                    prepend-inner-icon="mdi-account-check"
                    clearable
                  ></v-select>
                </v-col>

                <v-col cols="12">
                  <v-text-field
                    v-model="filters.search"
                    label="Cari NIP atau Nama (Opsional)"
                    variant="filled"
                    flat
                    rounded="lg"
                    prepend-inner-icon="mdi-magnify"
                    clearable
                    hide-details
                  ></v-text-field>
                </v-col>
              </v-row>

              <v-divider class="my-8 opacity-10"></v-divider>

              <div class="d-flex align-center justify-space-between">
                <div class="text-caption text-medium-emphasis">
                  Format: <v-chip size="x-small" color="success" class="font-weight-bold">.xlsx (Excel)</v-chip>
                </div>
                <v-btn
                  color="primary"
                  size="large"
                  prepend-icon="mdi-microsoft-excel"
                  :loading="exporting"
                  @click="exportData"
                  variant="flat"
                  rounded="pill"
                  class="px-8 font-weight-black"
                >
                  DOWNLOAD DATA
                </v-btn>
              </div>
            </v-card>
          </v-col>

          <v-col cols="12" md="6" lg="7">
            <v-card class="glass-panel pa-8 border-dashed" elevation="0">
              <div class="d-flex align-center mb-6">
                <v-avatar color="info-lighten-4" size="40" class="mr-4">
                  <v-icon color="info" size="24">mdi-information-outline</v-icon>
                </v-avatar>
                <h3 class="text-h6 font-weight-black text-high-emphasis">Informasi Export</h3>
              </div>
              
              <v-list bg-color="transparent" class="pa-0">
                <v-list-item class="px-0 mb-3">
                  <template v-slot:prepend>
                    <v-icon color="success" size="20" class="mr-3">mdi-check-decagram-outline</v-icon>
                  </template>
                  <v-list-item-title class="text-body-2 text-medium-emphasis text-wrap">
                    Gunakan filter **SKPD** untuk mengeksport data spesifik per instansi.
                  </v-list-item-title>
                </v-list-item>
                
                <v-list-item class="px-0 mb-3">
                  <template v-slot:prepend>
                    <v-icon color="success" size="20" class="mr-3">mdi-check-decagram-outline</v-icon>
                  </template>
                  <v-list-item-title class="text-body-2 text-medium-emphasis text-wrap">
                    Data bersumber dari master terbaru hasil pemrosesan file **DBF**.
                  </v-list-item-title>
                </v-list-item>

                <v-list-item class="px-0 mb-3">
                  <template v-slot:prepend>
                    <v-icon color="success" size="20" class="mr-3">mdi-check-decagram-outline</v-icon>
                  </template>
                  <v-list-item-title class="text-body-2 text-medium-emphasis text-wrap">
                    Kolom exported: **NIP, NIK (No.KTP), NPWP, SKPD, Jabatan**, dan **Alamat**.
                  </v-list-item-title>
                </v-list-item>

                <v-list-item class="px-0">
                  <template v-slot:prepend>
                    <v-icon color="success" size="20" class="mr-3">mdi-check-decagram-outline</v-icon>
                  </template>
                  <v-list-item-title class="text-body-2 text-medium-emphasis text-wrap">
                    Proses otomatis menyesuaikan dengan volume data (beberapa detik).
                  </v-list-item-title>
                </v-list-item>
              </v-list>

              <v-alert
                type="info"
                variant="tonal"
                class="mt-8 rounded-xl border-0"
                density="comfortable"
              >
                <div class="text-caption">
                   Data ini dapat digunakan sebagai referensi untuk rekonsiliasi berkala dengan **Simpeg BKD**.
                </div>
              </v-alert>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>

    <!-- Snackbar -->
    <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="3000" class="rounded-lg">
      <div class="d-flex align-center">
        <v-icon start :icon="snackbarColor === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle'" class="mr-2"></v-icon>
        {{ snackbarText }}
      </div>
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
.modern-dashboard {
  min-height: 100vh;
}

.bg-dashboard {
  background-color: rgb(var(--v-theme-background));
  background-image: 
    radial-gradient(at 0% 0%, rgba(var(--v-theme-primary), 0.05) 0, transparent 50%),
    radial-gradient(at 100% 100%, rgba(var(--v-theme-info), 0.05) 0, transparent 50%);
}

.glass-panel {
  background: rgba(var(--v-theme-surface), 0.7) !important;
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(var(--v-border-color), 0.1) !important;
  border-radius: 28px !important;
  box-shadow: 0 4px 10px -2px rgba(0, 0, 0, 0.03), 0 2px 5px -2px rgba(0, 0, 0, 0.02) !important;
}

.border-dashed {
  border-style: dashed !important;
}

.text-wrap {
  white-space: normal !important;
}
</style>
