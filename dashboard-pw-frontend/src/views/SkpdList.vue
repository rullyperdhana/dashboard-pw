<template>
  <v-app class="modern-bg">
    <Navbar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />
    <Sidebar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />

    <v-main>
      <v-container fluid class="pa-8">
        <!-- Search and Global Stats -->
        <v-row class="mb-6 align-center">
          <v-col cols="12" md="6">
            <h1 class="text-h4 font-weight-bold">Units & SKPD</h1>
            <p class="text-grey-darken-1">Manage and view all registered administrative units.</p>
          </v-col>
          <v-col cols="12" md="6" class="text-md-right">
             <v-text-field
                v-model="search"
                prepend-inner-icon="mdi-magnify"
                label="Quick search units..."
                variant="solo-filled"
                density="comfortable"
                rounded="pill"
                hide-details
                flat
                style="max-width: 400px; display: inline-block; width: 100%;"
              ></v-text-field>
          </v-col>
        </v-row>

        <v-row v-if="!loading">
          <!-- Featured SKPD Cards (SKPD Utama) -->
          <v-col v-for="item in filteredSkpds.filter(s => s.is_skpd).slice(0, 6)" :key="item.id_skpd" cols="12" sm="6" md="4">
            <v-card class="glass-card rounded-xl pa-2 featured-card" elevation="0">
              <v-card-text>
                <div class="d-flex align-center mb-4">
                  <v-avatar color="primary-lighten-5" rounded="lg" size="48">
                    <v-icon color="primary">mdi-city-variant-outline</v-icon>
                  </v-avatar>
                  <v-spacer></v-spacer>
                  <v-chip color="success" size="x-small" variant="flat" class="font-weight-bold px-3">MAJOR UNIT</v-chip>
                </div>
                <div class="text-h6 font-weight-bold mb-1 text-truncate">{{ item.nama_skpd }}</div>
                <div class="text-caption text-grey mb-4">Code: {{ item.kode_skpd }}</div>
                
                <v-divider class="mb-4"></v-divider>
                
                <div class="d-flex align-center justify-space-between">
                  <div class="text-caption text-grey">Data Integrity</div>
                  <v-icon color="success" size="16">mdi-check-decagram</v-icon>
                </div>
              </v-card-text>
              <v-card-actions class="px-4 pb-4">
                <v-btn block variant="tonal" color="primary" rounded="lg" size="small" @click="showDetails(item)">VIEW DETAILS</v-btn>
              </v-card-actions>
            </v-card>
          </v-col>

          <!-- Full List Table -->
          <v-col cols="12" class="mt-6">
            <v-card class="glass-card rounded-xl overflow-hidden" elevation="0">
              <v-toolbar color="transparent" flat class="px-6 py-4 border-b">
                <v-toolbar-title class="font-weight-bold text-subtitle-1">Complete Registry ({{ filteredSkpds.length }} Units)</v-toolbar-title>
              </v-toolbar>

              <v-data-table
                :headers="headers"
                :items="filteredSkpds"
                class="modern-table"
                hover
                :items-per-page="10"
              >
                <template v-slot:item.nama_skpd="{ item }">
                  <div class="font-weight-bold text-body-2 py-3">{{ item.nama_skpd }}</div>
                </template>

                <template v-slot:item.is_skpd="{ item }">
                  <v-chip 
                    :color="item.is_skpd ? 'primary-lighten-4' : 'grey-lighten-4'" 
                    :text-color="item.is_skpd ? 'primary-darken-2' : 'grey-darken-3'"
                    size="small" 
                    variant="flat" 
                    class="font-weight-bold"
                  >
                    {{ item.is_skpd ? 'SKPD Utama' : 'Unit Kerja' }}
                  </v-chip>
                </template>

                <template v-slot:item.actions="{ item }">
                   <v-btn icon="mdi-eye-outline" variant="text" color="primary" size="small" @click="showDetails(item)"></v-btn>
                </template>
                
                <template v-slot:no-data>
                   <div class="text-center py-12 text-grey">No matching institutions found.</div>
                </template>
              </v-data-table>
            </v-card>
          </v-col>
        </v-row>

        <v-row v-else>
          <v-col v-for="i in 6" :key="i" cols="12" sm="6" md="4">
            <v-skeleton-loader type="card" class="rounded-xl"></v-skeleton-loader>
          </v-col>
        </v-row>
      </v-container>
    </v-main>

    <!-- SKPD Detail Dialog -->
    <v-dialog v-model="detailDialog" max-width="900px" scrollable>
      <v-card class="rounded-xl overflow-hidden glass-card">
        <v-toolbar color="primary" class="px-4">
          <v-icon color="white" class="mr-3">mdi-office-building-cog</v-icon>
          <v-toolbar-title class="text-white font-weight-bold">Unit Information</v-toolbar-title>
          <v-spacer></v-spacer>
          <v-btn icon="mdi-close" color="white" variant="text" @click="detailDialog = false"></v-btn>
        </v-toolbar>
        
        <v-card-text class="pa-6" v-if="selectedSkpd">
           <div class="mb-6">
              <div class="d-flex align-center">
                 <v-avatar color="primary-lighten-5" size="64" class="mr-4" rounded="lg">
                    <v-icon color="primary" size="32">mdi-city-variant</v-icon>
                 </v-avatar>
                 <div>
                    <h2 class="text-h5 font-weight-black">{{ selectedSkpd.nama_skpd }}</h2>
                    <div class="text-body-1 text-grey font-weight-bold">CODE: {{ selectedSkpd.kode_skpd }}</div>
                 </div>
              </div>
           </div>

           <v-divider class="mb-6"></v-divider>

           <div class="d-flex align-center justify-space-between mb-4">
              <h3 class="text-subtitle-1 font-weight-bold">Registered Employees ({{ unitEmployees.length }})</h3>
              <v-btn color="primary" variant="text" size="small" :to="'/employees?skpd_id=' + selectedSkpd.id_skpd" prepend-icon="mdi-account-multiple-outline">MANAGE ALL</v-btn>
           </div>

           <div v-if="employeesLoading" class="text-center py-12">
              <v-progress-circular indeterminate color="primary"></v-progress-circular>
              <div class="mt-2 text-caption text-grey">Loading personnel data...</div>
           </div>
           
           <div v-else-if="unitEmployees.length > 0">
              <v-table density="comfortable" class="border rounded-lg overflow-hidden">
                <thead>
                  <tr class="bg-grey-lighten-4">
                    <th class="font-weight-bold">NAME</th>
                    <th class="font-weight-bold">NIP</th>
                    <th class="font-weight-bold">POSITION</th>
                    <th class="font-weight-bold">STATUS</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="emp in unitEmployees" :key="emp.id">
                    <td class="font-weight-bold">{{ emp.nama }}</td>
                    <td>{{ emp.nip }}</td>
                    <td class="text-caption">{{ emp.jabatan }}</td>
                    <td>
                       <v-chip size="x-small" color="success" variant="flat">ACTIVE</v-chip>
                    </td>
                  </tr>
                </tbody>
              </v-table>
           </div>

           <div v-else class="text-center py-12 bg-grey-lighten-4 rounded-xl">
              <v-icon size="48" color="grey-lighten-1">mdi-account-off-outline</v-icon>
              <div class="text-grey mt-2">No employees currently assigned to this unit.</div>
           </div>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Global Feedback Snackbar -->
    <v-snackbar v-model="snackbar" :timeout="3000" color="primary" rounded="lg" elevation="24">
      <div class="d-flex align-center">
        <v-icon class="mr-3">mdi-office-building-cog-outline</v-icon>
        <div>
          <div class="font-weight-bold">{{ snackbarTitle }}</div>
          <div class="text-caption">This feature is coming soon in the next update.</div>
        </div>
      </div>
      <template v-slot:actions>
        <v-btn variant="text" @click="snackbar = false">CLOSE</v-btn>
      </template>
    </v-snackbar>
  </v-app>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '../api'
import ThemeToggle from '../components/ThemeToggle.vue'
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

const showComingSoon = (feature) => {
  snackbarTitle.value = feature
  snackbar.value = true
}

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
  { title: 'INSTITUTION CODE', key: 'kode_skpd', sortable: true, width: '200px' },
  { title: 'OFFICIAL NAME', key: 'nama_skpd', sortable: true },
  { title: 'TYPE', key: 'is_skpd', sortable: true, width: '150px' },
  { title: '', key: 'actions', sortable: false, align: 'end' },
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
.modern-bg {
  background-color: #f8fafc !important;
}

.glass-nav {
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important;
  z-index: 1000;
}

.glass-card {
  background: white !important;
  border: 1px solid rgba(0, 0, 0, 0.05) !important;
}

.featured-card {
  transition: transform 0.2s;
}

.featured-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.08) !important;
}

.text-primary-gradient {
  background: linear-gradient(45deg, #1867C0, #5CBBF6);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  font-weight: 800;
}

/* Table Styling */
:deep(.modern-table) {
  background: transparent !important;
}

:deep(.v-data-table-header) {
  background: rgba(0, 0, 0, 0.02);
}

:deep(.v-data-table-header th) {
  font-weight: 700 !important;
  color: #64748b !important;
  text-transform: uppercase;
  font-size: 0.75rem !important;
  letter-spacing: 0.05em;
}

.border-b {
  border-bottom: 1px solid rgba(0, 0, 0, 0.05) !important;
}
</style>
