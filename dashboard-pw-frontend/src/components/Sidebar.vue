<template>
  <v-navigation-drawer permanent elevation="0" class="glass-sidebar pt-4" width="260" fixed>
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

    <v-list nav class="px-3" open-strategy="sibling">
      <template v-for="(item, i) in filteredMenuItems" :key="i">
        <v-divider v-if="item.divider" class="my-2 mx-1 border-opacity-10"></v-divider>
        
        <v-list-subheader v-else-if="item.header" class="text-uppercase text-caption font-weight-bold mb-1 mt-4 text-disabled px-4">
          {{ item.header }}
        </v-list-subheader>

        <!-- Collapsible Group -->
        <v-list-group v-else-if="item.children && item.children.length > 0" :value="item.title">
          <template v-slot:activator="{ props }">
            <v-list-item
              v-bind="props"
              :prepend-icon="item.icon"
              :title="item.title"
              class="mb-1 rounded-lg text-body-2 font-weight-medium"
              color="primary"
            ></v-list-item>
          </template>

          <v-list-item
            v-for="(child, ci) in item.children"
            :key="ci"
            :value="child.value"
            :to="child.to"
            :prepend-icon="child.icon || 'mdi-circle-small'"
            :title="child.title"
            color="primary"
            rounded="lg"
            class="mb-1 pl-6"
          >
            <template v-slot:title>
              <div class="text-body-2 font-weight-medium">{{ child.title }}</div>
            </template>
          </v-list-item>
        </v-list-group>

        <!-- Single Item -->
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
        <v-card variant="tonal" color="primary" class="rounded-lg pa-3" flat>
          <div class="d-flex align-center mb-3">
            <v-avatar size="32" color="primary" variant="flat" class="mr-2">
              <span class="text-caption font-weight-bold text-white">{{ userInitials }}</span>
            </v-avatar>
            <div style="overflow: hidden">
              <div class="text-caption font-weight-bold text-truncate text-high-emphasis">{{ user.name }}</div>
              <div class="text-caption text-medium-emphasis text-truncate" style="font-size: 10px">{{ user.role?.toUpperCase() }}</div>
            </div>
          </div>
          <v-divider class="mb-2 border-opacity-10"></v-divider>
          <v-btn
            block
            prepend-icon="mdi-key-outline"
            variant="text"
            size="x-small"
            class="justify-start mb-1 px-2 text-medium-emphasis"
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
      <v-card class="rounded-xl pa-2 glass-modal">
        <v-card-title class="pa-4 font-weight-bold text-high-emphasis">Ganti Password</v-card-title>
        <v-card-text>
          <v-form ref="pwForm" v-model="pwValid">
            <v-text-field
              v-model="pwData.current_password"
              label="Password Sekarang"
              type="password"
              variant="outlined"
              density="compact"
              rounded="lg"
              :rules="[v => !!v || 'Wajib diisi']"
            ></v-text-field>
            <v-text-field
              v-model="pwData.new_password"
              label="Password Baru"
              type="password"
              variant="outlined"
              density="compact"
              rounded="lg"
              :rules="[v => !!v || 'Wajib diisi', v => (v && v.length >= 6) || 'Minimal 6 karakter']"
            ></v-text-field>
            <v-text-field
              v-model="pwData.new_password_confirmation"
              label="Konfirmasi Password Baru"
              type="password"
              variant="outlined"
              density="compact"
              rounded="lg"
              :rules="[v => !!v || 'Wajib diisi', v => v === pwData.new_password || 'Konfirmasi password tidak cocok']"
            ></v-text-field>
          </v-form>
        </v-card-text>
        <v-card-actions class="pa-4 pt-0">
          <v-spacer></v-spacer>
          <v-btn color="grey" variant="text" @click="passwordDialog = false">Batal</v-btn>
          <v-btn color="primary" variant="flat" rounded="lg" @click="changePassword" :loading="pwLoading" :disabled="!pwValid">Simpan</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-navigation-drawer>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '../api'

const router = useRouter()
const route = useRoute()
const getUserFromStorage = () => {
  try {
    const stored = localStorage.getItem('user')
    return (stored && stored !== 'null') ? JSON.parse(stored) : {}
  } catch (e) {
    return {}
  }
}

const user = ref(getUserFromStorage())

// Re-sync user data whenever route changes (to catch login updates)
watch(() => route.path, () => {
  user.value = getUserFromStorage()
})

const userInitials = computed(() => {
  const nameToUse = user.value?.name || user.value?.username || ''
  if (!nameToUse) return 'U'
  try {
    return nameToUse.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()
  } catch (e) {
    return 'U'
  }
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
  {
    title: 'PPPK-PW',
    icon: 'mdi-account-clock-outline',
    children: [
      { title: 'Dashboard PW', icon: 'mdi-view-dashboard-outline', value: 'dashboard', to: '/' },
      { title: 'Pegawai PW', icon: 'mdi-account-group-outline', value: 'employees', to: '/employees' },
      { title: 'Payroll PW', icon: 'mdi-wallet-outline', value: 'payments', to: '/payments' },
      { title: 'Data Gaji PPPK', icon: 'mdi-account-check-outline', value: 'gaji-pppk', to: '/gaji-pppk' },
      { title: 'Trace Gaji', icon: 'mdi-account-search-outline', value: 'employee-trace', to: '/employee-trace' },
      { title: 'THR PPPK-PW', icon: 'mdi-cash-fast', value: 'pppk-pw-thr', to: '/reports/thr-pppk-pw' },
    ]
  },
  {
    title: 'PNS & PPPK',
    icon: 'mdi-account-tie-outline',
    children: [
      { title: 'Dashboard PNS', icon: 'mdi-view-quilt-outline', value: 'pns', to: '/pns' },
      { title: 'Update NIK Massal', icon: 'mdi-account-plus-outline', value: 'employees', to: '/employees/import-nik' },
      { title: 'Data Gaji PNS', icon: 'mdi-badge-account-horizontal-outline', value: 'gaji-pns', to: '/gaji-pns' },
      { title: 'Master Pegawai (DBF)', icon: 'mdi-database-import', value: 'master-pegawai', to: '/master-pegawai' },
      { title: 'Export Master', icon: 'mdi-account-arrow-right', value: 'master-pegawai-export', to: '/master-pegawai/export' },
      { title: 'Upload TPP', icon: 'mdi-upload-multiple', value: 'tpp-upload', to: '/tpp/upload' },
    ]
  },
  {
    title: 'Laporan & Verif',
    icon: 'mdi-file-chart-outline',
    children: [
      { title: 'Verifikasi SP2D', icon: 'mdi-file-check-outline', value: 'sp2d-verification', to: '/sp2d-verification' },
      { title: 'Laporan SKPD', icon: 'mdi-file-table-outline', value: 'skpd-monthly', to: '/reports/skpd-monthly' },
      { title: 'Estimasi JKK/JKM', icon: 'mdi-shield-check-outline', value: 'pppk-settings', to: '/settings/pppk' },
      { title: 'Rekon BPJS 4%', icon: 'mdi-hospital-box-outline', value: 'bpjs-rekon', to: '/bpjs-rekon' },
      { title: 'Dashboard TPG', icon: 'mdi-school-outline', value: 'tpg-dashboard', to: '/tpg-dashboard' },
      { title: 'Upload TPG', icon: 'mdi-file-upload-outline', value: 'tpg-upload', to: '/tpg-upload' },
    ]
  },
  {
    title: 'Data Referensi',
    icon: 'mdi-database-outline',
    children: [
      { title: 'SKPD PW', icon: 'mdi-office-building-outline', value: 'skpd', to: '/skpd' },
      { title: 'SKPD Mapping', icon: 'mdi-swap-horizontal', value: 'skpd-mapping', to: '/settings/skpd-mapping' },
      { title: 'Ref Satker PNS', icon: 'mdi-office-building-cog', value: 'satker-setting', to: '/settings/satker' },
      { title: 'PTKP PNS', icon: 'mdi-account-cash-outline', value: 'tax-status', to: '/settings/tax-status' },
      { title: 'Sumber Dana PW', icon: 'mdi-cash-multiple', value: 'sumber-dana', to: '/settings/sumber-dana' },
    ]
  },
  {
    title: 'Manajemen Sistem',
    icon: 'mdi-cog-outline',
    children: [
      { title: 'Posting Data', icon: 'mdi-lock-check-outline', value: 'posting-data', to: '/posting-data' },
      { title: 'Manajemen User', icon: 'mdi-account-group-outline', value: 'users', to: '/settings/users', roles: ['superadmin'] },
      { title: 'API Keys', icon: 'mdi-key-chain', value: 'api-keys', to: '/settings/api-keys', roles: ['superadmin'] },
      { title: 'Riwayat Ekspor', icon: 'mdi-history', value: 'export-logs', to: '/settings/export-logs', roles: ['superadmin'] },
      { title: 'Pemeliharaan', icon: 'mdi-database-wrench', value: 'data-maintenance', to: '/settings/maintenance', roles: ['superadmin'] },
    ]
  },
])

const filteredMenuItems = computed(() => {
  const isAuthorized = (item) => {
    if (user.value.role === 'superadmin') return true
    if (item.roles && !item.roles.includes(user.value.role)) return false
    
    // Check app_access for leaf items
    if (item.value) {
      if (user.value.app_access && Array.isArray(user.value.app_access)) {
        return user.value.app_access.includes(item.value)
      }
      
      // Fallback: Show basic items by default
      const basicItems = ['dashboard', 'pns', 'employees', 'payments', 'sp2d-verification', 'tax-status', 'master-pegawai-export']
      return basicItems.includes(item.value)
    }
    
    return true
  }

  return menuItems.value.map(group => {
    if (group.children) {
      const filteredChildren = group.children.filter(child => isAuthorized(child))
      if (filteredChildren.length === 0) return null
      return { ...group, children: filteredChildren }
    }
    return isAuthorized(group) ? group : null
  }).filter(group => group !== null)
})
</script>

<style scoped>
.logo-circle {
  width: 32px;
  height: 32px;
  border-radius: 10px;
  box-shadow: 0 4px 12px rgba(var(--v-theme-primary), 0.2);
}

.glass-modal {
  background: rgba(var(--v-theme-surface), 0.95) !important;
  backdrop-filter: blur(24px);
  border: 1px solid rgba(var(--v-border-color), 0.1);
}

.v-list-item--active {
  background: rgb(var(--v-theme-primary), 0.1) !important;
  color: rgb(var(--v-theme-primary)) !important;
}

.v-list-item--active .v-icon {
  color: rgb(var(--v-theme-primary)) !important;
}
</style>
