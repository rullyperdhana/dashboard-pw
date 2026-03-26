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
            <v-icon start color="primary" size="36">mdi-account-group</v-icon>
            Manajemen User
          </h1>
          <p class="text-subtitle-1 text-grey-darken-1">Kelola akun pengguna, peran, dan hak akses SKPD.</p>
        </v-col>
        <v-col cols="12" md="6" class="text-right">
          <v-btn color="primary" prepend-icon="mdi-account-plus" @click="openDialog()" size="large" rounded>
            Tambah User
          </v-btn>
        </v-col>
      </v-row>

      <!-- Search & Filters -->
      <v-card class="rounded-xl mb-6 pa-4" elevation="0" border>
        <v-row dense align="center">
          <v-col cols="12" md="4">
            <v-text-field
              v-model="search"
              prepend-inner-icon="mdi-magnify"
              label="Cari nama atau username..."
              hide-details
              variant="outlined"
              density="compact"
            ></v-text-field>
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="filterRole"
              :items="roles"
              label="Role"
              hide-details
              variant="outlined"
              density="compact"
            ></v-select>
          </v-col>
        </v-row>
      </v-card>

      <!-- Users Table -->
      <v-card class="rounded-xl" elevation="0" border :loading="loading">
        <v-data-table
          :headers="headers"
          :items="filteredUsers"
          :search="search"
          hover
          class="bg-transparent"
        >
          <template v-slot:item.skpd="{ item }">
            <template v-if="item.role === 'superadmin'">
              Semua SKPD (Superadmin)
            </template>
            <template v-else-if="item.skpd_access && item.skpd_access.length > 0">
              <v-chip v-for="id in item.skpd_access.slice(0, 2)" :key="id" size="x-small" class="mr-1" variant="tonal">
                {{ getSkpdName(id) }}
              </v-chip>
              <span v-if="item.skpd_access.length > 2" class="text-caption text-grey">
                +{{ item.skpd_access.length - 2 }} lainnya
              </span>
            </template>
            <template v-else-if="item.skpd">
              {{ item.skpd.nama_skpd }}
            </template>
            <template v-else>
              -
            </template>
          </template>
          <template v-slot:item.role="{ item }">
            <v-chip :color="item.role === 'superadmin' ? 'purple' : (item.role === 'eksekutif' ? 'teal' : 'blue')" size="small" label>
              {{ item.role.toUpperCase() }}
            </v-chip>
          </template>
          <template v-slot:item.status="{ item }">
            <v-chip :color="item.status === 'approved' ? 'success' : 'warning'" size="small" variant="tonal">
              {{ item.status === 'approved' ? 'Aktif' : 'Pending' }}
            </v-chip>
          </template>
          <template v-slot:item.actions="{ item }">
            <v-btn icon="mdi-pencil" variant="text" size="small" color="primary" @click="openDialog(item)"></v-btn>
            <v-btn icon="mdi-key" variant="text" size="small" color="orange" @click="openResetPassword(item)"></v-btn>
            <v-btn icon="mdi-delete" variant="text" size="small" color="error" @click="confirmDelete(item)"></v-btn>
          </template>
        </v-data-table>
      </v-card>

      <!-- User Dialog -->
      <v-dialog v-model="dialog" max-width="600px" persistent>
        <v-card class="rounded-xl pa-2">
          <v-card-title class="text-h5 font-weight-bold pa-4">
            {{ editedIndex > -1 ? 'Edit User' : 'Tambah User' }}
          </v-card-title>
          <v-card-text>
            <v-form ref="form" v-model="valid">
              <v-row>
                <v-col cols="12" md="6">
                  <v-text-field v-model="editedItem.name" label="Nama Lengkap" :rules="[rules.required]" variant="outlined"></v-text-field>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="editedItem.username" label="Username" :rules="[rules.required]" variant="outlined"></v-text-field>
                </v-col>
                <v-col cols="12" md="8">
                  <v-text-field v-model="editedItem.email" label="Email" :rules="[rules.required, rules.email]" variant="outlined"></v-text-field>
                </v-col>
                <v-col cols="12" md="4">
                  <v-select
                    v-model="editedItem.user_group_id"
                    :items="userGroups"
                    item-title="name"
                    item-value="id"
                    label="Group User"
                    variant="outlined"
                    clearable
                    placeholder="Pilih Group (Opsional)"
                  ></v-select>
                </v-col>
                <v-col cols="12" v-if="editedIndex === -1">
                  <v-text-field v-model="editedItem.password" label="Password" type="password" :rules="[rules.required, rules.min]" variant="outlined"></v-text-field>
                </v-col>
                <v-col cols="12" md="6">
                  <v-select v-model="editedItem.role" :items="['superadmin', 'operator', 'eksekutif']" label="Role" :rules="[rules.required]" variant="outlined"></v-select>
                </v-col>
                <v-col cols="12" md="6">
                  <v-select v-model="editedItem.status" :items="['approved', 'pending']" label="Status" :rules="[rules.required]" variant="outlined"></v-select>
                </v-col>
                <v-col cols="12" v-if="editedItem.role === 'operator'">
                  <v-autocomplete
                    v-model="selectedSkpdIds"
                    :items="skpdList"
                    item-title="nama_skpd"
                    item-value="id_skpd"
                    label="Pilih Unit Organisasi (SKPD)"
                    variant="outlined"
                    multiple
                    chips
                    closable-chips
                    placeholder="Pilih satu atau lebih SKPD"
                    hide-details
                    class="mb-4"
                  ></v-autocomplete>

                  <div v-if="selectedSkpdIds.length > 0" class="border rounded-lg overflow-hidden mb-4">
                    <div class="bg-grey-lighten-4 pa-2 text-subtitle-2 font-weight-bold border-b">
                      Pembagian Wilayah Kerja User
                    </div>
                    <v-table density="compact">
                      <thead>
                        <tr>
                          <th class="text-left">SKPD</th>
                          <th class="text-center" width="100">PNS/PPPK</th>
                          <th class="text-center" width="100">PPPK-PW</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="id in selectedSkpdIds" :key="id">
                          <td class="text-caption py-2 font-weight-bold">{{ getSkpdName(id) }}</td>
                          <td class="text-center">
                            <v-checkbox-btn
                              v-model="getGranularAccess(id).pns"
                              color="primary"
                              density="compact"
                              hide-details
                            ></v-checkbox-btn>
                          </td>
                          <td class="text-center">
                            <v-checkbox-btn
                              v-model="getGranularAccess(id).pw"
                              color="primary"
                              density="compact"
                              hide-details
                            ></v-checkbox-btn>
                          </td>
                        </tr>
                      </tbody>
                    </v-table>
                  </div>
                  <div v-if="editedItem.role === 'operator' && selectedSkpdIds.length === 0" class="text-caption text-error mb-4">
                    Minimal satu SKPD wajib dipilih
                  </div>
                </v-col>

                <!-- Menu Access Selection -->
                <v-col cols="12">
                  <v-divider class="mb-4"></v-divider>
                  <div class="text-subtitle-1 font-weight-bold mb-4">Hak Akses Menu</div>
                  
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
            <v-btn color="primary" @click="save" :loading="saving" :disabled="!valid">Simpan</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Reset Password Dialog -->
      <v-dialog v-model="resetDialog" max-width="400px">
        <v-card class="rounded-xl pa-2">
          <v-card-title class="pa-4 font-weight-bold">Reset Password: {{ resetItem.username }}</v-card-title>
          <v-card-text>
            <v-text-field v-model="newPassword" label="Password Baru" type="password" variant="outlined" :rules="[rules.required, rules.min]"></v-text-field>
          </v-card-text>
          <v-card-actions class="pa-4 pt-0">
            <v-spacer></v-spacer>
            <v-btn color="grey" variant="text" @click="resetDialog = false">Batal</v-btn>
            <v-btn color="orange" @click="resetPassword" :loading="resetting">Reset Password</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Delete Confirmation -->
      <v-dialog v-model="deleteDialog" max-width="400px">
        <v-card class="rounded-xl">
          <v-card-title class="pa-4 font-weight-bold">Hapus User?</v-card-title>
          <v-card-text>Apakah Anda yakin ingin menghapus akun <b>{{ deleteItem.username }}</b>? Tindakan ini tidak dapat dibatalkan.</v-card-text>
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
import { ref, computed, onMounted, watch } from 'vue'
import Sidebar from '../../components/Sidebar.vue'
import Navbar from '../../components/Navbar.vue'
import api from '../../api'

const loading = ref(false)
const users = ref([])
const userGroups = ref([])
const skpdList = ref([])
const search = ref('')
const filterRole = ref('Semua Role')
const roles = ['Semua Role', 'superadmin', 'operator', 'eksekutif']

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
  const isChecked = event.target.checked || event.target.parentElement?.getAttribute('aria-checked') === 'true'
  const values = groupItems.map(i => i.value)
  
  if (isGroupSelected(groupItems)) {
    // Unselect all in this group
    editedItem.value.app_access = editedItem.value.app_access.filter(val => !values.includes(val))
  } else {
    // Select all in this group
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
  { title: 'Nama', key: 'name', align: 'start' },
  { title: 'Username', key: 'username' },
  { title: 'Group', key: 'user_group.name', sortable: true },
  { title: 'SKPD', key: 'skpd' },
  { title: 'Role', key: 'role', align: 'center' },
  { title: 'Status', key: 'status', align: 'center' },
  { title: 'Aksi', key: 'actions', sortable: false, align: 'end' },
]

const rules = {
  required: v => !!v || 'Wajib diisi',
  min: v => (v && v.length >= 6) || 'Minimal 6 karakter',
  email: v => /.+@.+\..+/.test(v) || 'Email tidak valid',
}

const filteredUsers = computed(() => {
  return users.value.filter(u => {
    const roleMatch = filterRole.value === 'Semua Role' || u.role === filterRole.value
    return roleMatch
  })
})

const dialog = ref(false)
const valid = ref(false)
const saving = ref(false)
const editedIndex = ref(-1)
const editedItem = ref({
  name: '',
  username: '',
  email: '',
  password: '',
  role: 'operator',
  status: 'approved',
  institution: null,
  user_group_id: null,
  skpd_access: [],
  app_access: []
})

const selectedSkpdIds = ref([])

const getGranularAccess = (id) => {
  let access = editedItem.value.skpd_access.find(a => 
    (typeof a === 'object' ? (a.id === id || a.id_skpd === id) : a === id)
  )
  
  if (!access || typeof access !== 'object') {
    // If it was a simple ID or not found, convert/create
    const newAccess = { id: id, pns: true, pw: true }
    // Update the array without triggering full reactivity loops if possible
    const index = editedItem.value.skpd_access.findIndex(a => 
      (typeof a === 'object' ? (a.id === id || a.id_skpd === id) : a === id)
    )
    if (index > -1) editedItem.value.skpd_access[index] = newAccess
    else editedItem.value.skpd_access.push(newAccess)
    return newAccess
  }
  
  // Ensure ID is set correctly if it was missing in the object
  if (!access.id) access.id = id
  
  return access
}

watch(selectedSkpdIds, (newIds) => {
  // Sync editedItem.institution with the first selected SKPD
  if (newIds && newIds.length > 0) {
    editedItem.value.institution = parseInt(newIds[0])
  } else {
    editedItem.value.institution = null
  }

  // Sync editedItem.skpd_access (array of objects) with selectedSkpdIds (array of IDs)
  const currentAccess = [...editedItem.value.skpd_access]
  
  // 1. Remove IDs that are no longer selected
  editedItem.value.skpd_access = currentAccess.filter(a => {
    const id = typeof a === 'object' ? (a.id || a.id_skpd) : a
    return newIds.includes(parseInt(id))
  })
  
  // 2. Add IDs that are newly selected
  newIds.forEach(id => {
    const exists = editedItem.value.skpd_access.some(a => {
      const aid = typeof a === 'object' ? (a.id || a.id_skpd) : a
      return parseInt(aid) === parseInt(id)
    })
    if (!exists) {
      editedItem.value.skpd_access.push({ id: id, pns: true, pw: true })
    }
  })
}, { deep: true })

const openDialog = (item = null) => {
  if (item) {
    editedIndex.value = users.value.indexOf(item)
    editedItem.value = JSON.parse(JSON.stringify(item))
    if (!editedItem.value.app_access) editedItem.value.app_access = []
    if (!editedItem.value.skpd_access) editedItem.value.skpd_access = []
    
    // Set selectedSkpdIds for autocomplete
    selectedSkpdIds.value = editedItem.value.skpd_access.map(a => 
      typeof a === 'object' ? parseInt(a.id || a.id_skpd) : parseInt(a)
    )
    
    editedItem.value.password = '' // Don't show hashed password
  } else {
    editedIndex.value = -1
    selectedSkpdIds.value = []
    editedItem.value = {
      name: '',
      username: '',
      email: '',
      password: '',
      role: 'operator',
      status: 'approved',
      institution: null,
      user_group_id: null,
      skpd_access: [],
      app_access: []
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
      await api.put(`/users/${id}`, editedItem.value)
    } else {
      await api.post('/users', editedItem.value)
    }
    await fetchUsers()
    
    // If editing own profile, update local session
    const currentUser = JSON.parse(localStorage.getItem('user') || '{}')
    if (editedItem.value.id === currentUser.id) {
      // Merge changes into current user session
      const updatedUser = { ...currentUser, ...editedItem.value }
      localStorage.setItem('user', JSON.stringify(updatedUser))
      // Force refreshing the user state if broadcast channel or global state exists
      // For now, Sidebar.vue has a route watcher that will catch this on next navigation
    }

    closeDialog()
  } catch (error) {
    console.error('Error saving user:', error)
    alert(error.response?.data?.message || 'Gagal menyimpan user')
  } finally {
    saving.value = false
  }
}

const resetDialog = ref(false)
const resetting = ref(false)
const newPassword = ref('')
const resetItem = ref({})

const openResetPassword = (item) => {
  resetItem.value = item
  newPassword.value = ''
  resetDialog.value = true
}

const resetPassword = async () => {
  if (!newPassword.value || newPassword.value.length < 6) return
  
  resetting.value = true
  try {
    const id = resetItem.value.id
    await api.post(`/users/${id}/reset-password`, { password: newPassword.value })
    resetDialog.value = false
    alert('Password berhasil direset')
  } catch (error) {
    console.error('Error resetting password:', error)
    alert('Gagal mereset password')
  } finally {
    resetting.value = false
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
    await api.delete(`/users/${deleteItem.value.id}`)
    await fetchUsers()
    deleteDialog.value = false
  } catch (error) {
    console.error('Error deleting user:', error)
    alert(error.response?.data?.message || 'Gagal menghapus user')
  } finally {
    deleting.value = false
  }
}

const getSkpdName = (idOrObject) => {
  const id = typeof idOrObject === 'object' ? (idOrObject.id || idOrObject.id_skpd) : idOrObject
  const skpd = skpdList.value.find(s => parseInt(s.id_skpd) === parseInt(id))
  return skpd ? skpd.nama_skpd : `SKPD #${id}`
}

const fetchUsers = async () => {
  loading.value = true
  try {
    const response = await api.get('/users')
    users.value = response.data.data
  } catch (error) {
    console.error('Error fetching users:', error)
  } finally {
    loading.value = false
  }
}

const fetchSkpd = async () => {
  try {
    const response = await api.get('/skpd')
    skpdList.value = response.data.data
  } catch (error) {
    console.error('Error fetching SKPD:', error)
  }
}

const fetchGroups = async () => {
  try {
    const response = await api.get('/user-groups')
    userGroups.value = response.data.data
  } catch (error) {
    console.error('Error fetching groups:', error)
  }
}

onMounted(() => {
  fetchUsers()
  fetchSkpd()
  fetchGroups()
})
</script>
