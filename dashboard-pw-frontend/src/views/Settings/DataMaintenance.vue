<template>
  <div>
    <Sidebar />
    <v-main class="bg-light">
      <Navbar />
      <v-container fluid class="pa-6">
        <div class="d-flex align-center mb-6">
          <v-btn icon="mdi-arrow-left" variant="text" @click="$router.back()" class="mr-2"></v-btn>
          <div>
            <h1 class="text-h4 font-weight-bold text-primary">Pemeliharaan Data</h1>
            <p class="text-subtitle-1 text-medium-emphasis">Kelola dan bersihkan data transaksi sistem</p>
          </div>
        </div>

        <v-row>
          <v-col cols="12" md="8">
            <v-card class="rounded-xl overflow-hidden mb-6" elevation="2">
              <v-card-title class="pa-4 bg-error text-white d-flex align-center">
                <v-icon start icon="mdi-alert-octagon" color="white"></v-icon>
                <span>Kosongkan Data Gaji</span>
              </v-card-title>
              <v-card-text class="pa-6">
                <v-alert
                  type="warning"
                  variant="tonal"
                  class="mb-6 rounded-lg"
                  title="Peringatan Penting"
                  text="Tindakan ini akan menghapus data gaji dari database secara permanen. Data yang sudah dihapus tidak dapat dikembalikan."
                ></v-alert>

                <v-form ref="clearForm" v-model="isValid">
                  <v-row>
                    <v-col cols="12" sm="6">
                      <div class="text-subtitle-2 font-weight-bold mb-2">Target Data</div>
                      <v-select
                        v-model="clearParams.target"
                        :items="targetOptions"
                        label="Pilih Target"
                        variant="outlined"
                        density="comfortable"
                        :rules="[v => !!v || 'Target data wajib dipilih']"
                      ></v-select>
                    </v-col>
                    <v-col cols="12" sm="6">
                      <div class="text-subtitle-2 font-weight-bold mb-2">Scope Waktu</div>
                      <v-select
                        v-model="scopeType"
                        :items="scopeOptions"
                        label="Rentang Waktu"
                        variant="outlined"
                        density="comfortable"
                      ></v-select>
                    </v-col>
                  </v-row>

                  <v-expand-transition>
                    <v-row v-if="scopeType === 'period'">
                      <v-col cols="12" sm="6" v-if="clearParams.target !== 'tpg'">
                        <v-select
                          v-model="clearParams.month"
                          :items="months"
                          label="Bulan"
                          variant="outlined"
                          density="comfortable"
                          :rules="[v => scopeType !== 'period' || clearParams.target === 'tpg' || !!v || 'Bulan wajib dipilih']"
                        ></v-select>
                      </v-col>
                      <v-col cols="12" sm="6" v-if="clearParams.target === 'tpg'">
                        <v-select
                          v-model="clearParams.triwulan"
                          :items="triwulanOptions"
                          label="Triwulan"
                          variant="outlined"
                          density="comfortable"
                          :rules="[v => scopeType !== 'period' || clearParams.target !== 'tpg' || !!v || 'Triwulan wajib dipilih']"
                        ></v-select>
                      </v-col>
                      <v-col cols="12" sm="6">
                        <v-select
                          v-model="clearParams.year"
                          :items="years"
                          label="Tahun"
                          variant="outlined"
                          density="comfortable"
                          :rules="[v => scopeType !== 'period' || !!v || 'Tahun wajib dipilih']"
                        ></v-select>
                      </v-col>
                    </v-row>
                  </v-expand-transition>

                  <v-row class="mt-2">
                    <v-col cols="12" sm="6">
                      <div class="text-subtitle-2 font-weight-bold mb-2">Jenis Gaji (Opsional)</div>
                      <v-select
                        v-model="clearParams.jenis_gaji"
                        :items="jenisGajiOptions"
                        label="Pilih Jenis Gaji"
                        placeholder="Semua Jenis Gaji"
                        variant="outlined"
                        density="comfortable"
                        clearable
                        :disabled="!['pns', 'pppk', 'both', 'tpp', 'pns_kekurangan', 'pppk_kekurangan'].includes(clearParams.target)"
                      ></v-select>
                    </v-col>
                    <v-col cols="12" sm="6">
                      <div class="text-subtitle-2 font-weight-bold mb-2">SKPD (Opsional)</div>
                      <v-autocomplete
                        v-model="clearParams.skpd_id"
                        :items="skpds"
                        item-title="nama_skpd"
                        item-value="id_skpd"
                        label="Cari SKPD"
                        placeholder="Semua SKPD"
                        variant="outlined"
                        density="comfortable"
                        clearable
                        :loading="isLoadingSkpd"
                        :disabled="!clearParams.target || clearParams.target === 'tpg'"
                      ></v-autocomplete>
                    </v-col>
                    <v-col cols="12" class="mt-n4">
                      <p class="text-caption text-medium-emphasis">
                        <v-icon size="small" icon="mdi-information-outline" class="mr-1"></v-icon>
                        Gunakan filter di atas untuk menghapus hanya jenis gaji tertentu (misal: Kekurangan, THR) atau per SKPD.
                      </p>
                    </v-col>
                  </v-row>

                  <v-divider class="my-6"></v-divider>

                  <div class="d-flex justify-end">
                    <v-btn
                      color="error"
                      size="large"
                      prepend-icon="mdi-trash-can"
                      :disabled="!isValid"
                      @click="confirmationCode = ''; confirmDialog = true"
                    >
                      KOSONGKAN DATA
                    </v-btn>
                  </div>
                </v-form>
              </v-card-text>
            </v-card>

            <v-card class="rounded-xl overflow-hidden mb-6" elevation="2">
              <v-card-title class="pa-4 bg-primary text-white d-flex align-center">
                <v-icon start icon="mdi-database-sync" color="white"></v-icon>
                <span>Backup & Restore Database</span>
              </v-card-title>
              <v-card-text class="pa-6">
                <v-row>
                  <v-col cols="12" sm="6">
                    <div class="text-subtitle-1 font-weight-bold mb-2">Ekspor Database (Backup)</div>
                    <p class="text-caption text-medium-emphasis mb-4">
                      Buat salinan database saat ini dalam format SQL (Gzip). Gunakan file ini untuk sinkronisasi ke laptop lokal.
                    </p>
                    <v-btn
                      color="primary"
                      variant="flat"
                      prepend-icon="mdi-download"
                      block
                      size="large"
                      :loading="isBackingUp"
                      @click="handleBackup"
                    >
                      UNDUH BACKUP (.SQL.GZ)
                    </v-btn>
                  </v-col>
                  
                  <v-divider vertical class="hidden-xs-only mx-4"></v-divider>
                  
                  <v-col cols="12" sm="6" class="pt-6 pt-sm-0">
                    <div class="text-subtitle-1 font-weight-bold mb-2">Impor Database (Restore)</div>
                    <p class="text-caption text-medium-emphasis mb-4">
                      Pulihkan data dari file .sql atau .sql.gz. HANYA lakukan ini di localhost untuk sinkronisasi data dari VPS.
                    </p>
                    <v-file-input
                      v-model="selectedBackupFile"
                      label="Pilih File Database"
                      variant="outlined"
                      density="comfortable"
                      accept=".sql,.gz"
                      prepend-icon=""
                      prepend-inner-icon="mdi-file-database"
                      hide-details
                      class="mb-4"
                    ></v-file-input>
                    <v-btn
                      color="secondary"
                      variant="tonal"
                      prepend-icon="mdi-upload"
                      block
                      size="large"
                      :disabled="!selectedBackupFile"
                      :loading="isImporting"
                      @click="handleImport"
                    >
                      MULAI IMPOR DATA
                    </v-btn>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-card>
          </v-col>

          <v-col cols="12" md="4">
            <v-card class="rounded-xl pa-4" elevation="1">
              <h3 class="text-h6 font-weight-bold mb-4">Informasi</h3>
              <v-list density="compact" class="bg-transparent">
                <v-list-item prepend-icon="mdi-information-outline">
                  <v-list-item-subtitle>Gunakan fitur ini untuk membersihkan data hasil upload yang salah.</v-list-item-subtitle>
                </v-list-item>
                <v-list-item prepend-icon="mdi-history">
                  <v-list-item-subtitle>Setiap tindakan penghapusan akan dicatat ke dalam log sistem.</v-list-item-subtitle>
                </v-list-item>
              </v-list>
            </v-card>
          </v-col>
        </v-row>

        <!-- Confirmation Dialog -->
        <v-dialog v-model="confirmDialog" max-width="450">
          <v-card class="rounded-xl pa-2">
            <v-card-title class="pa-4 font-weight-bold text-error d-flex align-center">
              <v-icon start icon="mdi-alert" color="error"></v-icon>
              Konfirmasi Penghapusan
            </v-card-title>
            <v-card-text>
              Apakah Anda benar-benar yakin ingin menghapus data 
              <strong>{{ getTargetLabel(clearParams.target) }}</strong> 
              <span v-if="scopeType === 'period'">
                untuk periode 
                <span v-if="clearParams.target === 'tpg'">Triwulan {{ clearParams.triwulan }}</span>
                <span v-else>{{ getMonthLabel(clearParams.month) }}</span>
                {{ clearParams.year }}
              </span>
              <span v-else>seluruhnya (tanpa batasan waktu)</span>
              <span v-if="clearParams.jenis_gaji"> dengan jenis gaji <strong>{{ clearParams.jenis_gaji }}</strong></span>
              <span v-if="clearParams.skpd_id"> untuk <strong>{{ getSkpdName(clearParams.skpd_id) }}</strong></span>?
              <br><br>
              Tindakan ini <strong>TIDAK DAPAT DIBATALKAN</strong>.

              <v-text-field
                v-model="confirmationCode"
                label="Kode Konfirmasi"
                placeholder="Format: JamBulanTanggal (contoh: 140305)"
                variant="outlined"
                density="compact"
                class="mt-4"
                prepend-inner-icon="mdi-lock-outline"
              ></v-text-field>
            </v-card-text>
            <v-card-actions class="pa-4">
              <v-spacer></v-spacer>
              <v-btn color="grey" variant="text" @click="confirmDialog = false">BATAL</v-btn>
              <v-btn color="error" variant="flat" :loading="isSubmitting" :disabled="!confirmationCode" @click="handleClearData">YA, HAPUS PERMANEN</v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>

        <!-- Notification -->
        <v-snackbar v-model="snackbar.show" :color="snackbar.color" location="top">
          {{ snackbar.text }}
          <template v-slot:actions>
            <v-btn variant="text" @click="snackbar.show = false">Tutup</v-btn>
          </template>
        </v-snackbar>
      </v-container>
    </v-main>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import Sidebar from '../../components/Sidebar.vue'
import Navbar from '../../components/Navbar.vue'
import api from '../../api'

const isValid = ref(false)
const isSubmitting = ref(false)
const confirmDialog = ref(false)
const confirmationCode = ref('')
const scopeType = ref('all')
const skpds = ref([])
const isLoadingSkpd = ref(false)

const clearParams = reactive({
  target: null,
  month: new Date().getMonth() + 1,
  year: new Date().getFullYear(),
  triwulan: 1,
  jenis_gaji: null,
  skpd_id: null
})

const isBackingUp = ref(false)
const isImporting = ref(false)
const selectedBackupFile = ref(null)

const snackbar = reactive({
  show: false,
  text: '',
  color: 'success'
})

const targetOptions = [
  { title: 'Data Gaji PNS (Rincian)', value: 'pns' },
  { title: 'Data Gaji PPPK (Rincian)', value: 'pppk' },
  { title: 'Gaji PNS & PPPK (Keduanya)', value: 'both' },
  { title: 'Gaji Kekurangan PNS', value: 'pns_kekurangan' },
  { title: 'Gaji Kekurangan PPPK', value: 'pppk_kekurangan' },
  { title: 'Data TPP Standalone', value: 'tpp' },
  { title: 'Data TPG (Sertifikasi Guru)', value: 'tpg' },
]

const jenisGajiOptions = [
  'Induk', 'Susulan', 'Kekurangan', 'Terusan', 'THR', 'Gaji 13'
]

const triwulanOptions = [
  { title: 'Triwulan 1 (Jan-Mar)', value: 1 },
  { title: 'Triwulan 2 (Apr-Jun)', value: 2 },
  { title: 'Triwulan 3 (Jul-Sep)', value: 3 },
  { title: 'Triwulan 4 (Okt-Des)', value: 4 },
]

const scopeOptions = [
  { title: 'Seluruh Data', value: 'all' },
  { title: 'Periode Tertentu (Bulan/Tahun)', value: 'period' }
]

const months = [
  { title: 'Januari', value: 1 }, { title: 'Februari', value: 2 }, { title: 'Maret', value: 3 },
  { title: 'April', value: 4 }, { title: 'Mei', value: 5 }, { title: 'Juni', value: 6 },
  { title: 'Juli', value: 7 }, { title: 'Agustus', value: 8 }, { title: 'September', value: 9 },
  { title: 'Oktober', value: 10 }, { title: 'November', value: 11 }, { title: 'Desember', value: 12 }
]

const years = computed(() => {
  const currentYear = new Date().getFullYear()
  return Array.from({ length: 5 }, (_, i) => currentYear - i)
})

const getTargetLabel = (val) => targetOptions.find(o => o.value === val)?.title || ''
const getMonthLabel = (val) => months.find(m => m.value === val)?.title || ''
const getSkpdName = (id) => skpds.value.find(s => s.id_skpd === id)?.nama_skpd || id

const fetchSkpds = async () => {
  isLoadingSkpd.value = true
  try {
    const res = await api.get('/skpd')
    skpds.value = res.data
  } catch (e) {
    console.error('Failed to fetch SKPDs', e)
  } finally {
    isLoadingSkpd.value = false
  }
}

fetchSkpds()

const handleClearData = async () => {
  isSubmitting.value = true
  try {
    const payload = {
      target: clearParams.target
    }

    // Handle shortcuts for Kekurangan
    if (clearParams.target === 'pns_kekurangan') {
      payload.target = 'pns'
      payload.jenis_gaji = 'Kekurangan'
    } else if (clearParams.target === 'pppk_kekurangan') {
      payload.target = 'pppk'
      payload.jenis_gaji = 'Kekurangan'
    }

    if (scopeType.value === 'period') {
      if (clearParams.target === 'tpg') {
        payload.triwulan = clearParams.triwulan
      } else {
        payload.month = clearParams.month
      }
      payload.year = clearParams.year
    }

    if (clearParams.jenis_gaji && !payload.jenis_gaji) {
      payload.jenis_gaji = clearParams.jenis_gaji
    }

    if (clearParams.skpd_id) {
      payload.skpd_id = clearParams.skpd_id
    }

    payload.confirmation_code = confirmationCode.value

    const response = await api.post('/settings/clear-payroll', payload)
    
    if (response.data.success) {
      snackbar.text = response.data.message
      snackbar.color = 'success'
      snackbar.show = true
      confirmDialog.value = false
    }
  } catch (error) {
    console.error('Clear data error:', error)
    snackbar.text = error.response?.data?.message || 'Gagal menghapus data'
    snackbar.color = 'error'
    snackbar.show = true
  } finally {
    isSubmitting.value = false
  }
}

const handleBackup = async () => {
  isBackingUp.value = true
  try {
    const response = await api.get('/settings/db-backup', {
      responseType: 'blob'
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `backup_db_${new Date().toISOString().slice(0,10)}.sql.gz`)
    document.body.appendChild(link)
    link.click()
    snackbar.text = 'Backup berhasil dibuat dan diunduh'
    snackbar.color = 'success'
    snackbar.show = true
  } catch (error) {
    console.error('Backup error:', error)
    snackbar.text = 'Gagal membuat backup database'
    snackbar.color = 'error'
    snackbar.show = true
  } finally {
    isBackingUp.value = false
  }
}

const handleImport = async () => {
  if (!selectedBackupFile.value) return
  
  if (!confirm('Apakah Anda yakin ingin memulihkan database? Data saat ini mungkin akan tertimpa.')) return

  isImporting.value = true
  try {
    const formData = new FormData()
    formData.append('file', selectedBackupFile.value)

    const response = await api.post('/settings/db-import', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    if (response.data.success) {
      snackbar.text = 'Database berhasil dipulihkan!'
      snackbar.color = 'success'
      snackbar.show = true
      selectedBackupFile.value = null
    }
  } catch (error) {
    console.error('Import error:', error)
    snackbar.text = error.response?.data?.message || 'Gagal memulihkan database'
    snackbar.color = 'error'
    snackbar.show = true
  } finally {
    isImporting.value = false
  }
}
</script>
