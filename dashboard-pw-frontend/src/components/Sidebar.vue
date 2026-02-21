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
          <div class="d-flex align-center mb-2">
            <v-avatar size="32" color="primary" variant="flat" class="mr-2">
              <span class="text-caption font-weight-bold">A</span>
            </v-avatar>
            <div>
              <div class="text-caption font-weight-bold">Admin User</div>
              <div class="text-caption text-medium-emphasis" style="font-size: 10px">BKAD Prov Kalsel</div>
            </div>
          </div>
        </v-card>
      </div>
    </template>
  </v-navigation-drawer>
</template>

<script setup>
import { ref } from 'vue'

const menuItems = ref([
  { header: 'Dashboard & Analitik' },
  { title: 'Dashboard PPPK-PW', icon: 'mdi-view-dashboard-outline', value: 'dashboard', to: '/' },
  { title: 'Dashboard PNS', icon: 'mdi-account-tie-outline', value: 'pns', to: '/pns' },
  { title: 'Estimasi JKK/JKM', icon: 'mdi-shield-check-outline', value: 'pppk-settings', to: '/settings/pppk' },

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
  { title: 'Upload TPP', icon: 'mdi-upload-multiple', value: 'tpp-upload', to: '/tpp/upload' },
  { title: 'Laporan SKPD', icon: 'mdi-file-table-outline', value: 'skpd-monthly', to: '/reports/skpd-monthly' },

  { divider: true },
  { header: 'TPG (Tunjangan Profesi Guru)' },
  { title: 'Upload TPG', icon: 'mdi-file-upload-outline', value: 'tpg-upload', to: '/tpg-upload' },
  { title: 'Dashboard TPG', icon: 'mdi-school-outline', value: 'tpg-dashboard', to: '/tpg-dashboard' },
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
