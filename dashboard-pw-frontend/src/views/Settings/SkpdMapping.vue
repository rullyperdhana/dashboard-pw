<template>
  <v-app class="modern-dashboard">
    <Navbar @show-coming-soon="showComingSoon" />
    <Sidebar @show-coming-soon="showComingSoon" />

    <v-main class="bg-light">
      <v-container fluid class="pa-8">
        <!-- Page Header -->
        <div class="d-flex align-center mb-6">
          <div>
            <h1 class="text-h4 font-weight-bold text-grey-darken-2">SKPD Mapping</h1>
            <p class="text-body-2 text-medium-emphasis mt-1">
              Pemetaan nama SKPD dari data upload Excel ke master SKPD
            </p>
          </div>
          <v-spacer></v-spacer>
          <v-btn variant="tonal" color="info" rounded="pill" @click="loadAll" :loading="loading" class="mr-3">
            <v-icon start icon="mdi-refresh"></v-icon>
            Refresh
          </v-btn>
          <v-btn color="primary" rounded="pill" prepend-icon="mdi-plus" @click="openAddDialog">
            Tambah Mapping
          </v-btn>
        </div>

        <!-- Summary Chips -->
        <v-row class="mb-6">
          <v-col cols="12" md="4">
            <v-card class="glass-card rounded-xl pa-5" elevation="0">
              <div class="d-flex align-center">
                <v-avatar color="primary" variant="tonal" size="48" class="mr-4">
                  <v-icon icon="mdi-swap-horizontal" size="24"></v-icon>
                </v-avatar>
                <div>
                  <div class="text-h5 font-weight-bold">{{ mappings.length }}</div>
                  <div class="text-caption text-medium-emphasis">Total Mapping</div>
                </div>
              </div>
            </v-card>
          </v-col>
          <v-col cols="12" md="4">
            <v-card class="glass-card rounded-xl pa-5" elevation="0">
              <div class="d-flex align-center">
                <v-avatar color="warning" variant="tonal" size="48" class="mr-4">
                  <v-icon icon="mdi-alert-circle-outline" size="24"></v-icon>
                </v-avatar>
                <div>
                  <div class="text-h5 font-weight-bold">{{ unmappedCount }}</div>
                  <div class="text-caption text-medium-emphasis">Belum Dipetakan</div>
                </div>
              </div>
            </v-card>
          </v-col>
          <v-col cols="12" md="4">
            <v-card class="glass-card rounded-xl pa-5" elevation="0">
              <div class="d-flex align-center">
                <v-avatar color="success" variant="tonal" size="48" class="mr-4">
                  <v-icon icon="mdi-check-all" size="24"></v-icon>
                </v-avatar>
                <div>
                  <div class="text-h5 font-weight-bold">{{ skpdList.length }}</div>
                  <div class="text-caption text-medium-emphasis">SKPD Master</div>
                </div>
              </div>
            </v-card>
          </v-col>
        </v-row>

        <!-- Unmapped Alert Panel -->
        <v-expand-transition>
          <v-card v-if="unmappedCount > 0" class="glass-card rounded-xl mb-6" elevation="0">
            <v-card-title class="bg-warning text-white py-3 px-6 d-flex align-center">
              <v-icon start icon="mdi-alert-outline" class="mr-2"></v-icon>
              Nama SKPD Belum Dipetakan ({{ unmappedCount }})
              <v-spacer></v-spacer>
              <v-btn size="small" variant="tonal" color="white" @click="showUnmapped = !showUnmapped">
                {{ showUnmapped ? 'Sembunyikan' : 'Tampilkan' }}
              </v-btn>
            </v-card-title>

            <v-expand-transition>
              <v-card-text v-show="showUnmapped" class="pa-6">
                <v-tabs v-model="unmappedTab" color="warning">
                  <v-tab value="pns">
                    PNS
                    <v-chip size="x-small" color="warning" class="ml-2">{{ unmappedPns.length }}</v-chip>
                  </v-tab>
                  <v-tab value="pppk">
                    PPPK Penuh Waktu
                    <v-chip size="x-small" color="warning" class="ml-2">{{ unmappedPppk.length }}</v-chip>
                  </v-tab>
                </v-tabs>

                <v-window v-model="unmappedTab" class="mt-4">
                  <!-- PNS Unmapped -->
                  <v-window-item value="pns">
                    <div v-if="unmappedPns.length === 0" class="text-center pa-4 text-medium-emphasis">
                      <v-icon icon="mdi-check-circle-outline" color="success" size="32" class="mb-2"></v-icon>
                      <div>Semua SKPD PNS sudah dipetakan!</div>
                    </div>
                    <v-list v-else density="compact" class="rounded-lg">
                      <v-list-item
                        v-for="item in unmappedPns"
                        :key="item.source_name"
                        class="mb-1 border rounded-lg"
                      >
                        <template v-slot:prepend>
                          <v-icon icon="mdi-office-building-remove-outline" color="warning" size="20"></v-icon>
                        </template>
                        <v-list-item-title class="text-body-2 font-weight-medium">{{ item.source_name }}</v-list-item-title>
                        <template v-slot:append>
                          <v-btn size="small" color="primary" variant="tonal" @click="quickMap(item)">
                            <v-icon start icon="mdi-link-variant" size="16"></v-icon>
                            Petakan
                          </v-btn>
                        </template>
                      </v-list-item>
                    </v-list>
                  </v-window-item>

                  <!-- PPPK Unmapped -->
                  <v-window-item value="pppk">
                    <div v-if="unmappedPppk.length === 0" class="text-center pa-4 text-medium-emphasis">
                      <v-icon icon="mdi-check-circle-outline" color="success" size="32" class="mb-2"></v-icon>
                      <div>Semua SKPD PPPK Penuh Waktu sudah dipetakan!</div>
                    </div>
                    <v-list v-else density="compact" class="rounded-lg">
                      <v-list-item
                        v-for="item in unmappedPppk"
                        :key="item.source_name"
                        class="mb-1 border rounded-lg"
                      >
                        <template v-slot:prepend>
                          <v-icon icon="mdi-office-building-remove-outline" color="warning" size="20"></v-icon>
                        </template>
                        <v-list-item-title class="text-body-2 font-weight-medium">{{ item.source_name }}</v-list-item-title>
                        <template v-slot:append>
                          <v-btn size="small" color="primary" variant="tonal" @click="quickMap(item)">
                            <v-icon start icon="mdi-link-variant" size="16"></v-icon>
                            Petakan
                          </v-btn>
                        </template>
                      </v-list-item>
                    </v-list>
                  </v-window-item>
                </v-window>
              </v-card-text>
            </v-expand-transition>
          </v-card>
        </v-expand-transition>

        <!-- Existing Mappings Table -->
        <v-card class="glass-card rounded-xl" elevation="0">
          <v-card-title class="bg-primary text-white py-4 px-6">
            <v-icon start icon="mdi-table-check" class="mr-2"></v-icon>
            Daftar Mapping SKPD
          </v-card-title>

          <v-card-text class="pa-6">
            <v-row class="mb-4">
              <v-col cols="12" md="4">
                <v-text-field
                  v-model="search"
                  prepend-inner-icon="mdi-magnify"
                  label="Cari nama SKPD..."
                  variant="outlined"
                  density="compact"
                  hide-details
                  clearable
                ></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-select
                  v-model="filterType"
                  :items="[{ title: 'Semua Tipe', value: '' }, { title: 'PNS', value: 'pns' }, { title: 'PPPK', value: 'pppk' }, { title: 'All', value: 'all' }]"
                  item-title="title"
                  item-value="value"
                  label="Filter Tipe"
                  variant="outlined"
                  density="compact"
                  hide-details
                ></v-select>
              </v-col>
            </v-row>

            <v-data-table
              :headers="tableHeaders"
              :items="filteredMappings"
              :search="search"
              items-per-page="15"
              class="elevation-0 border rounded-lg"
              density="comfortable"
              hover
            >
              <template v-slot:item.type="{ item }">
                <v-chip
                  :color="typeColor(item.type)"
                  size="small"
                  label
                  class="font-weight-medium"
                >
                  {{ item.type.toUpperCase() }}
                </v-chip>
              </template>
              <template v-slot:item.nama_skpd="{ item }">
                <div class="d-flex align-center">
                  <v-icon icon="mdi-arrow-right" color="primary" size="16" class="mr-2"></v-icon>
                  <span class="font-weight-medium text-primary">{{ item.nama_skpd ?? '—' }}</span>
                  <v-chip v-if="item.kode_skpd" size="x-small" variant="tonal" color="grey" class="ml-2">
                    {{ item.kode_skpd }}
                  </v-chip>
                </div>
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
                  <v-icon icon="mdi-swap-horizontal-off" size="48" class="mb-3"></v-icon>
                  <div>Belum ada mapping. Klik Tambah Mapping untuk mulai.</div>
                </div>
              </template>
            </v-data-table>
          </v-card-text>
        </v-card>
      </v-container>
    </v-main>

    <!-- Add / Edit Dialog -->
    <v-dialog v-model="dialog" max-width="540" persistent>
      <v-card class="rounded-xl" elevation="8">
        <v-card-title class="bg-primary text-white pa-5">
          <v-icon :icon="editMode ? 'mdi-pencil-outline' : 'mdi-plus'" class="mr-2"></v-icon>
          {{ editMode ? 'Edit Mapping' : 'Tambah Mapping Baru' }}
        </v-card-title>
        <v-card-text class="pa-6">
          <v-alert type="info" variant="tonal" class="mb-5 rounded-lg" density="compact">
            Petakan nama SKPD dari file Excel ke SKPD master yang sesuai.
          </v-alert>

          <v-text-field
            v-model="form.source_name"
            label="Nama SKPD dari Excel"
            variant="outlined"
            placeholder="Contoh: DINAS PENDIDIKAN DAN KEBUDAYAAN PROVINSI KALSEL"
            :hint="editMode ? 'Nama ini sesuai dengan kolom SKPD di file Excel' : ''"
            persistent-hint
            :readonly="editMode && !editSourceName"
            class="mb-4"
          ></v-text-field>

          <v-autocomplete
            v-model="form.skpd_id"
            :items="skpdList"
            item-title="label"
            item-value="id_skpd"
            label="SKPD Master (Tujuan Mapping)"
            variant="outlined"
            :loading="loadingSkpd"
            class="mb-4"
            clearable
            auto-select-first
            placeholder="Ketik untuk mencari SKPD..."
            no-data-text="SKPD tidak ditemukan"
          ></v-autocomplete>

          <v-select
            v-model="form.type"
            :items="typeOptions"
            item-title="title"
            item-value="value"
            label="Berlaku untuk Tipe Data"
            variant="outlined"
          ></v-select>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="closeDialog" :disabled="saving">Batal</v-btn>
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
          Yakin ingin menghapus mapping <strong>{{ deletingItem?.source_name }}</strong>?
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

    <!-- Coming Soon Snackbar -->
    <v-snackbar v-model="comingSoon" timeout="2000" color="primary" rounded="lg">
      <v-icon start icon="mdi-information-outline"></v-icon>
      Fitur {{ comingSoonTitle }} akan segera hadir.
    </v-snackbar>
  </v-app>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api'
import Sidebar from '@/components/Sidebar.vue'
import Navbar from '@/components/Navbar.vue'

// State
const loading = ref(false)
const loadingSkpd = ref(false)
const saving = ref(false)
const deleting = ref(false)
const dialog = ref(false)
const deleteDialog = ref(false)
const editMode = ref(false)
const editSourceName = ref(false)
const showUnmapped = ref(true)
const unmappedTab = ref('pns')
const search = ref('')
const filterType = ref('')
const comingSoon = ref(false)
const comingSoonTitle = ref('')

const mappings = ref([])
const unmappedPns = ref([])
const unmappedPppk = ref([])
const skpdList = ref([])
const deletingItem = ref(null)

const form = ref({
  id: null,
  source_name: '',
  skpd_id: null,
  type: 'all',
})

const snackbar = ref({ show: false, message: '', color: 'success' })

const typeOptions = [
  { title: 'Semua Tipe (PNS & PPPK)', value: 'all' },
  { title: 'PNS saja', value: 'pns' },
  { title: 'PPPK Penuh Waktu saja', value: 'pppk' },
]

const tableHeaders = [
  { title: 'Nama SKPD di Excel (Source)', key: 'source_name', minWidth: '260px' },
  { title: 'SKPD Master (Tujuan)', key: 'nama_skpd', minWidth: '260px' },
  { title: 'Tipe', key: 'type', align: 'center', width: '100px' },
  { title: 'Aksi', key: 'actions', sortable: false, align: 'center', width: '100px' },
]

const unmappedCount = computed(() => unmappedPns.value.length + unmappedPppk.value.length)

const filteredMappings = computed(() => {
  if (!filterType.value) return mappings.value
  return mappings.value.filter(m => m.type === filterType.value)
})

const typeColor = (type) => {
  if (type === 'pns') return 'blue'
  if (type === 'pppk') return 'green'
  return 'grey'
}

// Show coming soon snackbar
const showComingSoon = (feature) => {
  comingSoonTitle.value = feature
  comingSoon.value = true
}

const notify = (message, color = 'success') => {
  snackbar.value = { show: true, message, color }
}

// Load all data
const loadAll = async () => {
  loading.value = true
  await Promise.all([loadMappings(), loadUnmapped(), loadSkpd()])
  loading.value = false
}

const loadMappings = async () => {
  try {
    const res = await api.get('/skpd-mapping')
    mappings.value = res.data.data ?? []
  } catch (e) {
    notify('Gagal memuat data mapping', 'error')
  }
}

const loadUnmapped = async () => {
  try {
    const res = await api.get('/skpd-mapping/unmapped')
    unmappedPns.value = res.data.data?.pns ?? []
    unmappedPppk.value = res.data.data?.pppk ?? []
  } catch (e) {
    console.error(e)
  }
}

const loadSkpd = async () => {
  if (skpdList.value.length > 0) return
  loadingSkpd.value = true
  try {
    const res = await api.get('/skpd')
    skpdList.value = (res.data.data ?? []).map(s => ({
      ...s,
      label: `${s.nama_skpd}${s.kode_skpd ? ' [' + s.kode_skpd + ']' : ''}`,
    }))
  } catch (e) {
    notify('Gagal memuat daftar SKPD', 'error')
  } finally {
    loadingSkpd.value = false
  }
}

// Dialogs
const openAddDialog = () => {
  form.value = { id: null, source_name: '', skpd_id: null, type: 'all' }
  editMode.value = false
  editSourceName.value = false
  loadSkpd()
  dialog.value = true
}

const openEditDialog = (item) => {
  form.value = {
    id: item.id,
    source_name: item.source_name,
    skpd_id: item.skpd_id,
    type: item.type,
  }
  editMode.value = true
  editSourceName.value = false
  loadSkpd()
  dialog.value = true
}

const quickMap = (item) => {
  form.value = { id: null, source_name: item.source_name, skpd_id: null, type: item.type }
  editMode.value = false
  editSourceName.value = true
  loadSkpd()
  dialog.value = true
}

const closeDialog = () => {
  dialog.value = false
}

const saveMapping = async () => {
  if (!form.value.source_name || !form.value.skpd_id) {
    notify('Nama SKPD dan SKPD Master wajib diisi', 'error')
    return
  }
  saving.value = true
  try {
    await api.post('/skpd-mapping', {
      source_name: form.value.source_name,
      skpd_id: form.value.skpd_id,
      type: form.value.type,
    })
    notify('Mapping berhasil disimpan!')
    closeDialog()
    await loadAll()
  } catch (e) {
    const msg = e.response?.data?.message ?? 'Gagal menyimpan mapping'
    notify(msg, 'error')
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
    await api.delete(`/skpd-mapping/${deletingItem.value.id}`)
    notify('Mapping berhasil dihapus')
    deleteDialog.value = false
    await loadAll()
  } catch (e) {
    notify('Gagal menghapus mapping', 'error')
  } finally {
    deleting.value = false
  }
}

onMounted(loadAll)
</script>

<style scoped>
.glass-card {
  background-color: rgb(var(--v-theme-surface)) !important;
  border: 1px solid rgba(var(--v-border-color), 0.08) !important;
  transition: box-shadow 0.2s ease;
}

.bg-light {
  background-color: rgb(var(--v-theme-background));
}

.modern-dashboard {
  background-color: rgb(var(--v-theme-background)) !important;
}
</style>
