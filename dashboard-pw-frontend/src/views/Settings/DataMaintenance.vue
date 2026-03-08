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
                      <div class="text-subtitle-2 font-weight-bold mb-2">Scope Waktu (Opsional)</div>
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
                      <v-col cols="12" sm="6">
                        <v-select
                          v-model="clearParams.month"
                          :items="months"
                          label="Bulan"
                          variant="outlined"
                          density="comfortable"
                          :rules="[v => scopeType !== 'period' || !!v || 'Bulan wajib dipilih']"
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
              <span v-if="scopeType === 'period'">untuk periode {{ getMonthLabel(clearParams.month) }} {{ clearParams.year }}</span>
              <span v-else>seluruhnya (tanpa batasan waktu)</span>?
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

const clearParams = reactive({
  target: null,
  month: new Date().getMonth() + 1,
  year: new Date().getFullYear()
})

const snackbar = reactive({
  show: false,
  text: '',
  color: 'success'
})

const targetOptions = [
  { title: 'Data Gaji PNS', value: 'pns' },
  { title: 'Data Gaji PPPK', value: 'pppk' },
  { title: 'Semua Data Gaji (PNS & PPPK)', value: 'both' }
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

const handleClearData = async () => {
  isSubmitting.value = true
  try {
    const payload = {
      target: clearParams.target
    }

    if (scopeType.value === 'period') {
      payload.month = clearParams.month
      payload.year = clearParams.year
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
</script>
