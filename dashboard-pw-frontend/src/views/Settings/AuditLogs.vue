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
              <v-icon start color="primary" size="36">mdi-auto-fix</v-icon>
              Log Aktivitas Sistem
            </h1>
            <p class="text-subtitle-1 text-grey-darken-1">Pantau riwayat perubahan parameter dan aktivitas administratif.</p>
          </v-col>
        </v-row>

        <!-- Search & Filter -->
        <v-card class="glass-card rounded-xl mb-6 pa-4 shadow-premium" elevation="0">
          <v-row dense align="center">
            <v-col cols="12" md="6">
              <v-text-field
                v-model="search"
                prepend-inner-icon="mdi-magnify"
                label="Cari username, aksi, atau deskripsi..."
                hide-details
                variant="outlined"
                density="comfortable"
                rounded="lg"
                @keyup.enter="fetchLogs(1)"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="filterAction"
                :items="actionItems"
                label="Filter Aksi"
                variant="outlined"
                density="comfortable"
                rounded="lg"
                hide-details
                clearable
                @update:model-value="fetchLogs(1)"
              ></v-select>
            </v-col>
            <v-col cols="12" md="3">
              <v-btn color="primary" block size="large" rounded="lg" @click="fetchLogs(1)" :loading="loading">
                Segarkan Data
              </v-btn>
            </v-col>
          </v-row>
        </v-card>

        <!-- Logs Table -->
        <v-card class="glass-card rounded-xl shadow-premium overflow-hidden" elevation="0">
          <v-data-table-server
            v-model:items-per-page="itemsPerPage"
            :headers="headers"
            :items="logs"
            :items-length="totalItems"
            :loading="loading"
            @update:options="loadItems"
            hover
            show-expand
          >
            <template v-slot:item.action="{ item }">
              <v-chip
                :color="getActionColor(item.action)"
                size="small"
                variant="flat"
                class="font-weight-bold"
              >
                {{ formatAction(item.action) }}
              </v-chip>
            </template>

            <template v-slot:item.username="{ item }">
              <div class="d-flex align-center">
                <v-avatar size="24" color="grey-lighten-3" class="mr-2">
                  <v-icon size="14" color="grey-darken-1">mdi-account</v-icon>
                </v-avatar>
                <span class="font-weight-medium">{{ item.username }}</span>
              </div>
            </template>

            <template v-slot:item.created_at="{ item }">
              <span class="text-caption text-medium-emphasis">{{ formatDate(item.created_at) }}</span>
            </template>

            <!-- Expanded Row for Details -->
            <template v-slot:expanded-row="{ columns, item }">
              <tr>
                <td :colspan="columns.length" class="bg-grey-lighten-4 pa-6">
                  <v-row>
                    <v-col cols="12" md="6">
                      <div class="text-overline mb-2">Nilai Lama (Old)</div>
                      <v-card variant="outlined" class="pa-4 bg-white" rounded="lg">
                        <pre class="json-viewer">{{ formatJson(item.old_values) }}</pre>
                      </v-card>
                    </v-col>
                    <v-col cols="12" md="6">
                      <div class="text-overline mb-2">Nilai Baru (New)</div>
                      <v-card variant="outlined" class="pa-4 bg-white" rounded="lg">
                        <pre class="json-viewer text-success">{{ formatJson(item.new_values) }}</pre>
                      </v-card>
                    </v-col>
                  </v-row>
                  <div class="mt-4 d-flex align-center text-caption text-grey">
                    <v-icon size="14" class="mr-1">mdi-map-marker-outline</v-icon>
                    IP: {{ item.ip_address }} 
                    <v-divider vertical class="mx-3"></v-divider>
                    <v-icon size="14" class="mr-1">mdi-table</v-icon>
                    Table: {{ item.table_name || 'N/A' }} 
                    <v-divider vertical class="mx-3"></v-divider>
                    <v-icon size="14" class="mr-1">mdi-identifier</v-icon>
                    ID: {{ item.record_id || 'N/A' }}
                  </div>
                </td>
              </tr>
            </template>
          </v-data-table-server>
        </v-card>
      </v-container>
    </v-main>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Sidebar from '../../components/Sidebar.vue'
import Navbar from '../../components/Navbar.vue'
import api from '../../api'

const loading = ref(false)
const logs = ref([])
const totalItems = ref(0)
const itemsPerPage = ref(20)
const search = ref('')
const filterAction = ref(null)

const actionItems = [
  { title: 'Update Parameter', value: 'update_payroll_parameter' },
  { title: 'Trigger Rekonsiliasi', value: 'trigger_reconciliation' },
  { title: 'Export Data', value: 'export_data' },
  { title: 'Login', value: 'login' },
]

const headers = [
  { title: 'Waktu', key: 'created_at', sortable: false, width: '180px' },
  { title: 'User', key: 'username', sortable: false },
  { title: 'Aksi', key: 'action', sortable: false },
  { title: 'Keterangan', key: 'description', sortable: false },
  { title: '', key: 'data-table-expand' },
]

const getActionColor = (action) => {
  if (action?.includes('update')) return 'orange'
  if (action?.includes('delete')) return 'error'
  if (action?.includes('insert') || action?.includes('create')) return 'success'
  if (action?.includes('export')) return 'info'
  return 'primary'
}

const formatAction = (action) => {
  return action?.replace(/_/g, ' ').toUpperCase() || 'UNKNOWN'
}

const formatJson = (val) => {
  if (!val) return 'Tidak ada data detail'
  try {
    return JSON.stringify(val, null, 2)
  } catch (e) {
    return val
  }
}

const loadItems = ({ page, itemsPerPage }) => {
  fetchLogs(page)
}

const fetchLogs = async (page = 1) => {
  loading.value = true
  try {
    const response = await api.get('/audit-logs', {
      params: {
        page,
        per_page: itemsPerPage.value,
        search: search.value,
        action: filterAction.value
      }
    })
    logs.value = response.data.data.data
    totalItems.value = response.data.data.total
  } catch (error) {
    console.error('Error fetching audit logs:', error)
  } finally {
    loading.value = false
  }
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  }).format(date)
}
</script>

<style scoped>
.glass-card {
  background: rgba(255, 255, 255, 0.8) !important;
  backdrop-filter: blur(12px) !important;
  border: 1px solid rgba(255, 255, 255, 0.3) !important;
}

.shadow-premium {
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05) !important;
}

.json-viewer {
  font-family: 'Fira Code', 'Courier New', Courier, monospace;
  font-size: 11px;
  line-height: 1.4;
  white-space: pre-wrap;
  word-wrap: break-word;
}
</style>
