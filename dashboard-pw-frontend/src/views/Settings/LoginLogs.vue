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
              <v-icon start color="primary" size="36">mdi-history</v-icon>
              Log Login Pengguna
            </h1>
            <p class="text-subtitle-1 text-grey-darken-1">Pantau aktivitas login dan percobaaan akses mencurigakan.</p>
          </v-col>
        </v-row>

        <!-- Search & Filter -->
        <v-card class="rounded-xl mb-6 pa-4" elevation="0" border>
          <v-row dense align="center">
            <v-col cols="12" md="4">
              <v-text-field
                v-model="search"
                prepend-inner-icon="mdi-magnify"
                label="Cari username atau IP..."
                hide-details
                variant="outlined"
                density="compact"
                @keyup.enter="fetchLogs"
              ></v-text-field>
            </v-col>
            <v-col cols="12" md="2">
              <v-btn color="primary" block @click="fetchLogs" :loading="loading">
                Cari
              </v-btn>
            </v-col>
          </v-row>
        </v-card>

        <!-- Logs Table -->
        <v-card class="rounded-xl" elevation="0" border>
          <v-data-table-server
            v-model:items-per-page="itemsPerPage"
            :headers="headers"
            :items="logs"
            :items-length="totalItems"
            :loading="loading"
            @update:options="loadItems"
            hover
          >
            <template v-slot:item.status="{ item }">
              <v-chip
                :color="item.status === 'success' ? 'success' : 'error'"
                size="small"
                variant="tonal"
                class="font-weight-bold"
              >
                {{ item.status.toUpperCase() }}
              </v-chip>
            </template>
            <template v-slot:item.created_at="{ item }">
              {{ formatDate(item.created_at) }}
            </template>
            <template v-slot:item.user_agent="{ item }">
              <div class="text-caption text-truncate" style="max-width: 200px">
                {{ item.user_agent }}
                <v-tooltip activator="parent" location="bottom">
                  {{ item.user_agent }}
                </v-tooltip>
              </div>
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

const headers = [
  { title: 'Waktu', key: 'created_at', sortable: false },
  { title: 'Username', key: 'username', sortable: false },
  { title: 'IP Address', key: 'ip_address', sortable: false },
  { title: 'Status', key: 'status', align: 'center', sortable: false },
  { title: 'Pesan', key: 'message', sortable: false },
  { title: 'User Agent', key: 'user_agent', sortable: false },
]

const loadItems = ({ page, itemsPerPage, sortBy }) => {
  fetchLogs(page)
}

const fetchLogs = async (page = 1) => {
  loading.value = true
  try {
    const response = await api.get('/login-logs', {
      params: {
        page,
        search: search.value
      }
    })
    logs.value = response.data.data.data
    totalItems.value = response.data.data.total
  } catch (error) {
    console.error('Error fetching logs:', error)
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
