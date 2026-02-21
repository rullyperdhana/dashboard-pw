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
              <v-icon start color="teal" size="36">mdi-upload-multiple</v-icon>
              Upload Data TPP
            </h1>
            <p class="text-subtitle-1 text-grey-darken-1">Upload data Tunjangan Penghasilan Pegawai (TPP) via Excel.</p>
          </v-col>
        </v-row>

        <v-row>
          <v-col cols="12" md="8" lg="6">
            <v-card class="glass-card rounded-xl pa-6">
              <v-form @submit.prevent="submitUpload" v-model="valid">
                <v-row>
                    <v-col cols="12" md="6">
                        <v-select
                        v-model="selectedMonth"
                        :items="months"
                        item-title="title"
                        item-value="value"
                        label="Bulan"
                        variant="outlined"
                        color="teal"
                        prepend-inner-icon="mdi-calendar"
                        :rules="[v => !!v || 'Bulan harus dipilih']"
                        ></v-select>
                    </v-col>
                    <v-col cols="12" md="6">
                        <v-select
                        v-model="selectedYear"
                        :items="years"
                        label="Tahun"
                        variant="outlined"
                        color="teal"
                        prepend-inner-icon="mdi-calendar"
                        :rules="[v => !!v || 'Tahun harus dipilih']"
                        ></v-select>
                    </v-col>
                </v-row>

                <v-select
                  v-model="employeeType"
                  :items="employeeTypes"
                  item-title="title"
                  item-value="value"
                  label="Tipe Pegawai"
                  variant="outlined"
                  color="teal"
                  class="mt-2"
                  prepend-inner-icon="mdi-account-group"
                  :rules="[v => !!v || 'Tipe pegawai harus dipilih']"
                ></v-select>

                <v-file-input
                  v-model="file"
                  label="Pilih File Excel"
                  accept=".xlsx,.xls,.csv"
                  variant="outlined"
                  color="teal"
                  class="mt-2"
                  prepend-inner-icon="mdi-file-excel"
                  show-size
                  :rules="[
                    v => !!v && (Array.isArray(v) ? v.length > 0 : true) || 'File harus dipilih',
                    v => {
                        const f = Array.isArray(v) ? v[0] : v;
                        return !f || f.size < 5000000 || 'Ukuran file maks 5MB';
                    }
                  ]"
                ></v-file-input>

                <div class="d-flex justify-space-between align-center mt-6">
                   <v-btn
                    variant="text"
                    color="primary"
                    prepend-icon="mdi-download"
                    @click="downloadTemplate"
                  >
                    Download Template
                  </v-btn>

                  <v-btn
                    type="submit"
                    color="teal"
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

          <v-col cols="12" md="4" lg="6">
             <v-card class="glass-card rounded-xl pa-6 bg-teal-lighten-5">
                <h3 class="text-h6 font-weight-bold mb-2 text-teal-darken-2">Petunjuk Upload</h3>
                <ul class="pl-4 text-body-2 text-grey-darken-3">
                    <li class="mb-2">Pastikan file Excel memiliki header: <strong>NIP, NAMA, NILAI</strong>.</li>
                    <li class="mb-2">Kolom <strong>NIP</strong> wajib diisi dan harus sesuai dengan data pegawai.</li>
                    <li class="mb-2">Kolom <strong>NILAI</strong> berisi nominal TPP (contoh: 1500000 atau 1.500.000).</li>
                    <li class="mb-2">Sistem akan otomatis menghitung ulang Total Tunjangan, Gaji Kotor, dan Gaji Bersih.</li>
                    <li class="mb-2">Format file yang didukung: .xlsx, .xls, .csv.</li>
                </ul>
             </v-card>
          </v-col>
        </v-row>
      
        <!-- Snackbar for notifications -->
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
import { ref, computed } from 'vue'
import api from '../api'
import Navbar from '../components/Navbar.vue'
import Sidebar from '../components/Sidebar.vue'

const loading = ref(false)
const valid = ref(false)
const file = ref([])
const selectedMonth = ref(new Date().getMonth() + 1)
const selectedYear = ref(new Date().getFullYear())
const employeeType = ref('pns')

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

const downloadTemplate = async () => {
    try {
        const response = await api.get('/tpp/template', { responseType: 'blob' })
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', 'template_upload_tpp.xlsx')
        document.body.appendChild(link)
        link.click()
        link.remove()
    } catch (error) {
        showSnackbar('Gagal download template', 'error')
    }
}

const submitUpload = async () => {
    const fileToUpload = Array.isArray(file.value) ? file.value[0] : file.value
    if (!fileToUpload) return

    loading.value = true
    const formData = new FormData()
    formData.append('file', fileToUpload)
    formData.append('month', selectedMonth.value)
    formData.append('year', selectedYear.value)
    formData.append('type', employeeType.value)

    try {
        const response = await api.post('/tpp/upload', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
        showSnackbar(response.data.message || 'Upload berhasil!', 'success')
        // Reset file input
        file.value = []
    } catch (error) {
        const msg = error.response?.data?.message || 'Gagal upload data'
        showSnackbar(msg, 'error')
    } finally {
        loading.value = false
    }
}

const showSnackbar = (msg, color = 'success') => {
    snackbar.value.message = msg
    snackbar.value.color = color
    snackbar.value.show = true
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
