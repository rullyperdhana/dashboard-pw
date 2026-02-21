<template>
  <v-app class="modern-bg">
    <Navbar />
    <Sidebar />

    <v-main>
      <v-container fluid class="pa-8">
        <!-- Page Header -->
        <div class="d-flex align-center mb-6">
          <div>
            <h1 class="text-h5 font-weight-bold">Data Gaji PPPK</h1>
            <p class="text-body-2 text-medium-emphasis mt-1">Kelola data gaji PPPK Penuh Waktu</p>
          </div>
          <v-spacer></v-spacer>
          <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog" class="rounded-lg">
            TAMBAH DATA
          </v-btn>
        </div>

        <v-row>
          <!-- Filter Sidebar -->
          <v-col cols="12" md="3">
            <div class="sticky-top">
              <!-- Stats Card -->
              <v-card class="glass-card rounded-xl mb-4 pa-4" elevation="0">
                <div class="text-overline text-grey mb-3">Ringkasan</div>
                <div class="stat-item mb-3">
                  <div class="d-flex align-center justify-space-between">
                    <span class="text-body-2 text-grey">Total Pegawai</span>
                    <v-chip size="small" color="teal" variant="flat">{{ stats.total.toLocaleString('id-ID') }}</v-chip>
                  </div>
                </div>
                <div class="stat-item mb-3">
                  <div class="d-flex align-center justify-space-between">
                    <span class="text-body-2 text-grey">Gaji Pokok</span>
                    <span class="text-caption font-weight-bold text-teal">{{ formatCurrency(stats.total_gaji_pokok) }}</span>
                  </div>
                </div>
                <div class="stat-item mb-3">
                  <div class="d-flex align-center justify-space-between">
                    <span class="text-body-2 text-grey">Total Kotor</span>
                    <span class="text-caption font-weight-bold text-success">{{ formatCurrency(stats.total_kotor) }}</span>
                  </div>
                </div>
                <div class="stat-item">
                  <div class="d-flex align-center justify-space-between">
                    <span class="text-body-2 text-grey">Total Bersih</span>
                    <span class="text-caption font-weight-bold text-info">{{ formatCurrency(stats.total_bersih) }}</span>
                  </div>
                </div>
              </v-card>

              <!-- Filter Card -->
              <v-card class="glass-card rounded-xl pa-4" elevation="0">
                <div class="text-overline text-grey mb-3">Filter</div>
                <v-select
                  v-model="selectedPeriod"
                  label="Periode"
                  :items="periodOptions"
                  item-title="label"
                  item-value="value"
                  variant="underlined"
                  density="compact"
                  class="mb-3"
                  clearable
                  @update:model-value="fetchData"
                ></v-select>
                <v-select
                  v-model="selectedSkpd"
                  label="SKPD"
                  :items="skpdOptions"
                  item-title="skpd"
                  item-value="kdskpd"
                  variant="underlined"
                  density="compact"
                  clearable
                  @update:model-value="fetchData"
                ></v-select>
              </v-card>
            </div>
          </v-col>

          <!-- Main Table -->
          <v-col cols="12" md="9">
            <v-card class="glass-card rounded-xl overflow-hidden" elevation="0">
              <v-toolbar color="transparent" flat class="px-6 py-4">
                <v-toolbar-title class="font-weight-bold text-h6">Daftar Gaji PPPK</v-toolbar-title>
                <v-spacer></v-spacer>
                <v-text-field
                  v-model="search"
                  prepend-inner-icon="mdi-magnify"
                  label="Cari NIP atau Nama..."
                  variant="solo-filled"
                  density="comfortable"
                  rounded="pill"
                  hide-details
                  flat
                  style="max-width: 300px;"
                  class="mr-4"
                  @keyup.enter="fetchData"
                ></v-text-field>
              </v-toolbar>

              <v-data-table-server
                :headers="headers"
                :items="records"
                :items-length="totalRecords"
                :loading="loading"
                :items-per-page="itemsPerPage"
                :page="page"
                class="modern-table"
                hover
                @update:page="onPageChange"
                @update:items-per-page="onPerPageChange"
              >
                <!-- Name Column -->
                <template v-slot:item.nama="{ item }">
                  <div class="d-flex align-center py-2">
                    <v-avatar size="36" color="teal-lighten-5" class="mr-3">
                      <v-icon color="teal" size="small">mdi-account-check</v-icon>
                    </v-avatar>
                    <div>
                      <div class="font-weight-bold text-body-2">{{ item.nama }}</div>
                      <div class="text-caption text-grey">{{ item.nip }}</div>
                    </div>
                  </div>
                </template>

                <template v-slot:item.skpd="{ item }">
                  <div class="text-body-2 text-truncate" style="max-width: 200px;">{{ item.skpd || '-' }}</div>
                </template>

                <template v-slot:item.gaji_pokok="{ item }">
                  <span class="font-weight-medium">{{ formatCurrency(item.gaji_pokok) }}</span>
                </template>

                <template v-slot:item.kotor="{ item }">
                  <span class="font-weight-medium text-success">{{ formatCurrency(item.kotor) }}</span>
                </template>

                <template v-slot:item.bersih="{ item }">
                  <span class="font-weight-bold text-teal">{{ formatCurrency(item.bersih) }}</span>
                </template>

                <!-- Actions -->
                <template v-slot:item.actions="{ item }">
                  <v-btn icon size="small" variant="text" color="teal" @click.stop="showDetails(item)">
                    <v-icon size="18">mdi-eye</v-icon>
                  </v-btn>
                  <v-btn icon size="small" variant="text" color="warning" @click.stop="openEditDialog(item)">
                    <v-icon size="18">mdi-pencil</v-icon>
                  </v-btn>
                  <v-btn icon size="small" variant="text" color="error" @click.stop="confirmDelete(item)">
                    <v-icon size="18">mdi-delete</v-icon>
                  </v-btn>
                </template>

                <template v-slot:no-data>
                  <div class="text-center py-12">
                    <v-icon size="64" color="grey-lighten-3">mdi-database-off</v-icon>
                    <div class="text-grey mt-2">Tidak ada data gaji PPPK.</div>
                  </div>
                </template>

                <template v-slot:loading>
                  <v-skeleton-loader type="table-row-divider@10"></v-skeleton-loader>
                </template>
              </v-data-table-server>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>

    <!-- Create/Edit Dialog -->
    <v-dialog v-model="formDialog" max-width="1000px" scrollable persistent>
      <v-card class="rounded-xl">
        <v-toolbar color="teal" dark>
          <v-toolbar-title>{{ isEditing ? 'Edit Data Gaji' : 'Tambah Data Gaji Baru' }}</v-toolbar-title>
          <v-spacer></v-spacer>
          <v-btn icon @click="formDialog = false"><v-icon>mdi-close</v-icon></v-btn>
        </v-toolbar>

        <v-card-text class="pa-6">
          <v-form ref="formRef" v-model="formValid">
            <v-row dense>
              <!-- Periode -->
              <v-col cols="12"><div class="text-overline text-teal mb-2">Periode</div></v-col>
              <v-col cols="6" md="3">
                <v-select v-model.number="form.bulan" label="Bulan *" :items="bulanOptions" item-title="label" item-value="value" variant="outlined" density="compact" :rules="[rules.required]"></v-select>
              </v-col>
              <v-col cols="6" md="3">
                <v-text-field v-model.number="form.tahun" label="Tahun *" type="number" variant="outlined" density="compact" :rules="[rules.required]"></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.jenis_gaji" label="Jenis Gaji" variant="outlined" density="compact"></v-text-field>
              </v-col>

              <!-- Data Pegawai -->
              <v-col cols="12" class="mt-4"><div class="text-overline text-teal mb-2">Data Pegawai</div></v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="form.nip" label="NIP *" variant="outlined" density="compact" :rules="[rules.required]"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="form.nama" label="Nama *" variant="outlined" density="compact" :rules="[rules.required]"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="form.golongan" label="Golongan" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="form.jabatan" label="Jabatan" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="form.skpd" label="SKPD" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="form.kdskpd" label="Kode SKPD" variant="outlined" density="compact"></v-text-field>
              </v-col>

              <!-- Gaji & Tunjangan -->
              <v-col cols="12" class="mt-4"><div class="text-overline text-teal mb-2">Gaji & Tunjangan</div></v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model.number="form.gaji_pokok" label="Gaji Pokok" type="number" variant="outlined" density="compact" prefix="Rp"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model.number="form.tunj_istri" label="Tunj. Istri" type="number" variant="outlined" density="compact" prefix="Rp"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model.number="form.tunj_anak" label="Tunj. Anak" type="number" variant="outlined" density="compact" prefix="Rp"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model.number="form.tunj_fungsional" label="Tunj. Fungsional" type="number" variant="outlined" density="compact" prefix="Rp"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model.number="form.tunj_struktural" label="Tunj. Struktural" type="number" variant="outlined" density="compact" prefix="Rp"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model.number="form.tunj_umum" label="Tunj. Umum" type="number" variant="outlined" density="compact" prefix="Rp"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model.number="form.tunj_beras" label="Tunj. Beras" type="number" variant="outlined" density="compact" prefix="Rp"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model.number="form.tunj_pph" label="Tunj. PPH" type="number" variant="outlined" density="compact" prefix="Rp"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model.number="form.tunj_tpp" label="Tunj. TPP" type="number" variant="outlined" density="compact" prefix="Rp"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model.number="form.kotor" label="Penghasilan Kotor" type="number" variant="outlined" density="compact" prefix="Rp"></v-text-field>
              </v-col>

              <!-- Potongan -->
              <v-col cols="12" class="mt-4"><div class="text-overline text-teal mb-2">Potongan</div></v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model.number="form.pot_iwp" label="Pot. IWP" type="number" variant="outlined" density="compact" prefix="Rp"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model.number="form.pot_askes" label="Pot. Askes" type="number" variant="outlined" density="compact" prefix="Rp"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model.number="form.pot_pph" label="Pot. PPH" type="number" variant="outlined" density="compact" prefix="Rp"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model.number="form.pot_taperum" label="Pot. Taperum" type="number" variant="outlined" density="compact" prefix="Rp"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model.number="form.pot_jkk" label="Pot. JKK" type="number" variant="outlined" density="compact" prefix="Rp"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model.number="form.pot_jkm" label="Pot. JKM" type="number" variant="outlined" density="compact" prefix="Rp"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model.number="form.total_potongan" label="Total Potongan" type="number" variant="outlined" density="compact" prefix="Rp"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model.number="form.bersih" label="Penghasilan Bersih" type="number" variant="outlined" density="compact" prefix="Rp"></v-text-field>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>

        <v-card-actions class="pa-6 pt-0">
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="formDialog = false">BATAL</v-btn>
          <v-btn color="teal" variant="flat" @click="saveRecord" :loading="saving" :disabled="!formValid">
            {{ isEditing ? 'SIMPAN PERUBAHAN' : 'TAMBAH DATA' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Detail Dialog -->
    <v-dialog v-model="detailDialog" max-width="800px" scrollable>
      <v-card class="rounded-xl overflow-hidden glass-card">
        <v-toolbar color="teal" dark>
          <v-toolbar-title>Detail Gaji PPPK</v-toolbar-title>
          <v-spacer></v-spacer>
          <v-btn icon @click="detailDialog = false"><v-icon>mdi-close</v-icon></v-btn>
        </v-toolbar>

        <v-card-text class="pa-6" v-if="selectedRecord">
          <!-- Identity -->
          <div class="mb-4">
            <h3 class="text-h6 font-weight-bold">{{ selectedRecord.nama }}</h3>
            <div class="text-body-2 text-teal font-weight-bold">NIP: {{ selectedRecord.nip }}</div>
            <v-chip size="small" color="teal" variant="tonal" class="mt-1 mr-1">{{ selectedRecord.golongan || '-' }}</v-chip>
            <v-chip size="small" color="grey" variant="tonal" class="mt-1">{{ monthName(selectedRecord.bulan) }} {{ selectedRecord.tahun }}</v-chip>
          </div>

          <v-divider class="mb-4"></v-divider>

          <v-row dense>
            <v-col cols="6" class="mb-2">
              <div class="text-caption text-grey">SKPD</div>
              <div class="text-body-2 font-weight-medium">{{ selectedRecord.skpd || '-' }}</div>
            </v-col>
            <v-col cols="6" class="mb-2">
              <div class="text-caption text-grey">Jabatan</div>
              <div class="text-body-2 font-weight-medium">{{ selectedRecord.jabatan || '-' }}</div>
            </v-col>
          </v-row>

          <!-- Tunjangan -->
          <div class="text-overline text-teal mt-4 mb-2">Gaji & Tunjangan</div>
          <v-table density="compact" class="rounded-lg">
            <tbody>
              <tr v-for="item in tunjanganItems" :key="item.key">
                <td class="text-body-2">{{ item.label }}</td>
                <td class="text-right font-weight-medium">{{ formatCurrency(selectedRecord[item.key]) }}</td>
              </tr>
              <tr class="bg-green-lighten-5">
                <td class="text-body-2 font-weight-bold">Penghasilan Kotor</td>
                <td class="text-right font-weight-bold text-success">{{ formatCurrency(selectedRecord.kotor) }}</td>
              </tr>
            </tbody>
          </v-table>

          <!-- Potongan -->
          <div class="text-overline text-error mt-4 mb-2">Potongan</div>
          <v-table density="compact" class="rounded-lg">
            <tbody>
              <tr v-for="item in potonganItems" :key="item.key">
                <td class="text-body-2">{{ item.label }}</td>
                <td class="text-right font-weight-medium">{{ formatCurrency(selectedRecord[item.key]) }}</td>
              </tr>
              <tr class="bg-red-lighten-5">
                <td class="text-body-2 font-weight-bold">Total Potongan</td>
                <td class="text-right font-weight-bold text-error">{{ formatCurrency(selectedRecord.total_potongan) }}</td>
              </tr>
            </tbody>
          </v-table>

          <v-card class="mt-4 pa-4 bg-teal rounded-lg" flat>
            <div class="d-flex justify-space-between align-center">
              <span class="text-body-1 font-weight-bold text-white">PENGHASILAN BERSIH</span>
              <span class="text-h6 font-weight-black text-white">{{ formatCurrency(selectedRecord.bersih) }}</span>
            </div>
          </v-card>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Delete Confirmation -->
    <v-dialog v-model="deleteDialog" max-width="400px">
      <v-card class="rounded-xl">
        <v-card-title class="text-h6 pa-6">Konfirmasi Hapus</v-card-title>
        <v-card-text class="px-6">
          Apakah Anda yakin ingin menghapus data gaji <strong>{{ recordToDelete?.nama }}</strong>?
          <br>Tindakan ini tidak dapat dibatalkan.
        </v-card-text>
        <v-card-actions class="pa-6 pt-0">
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="deleteDialog = false">BATAL</v-btn>
          <v-btn color="error" variant="flat" @click="deleteRecord" :loading="deleting">HAPUS</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Snackbar -->
    <v-snackbar v-model="snackbar" :timeout="3000" :color="snackbarColor" rounded="lg">
      {{ snackbarMessage }}
    </v-snackbar>
  </v-app>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import api from '../api'
import Navbar from '../components/Navbar.vue'
import Sidebar from '../components/Sidebar.vue'

const loading = ref(true)
const search = ref('')
const records = ref([])
const totalRecords = ref(0)
const page = ref(1)
const itemsPerPage = ref(20)
const stats = ref({ total: 0, total_gaji_pokok: 0, total_kotor: 0, total_bersih: 0 })
const selectedPeriod = ref(null)
const selectedSkpd = ref(null)
const periodOptions = ref([])
const skpdOptions = ref([])

// Dialogs
const formDialog = ref(false)
const detailDialog = ref(false)
const deleteDialog = ref(false)
const selectedRecord = ref(null)
const recordToDelete = ref(null)
const isEditing = ref(false)
const formRef = ref(null)
const formValid = ref(false)
const saving = ref(false)
const deleting = ref(false)

// Snackbar
const snackbar = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref('success')

const bulanOptions = [
  { label: 'Januari', value: 1 }, { label: 'Februari', value: 2 }, { label: 'Maret', value: 3 },
  { label: 'April', value: 4 }, { label: 'Mei', value: 5 }, { label: 'Juni', value: 6 },
  { label: 'Juli', value: 7 }, { label: 'Agustus', value: 8 }, { label: 'September', value: 9 },
  { label: 'Oktober', value: 10 }, { label: 'November', value: 11 }, { label: 'Desember', value: 12 },
]

const monthName = (m) => bulanOptions.find(b => b.value === m)?.label || m

const defaultForm = {
  nip: '', nama: '', golongan: '', kdpangkat: '', jabatan: '', skpd: '', satker: '', kdskpd: '',
  kdjenkel: '', pendidikan: '', norek: '', npwp: '', noktp: '',
  gaji_pokok: null, tunj_istri: null, tunj_anak: null, tunj_fungsional: null,
  tunj_struktural: null, tunj_umum: null, tunj_beras: null, tunj_pph: null,
  tunj_tpp: null, tunj_eselon: null, tunj_guru: null, tunj_langka: null,
  tunj_tkd: null, tunj_terpencil: null, tunj_khusus: null, tunj_askes: null,
  tunj_kk: null, tunj_km: null, pembulatan: null, kotor: null,
  pot_iwp: null, pot_iwp1: null, pot_iwp8: null, pot_askes: null, pot_pph: null,
  pot_bulog: null, pot_taperum: null, pot_sewa: null, pot_hutang: null,
  pot_korpri: null, pot_irdhata: null, pot_koperasi: null, pot_jkk: null, pot_jkm: null,
  total_potongan: null, bersih: null, bulan: null, tahun: null, jenis_gaji: '',
}
const form = ref({ ...defaultForm })

const rules = { required: v => !!v || 'Field ini wajib diisi' }

const headers = [
  { title: 'PEGAWAI', key: 'nama', sortable: false },
  { title: 'GOLONGAN', key: 'golongan', sortable: false, width: '100px' },
  { title: 'SKPD', key: 'skpd', sortable: false },
  { title: 'GAJI POKOK', key: 'gaji_pokok', sortable: false, align: 'end' },
  { title: 'KOTOR', key: 'kotor', sortable: false, align: 'end' },
  { title: 'BERSIH', key: 'bersih', sortable: false, align: 'end' },
  { title: 'AKSI', key: 'actions', sortable: false, width: '140px', align: 'center' },
]

const tunjanganItems = [
  { label: 'Gaji Pokok', key: 'gaji_pokok' },
  { label: 'Tunj. Istri', key: 'tunj_istri' },
  { label: 'Tunj. Anak', key: 'tunj_anak' },
  { label: 'Tunj. Fungsional', key: 'tunj_fungsional' },
  { label: 'Tunj. Struktural', key: 'tunj_struktural' },
  { label: 'Tunj. Umum', key: 'tunj_umum' },
  { label: 'Tunj. Beras', key: 'tunj_beras' },
  { label: 'Tunj. PPH', key: 'tunj_pph' },
  { label: 'Tunj. TPP', key: 'tunj_tpp' },
]

const potonganItems = [
  { label: 'Pot. IWP', key: 'pot_iwp' },
  { label: 'Pot. Askes', key: 'pot_askes' },
  { label: 'Pot. PPH', key: 'pot_pph' },
  { label: 'Pot. Taperum', key: 'pot_taperum' },
  { label: 'Pot. JKK', key: 'pot_jkk' },
  { label: 'Pot. JKM', key: 'pot_jkm' },
]

const formatCurrency = (value) => {
  if (!value) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value)
}

const fetchData = async () => {
  loading.value = true
  try {
    const params = {
      search: search.value || undefined,
      per_page: itemsPerPage.value,
      page: page.value,
      kdskpd: selectedSkpd.value || undefined,
    }
    if (selectedPeriod.value) {
      const [b, t] = selectedPeriod.value.split('-')
      params.bulan = b
      params.tahun = t
    }
    const response = await api.get('/gaji-pppk', { params })
    if (response.data.success) {
      records.value = response.data.data.data || []
      totalRecords.value = response.data.data.total || 0
      stats.value = response.data.stats || stats.value
      if (response.data.periods) {
        periodOptions.value = response.data.periods.map(p => ({
          label: `${monthName(p.bulan)} ${p.tahun}`,
          value: `${p.bulan}-${p.tahun}`,
        }))
      }
      if (response.data.skpds) {
        skpdOptions.value = response.data.skpds
      }
    }
  } catch (err) {
    console.error('Error:', err)
    showSnackbar('Gagal memuat data', 'error')
  } finally {
    loading.value = false
  }
}

const onPageChange = (newPage) => { page.value = newPage; fetchData() }
const onPerPageChange = (newPerPage) => { itemsPerPage.value = newPerPage; page.value = 1; fetchData() }

const showDetails = (item) => { selectedRecord.value = item; detailDialog.value = true }

const openCreateDialog = () => {
  isEditing.value = false
  form.value = { ...defaultForm }
  formDialog.value = true
}

const openEditDialog = (item) => {
  isEditing.value = true
  form.value = { ...defaultForm, ...item }
  formDialog.value = true
}

const saveRecord = async () => {
  saving.value = true
  try {
    const payload = { ...form.value }
    Object.keys(payload).forEach(key => {
      if (payload[key] === '' || payload[key] === null) delete payload[key]
    })
    let response
    if (isEditing.value) {
      response = await api.put(`/gaji-pppk/${form.value.id}`, payload)
    } else {
      response = await api.post('/gaji-pppk', payload)
    }
    if (response.data.success) {
      showSnackbar(response.data.message, 'success')
      formDialog.value = false
      fetchData()
    }
  } catch (err) {
    console.error('Error saving:', err)
    showSnackbar(err.response?.data?.message || 'Gagal menyimpan data', 'error')
  } finally {
    saving.value = false
  }
}

const confirmDelete = (item) => { recordToDelete.value = item; deleteDialog.value = true }

const deleteRecord = async () => {
  deleting.value = true
  try {
    const response = await api.delete(`/gaji-pppk/${recordToDelete.value.id}`)
    if (response.data.success) {
      showSnackbar('Data berhasil dihapus', 'success')
      deleteDialog.value = false
      fetchData()
    }
  } catch (err) {
    console.error('Error deleting:', err)
    showSnackbar('Gagal menghapus data', 'error')
  } finally {
    deleting.value = false
  }
}

const showSnackbar = (message, color = 'success') => {
  snackbarMessage.value = message
  snackbarColor.value = color
  snackbar.value = true
}

onMounted(() => { fetchData() })

let searchTimeout = null
watch(search, () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => { page.value = 1; fetchData() }, 500)
})
</script>

<style scoped>
.modern-bg { background-color: rgb(var(--v-theme-background)) !important; }
.glass-card { background-color: rgb(var(--v-theme-surface)) !important; border: 1px solid rgba(var(--v-border-color), 0.08) !important; }
.sticky-top { position: sticky; top: 96px; }
.stat-item { background: rgba(var(--v-border-color), 0.04); padding: 12px; border-radius: 12px; transition: all 0.2s; }
.stat-item:hover { background: rgb(var(--v-theme-surface)); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); }
:deep(.modern-table) { background: transparent !important; }
:deep(.v-data-table-header) { background: rgba(var(--v-border-color), 0.05) !important; }
:deep(.v-data-table-header th) { font-weight: 700 !important; color: rgb(var(--v-theme-on-surface)) !important; opacity: 0.7; text-transform: uppercase; font-size: 0.7rem !important; letter-spacing: 0.05em; }
:deep(.v-data-table__tr:hover) { background-color: rgba(var(--v-theme-primary), 0.02) !important; }
</style>
