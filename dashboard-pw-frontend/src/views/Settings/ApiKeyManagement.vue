<template>
  <div>
    <Navbar />
    <Sidebar />
    <v-main class="bg-light">
    <v-container fluid class="pa-8">
      <!-- Header -->
      <v-row class="mb-6 align-center">
        <v-col cols="12" md="6">
          <h1 class="text-h4 font-weight-bold mb-1">
            <v-icon start color="primary" size="36">mdi-key-chain</v-icon>
            API Keys
          </h1>
          <p class="text-subtitle-1 text-grey-darken-1">Kelola API Key untuk integrasi SIMGAJI.</p>
        </v-col>
        <v-col cols="12" md="6" class="text-right">
          <v-btn color="primary" prepend-icon="mdi-plus" @click="openDialog()" size="large" rounded>
            Buat API Key
          </v-btn>
        </v-col>
      </v-row>

      <!-- Info Card -->
      <v-alert type="info" variant="tonal" class="mb-6 rounded-xl" border="start">
        <div class="font-weight-bold mb-1">Cara Penggunaan API</div>
        <div class="text-body-2">
          Sertakan header <code>X-API-Key</code> pada setiap request ke endpoint SIMGAJI:
        </div>
        <div class="mt-2 pa-3 bg-grey-darken-4 rounded-lg text-body-2" style="font-family: monospace; color: #e0e0e0;">
          curl -H "X-API-Key: &lt;your_api_key&gt;" {{ baseUrl }}/api/listinstansi
        </div>
      </v-alert>

      <!-- API Keys Table -->
      <v-card class="rounded-xl" elevation="0" border :loading="loading">
        <v-data-table
          :headers="headers"
          :items="apiKeys"
          hover
          class="bg-transparent"
          :items-per-page="10"
        >
          <template v-slot:item.key="{ item }">
            <div class="d-flex align-center">
              <code class="text-body-2">{{ maskKey(item.key) }}</code>
              <v-btn icon="mdi-content-copy" variant="text" size="x-small" class="ml-1"
                @click="copyToClipboard(item.key)" title="Copy API Key">
              </v-btn>
            </div>
          </template>
          <template v-slot:item.is_active="{ item }">
            <v-chip :color="item.is_active ? 'success' : 'grey'" size="small" label>
              {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
            </v-chip>
          </template>
          <template v-slot:item.last_used_at="{ item }">
            <span class="text-body-2">{{ item.last_used_at ? formatDate(item.last_used_at) : 'Belum pernah' }}</span>
          </template>
          <template v-slot:item.created_at="{ item }">
            <span class="text-body-2">{{ formatDate(item.created_at) }}</span>
          </template>
          <template v-slot:item.actions="{ item }">
            <v-btn
              :icon="item.is_active ? 'mdi-toggle-switch' : 'mdi-toggle-switch-off'"
              variant="text" size="small"
              :color="item.is_active ? 'success' : 'grey'"
              @click="toggleKey(item)"
              :title="item.is_active ? 'Nonaktifkan' : 'Aktifkan'"
            ></v-btn>
            <v-btn icon="mdi-delete" variant="text" size="small" color="error"
              @click="confirmDelete(item)" title="Hapus">
            </v-btn>
          </template>
        </v-data-table>
      </v-card>

      <!-- Create Dialog -->
      <v-dialog v-model="dialog" max-width="500px" persistent>
        <v-card class="rounded-xl pa-2">
          <v-card-title class="text-h5 font-weight-bold pa-4">
            Buat API Key Baru
          </v-card-title>
          <v-card-text>
            <v-form ref="form" v-model="valid">
              <v-text-field
                v-model="newKeyName"
                label="Nama Aplikasi / Client"
                placeholder="contoh: Sistem Internal Pemprov"
                :rules="[v => !!v || 'Nama wajib diisi']"
                variant="outlined"
                prepend-inner-icon="mdi-application-outline"
              ></v-text-field>
            </v-form>
          </v-card-text>
          <v-card-actions class="pa-4 pt-0">
            <v-spacer></v-spacer>
            <v-btn color="grey-darken-1" variant="text" @click="dialog = false" :disabled="saving">Batal</v-btn>
            <v-btn color="primary" @click="createKey" :loading="saving" :disabled="!valid">Buat Key</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Show New Key Dialog -->
      <v-dialog v-model="showKeyDialog" max-width="600px" persistent>
        <v-card class="rounded-xl pa-2">
          <v-card-title class="text-h5 font-weight-bold pa-4">
            <v-icon start color="success">mdi-check-circle</v-icon>
            API Key Berhasil Dibuat
          </v-card-title>
          <v-card-text>
            <v-alert type="warning" variant="tonal" class="mb-4" density="compact">
              <strong>Penting!</strong> Simpan API Key ini sekarang. Key tidak akan ditampilkan lagi secara lengkap.
            </v-alert>
            <v-label class="mb-2 font-weight-bold">API Key:</v-label>
            <div class="d-flex align-center pa-3 bg-grey-lighten-4 rounded-lg mb-4">
              <code class="text-body-2 flex-grow-1" style="word-break: break-all;">{{ createdKey }}</code>
              <v-btn icon="mdi-content-copy" variant="text" size="small" color="primary" class="ml-2"
                @click="copyToClipboard(createdKey)">
              </v-btn>
            </div>
            <v-label class="mb-2 font-weight-bold">Contoh Penggunaan:</v-label>
            <div class="pa-3 bg-grey-darken-4 rounded-lg text-body-2" style="font-family: monospace; color: #e0e0e0; word-break: break-all;">
              curl -H "X-API-Key: {{ createdKey }}" {{ baseUrl }}/api/listinstansi
            </div>
          </v-card-text>
          <v-card-actions class="pa-4 pt-0">
            <v-spacer></v-spacer>
            <v-btn color="primary" @click="showKeyDialog = false">Saya Sudah Menyimpan</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Delete Confirmation -->
      <v-dialog v-model="deleteDialog" max-width="400px">
        <v-card class="rounded-xl">
          <v-card-title class="pa-4 font-weight-bold">Hapus API Key?</v-card-title>
          <v-card-text>
            Apakah Anda yakin ingin menghapus API Key <b>{{ deleteItem.name }}</b>?
            Semua aplikasi yang menggunakan key ini tidak akan bisa mengakses API lagi.
          </v-card-text>
          <v-card-actions class="pa-4 pt-0">
            <v-spacer></v-spacer>
            <v-btn color="grey" variant="text" @click="deleteDialog = false">Batal</v-btn>
            <v-btn color="error" @click="doDelete" :loading="deleting">Ya, Hapus</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Snackbar -->
      <v-snackbar v-model="snackbar" :color="snackbarColor" :timeout="3000" location="bottom right">
        {{ snackbarText }}
      </v-snackbar>
    </v-container>
  </v-main>
</div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import Sidebar from '../../components/Sidebar.vue'
import Navbar from '../../components/Navbar.vue'
import api from '../../api'

const loading = ref(false)
const apiKeys = ref([])
const baseUrl = ref(window.location.origin)

const headers = [
  { title: 'Nama', key: 'name', align: 'start' },
  { title: 'API Key', key: 'key', sortable: false },
  { title: 'Status', key: 'is_active', align: 'center' },
  { title: 'Terakhir Digunakan', key: 'last_used_at' },
  { title: 'Dibuat', key: 'created_at' },
  { title: 'Aksi', key: 'actions', sortable: false, align: 'end' },
]

// Snackbar
const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

const showSnack = (text, color = 'success') => {
  snackbarText.value = text
  snackbarColor.value = color
  snackbar.value = true
}

// Mask key for display
const maskKey = (key) => {
  if (!key) return ''
  return key.substring(0, 8) + '••••••••' + key.substring(key.length - 8)
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const copyToClipboard = async (text) => {
  try {
    await navigator.clipboard.writeText(text)
    showSnack('API Key berhasil disalin!')
  } catch {
    // Fallback for older browsers
    const el = document.createElement('textarea')
    el.value = text
    document.body.appendChild(el)
    el.select()
    document.execCommand('copy')
    document.body.removeChild(el)
    showSnack('API Key berhasil disalin!')
  }
}

// Create Key Dialog
const dialog = ref(false)
const valid = ref(false)
const saving = ref(false)
const newKeyName = ref('')
const showKeyDialog = ref(false)
const createdKey = ref('')

const openDialog = () => {
  newKeyName.value = ''
  dialog.value = true
}

const createKey = async () => {
  saving.value = true
  try {
    const response = await api.post('/api-keys', { name: newKeyName.value })
    createdKey.value = response.data.plain_key
    dialog.value = false
    showKeyDialog.value = true
    await fetchKeys()
  } catch (error) {
    console.error('Error creating API key:', error)
    showSnack(error.response?.data?.message || 'Gagal membuat API Key', 'error')
  } finally {
    saving.value = false
  }
}

// Toggle Active
const toggleKey = async (item) => {
  try {
    await api.put(`/api-keys/${item.id}/toggle`)
    await fetchKeys()
    showSnack(item.is_active ? 'API Key dinonaktifkan' : 'API Key diaktifkan')
  } catch (error) {
    console.error('Error toggling API key:', error)
    showSnack('Gagal mengubah status API Key', 'error')
  }
}

// Delete
const deleteDialog = ref(false)
const deleting = ref(false)
const deleteItem = ref({})

const confirmDelete = (item) => {
  deleteItem.value = item
  deleteDialog.value = true
}

const doDelete = async () => {
  deleting.value = true
  try {
    await api.delete(`/api-keys/${deleteItem.value.id}`)
    await fetchKeys()
    deleteDialog.value = false
    showSnack('API Key berhasil dihapus')
  } catch (error) {
    console.error('Error deleting API key:', error)
    showSnack('Gagal menghapus API Key', 'error')
  } finally {
    deleting.value = false
  }
}

// Fetch Keys
const fetchKeys = async () => {
  loading.value = true
  try {
    const response = await api.get('/api-keys')
    apiKeys.value = response.data.data
  } catch (error) {
    console.error('Error fetching API keys:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchKeys()
})
</script>
