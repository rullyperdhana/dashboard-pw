<template>
  <div class="modern-dashboard">
    <Navbar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />
    <Sidebar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />

    <v-main class="bg-light">
      <v-container fluid class="pa-8">
        <!-- Page Header -->
        <div class="d-flex align-center mb-6">
          <div>
            <h1 class="text-h4 font-weight-bold text-grey-darken-2">Mapping Jabatan PPPK-PW</h1>
            <p class="text-body-2 text-medium-emphasis mt-1">
              Pemetaan Jabatan ke Kode Rekening (Anggaran) untuk pelaporan PPPK Paruh Waktu.
            </p>
          </div>
          <v-spacer></v-spacer>
          <v-btn variant="tonal" color="info" rounded="pill" @click="loadMappings" :loading="loading" class="mr-3">
            <v-icon start icon="mdi-refresh"></v-icon>
            Refresh
          </v-btn>
          <v-btn color="primary" rounded="pill" prepend-icon="mdi-plus" @click="openAddDialog">
            Tambah Mapping
          </v-btn>
        </div>

        <!-- Mappings Table -->
        <v-card class="glass-card rounded-xl" elevation="0">
          <v-card-title class="bg-primary text-white py-4 px-6">
            <v-icon start icon="mdi-briefcase-account-outline" class="mr-2"></v-icon>
            Daftar Mapping Jabatan
          </v-card-title>

          <v-card-text class="pa-6">
            <v-data-table
              :headers="tableHeaders"
              :items="mappings"
              :loading="loading"
              items-per-page="15"
              class="elevation-0 border rounded-lg"
              density="comfortable"
              hover
            >
              <template v-slot:item.keyword="{ item }">
                <v-chip size="small" variant="tonal" color="primary" class="font-weight-medium">
                  {{ item.keyword }}
                </v-chip>
              </template>
              
              <template v-slot:item.kode_rekening="{ item }">
                <span class="font-weight-bold text-blue-darken-2">{{ item.kode_rekening }}</span>
              </template>

              <template v-slot:item.actions="{ item }">
                <div class="d-flex ga-1">
                  <v-btn icon size="small" variant="text" color="primary" @click="openEditDialog(item)">
                    <v-icon icon="mdi-pencil-outline" size="18"></v-icon>
                    <v-tooltip activator="parent">Edit</v-tooltip>
                  </v-btn>
                  <v-btn icon size="small" variant="text" color="error" @click="confirmDelete(item)">
                    <v-icon icon="mdi-delete-outline" size="18"></v-icon>
                    <v-tooltip activator="parent">Hapus</v-tooltip>
                  </v-btn>
                </div>
              </template>

              <template v-slot:no-data>
                <div class="py-8 text-center text-medium-emphasis">
                  <v-icon icon="mdi-database-off-outline" size="48" class="mb-3"></v-icon>
                  <div>Belum ada mapping jabatan. Klik Tambah Mapping untuk mulai.</div>
                </div>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-container>
    </v-main>

    <!-- Add / Edit Dialog -->
    <v-dialog v-model="dialog" max-width="500" persistent>
      <v-card class="rounded-xl" elevation="8">
        <v-card-title class="bg-primary text-white pa-5">
          <v-icon :icon="editMode ? 'mdi-pencil-outline' : 'mdi-plus'" class="mr-2"></v-icon>
          {{ editMode ? 'Edit Mapping' : 'Tambah Mapping Baru' }}
        </v-card-title>
        <v-card-text class="pa-6">
          <v-text-field
            v-model="form.nama_kelompok"
            label="Nama Kelompok / Kategori"
            placeholder="Contoh: Guru, Teknis, dsb"
            variant="outlined"
            density="comfortable"
            class="mb-4"
            hide-details="auto"
            required
          ></v-text-field>

          <v-text-field
            v-model="form.kode_rekening"
            label="Kode Rekening (Anggaran)"
            placeholder="Contoh: 5.1.02.03.01.0083"
            variant="outlined"
            density="comfortable"
            class="mb-4"
            hide-details="auto"
            required
          ></v-text-field>

          <v-text-field
            v-model="form.keyword"
            label="Keyword Jabatan (Matching)"
            placeholder="Kata kunci yang ada di kolom jabatan"
            variant="outlined"
            density="comfortable"
            class="mb-4"
            hide-details="auto"
            persistent-hint
            hint="Sistem akan mencari jabatan yang mengandung kata ini."
            required
          ></v-text-field>

          <v-text-field
            v-model="form.order_weight"
            label="Prioritas (Order Weight)"
            type="number"
            variant="outlined"
            density="comfortable"
            class="mb-4"
            hide-details="auto"
            persistent-hint
            hint="Semakin tinggi semakin diprioritaskan jika ada keyword yang mirip."
          ></v-text-field>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="dialog = false" :disabled="saving">Batal</v-btn>
          <v-btn color="primary" variant="flat" :loading="saving" @click="saveMapping">
            <v-icon start icon="mdi-content-save"></v-icon>
            Simpan
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Delete Confirm Dialog -->
    <v-dialog v-model="deleteDialog" max-width="400">
      <v-card class="rounded-xl" elevation="8">
        <v-card-title class="pa-5 d-flex align-center">
          <v-icon icon="mdi-alert-circle-outline" color="error" class="mr-2"></v-icon>
          Hapus Mapping
        </v-card-title>
        <v-card-text class="px-5">
          Yakin ingin menghapus mapping untuk kelompok <strong>{{ deletingItem?.nama_kelompok }}</strong>?
        </v-card-text>
        <v-card-actions class="pa-5 pt-2">
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="deleteDialog = false">Batal</v-btn>
          <v-btn color="error" variant="flat" :loading="deleting" @click="deleteMapping">Hapus</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Snackbar -->
    <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000" rounded="lg">
      {{ snackbar.message }}
      <template v-slot:actions>
        <v-btn variant="text" @click="snackbar.show = false">Tutup</v-btn>
      </template>
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'
import Sidebar from '@/components/Sidebar.vue'
import Navbar from '@/components/Navbar.vue'

const loading = ref(false)
const saving = ref(false)
const deleting = ref(false)
const dialog = ref(false)
const deleteDialog = ref(false)
const editMode = ref(false)
const mappings = ref([])
const deletingItem = ref(null)

const form = ref({
  id: null,
  nama_kelompok: '',
  kode_rekening: '',
  keyword: '',
  order_weight: 0
})

const snackbar = ref({ show: false, message: '', color: 'success' })

const tableHeaders = [
  { title: 'Nama Kelompok', key: 'nama_kelompok', align: 'start' },
  { title: 'Kode Rekening', key: 'kode_rekening', align: 'start' },
  { title: 'Keyword Match', key: 'keyword', align: 'start' },
  { title: 'Bobot', key: 'order_weight', align: 'center', width: '100px' },
  { title: 'Aksi', key: 'actions', sortable: false, align: 'center', width: '120px' },
]

const loadMappings = async () => {
  loading.value = true
  try {
    const res = await api.get('/pppk-pw-jabatan-mapping')
    mappings.value = res.data.data
  } catch (e) {
    notify('Gagal memuat data mapping', 'error')
  } finally {
    loading.value = false
  }
}

const openAddDialog = () => {
  form.value = { id: null, nama_kelompok: '', kode_rekening: '', keyword: '', order_weight: 0 }
  editMode.value = false
  dialog.value = true
}

const openEditDialog = (item) => {
  form.value = { ...item }
  editMode.value = true
  dialog.value = true
}

const saveMapping = async () => {
  if (!form.value.nama_kelompok || !form.value.kode_rekening || !form.value.keyword) {
    notify('Mohon lengkapi semua field yang wajib diisi', 'warning')
    return
  }

  saving.value = true
  try {
    if (editMode.value) {
      await api.put(`/pppk-pw-jabatan-mapping/${form.value.id}`, form.value)
      notify('Mapping berhasil diperbarui')
    } else {
      await api.post('/pppk-pw-jabatan-mapping', form.value)
      notify('Mapping berhasil ditambahkan')
    }
    dialog.value = false
    loadMappings()
  } catch (e) {
    notify('Gagal menyimpan mapping', 'error')
  } finally {
    saving.value = false
  }
}

const confirmDelete = (item) => {
  deletingItem.value = item
  deleteDialog.value = true
}

const deleteMapping = async () => {
  deleting.value = true
  try {
    await api.delete(`/pppk-pw-jabatan-mapping/${deletingItem.value.id}`)
    notify('Mapping berhasil dihapus')
    deleteDialog.value = false
    loadMappings()
  } catch (e) {
    notify('Gagal menghapus mapping', 'error')
  } finally {
    deleting.value = false
  }
}

const notify = (message, color = 'success') => {
  snackbar.value = { show: true, message, color }
}

onMounted(() => {
  loadMappings()
})
</script>

<style scoped>
.modern-dashboard { background-color: rgb(var(--v-theme-background)) !important; }
.bg-light         { background-color: rgb(var(--v-theme-background)) !important; }
.glass-card {
  background-color: rgb(var(--v-theme-surface)) !important;
  border: 1px solid rgba(var(--v-border-color), 0.08) !important;
  box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07) !important;
}
</style>
