<template>
  <v-navigation-drawer permanent elevation="0" class="glass-sidebar pt-4" width="260">
    <div class="px-4 mb-4 d-flex align-center">
      <div class="logo-circle bg-primary d-flex align-center justify-center mr-3">
        <v-icon icon="mdi-shield-crown-outline" color="white" size="20"></v-icon>
      </div>
      <div>
        <div class="text-h6 font-weight-bold text-primary" style="line-height: 1.1">SIP-Gaji</div>
        <div class="text-caption text-medium-emphasis">Government Payroll</div>
      </div>
    </div>

    <v-divider class="mx-4 mb-4 border-opacity-10"></v-divider>

    <v-list nav class="px-3">
      <template v-for="(item, i) in menuItems" :key="i">
        <v-divider v-if="item.divider" class="my-4 mx-1 border-opacity-10"></v-divider>
        
        <v-list-subheader v-else-if="item.header" class="text-uppercase text-caption font-weight-bold mb-1 mt-2 text-disabled">
          {{ item.header }}
        </v-list-subheader>

        <v-list-item
          v-else
          :value="item.value"
          :to="item.to"
          color="primary"
          rounded="lg"
          class="mb-1 transition-all"
        >
          <template v-slot:prepend>
            <v-icon :icon="item.icon" size="20" class="mr-2"></v-icon>
          </template>
          <v-list-item-title class="font-weight-medium text-body-2">{{ item.title }}</v-list-item-title>
        </v-list-item>
      </template>
    </v-list>
    
    <template v-slot:append>
      <div class="pa-4">
        <v-card variant="tonal" class="bg-primary-lighten-5 rounded-lg pa-3" flat>
          <div class="d-flex align-center mb-3">
            <v-avatar size="32" color="primary" variant="flat" class="mr-2">
              <span class="text-caption font-weight-bold">{{ userInitials }}</span>
            </v-avatar>
            <div style="overflow: hidden">
              <div class="text-caption font-weight-bold text-truncate">{{ user.name }}</div>
              <div class="text-caption text-medium-emphasis text-truncate" style="font-size: 10px">{{ user.role?.toUpperCase() }}</div>
            </div>
          </div>
          <v-divider class="mb-2 border-opacity-10"></v-divider>
          <v-btn
            block
            prepend-icon="mdi-key-outline"
            variant="text"
            size="x-small"
            class="justify-start mb-1 px-2"
            @click="passwordDialog = true"
          >
            Ganti Password
          </v-btn>
          <v-btn
            block
            prepend-icon="mdi-logout"
            color="error"
            variant="text"
            size="x-small"
            class="justify-start px-2"
            @click="handleLogout"
          >
            Logout
          </v-btn>
        </v-card>
      </div>
    </template>

    <!-- Change Password Dialog -->
    <v-dialog v-model="passwordDialog" max-width="400px">
      <v-card class="rounded-xl pa-2">
        <v-card-title class="pa-4 font-weight-bold">Ganti Password</v-card-title>
        <v-card-text>
          <v-form ref="pwForm" v-model="pwValid">
            <v-text-field
              v-model="pwData.current_password"
              label="Password Sekarang"
              type="password"
              variant="outlined"
              density="compact"
              :rules="[v => !!v || 'Wajib diisi']"
            ></v-text-field>
            <v-text-field
              v-model="pwData.new_password"
              label="Password Baru"
              type="password"
              variant="outlined"
              density="compact"
              :rules="[v => !!v || 'Wajib diisi', v => (v && v.length >= 6) || 'Minimal 6 karakter']"
            ></v-text-field>
            <v-text-field
              v-model="pwData.new_password_confirmation"
              label="Konfirmasi Password Baru"
              type="password"
              variant="outlined"
              density="compact"
              :rules="[v => !!v || 'Wajib diisi', v => v === pwData.new_password || 'Konfirmasi password tidak cocok']"
            ></v-text-field>
          </v-form>
        </v-card-text>
        <v-card-actions class="pa-4 pt-0">
          <v-spacer></v-spacer>
          <v-btn color="grey" variant="text" @click="passwordDialog = false">Batal</v-btn>
          <v-btn color="primary" @click="changePassword" :loading="pwLoading" :disabled="!pwValid">Simpan</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-navigation-drawer>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api'

const router = useRouter()
const user = ref(JSON.parse(localStorage.getItem('user') || '{}'))

const userInitials = computed(() => {
  if (!user.value.name) return 'U'
  return user.value.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()
})

const passwordDialog = ref(false)
const pwLoading = ref(false)
const pwValid = ref(false)
const pwData = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})

const changePassword = async () => {
  pwLoading.value = true
  try {
    await api.post('/change-password', pwData.value)
    alert('Password berhasil diubah')
    passwordDialog.value = false
    pwData.value = { current_password: '', new_password: '', new_password_confirmation: '' }
  } catch (error) {
    console.error('Error changing password:', error)
    alert(error.response?.data?.message || 'Gagal mengubah password. Pastikan password lama benar.')
  } finally {
    pwLoading.value = false
  }
}

const handleLogout = async () => {
  try {
    await api.post('/logout')
  } catch (error) {
    console.error('Logout error:', error)
  } finally {
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    router.push('/login')
  }
}

const menuItems = ref([
  { header: 'Dashboard & Analitik' },
  { title: 'Dashboard PPPK-PW', icon: 'mdi-view-dashboard-outline', value: 'dashboard', to: '/' },
  { title: 'Dashboard PNS', icon: 'mdi-account-tie-outline', value: 'pns', to: '/pns' },
  { title: 'Estimasi JKK/JKM/JKN', icon: 'mdi-shield-check-outline', value: 'pppk-settings', to: '/settings/pppk' },

  { divider: true },
  { header: 'Data Master' },
  { title: 'Pegawai PW', icon: 'mdi-account-group-outline', value: 'employees', to: '/employees' },
  { title: 'Data Gaji PNS', icon: 'mdi-badge-account-horizontal-outline', value: 'gaji-pns', to: '/gaji-pns' },
  { title: 'Data Gaji PPPK', icon: 'mdi-account-check-outline', value: 'gaji-pppk', to: '/gaji-pppk' },
  { title: 'Instansi / SKPD', icon: 'mdi-office-building-outline', value: 'skpd', to: '/skpd' },
  { title: 'SKPD Mapping', icon: 'mdi-swap-horizontal', value: 'skpd-mapping', to: '/settings/skpd-mapping' },

  { divider: true },
  { header: 'Keuangan' },
  { title: 'Payroll', icon: 'mdi-wallet-outline', value: 'payments', to: '/payments' },
  { title: 'Trace Gaji', icon: 'mdi-account-search-outline', value: 'employee-trace', to: '/employee-trace' },
  { title: 'Upload TPP', icon: 'mdi-upload-multiple', value: 'tpp-upload', to: '/tpp/upload' },
  { title: 'Rekon BPJS 4%', icon: 'mdi-hospital-box-outline', value: 'bpjs-rekon', to: '/bpjs-rekon' },
  { title: 'Laporan SKPD', icon: 'mdi-file-table-outline', value: 'skpd-monthly', to: '/reports/skpd-monthly' },

  { divider: true },
  { header: 'TPG (Tunjangan Profesi Guru)' },
  { title: 'Upload TPG', icon: 'mdi-file-upload-outline', value: 'tpg-upload', to: '/tpg-upload' },
  { title: 'Dashboard TPG', icon: 'mdi-school-outline', value: 'tpg-dashboard', to: '/tpg-dashboard' },

  { divider: true },
  { header: 'Pengaturan' },
  { title: 'Posting Data', icon: 'mdi-lock-check-outline', value: 'posting-data', to: '/posting-data' },
  { title: 'Sumber Dana SKPD', icon: 'mdi-cash-multiple', value: 'sumber-dana', to: '/settings/sumber-dana' },
  { title: 'Manajemen User', icon: 'mdi-account-group-outline', value: 'users', to: '/settings/users' },
])
</script>

<style scoped>
.glass-sidebar {
  border-right: 1px solid rgba(var(--v-border-color), 0.08) !important;
  background-color: rgba(var(--v-theme-surface), 0.95) !important;
  backdrop-filter: blur(10px);
}

.logo-circle {
  width: 32px;
  height: 32px;
  border-radius: 10px;
  box-shadow: 0 4px 12px rgba(var(--v-theme-primary), 0.2);
}

.v-list-item--active {
  background: rgb(var(--v-theme-primary), 0.1) !important;
  color: rgb(var(--v-theme-primary)) !important;
}

.v-list-item--active .v-icon {
  color: rgb(var(--v-theme-primary)) !important;
}
</style>
