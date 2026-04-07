<template>
  <div class="modern-dashboard">
    <Navbar />
    <Sidebar />
    <v-main class="bg-light min-vh-100">
      <v-container fluid class="pa-8">
        <div class="pa-4">
    <v-row class="mb-4 d-flex align-center">
      <v-col cols="12" md="6">
        <h2 class="text-h5 font-weight-bold">Input Anggaran Belanja</h2>
        <div class="text-subtitle-2 text-medium-emphasis">
          Kelola data pangu anggaran per SKPD dan kategorinya.
        </div>
      </v-col>
      <v-col cols="12" md="6" class="text-right">
        <v-btn color="primary" prepend-icon="mdi-plus" @click="openDialog()">
          Tambah Anggaran
        </v-btn>
      </v-col>
    </v-row>

    <!-- Filter Bar -->
    <v-card class="mb-4 rounded-xl" elevation="0" border>
      <v-card-text>
        <v-row align="center">
          <v-col cols="12" md="3">
            <v-select
              v-model="filterTahun"
              :items="tahunOptions"
              label="Tahun"
              variant="outlined"
              density="compact"
              hide-details
            ></v-select>
          </v-col>
          <v-col cols="12" md="5">
            <v-autocomplete
              v-model="filterSkpd"
              :items="skpdList"
              item-title="nama_skpd"
              item-value="id_skpd"
              label="Cari SKPD"
              variant="outlined"
              density="compact"
              hide-details
              clearable
            ></v-autocomplete>
          </v-col>
          <v-col cols="12" md="4">
            <v-btn color="primary" variant="tonal" class="mr-2" @click="fetchBudgets">
              Terapkan
            </v-btn>
            <v-btn color="secondary" variant="text" @click="resetFilter">
              Reset
            </v-btn>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Data Table -->
    <v-card class="rounded-xl border border-opacity-10" elevation="0">
      <v-data-table
        :headers="headers"
        :items="budgets"
        :loading="loading"
        class="elevation-0 bg-transparent"
        hover
      >
        <template v-slot:item.nominal="{ item }">
          <div class="font-weight-medium">Rp {{ formatCurrency(item.nominal) }}</div>
        </template>
        <template v-slot:item.jenis_anggaran="{ item }">
          <v-chip size="small" :color="getCategoryColor(item.jenis_anggaran)" variant="flat" class="font-weight-bold">
            {{ item.jenis_anggaran }}
          </v-chip>
        </template>
        <template v-slot:item.tipe_anggaran="{ item }">
          <v-chip size="small" :color="item.tipe_anggaran === 'MURNI' ? 'success' : 'warning'" variant="tonal">
            {{ item.tipe_anggaran.replace('_', ' ') }}
          </v-chip>
        </template>
        <template v-slot:item.actions="{ item }">
          <v-btn icon="mdi-pencil" size="small" color="primary" variant="text" @click="openDialog(item)"></v-btn>
          <v-btn icon="mdi-delete" size="small" color="error" variant="text" @click="confirmDelete(item)"></v-btn>
        </template>
      </v-data-table>
    </v-card>

    <!-- Form Dialog -->
    <v-dialog v-model="dialog" max-width="600px" persistent>
      <v-card class="rounded-xl">
        <v-card-title class="pa-4 bg-primary text-white d-flex align-center">
          <v-icon icon="mdi-file-document-edit-outline" class="mr-2"></v-icon>
          <span>{{ form.id ? 'Edit' : 'Tambah' }} Anggaran</span>
        </v-card-title>
        <v-card-text class="pa-6">
          <v-form ref="formRef" v-model="valid">
            <v-autocomplete
              v-model="form.skpd_id"
              :items="skpdList"
              item-title="nama_skpd"
              item-value="id_skpd"
              label="Pilih SKPD"
              variant="outlined"
              :rules="[v => !!v || 'SKPD harus diisi']"
              :disabled="!!form.id"
              class="mb-3"
            ></v-autocomplete>
            
            <v-row>
              <v-col cols="6">
                <v-select
                  v-model="form.tahun"
                  :items="tahunOptions"
                  label="Tahun"
                  variant="outlined"
                  :rules="[v => !!v || 'Tahun harus diisi']"
                  :disabled="!!form.id"
                ></v-select>
              </v-col>
              <v-col cols="6">
                <v-select
                  v-model="form.jenis_anggaran"
                  :items="['PNS', 'PPPK', 'TPP', 'PPPK_PW', 'LAINNYA']"
                  label="Kategori / Jenis"
                  variant="outlined"
                  :rules="[v => !!v || 'Jenis harus diisi']"
                ></v-select>
              </v-col>
            </v-row>
            
            <v-row>
              <v-col cols="6">
                <v-select
                  v-model="form.tipe_anggaran"
                  :items="['MURNI', 'PERUBAHAN_1', 'PERUBAHAN_2', 'PERUBAHAN_3', 'PERUBAHAN_4']"
                  label="Tipe Anggaran"
                  variant="outlined"
                  :rules="[v => !!v || 'Tipe harus diisi']"
                ></v-select>
              </v-col>
              <v-col cols="6">
                <v-text-field
                  v-model="form.nominal"
                  label="Nilai Anggaran (Rp)"
                  variant="outlined"
                  type="number"
                  :rules="[v => !!v || 'Nominal harus diisi']"
                ></v-text-field>
              </v-col>
            </v-row>
            
            <v-textarea
              v-model="form.keterangan"
              label="Keterangan (Opsional)"
              variant="outlined"
              rows="2"
            ></v-textarea>
          </v-form>
        </v-card-text>
        <v-divider></v-divider>
        <v-card-actions class="pa-4 bg-grey-lighten-4">
          <v-spacer></v-spacer>
          <v-btn color="grey-darken-1" variant="text" @click="closeDialog" :disabled="saving">Batal</v-btn>
          <v-btn color="primary" variant="flat" @click="saveChanges" :loading="saving">Simpan</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" location="top">
      {{ snackbar.text }}
      <template v-slot:actions>
        <v-btn icon="mdi-close" variant="text" @click="snackbar.show = false"></v-btn>
      </template>
        </v-snackbar>
      </div>
    </v-container>
    </v-main>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '../../api'
import Sidebar from '../../components/Sidebar.vue'
import Navbar from '../../components/Navbar.vue'

const loading = ref(false)
const saving = ref(false)
const valid = ref(false)
const formRef = ref(null)

const budgets = ref([])
const skpdList = ref([])

const currentYear = new Date().getFullYear()
const tahunOptions = Array.from({length: 5}, (_, i) => currentYear - 2 + i)

const filterTahun = ref(currentYear)
const filterSkpd = ref(null)

const dialog = ref(false)
const form = ref({
  id: null,
  skpd_id: null,
  tahun: currentYear,
  jenis_anggaran: 'PNS',
  tipe_anggaran: 'MURNI',
  nominal: null,
  keterangan: ''
})

const snackbar = ref({
  show: false,
  text: '',
  color: 'success'
})

const headers = [
  { title: 'SKPD', key: 'skpd.nama_skpd' },
  { title: 'Tahun', key: 'tahun' },
  { title: 'Kategori', key: 'jenis_anggaran' },
  { title: 'Tipe', key: 'tipe_anggaran' },
  { title: 'Nominal', key: 'nominal', align: 'end' },
  { title: 'Aksi', key: 'actions', sortable: false, align: 'end' }
]

const formatCurrency = (val) => {
  if (!val) return '0'
  return parseFloat(val).toLocaleString('id-ID')
}

const getCategoryColor = (cat) => {
  const map = {
    'PNS': 'blue-darken-1',
    'PPPK': 'teal-darken-1',
    'TPP': 'deep-purple-darken-1',
    'PPPK_PW': 'orange-darken-2'
  }
  return map[cat] || 'grey'
}

const fetchBudgets = async () => {
  loading.value = true
  try {
    const res = await api.get('/budgets', {
      params: {
        tahun: filterTahun.value,
        skpd_id: filterSkpd.value
      }
    })
    budgets.value = res.data.data
  } catch (err) {
    showSnackbar('Gagal mengambil data anggaran', 'error')
  } finally {
    loading.value = false
  }
}

const fetchSkpds = async () => {
  try {
    const response = await api.get('/skpd')
    skpdList.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching SKPDs:', error)
  }
}

const resetFilter = () => {
  filterTahun.value = currentYear
  filterSkpd.value = null
  fetchBudgets()
}

const openDialog = (item = null) => {
  if (item) {
    form.value = { ...item }
  } else {
    form.value = {
      id: null,
      skpd_id: filterSkpd.value || null,
      tahun: filterTahun.value,
      jenis_anggaran: 'PNS',
      tipe_anggaran: 'MURNI',
      nominal: null,
      keterangan: ''
    }
  }
  dialog.value = true
}

const closeDialog = () => {
  dialog.value = false
  if (formRef.value) formRef.value.resetValidation()
}

const saveChanges = async () => {
  const { valid } = await formRef.value.validate()
  if (!valid) return

  saving.value = true
  try {
    if (form.value.id) {
      await api.put(`/budgets/${form.value.id}`, form.value)
      showSnackbar('Anggaran berhasil diperbarui')
    } else {
      await api.post('/budgets', form.value)
      showSnackbar('Anggaran berhasil ditambahkan')
    }
    closeDialog()
    fetchBudgets()
  } catch (err) {
    const msg = err.response?.data?.message || 'Terjadi kesalahan saat menyimpan'
    showSnackbar(msg, 'error')
  } finally {
    saving.value = false
  }
}

const confirmDelete = async (item) => {
  if (confirm(`Yakin ingin menghapus data anggaran ${item.tipe_anggaran} ${item.jenis_anggaran} ini?`)) {
    try {
      await api.delete(`/budgets/${item.id}`)
      showSnackbar('Data berhasil dihapus')
      fetchBudgets()
    } catch (err) {
      showSnackbar('Gagal menghapus data', 'error')
    }
  }
}

const showSnackbar = (text, color = 'success') => {
  snackbar.value = { show: true, text, color }
}

onMounted(() => {
  fetchSkpds()
  fetchBudgets()
})
</script>
