<template>
  <v-navigation-drawer 
    v-model="isSidebarOpen" 
    elevation="0" 
    class="glass-sidebar" 
    width="260" 
    :style="{ top: $vuetify.display.mdAndUp ? '64px' : '0', height: $vuetify.display.mdAndUp ? 'calc(100% - 64px)' : '100%' }"
    :temporary="!$vuetify.display.mdAndUp"
  >
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
          <div class="d-flex align-center justify-space-between mb-3">
            <div class="d-flex align-center">
              <v-avatar size="32" color="primary" variant="flat" class="mr-2">
                <span class="text-caption font-weight-bold text-white">{{ userInitials }}</span>
              </v-avatar>
              <div style="overflow: hidden">
                <div class="text-caption font-weight-bold text-truncate text-high-emphasis">{{ user.name }}</div>
                <div class="text-caption text-medium-emphasis text-truncate" style="font-size: 10px">{{ user.role?.toUpperCase() }}</div>
              </div>
            </div>
            <ThemeToggle />
          </div>
          <v-divider class="mb-2 border-opacity-10"></v-divider>
          <v-btn
            block
            prepend-icon="mdi-account-cog-outline"
            variant="text"
            size="x-small"
            class="justify-start mb-1 px-2 text-medium-emphasis"
            @click="openSettings"
          >
            Pengaturan Akun
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
        <div class="text-center mt-2">
          <span class="text-disabled font-weight-medium" style="font-size: 10px; letter-spacing: 1px;">VERSI {{ appVersion }}</span>
        </div>
      </div>
    </template>

    <!-- Account Settings Dialog -->
    <AccountSettingsDialog
      v-model="settingsDialog"
      :user="user"
      @user-updated="handleUserUpdated"
    />
  </v-navigation-drawer>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '../api'
import AccountSettingsDialog from './AccountSettingsDialog.vue'
import ThemeToggle from './ThemeToggle.vue'
import { isSidebarOpen } from '../utils/sidebarState'

const appVersion = APP_VERSION

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

const settingsDialog = ref(false)

const handleUserUpdated = (updatedUser) => {
  localStorage.setItem('user', JSON.stringify(updatedUser))
  user.value = updatedUser
}

const openSettings = () => {
  settingsDialog.value = true
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
    title: 'Beranda',
    icon: 'mdi-home-outline',
    value: 'welcome',
    to: '/welcome'
  },
  {
    title: 'PPPK-PW',
    icon: 'mdi-account-clock-outline',
    children: [
      { title: 'Dashboard PW', icon: 'mdi-view-dashboard-outline', value: 'dashboard', to: '/dashboard-pppk-pw' },
      { title: 'Laporan Bulanan PW', icon: 'mdi-file-table-outline', value: 'pppk-pw-thr', to: '/reports/pppk-pw-monthly' },
      { title: 'Laporan Periodik PW', icon: 'mdi-calendar-range', value: 'pppk-pw-thr', to: '/reports/pppk-pw-periodic' },
      { title: 'Pegawai PW', icon: 'mdi-account-group-outline', value: 'employees', to: '/employees' },
      { title: 'Payroll PW', icon: 'mdi-wallet-outline', value: 'payments', to: '/payments' },
      { title: 'Trace Gaji', icon: 'mdi-account-search-outline', value: 'employee-trace', to: '/employee-trace' },
      { title: 'THR PPPK-PW', icon: 'mdi-cash-fast', value: 'pppk-pw-thr', to: '/reports/thr-pppk-pw' },
      { title: 'Gaji 13 PPPK-PW', icon: 'mdi-cash-check', value: 'pppk-pw-thr', to: '/reports/gaji-13-pppk-pw' },
    ]
  },
  {
    title: 'PNS & PPPK',
    icon: 'mdi-account-tie-outline',
    children: [
      { title: 'Dashboard PNS', icon: 'mdi-view-quilt-outline', value: 'pns', to: '/pns' },
      { title: 'Update NIK Massal', icon: 'mdi-account-plus-outline', value: 'employees', to: '/employees/import-nik' },
      { title: 'Data Gaji PNS', icon: 'mdi-badge-account-horizontal-outline', value: 'gaji-pns', to: '/gaji-pns' },
      { title: 'Data Gaji PPPK', icon: 'mdi-account-check-outline', value: 'gaji-pppk', to: '/gaji-pppk' },
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
      { title: 'Laporan Periodik', icon: 'mdi-calendar-range', value: 'skpd-monthly', to: '/reports/periodic' },
      { title: 'Analitik TAPD', icon: 'mdi-chart-scatter-plot', value: 'tapd-dashboard', to: '/analytics/tapd' },
      { title: 'Mobile Eksekutif', icon: 'mdi-cellphone-text', value: 'executive-mobile', to: '/executive/mobile' },
      { title: 'Estimasi JKK/JKM', icon: 'mdi-shield-check-outline', value: 'pppk-settings', to: '/settings/pppk' },
      { title: 'Rekon BPJS 4%', icon: 'mdi-hospital-box-outline', value: 'bpjs-rekon', to: '/bpjs-rekon' },
      { title: 'Dashboard TPG', icon: 'mdi-school-outline', value: 'tpg-dashboard', to: '/tpg-dashboard' },
      { title: 'Upload TPG', icon: 'mdi-file-upload-outline', value: 'tpg-upload', to: '/tpg-upload' },
      { title: 'Riwayat Selisih TPP', icon: 'mdi-history', value: 'tpp-upload', to: '/tpp/discrepancy-history' },
      { title: 'Pajak TER (A2)', icon: 'mdi-calculator-variant-outline', value: 'pph21-report', to: '/reports/pph21' },
    ]
  },
  {
    title: 'Anggaran & Realisasi',
    icon: 'mdi-chart-pie',
    children: [
      { title: 'Input Anggaran', icon: 'mdi-cash-plus', value: 'budgets', to: '/budget/input' },
      { title: 'Laporan Realisasi', icon: 'mdi-file-chart-outline', value: 'budgets', to: '/budget/report' },
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
      { title: 'Manajemen Group', icon: 'mdi-account-details-outline', value: 'users', to: '/settings/groups', roles: ['superadmin'] },
      { title: 'API Keys', icon: 'mdi-key-chain', value: 'api-keys', to: '/settings/api-keys', roles: ['superadmin'] },
      { title: 'Riwayat Ekspor', icon: 'mdi-history', value: 'export-logs', to: '/settings/export-logs', roles: ['superadmin'] },
      { title: 'Pemeliharaan', icon: 'mdi-database-wrench', value: 'data-maintenance', to: '/settings/maintenance', roles: ['superadmin'] },
      { title: 'Log Login', icon: 'mdi-shield-history', value: 'login-logs', to: '/settings/login-logs', roles: ['superadmin'] },
      { title: 'Log Aktivitas', icon: 'mdi-history', value: 'audit-logs', to: '/settings/audit-logs', roles: ['superadmin'] },
      { title: 'Rekon Data BKD', icon: 'mdi-compare-horizontal', value: 'bkd-recon', to: '/settings/bkd-recon', roles: ['superadmin', 'operator'] },
      { title: 'Kelola Pengumuman', icon: 'mdi-bullhorn-outline', value: 'announcements', to: '/settings/announcements', roles: ['superadmin'] },
    ]
  },
  {
    title: 'Pusat Bantuan',
    icon: 'mdi-help-circle-outline',
    value: 'help-center',
    to: '/help'
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
      const basicItems = ['dashboard', 'pns', 'employees', 'payments', 'sp2d-verification', 'tax-status', 'master-pegawai-export', 'tapd-dashboard', 'pph21-report', 'budgets']
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
