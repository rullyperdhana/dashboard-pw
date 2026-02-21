<template>
  <v-app>
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
            {{ item.skpd ? item.skpd.nama_skpd : 'Semua SKPD (Superadmin)' }}
          </template>
          <template v-slot:item.role="{ item }">
            <v-chip :color="item.role === 'superadmin' ? 'purple' : 'blue'" size="small" label>
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
                <v-col cols="12">
                  <v-text-field v-model="editedItem.email" label="Email" :rules="[rules.required, rules.email]" variant="outlined"></v-text-field>
                </v-col>
                <v-col cols="12" v-if="editedIndex === -1">
                  <v-text-field v-model="editedItem.password" label="Password" type="password" :rules="[rules.required, rules.min]" variant="outlined"></v-text-field>
                </v-col>
                <v-col cols="12" md="6">
                  <v-select v-model="editedItem.role" :items="['superadmin', 'operator']" label="Role" :rules="[rules.required]" variant="outlined"></v-select>
                </v-col>
                <v-col cols="12" md="6">
                  <v-select v-model="editedItem.status" :items="['approved', 'pending']" label="Status" :rules="[rules.required]" variant="outlined"></v-select>
                </v-col>
                <v-col cols="12" v-if="editedItem.role === 'operator'">
                  <v-autocomplete
                    v-model="editedItem.institution"
                    :items="skpdList"
                    item-title="nama_skpd"
                    item-value="id_skpd"
                    label="SKPD"
                    variant="outlined"
                    :rules="[v => editedItem.role === 'superadmin' || !!v || 'SKPD wajib diisi untuk operator']"
                  ></v-autocomplete>
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
</v-app>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import Sidebar from '../../components/Sidebar.vue'
import Navbar from '../../components/Navbar.vue'
import api from '../../api'

const loading = ref(false)
const users = ref([])
const skpdList = ref([])
const search = ref('')
const filterRole = ref('Semua Role')
const roles = ['Semua Role', 'superadmin', 'operator']

const headers = [
  { title: 'Nama', key: 'name', align: 'start' },
  { title: 'Username', key: 'username' },
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
  institution: null
})

const openDialog = (item = null) => {
  if (item) {
    editedIndex.value = users.value.indexOf(item)
    editedItem.value = JSON.parse(JSON.stringify(item))
    editedItem.value.password = '' // Don't show hashed password
  } else {
    editedIndex.value = -1
    editedItem.value = {
      name: '',
      username: '',
      email: '',
      password: '',
      role: 'operator',
      status: 'approved',
      institution: null
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

onMounted(() => {
  fetchUsers()
  fetchSkpd()
})
</script>
