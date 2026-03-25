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
            <v-icon start color="primary" size="36">mdi-account-details-outline</v-icon>
            Manajemen Group User
          </h1>
          <p class="text-subtitle-1 text-grey-darken-1">Kelola grup pengguna dan hak akses menu secara kolektif.</p>
        </v-col>
        <v-col cols="12" md="6" class="text-right">
          <v-btn color="primary" prepend-icon="mdi-plus" @click="openDialog()" size="large" rounded>
            Tambah Group
          </v-btn>
        </v-col>
      </v-row>

      <!-- Search -->
      <v-card class="rounded-xl mb-6 pa-4" elevation="0" border>
        <v-row dense align="center">
          <v-col cols="12" md="4">
            <v-text-field
              v-model="search"
              prepend-inner-icon="mdi-magnify"
              label="Cari nama group..."
              hide-details
              variant="outlined"
              density="compact"
            ></v-text-field>
          </v-col>
        </v-row>
      </v-card>

      <!-- Groups Table -->
      <v-card class="rounded-xl" elevation="0" border :loading="loading">
        <v-data-table
          :headers="headers"
          :items="groups"
          :search="search"
          hover
          class="bg-transparent"
        >
          <template v-slot:item.users_count="{ item }">
            <v-chip size="small" variant="tonal" color="primary">
              {{ item.users_count }} User
            </v-chip>
          </template>
          <template v-slot:item.app_access="{ item }">
            <template v-if="item.app_access && item.app_access.length > 0">
              <v-chip v-for="access in item.app_access.slice(0, 3)" :key="access" size="x-small" class="mr-1" variant="outlined">
                {{ formatAccess(access) }}
              </v-chip>
              <span v-if="item.app_access.length > 3" class="text-caption text-grey">
                +{{ item.app_access.length - 3 }} lainnya
              </span>
            </template>
            <template v-else>
              <span class="text-caption text-grey italic">Tidak ada akses menu khusus</span>
            </template>
          </template>
          <template v-slot:item.actions="{ item }">
            <v-btn icon="mdi-pencil" variant="text" size="small" color="primary" @click="openDialog(item)"></v-btn>
            <v-btn icon="mdi-delete" variant="text" size="small" color="error" @click="confirmDelete(item)"></v-btn>
          </template>
        </v-data-table>
      </v-card>

      <!-- Group Dialog -->
      <v-dialog v-model="dialog" max-width="800px" persistent scrollable>
        <v-card class="rounded-xl pa-2">
          <v-card-title class="text-h5 font-weight-bold pa-4">
            {{ editedIndex > -1 ? 'Edit Group' : 'Tambah Group' }}
          </v-card-title>
          <v-card-text style="max-height: 70vh">
            <v-form ref="form" v-model="valid">
              <v-row>
                <v-col cols="12">
                  <v-text-field v-model="editedItem.name" label="Nama Group" :rules="[rules.required]" variant="outlined" placeholder="Contoh: Operator PPPK-PW"></v-text-field>
                </v-col>
                <v-col cols="12">
                  <v-textarea v-model="editedItem.description" label="Keterangan" variant="outlined" rows="2" hide-details class="mb-4"></v-textarea>
                </v-col>

                <!-- Menu Access Selection -->
                <v-col cols="12">
                  <v-divider class="mb-4"></v-divider>
                  <div class="text-subtitle-1 font-weight-bold mb-4">Hak Akses Menu Group</div>
                  
                  <div v-for="group in moduleGroups" :key="group.name" class="mb-6">
                    <div class="d-flex align-center bg-grey-lighten-4 pa-2 rounded-lg mb-2">
                      <v-checkbox
                        :model-value="isGroupSelected(group.items)"
                        :indeterminate="isGroupPartial(group.items)"
                        @click.stop="toggleGroup(group.items, $event)"
                        hide-details
                        density="compact"
                        color="primary"
                        class="mt-0 pt-0"
                      >
                        <template v-slot:label>
                           <span class="text-subtitle-2 font-weight-bold text-primary">{{ group.name }}</span>
                        </template>
                      </v-checkbox>
                    </div>
                    
                    <v-row dense class="px-2">
                      <v-col v-for="module in group.items" :key="module.value" cols="12" md="6">
                        <v-checkbox
                          v-model="editedItem.app_access"
                          :label="module.title"
                          :value="module.value"
                          hide-details
                          density="compact"
                          color="primary"
                        ></v-checkbox>
                      </v-col>
                    </v-row>
                  </div>
                </v-col>
              </v-row>
            </v-form>
          </v-card-text>
          <v-card-actions class="pa-4 pt-0">
            <v-spacer></v-spacer>
            <v-btn color="grey-darken-1" variant="text" @click="closeDialog" :disabled="saving">Batal</v-btn>
            <v-btn color="primary" @click="save" :loading="saving" :disabled="!valid">Simpan Group</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Delete Confirmation -->
      <v-dialog v-model="deleteDialog" max-width="400px">
        <v-card class="rounded-xl">
          <v-card-title class="pa-4 font-weight-bold">Hapus Group?</v-card-title>
          <v-card-text>Apakah Anda yakin ingin menghapus group <b>{{ deleteItem.name }}</b>? Tindakan ini tidak dapat dibatalkan.</v-card-text>
          <v-card-actions class="pa-4 pt-0">
            <v-spacer></v-spacer>
            <v-btn color="grey" variant="text" @click="deleteDialog = false">Batal</v-btn>
            <v-btn color="error" @click="doDelete" :loading="deleting">Ya, Hapus</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </v-main>
</div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Sidebar from '../../components/Sidebar.vue'
import Navbar from '../../components/Navbar.vue'
import api from '../../api'

const loading = ref(false)
const groups = ref([])
const search = ref('')

const moduleGroups = [
  {
    name: 'Dashboard & Analitik',
    items: [
      { title: 'Dashboard PPPK-PW', value: 'dashboard' },
      { title: 'Dashboard PNS', value: 'pns' },
      { title: 'Analitik TAPD', value: 'tapd-dashboard' },
      { title: 'Mobile Eksekutif', value: 'executive-mobile' },
      { title: 'Estimasi JKK/JKM/JKN', value: 'pppk-settings' },
    ]
  },
  {
    name: 'Data Master',
    items: [
      { title: 'Pegawai PW', value: 'employees' },
      { title: 'Data Gaji PNS', value: 'gaji-pns' },
      { title: 'Data Gaji PPPK', value: 'gaji-pppk' },
      { title: 'Instansi / SKPD', value: 'skpd' },
      { title: 'SKPD Mapping', value: 'skpd-mapping' },
      { title: 'Master Pegawai (DBF)', value: 'master-pegawai' },
      { title: 'Export Master (DBF)', value: 'master-pegawai-export' },
    ]
  },
  {
    name: 'Keuangan',
    items: [
      { title: 'Payroll', value: 'payments' },
      { title: 'Trace Gaji', value: 'employee-trace' },
      { title: 'Upload TPP', value: 'tpp-upload' },
      { title: 'THR PPPK-PW', value: 'pppk-pw-thr' },
      { title: 'Rekon BPJS 4%', value: 'bpjs-rekon' },
      { title: 'Verifikasi SP2D', value: 'sp2d-verification' },
      { title: 'Laporan SKPD', value: 'skpd-monthly' },
      { title: 'Pajak TER (A2)', value: 'pph21-report' },
    ]
  },
  {
    name: 'TPG (Tunjangan Profesi Guru)',
    items: [
      { title: 'Upload TPG', value: 'tpg-upload' },
      { title: 'Dashboard TPG', value: 'tpg-dashboard' },
    ]
  },
  {
    name: 'Pengaturan',
    items: [
      { title: 'Status Pajak (PTKP)', value: 'tax-status' },
      { title: 'Posting Data', value: 'posting-data' },
      { title: 'Sumber Dana SKPD', value: 'sumber-dana' },
      { title: 'Referensi Satker', value: 'satker-setting' },
      { title: 'Riwayat Ekspor', value: 'export-logs' },
      { title: 'Pemeliharaan Data', value: 'data-maintenance' },
      { title: 'Manajemen User', value: 'users' },
      { title: 'API Keys', value: 'api-keys' },
      { title: 'Rekon Data BKD', value: 'bkd-recon' },
      { title: 'Pusat Bantuan', value: 'help-center' },
    ]
  }
]

const toggleGroup = (groupItems, event) => {
  const values = groupItems.map(i => i.value)
  
  if (isGroupSelected(groupItems)) {
    editedItem.value.app_access = editedItem.value.app_access.filter(val => !values.includes(val))
  } else {
    const newAccess = new Set([...editedItem.value.app_access, ...values])
    editedItem.value.app_access = Array.from(newAccess)
  }
}

const isGroupSelected = (groupItems) => {
  return groupItems.every(i => editedItem.value.app_access.includes(i.value))
}

const isGroupPartial = (groupItems) => {
  const selectedCount = groupItems.filter(i => editedItem.value.app_access.includes(i.value)).length
  return selectedCount > 0 && selectedCount < groupItems.length
}

const headers = [
  { title: 'Nama Group', key: 'name', align: 'start' },
  { title: 'Keterangan', key: 'description' },
  { title: 'Jumlah User', key: 'users_count', align: 'center' },
  { title: 'Hak Akses Menu', key: 'app_access' },
  { title: 'Aksi', key: 'actions', sortable: false, align: 'end' },
]

const rules = {
  required: v => !!v || 'Wajib diisi',
}

const dialog = ref(false)
const valid = ref(false)
const saving = ref(false)
const editedIndex = ref(-1)
const editedItem = ref({
  name: '',
  description: '',
  app_access: [],
  skpd_access: []
})

const formatAccess = (value) => {
  let found = null
  moduleGroups.forEach(g => {
    const item = g.items.find(i => i.value === value)
    if (item) found = item.title
  })
  return found || value
}

const openDialog = (item = null) => {
  if (item) {
    editedIndex.value = groups.value.indexOf(item)
    editedItem.value = JSON.parse(JSON.stringify(item))
    if (!editedItem.value.app_access) editedItem.value.app_access = []
  } else {
    editedIndex.value = -1
    editedItem.value = {
      name: '',
      description: '',
      app_access: [],
      skpd_access: []
    }
  }
  dialog.value = true
}

const closeDialog = () => {
  dialog.value = false
}

const save = async () => {
  saving.value = true
  try {
    if (editedIndex.value > -1) {
      const id = editedItem.value.id
      await api.put(`/user-groups/${id}`, editedItem.value)
    } else {
      await api.post('/user-groups', editedItem.value)
    }
    await fetchGroups()
    closeDialog()
  } catch (error) {
    console.error('Error saving group:', error)
    alert(error.response?.data?.message || 'Gagal menyimpan group')
  } finally {
    saving.value = false
  }
}

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
    await api.delete(`/user-groups/${deleteItem.value.id}`)
    await fetchGroups()
    deleteDialog.value = false
  } catch (error) {
    console.error('Error deleting group:', error)
    alert(error.response?.data?.message || 'Gagal menghapus group')
  } finally {
    deleting.value = false
  }
}

const fetchGroups = async () => {
  loading.value = true
  try {
    const response = await api.get('/user-groups')
    groups.value = response.data.data
  } catch (error) {
    console.error('Error fetching groups:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchGroups()
})
</script>
