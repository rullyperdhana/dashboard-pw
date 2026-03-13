<template>
  <div class="modern-dashboard">
    <Navbar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />
    <Sidebar />

    <v-main class="bg-dashboard">
      <v-container fluid class="pa-6 pa-md-10">
        <!-- Header Section -->
        <header class="dashboard-header mb-10">
          <v-row align="center">
            <v-col cols="12" md="6">
              <div class="d-flex align-center mb-4">
                <v-avatar color="primary" size="56" class="elevation-10 mr-5">
                  <v-icon color="white" size="32">mdi-office-building-marker-outline</v-icon>
                </v-avatar>
                <div>
                  <h1 class="text-h3 font-weight-black tracking-tight text-high-emphasis">Units & SKPD</h1>
                  <p class="text-subtitle-1 text-medium-emphasis">Kelola dan pantau seluruh unit administrasi yang terdaftar.</p>
                </div>
              </div>
            </v-col>
            <v-col cols="12" md="6" class="d-flex justify-md-end">
               <v-text-field
                  v-model="search"
                  prepend-inner-icon="mdi-magnify"
                  placeholder="Cari nama atau kode unit..."
                  variant="filled"
                  flat
                  rounded="pill"
                  hide-details
                  class="search-field"
                  clearable
                ></v-text-field>
            </v-col>
          </v-row>
        </header>

        <v-row v-if="!loading">
          <!-- Top Featured Organizations -->
          <v-col v-for="item in filteredSkpds.filter(s => s.is_skpd).slice(0, 4)" :key="item.id_skpd" cols="12" sm="6" md="3">
            <v-card class="glass-panel featured-org-card h-100" elevation="0">
              <v-card-text class="pa-6">
                <div class="d-flex align-center mb-6">
                  <div class="org-icon-wrapper mr-4">
                    <v-icon color="primary" size="24">mdi-city-variant-outline</v-icon>
                  </div>
                  <v-spacer></v-spacer>
                  <v-chip color="primary" size="x-small" variant="flat" class="font-weight-black px-3">MAJOR</v-chip>
                </div>
                
                <h3 class="text-h6 font-weight-black mb-1 line-clamp-2">{{ item.nama_skpd }}</h3>
                <div class="text-caption text-medium-emphasis font-weight-bold mb-6">KODE: {{ item.kode_skpd }}</div>
                
                <v-divider class="mb-6 opacity-10"></v-divider>
                
                <v-btn block color="primary" variant="tonal" rounded="pill" size="large" class="font-weight-black" @click="showDetails(item)">
                   LIHAT DETAIL
                   <v-icon end icon="mdi-arrow-right" size="18"></v-icon>
                </v-btn>
              </v-card-text>
            </v-card>
          </v-col>

          <!-- Main List Container -->
          <v-col cols="12" class="mt-6">
            <v-card class="glass-panel overflow-hidden" elevation="0">
              <v-toolbar color="transparent" flat class="px-8 py-4 border-b">
                <v-toolbar-title class="text-h6 font-weight-black text-high-emphasis">
                  Seluruh Registri Unit Kerja
                  <v-chip size="small" variant="tonal" color="primary" class="ml-3 font-weight-black">{{ filteredSkpds.length }} ENTITAS</v-chip>
                </v-toolbar-title>
              </v-toolbar>

              <v-data-table
                :headers="headers"
                :items="filteredSkpds"
                class="bg-transparent"
                hover
                :items-per-page="12"
              >
                <template v-slot:item.nama_skpd="{ item }">
                  <div class="d-flex align-center py-4">
                    <v-avatar color="surface-variant" size="32" class="mr-3 text-caption font-weight-black">
                       {{ item.nama_skpd.charAt(0) }}
                    </v-avatar>
                    <span class="font-weight-bold text-high-emphasis">{{ item.nama_skpd }}</span>
                  </div>
                </template>

                <template v-slot:item.is_skpd="{ item }">
                  <v-chip 
                    :color="item.is_skpd ? 'primary' : 'medium-emphasis'" 
                    size="small" 
                    variant="tonal" 
                    class="font-weight-black text-uppercase"
                  >
                    <v-icon start :icon="item.is_skpd ? 'mdi-office-building' : 'mdi-domain-plus'" size="14"></v-icon>
                    {{ item.is_skpd ? 'SKPD Utama' : 'Unit Kerja' }}
                  </v-chip>
                </template>

                <template v-slot:item.actions="{ item }">
                   <v-btn icon="mdi-open-in-new" variant="text" color="primary" size="small" @click="showDetails(item)"></v-btn>
                </template>
                
                <template v-slot:no-data>
                   <div class="text-center py-16">
                      <v-icon size="64" color="medium-emphasis" class="mb-4">mdi-database-search-outline</v-icon>
                      <div class="text-h6 font-weight-bold text-medium-emphasis">Data unit tidak ditemukan</div>
                   </div>
                </template>
              </v-data-table>
            </v-card>
          </v-col>
        </v-row>

        <!-- Loading State -->
        <v-row v-else>
          <v-col v-for="i in 4" :key="i" cols="12" sm="6" md="3">
            <v-skeleton-loader type="card" class="glass-panel rounded-xl"></v-skeleton-loader>
          </v-col>
          <v-col cols="12" class="mt-6">
            <v-skeleton-loader type="table" class="glass-panel rounded-xl"></v-skeleton-loader>
          </v-col>
        </v-row>
      </v-container>
    </v-main>

    <!-- Detail Dialog -->
    <v-dialog v-model="detailDialog" max-width="1000px" transition="dialog-bottom-transition" scrollable>
      <v-card class="glass-panel overflow-hidden border-0">
        <v-toolbar flat class="px-6 bg-dashboard border-b py-4">
          <v-avatar color="primary" size="40" class="mr-4">
            <v-icon color="white">mdi-office-building-cog</v-icon>
          </v-avatar>
          <v-toolbar-title class="font-weight-black">Informasi Unit</v-toolbar-title>
          <v-spacer></v-spacer>
          <v-btn icon="mdi-close" variant="text" @click="detailDialog = false" rounded="pill"></v-btn>
        </v-toolbar>
        
        <v-card-text class="pa-8" v-if="selectedSkpd">
           <div class="d-md-flex align-end mb-10">
              <div class="mr-6 mb-4 mb-md-0">
                 <v-avatar color="primary" size="80" class="elevation-10" rounded="xl">
                    <v-icon color="white" size="40">mdi-domain</v-icon>
                 </v-avatar>
              </div>
              <div class="flex-grow-1">
                 <h2 class="text-h4 font-weight-black text-high-emphasis mb-2 leading-tight">{{ selectedSkpd.nama_skpd }}</h2>
                 <div class="d-flex align-center">
                    <v-chip color="primary" variant="flat" size="small" class="font-weight-bold mr-3">KODE: {{ selectedSkpd.kode_skpd }}</v-chip>
                    <span class="text-subtitle-1 text-medium-emphasis font-weight-medium">SKPD Registrasi Pemerintah Provinsi</span>
                 </div>
              </div>
           </div>

           <v-divider class="mb-8 opacity-10"></v-divider>

           <div class="d-flex align-center justify-space-between mb-6">
              <h3 class="text-h6 font-weight-black text-high-emphasis">
                Daftar Pegawai Terdaftar
                <v-chip class="ml-2 font-weight-black" size="x-small" color="primary">{{ unitEmployees.length }} ORANG</v-chip>
              </h3>
              <v-btn color="primary" variant="flat" rounded="pill" class="font-weight-black" 
                     :to="'/employees?skpd_id=' + selectedSkpd.id_skpd" prepend-icon="mdi-account-group">
                KELOLA SELURUH PEGAWAI
              </v-btn>
           </div>

           <div v-if="employeesLoading" class="text-center py-16">
              <v-progress-circular indeterminate color="primary" size="64" width="6"></v-progress-circular>
              <div class="mt-4 text-h6 font-weight-bold text-medium-emphasis">Mengambil data personil...</div>
           </div>
           
           <div v-else-if="unitEmployees.length > 0">
              <v-table class="bg-transparent border rounded-xl overflow-hidden">
                <thead>
                  <tr class="bg-surface-variant text-uppercase">
                    <th class="font-weight-black text-caption py-4">NAMA PEGAWAI</th>
                    <th class="font-weight-black text-caption py-4 text-center">NIP</th>
                    <th class="font-weight-black text-caption py-4">JABATAN</th>
                    <th class="font-weight-black text-caption py-4 text-right">STATUS</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="emp in unitEmployees" :key="emp.id" class="hover-row">
                    <td class="font-weight-bold py-4 text-high-emphasis">{{ emp.nama }}</td>
                    <td class="text-center font-weight-medium text-medium-emphasis">{{ emp.nip }}</td>
                    <td class="text-caption font-weight-medium text-medium-emphasis">{{ emp.jabatan }}</td>
                    <td class="text-right">
                       <v-chip size="x-small" color="success" variant="flat" class="font-weight-black">AKTIF</v-chip>
                    </td>
                  </tr>
                </tbody>
              </v-table>
           </div>

           <div v-else class="text-center py-16 border-dashed rounded-xl bg-surface-variant opacity-50">
              <v-icon size="64" color="medium-emphasis" class="mb-4">mdi-account-off-outline</v-icon>
              <div class="text-h6 font-weight-bold text-medium-emphasis">Tidak ada pegawai di unit ini.</div>
           </div>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Snackbar -->
    <v-snackbar v-model="snackbar" :timeout="3000" color="primary" rounded="xl" elevation="12" class="mb-4">
      <div class="d-flex align-center">
        <v-icon start icon="mdi-information" class="mr-3"></v-icon>
        <span class="font-weight-bold">{{ snackbarTitle }}</span>
      </div>
      <template v-slot:actions>
        <v-btn variant="text" @click="snackbar = false" icon="mdi-close"></v-btn>
      </template>
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '../api'
import Navbar from '../components/Navbar.vue'
import Sidebar from '../components/Sidebar.vue'

const loading = ref(true)
const employeesLoading = ref(false)
const search = ref('')
const skpds = ref([])
const snackbar = ref(false)
const snackbarTitle = ref('')
const detailDialog = ref(false)
const selectedSkpd = ref(null)
const unitEmployees = ref([])

const showDetails = async (item) => {
  selectedSkpd.value = item
  detailDialog.value = true
  employeesLoading.value = true
  
  try {
    const response = await api.get('/employees', {
      params: { skpd_id: item.id_skpd, per_page: 50 }
    })
    unitEmployees.value = response.data.data.data
  } catch (err) {
    console.error('Error fetching unit employees:', err)
  } finally {
    employeesLoading.value = false
  }
}

const headers = [
  { title: 'KODE UNIT', key: 'kode_skpd', sortable: true, width: '180px', align: 'start', className: 'font-weight-black' },
  { title: 'NAMA INSTANSI / UNIT KERJA', key: 'nama_skpd', sortable: true },
  { title: 'KLASIFIKASI', key: 'is_skpd', sortable: true, width: '180px' },
  { title: '', key: 'actions', sortable: false, align: 'end', width: '80px' },
]

const filteredSkpds = computed(() => {
  if (!search.value) return skpds.value
  const s = search.value.toLowerCase()
  return skpds.value.filter(item => 
    item.nama_skpd?.toLowerCase().includes(s) || 
    item.kode_skpd?.toLowerCase().includes(s)
  )
})

onMounted(async () => {
  try {
    const response = await api.get('/skpd')
    skpds.value = response.data.data
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.modern-dashboard {
  min-height: 100vh;
}

.bg-dashboard {
  background-color: rgb(var(--v-theme-background));
  background-image: 
    radial-gradient(at 0% 0%, rgba(var(--v-theme-primary), 0.05) 0, transparent 50%),
    radial-gradient(at 100% 100%, rgba(var(--v-theme-info), 0.05) 0, transparent 50%);
}

.glass-panel {
  background: rgba(var(--v-theme-surface), 0.8) !important;
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(var(--v-border-color), 0.1) !important;
  border-radius: 24px !important;
}

.org-icon-wrapper {
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(var(--v-theme-primary), 0.08);
  border-radius: 12px;
}

.search-field {
  max-width: 400px;
  width: 100%;
}

.search-field :deep(.v-field) {
  border-radius: 50px !important;
  background: rgba(var(--v-theme-surface), 0.7) !important;
  border: 1px solid rgba(var(--v-border-color), 0.1) !important;
}

.featured-org-card {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.featured-org-card:hover {
  transform: translateY(-8px);
  background: rgba(var(--v-theme-surface), 1) !important;
  box-shadow: 0 20px 40px -20px rgba(var(--v-theme-primary), 0.3) !important;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.border-b {
  border-bottom: 1px solid rgba(var(--v-border-color), 0.1) !important;
}

.border-dashed {
  border: 2px dashed rgba(var(--v-border-color), 0.2) !important;
}

.hover-row:hover {
  background: rgba(var(--v-theme-primary), 0.02);
}

:deep(.v-data-table-header) {
  background: rgba(var(--v-theme-on-surface), 0.03);
}

:deep(.v-data-table-header th) {
  font-weight: 900 !important;
  text-transform: uppercase;
  font-size: 0.7rem !important;
  letter-spacing: 0.075em;
  color: rgb(var(--v-theme-medium-emphasis)) !important;
}

.leading-tight {
  line-height: 1.2;
}
</style>
