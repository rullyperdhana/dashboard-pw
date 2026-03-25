<template>
  <div class="modern-dashboard">
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
          <v-btn variant="tonal" color="error" rounded="pill" @click="confirmDeleteAll" :disabled="mappings.length === 0" class="mr-3">
            <v-icon start icon="mdi-delete-sweep"></v-icon>
            Hapus ALL
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
                  <v-tab value="pppk_pw">
                    PPPK Paruh Waktu
                    <v-chip size="x-small" color="warning" class="ml-2">{{ unmappedPppkPw.length }}</v-chip>
                  </v-tab>
                  <v-tab value="sp2d">
                    SP2D (SIPD)
                    <v-chip size="x-small" color="warning" class="ml-2">{{ unmappedSp2d.length }}</v-chip>
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
                        <v-list-item-title class="text-body-2 font-weight-medium">
                          <v-chip size="x-small" color="warning" class="mr-2">{{ item.source_code }}</v-chip>
                          {{ item.source_name }}
                        </v-list-item-title>
                        <v-list-item-subtitle v-if="item.suggestion" class="text-caption text-primary">
                          Saran: {{ item.suggestion }}
                          <span v-if="item.kdskpd" class="text-grey ml-1">[{{ item.kdskpd }}]</span>
                        </v-list-item-subtitle>
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
                        <v-list-item-title class="text-body-2 font-weight-medium">
                          <v-chip size="x-small" color="warning" class="mr-2">{{ item.source_code }}</v-chip>
                          {{ item.source_name }}
                        </v-list-item-title>
                        <v-list-item-subtitle v-if="item.suggestion" class="text-caption text-primary">
                          Saran: {{ item.suggestion }}
                          <span v-if="item.kdskpd" class="text-grey ml-1">[{{ item.kdskpd }}]</span>
                        </v-list-item-subtitle>
                        <template v-slot:append>
                          <v-btn size="small" color="primary" variant="tonal" @click="quickMap(item)">
                            <v-icon start icon="mdi-link-variant" size="16"></v-icon>
                            Petakan
                          </v-btn>
                        </template>
                      </v-list-item>
                    </v-list>
                  </v-window-item>

                  <!-- PPPK-PW Unmapped -->
                  <v-window-item value="pppk_pw">
                    <div v-if="unmappedPppkPw.length === 0" class="text-center pa-4 text-medium-emphasis">
                      <v-icon icon="mdi-check-circle-outline" color="success" size="32" class="mb-2"></v-icon>
                      <div>Semua SKPD PPPK Paruh Waktu sudah dipetakan!</div>
                    </div>
                    <v-list v-else density="compact" class="rounded-lg">
                      <v-list-item
                        v-for="item in unmappedPppkPw"
                        :key="item.source_name"
                        class="mb-1 border rounded-lg"
                      >
                        <template v-slot:prepend>
                          <v-icon icon="mdi-office-building-remove-outline" color="warning" size="20"></v-icon>
                        </template>
                        <v-list-item-title class="text-body-2 font-weight-medium">
                          <v-chip size="x-small" color="warning" class="mr-2">{{ item.source_code }}</v-chip>
                          {{ item.source_name }}
                        </v-list-item-title>
                        <v-list-item-subtitle v-if="item.suggestion" class="text-caption text-primary">
                          Saran: {{ item.suggestion }}
                        </v-list-item-subtitle>
                        <template v-slot:append>
                          <v-btn size="small" color="primary" variant="tonal" @click="quickMap(item)">
                            <v-icon start icon="mdi-link-variant" size="16"></v-icon>
                            Petakan
                          </v-btn>
                        </template>
                      </v-list-item>
                    </v-list>
                  </v-window-item>

                  <!-- SP2D Unmapped -->
                  <v-window-item value="sp2d">
                    <div v-if="unmappedSp2d.length === 0" class="text-center pa-4 text-medium-emphasis">
                      <v-icon icon="mdi-check-circle-outline" color="success" size="32" class="mb-2"></v-icon>
                      <div>Semua nama SKPD dari SP2D sudah dipetakan!</div>
                    </div>
                    <v-list v-else density="compact" class="rounded-lg">
                      <v-list-item
                        v-for="item in unmappedSp2d"
                        :key="item.source_name"
                        class="mb-1 border rounded-lg"
                      >
                        <template v-slot:prepend>
                          <v-icon icon="mdi-file-document-remove-outline" color="warning" size="20"></v-icon>
                        </template>
                        <v-list-item-title class="text-body-2 font-weight-medium">
                          {{ item.source_name }}
                        </v-list-item-title>
                        <v-list-item-subtitle v-if="item.suggestion" class="text-caption text-primary">
                          Saran: {{ item.suggestion }}
                        </v-list-item-subtitle>
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
                  :items="[{ title: 'Semua Tipe', value: '' }, { title: 'PNS', value: 'pns' }, { title: 'PPPK', value: 'pppk' }, { title: 'PPPK-PW', value: 'pppk_pw' }, { title: 'All', value: 'all' }]"
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
              <template v-slot:item.source_code="{ item }">
                <v-chip size="small" variant="tonal" color="primary" class="font-weight-medium">
                  {{ item.source_code || '—' }}
                </v-chip>
              </template>
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
              <template v-slot:item.nama_skpd_2026="{ item }">
                <div class="d-flex align-center">
                  <v-icon icon="mdi-arrow-right-bottom" color="secondary" size="16" class="mr-2"></v-icon>
                  <span class="font-weight-medium text-secondary">{{ item.nama_skpd_2026 ?? '—' }}</span>
                  <v-chip v-if="item.kode_skpd_2026" size="x-small" variant="tonal" color="grey" class="ml-2">
                    {{ item.kode_skpd_2026 }}
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

          <v-row>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="form.source_code"
                label="Kode Sumber"
                variant="outlined"
                readonly
                persistent-hint
                hint="Kode unik sumber"
                class="mb-4"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="8">
              <v-text-field
                v-model="form.source_name"
                label="Nama Sumber"
                variant="outlined"
                :readonly="!editSourceName && editMode"
                persistent-hint
                hint="Nama asli sumber dari Excel/DBF"
                class="mb-4"
              ></v-text-field>
            </v-col>
          </v-row>

          <v-autocomplete
            v-model="form.skpd_id"
            :items="skpdList"
            item-title="label"
            item-value="id_skpd"
            label="SKPD Master Lama (Tujuan Dash/Lap)"
            variant="outlined"
            :loading="loadingSkpd"
            class="mb-4"
            clearable
            auto-select-first
            placeholder="Ketik untuk mencari SKPD lama..."
            no-data-text="SKPD tidak ditemukan"
          ></v-autocomplete>

          <v-autocomplete
            v-model="form.skpd_2026_id"
            :items="skpd2026List"
            item-title="label"
            item-value="id"
            label="SKPD Master 2026 (Opsional - Tujuan Simgaji)"
            variant="outlined"
            :loading="loadingSkpd2026"
            class="mb-4"
            clearable
            auto-select-first
            placeholder="Ketik untuk mencari SKPD 2026..."
            no-data-text="SKPD 2026 tidak ditemukan"
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

    <!-- Delete ALL Confirm Dialog -->
    <v-dialog v-model="deleteAllDialog" max-width="500">
      <v-card class="rounded-xl" elevation="8">
        <v-card-title class="pa-5 d-flex align-center bg-error text-white">
          <v-icon icon="mdi-alert-decagram" class="mr-2"></v-icon>
          Hapus SELURUH Mapping
        </v-card-title>
        <v-card-text class="pa-6">
          <div class="text-h6 mb-2">Apakah Anda yakin?</div>
          <p>Tindakan ini akan menghapus <strong>seluruh ({{ mappings.length }})</strong> data pemetaan SKPD yang telah dibuat. Tindakan ini <strong>tidak dapat dibatalkan</strong>.</p>
          <v-alert type="warning" variant="tonal" class="mt-4" density="compact">
            Menghapus semua mapping mungkin akan menyebabkan ketidakkonsistenan pada laporan sampai mapping dibuat kembali.
          </v-alert>
        </v-card-text>
        <v-card-actions class="pa-5 pt-0">
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="deleteAllDialog = false">Batal</v-btn>
          <v-btn color="error" variant="flat" :loading="deleting" @click="deleteAllMapping">
            Ya, Hapus Semua
          </v-btn>
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/api'
import Sidebar from '@/components/Sidebar.vue'
import Navbar from '@/components/Navbar.vue'

// State
const loading = ref(false)
const loadingSkpd = ref(false)
const loadingSkpd2026 = ref(false)
const saving = ref(false)
const deleting = ref(false)
const dialog = ref(false)
const deleteDialog = ref(false)
const deleteAllDialog = ref(false)
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
const unmappedPppkPw = ref([])
const unmappedSp2d = ref([])
const skpdList = ref([])
const skpd2026List = ref([])
const deletingItem = ref(null)

const form = ref({
  id: null,
  source_name: '',
  source_code: '',
  skpd_id: null,
  skpd_2026_id: null,
  type: 'all',
})

const snackbar = ref({ show: false, message: '', color: 'success' })

const typeOptions = [
  { title: 'Semua Tipe (PNS & PPPK)', value: 'all' },
  { title: 'PNS saja', value: 'pns' },
  { title: 'PPPK Penuh Waktu saja', value: 'pppk' },
  { title: 'PPPK Paruh Waktu saja', value: 'pppk_pw' },
]

const tableHeaders = [
  { title: 'Kode', key: 'source_code', width: '100px' },
  { title: 'Nama SKPD di Excel', key: 'source_name', minWidth: '220px' },
  { title: 'SKPD Master (Lama)', key: 'nama_skpd', minWidth: '220px' },
  { title: 'SKPD Master 2026', key: 'nama_skpd_2026', minWidth: '220px' },
  { title: 'Tipe', key: 'type', align: 'center', width: '100px' },
  { title: 'Aksi', key: 'actions', sortable: false, align: 'center', width: '100px' },
]

const unmappedCount = computed(() => unmappedPns.value.length + unmappedPppk.value.length + unmappedPppkPw.value.length + unmappedSp2d.value.length)

const filteredMappings = computed(() => {
  if (!filterType.value) return mappings.value
  return mappings.value.filter(m => m.type === filterType.value)
})

const typeColor = (type) => {
  if (type === 'pns') return 'blue'
  if (type === 'pppk') return 'green'
  if (type === 'pppk_pw') return 'orange'
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
  await Promise.all([loadMappings(), loadUnmapped(), loadSkpd(), loadSkpd2026()])
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
    unmappedPppkPw.value = res.data.data?.pppk_pw ?? []
    unmappedSp2d.value = res.data.data?.sp2d ?? []
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

const loadSkpd2026 = async () => {
  if (skpd2026List.value.length > 0) return
  loadingSkpd2026.value = true
  try {
    const res = await api.get('/skpd-2026')
    skpd2026List.value = (res.data.data ?? []).map(s => ({
      ...s,
      label: `${s.nama_skpd}${s.kode_skpd ? ' [' + s.kode_skpd + ']' : ''}`,
    }))
  } catch (e) {
    notify('Gagal memuat daftar SKPD 2026', 'error')
  } finally {
    loadingSkpd2026.value = false
  }
}

// Dialogs
const openAddDialog = () => {
  form.value = { id: null, source_name: '', source_code: '', skpd_id: null, skpd_2026_id: null, type: 'all' }
  editMode.value = false
  editSourceName.value = false
  loadSkpd()
  loadSkpd2026()
  dialog.value = true
}

const openEditDialog = (item) => {
  form.value = {
    id: item.id,
    source_name: item.source_name,
    source_code: item.source_code,
    skpd_id: item.skpd_id,
    skpd_2026_id: item.skpd_2026_id,
    type: item.type,
  }
  editMode.value = true
  editSourceName.value = false
  loadSkpd()
  loadSkpd2026()
  dialog.value = true
}

const quickMap = (item) => {
  form.value = { 
    id: null, 
    source_name: item.source_name, 
    source_code: item.source_code, 
    skpd_id: item.suggestion_id || null, 
    skpd_2026_id: null, 
    type: item.type 
  }
  editMode.value = false
  editSourceName.value = true
  loadSkpd()
  loadSkpd2026()
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
      source_code: form.value.source_code,
      skpd_id: form.value.skpd_id,
      skpd_2026_id: form.value.skpd_2026_id,
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

const confirmDeleteAll = () => {
  deleteAllDialog.value = true
}

const deleteAllMapping = async () => {
  deleting.value = true
  try {
    await api.delete('/skpd-mapping')
    notify('Seluruh mapping berhasil dihapus')
    deleteAllDialog.value = false
    await loadAll()
  } catch (e) {
    notify('Gagal menghapus seluruh mapping', 'error')
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
