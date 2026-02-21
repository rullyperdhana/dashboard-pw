<template>
  <v-app class="modern-dashboard">
    <Navbar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />
    <Sidebar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />

    <v-main class="bg-light">
      <v-container fluid class="pa-8">
        <!-- Header -->
        <v-row class="mb-6 align-center">
          <v-col cols="12">
            <h1 class="text-h4 font-weight-bold mb-1">
              <v-icon start color="deep-purple" size="36">mdi-school-outline</v-icon>
              Upload Data TPG
            </h1>
            <p class="text-subtitle-1 text-grey-darken-1">Upload data Tunjangan Profesi Guru (TPG) per Triwulan via Excel.</p>
          </v-col>
        </v-row>

        <v-row>
          <!-- Upload Form -->
          <v-col cols="12" md="7" lg="6">
            <v-card class="glass-card rounded-xl pa-6">
              <v-form @submit.prevent="submitUpload" v-model="valid">
                <v-row>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="selectedTriwulan"
                      :items="triwulanOptions"
                      item-title="title"
                      item-value="value"
                      label="Triwulan"
                      variant="outlined"
                      density="comfortable"
                      color="deep-purple"
                      prepend-inner-icon="mdi-calendar-range"
                      :rules="[v => !!v || 'Triwulan harus dipilih']"
                    ></v-select>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="selectedYear"
                      :items="years"
                      label="Tahun"
                      variant="outlined"
                      density="comfortable"
                      color="deep-purple"
                      prepend-inner-icon="mdi-calendar"
                      :rules="[v => !!v || 'Tahun harus dipilih']"
                    ></v-select>
                  </v-col>
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="selectedJenis"
                      :items="jenisOptions"
                      item-title="title"
                      item-value="value"
                      label="Jenis Upload"
                      variant="outlined"
                      density="comfortable"
                      color="deep-purple"
                      prepend-inner-icon="mdi-tag-outline"
                      :rules="[v => !!v || 'Jenis harus dipilih']"
                    ></v-select>
                    <v-alert
                      v-if="selectedJenis === 'INDUK'"
                      type="warning"
                      variant="tonal"
                      density="compact"
                      class="mt-1 text-caption"
                    >
                      Upload Induk akan <strong>menimpa</strong> data Induk sebelumnya untuk TW & Tahun yang sama.
                    </v-alert>
                    <v-alert
                      v-if="selectedJenis === 'SUSULAN'"
                      type="info"
                      variant="tonal"
                      density="compact"
                      class="mt-1 text-caption"
                    >
                      Upload Susulan akan <strong>menambahkan</strong> data tanpa menghapus data sebelumnya.
                    </v-alert>
                  </v-col>
                </v-row>

                <v-file-input
                  v-model="file"
                  label="Pilih File Excel (.xlsx)"
                  accept=".xlsx,.xls"
                  variant="outlined"
                  density="comfortable"
                  color="deep-purple"
                  prepend-icon=""
                  prepend-inner-icon="mdi-file-excel-outline"
                  show-size
                  :rules="[
                    v => {
                      const f = Array.isArray(v) ? v[0] : v;
                      return !f || f.size < 10000000 || 'Ukuran file maks 10MB';
                    }
                  ]"
                ></v-file-input>

                <div class="d-flex justify-space-between align-center mt-6">
                  <v-btn
                    variant="text"
                    color="deep-purple"
                    prepend-icon="mdi-eye-outline"
                    @click="viewDashboard"
                  >
                    Lihat Dashboard TPG
                  </v-btn>
                  <v-btn
                    type="submit"
                    color="deep-purple"
                    variant="elevated"
                    prepend-icon="mdi-cloud-upload-outline"
                    size="large"
                    :loading="loading"
                    :disabled="!valid || !file"
                    elevation="2"
                  >
                    Upload Data
                  </v-btn>
                </div>
              </v-form>
            </v-card>
          </v-col>

          <!-- Instructions Panel -->
          <v-col cols="12" md="5" lg="6">
            <v-card class="glass-card rounded-xl pa-6 bg-purple-lighten-5">
              <h3 class="text-h6 font-weight-bold mb-3 text-deep-purple-darken-2">
                <v-icon start size="20">mdi-information-outline</v-icon>
                Petunjuk Upload TPG
              </h3>
              <ul class="text-body-2 text-grey-darken-2" style="list-style: none; padding-left: 0;">
                <li class="mb-3 d-flex align-start">
                  <v-icon size="18" color="deep-purple" class="mr-2 mt-1">mdi-numeric-1-circle</v-icon>
                  <span>Pilih <strong>Triwulan</strong> (TW1–TW4) dan <strong>Tahun</strong> data TPG.</span>
                </li>
                <li class="mb-3 d-flex align-start">
                  <v-icon size="18" color="deep-purple" class="mr-2 mt-1">mdi-numeric-2-circle</v-icon>
                  <span>Pilih file Excel (.xlsx) yang berisi data TPG.</span>
                </li>
                <li class="mb-3 d-flex align-start">
                  <v-icon size="18" color="deep-purple" class="mr-2 mt-1">mdi-numeric-3-circle</v-icon>
                  <span>File harus memiliki kolom: <strong>NIP, NAMA, NO. REKENING, SATDIK, SALUR BRUT, POT. JKN, SALUR NETT</strong>.</span>
                </li>
                <li class="mb-3 d-flex align-start">
                  <v-icon size="18" color="deep-purple" class="mr-2 mt-1">mdi-numeric-4-circle</v-icon>
                <span>Upload <strong>Induk</strong> untuk triwulan & tahun yang sama akan <strong>menimpa</strong> data Induk sebelumnya. Upload <strong>Susulan</strong> akan <strong>menambahkan</strong> data tanpa menghapus data yang ada.</span>
                </li>
                <li class="mb-2 d-flex align-start">
                  <v-icon size="18" color="deep-purple" class="mr-2 mt-1">mdi-numeric-5-circle</v-icon>
                  <span>Ukuran file maksimal <strong>10 MB</strong>.</span>
                </li>
              </ul>

              <v-divider class="my-4"></v-divider>

              <div class="text-caption text-grey-darken-1">
                <v-icon size="14" class="mr-1">mdi-lightbulb-outline</v-icon>
                Baris pertama file Excel harus berisi header kolom.
              </div>
            </v-card>
          </v-col>
        </v-row>

        <!-- Upload Result -->
        <v-row v-if="uploadResult" class="mt-4">
          <v-col cols="12" md="7" lg="6">
            <v-alert
              :type="uploadResult.success ? 'success' : 'error'"
              variant="tonal"
              closable
              @click:close="uploadResult = null"
              class="rounded-lg"
            >
              <div class="font-weight-bold mb-1">{{ uploadResult.success ? 'Upload Berhasil!' : 'Upload Gagal' }}</div>
              {{ uploadResult.message }}
            </v-alert>
          </v-col>
        </v-row>

        <!-- Snackbar -->
        <v-snackbar
          v-model="snackbar.show"
          :color="snackbar.color"
          timeout="5000"
          location="top right"
        >
          {{ snackbar.message }}
          <template v-slot:actions>
            <v-btn variant="text" @click="snackbar.show = false">Tutup</v-btn>
          </template>
        </v-snackbar>

      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api'
import Navbar from '../components/Navbar.vue'
import Sidebar from '../components/Sidebar.vue'

const router = useRouter()
const loading = ref(false)
const valid = ref(false)
const file = ref(null)
const uploadResult = ref(null)

const selectedTriwulan = ref(null)
const selectedYear = ref(new Date().getFullYear())
const selectedJenis = ref('INDUK')

const triwulanOptions = [
  { title: 'Triwulan 1 (Jan–Mar)', value: 1 },
  { title: 'Triwulan 2 (Apr–Jun)', value: 2 },
  { title: 'Triwulan 3 (Jul–Sep)', value: 3 },
  { title: 'Triwulan 4 (Okt–Des)', value: 4 },
]

const jenisOptions = [
  { title: 'Induk (Data Utama)', value: 'INDUK' },
  { title: 'Susulan (Tambahan)', value: 'SUSULAN' },
]

const years = [2024, 2025, 2026, 2027]

const snackbar = ref({ show: false, message: '', color: 'success' })

const viewDashboard = () => {
  router.push('/tpg-dashboard')
}

const submitUpload = async () => {
  if (!file.value) return

  loading.value = true
  uploadResult.value = null

  try {
    const formData = new FormData()
    const f = Array.isArray(file.value) ? file.value[0] : file.value
    formData.append('file', f)
    formData.append('triwulan', selectedTriwulan.value)
    formData.append('tahun', selectedYear.value)
    formData.append('jenis', selectedJenis.value)

    const response = await api.post('/tpg/upload', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    uploadResult.value = {
      success: true,
      message: response.data.message
    }

    showSnackbar(response.data.message, 'success')
    file.value = null
  } catch (error) {
    const msg = error.response?.data?.message || 'Terjadi kesalahan saat upload.'
    uploadResult.value = {
      success: false,
      message: msg
    }
    showSnackbar(msg, 'error')
  } finally {
    loading.value = false
  }
}

const showSnackbar = (msg, color = 'success') => {
  snackbar.value = { show: true, message: msg, color }
}
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
</style>
