<template>
  <v-app-bar flat class="glass-nav px-4">
    <v-app-bar-nav-icon @click="toggleSidebar" class="mr-2"></v-app-bar-nav-icon>
    <v-app-bar-title>
      <v-breadcrumbs :items="breadcrumbItems" class="pa-0 text-caption font-weight-bold">
        <template v-slot:divider>
          <v-icon icon="mdi-chevron-right" size="14"></v-icon>
        </template>
      </v-breadcrumbs>
    </v-app-bar-title>
    
    <v-spacer></v-spacer>

    <!-- Global Search -->
    <v-responsive max-width="300" class="mx-4 d-none d-md-flex">
      <v-text-field
        v-model="searchQuery"
        prepend-inner-icon="mdi-magnify"
        placeholder="Cari fitur atau data..."
        variant="solo-filled"
        density="compact"
        flat
        hide-details
        rounded="lg"
        @keyup.enter="handleGlobalSearch"
      ></v-text-field>
    </v-responsive>
    
    <div class="d-flex align-center mr-4">
      <v-icon size="18" class="mr-2 text-medium-emphasis">mdi-clock-outline</v-icon>
      <div style="line-height: 1.2;">
        <div class="text-caption font-weight-bold">{{ currentTime }}</div>
        <div class="text-caption text-medium-emphasis" style="font-size: 10px;">{{ currentDate }}</div>
      </div>
    </div>

    <ThemeToggle />
    
    <!-- Notifications for Upload Jobs -->
    <v-menu offset-y width="300">
      <template v-slot:activator="{ props }">
        <v-btn icon flat class="mr-2" v-bind="props">
          <v-badge :content="pendingJobsCount" :model-value="pendingJobsCount > 0" color="error">
            <v-icon>mdi-bell-outline</v-icon>
          </v-badge>
        </v-btn>
      </template>
      <v-card class="rounded-xl overflow-hidden">
        <v-list density="comfortable">
          <v-list-subheader class="text-uppercase font-weight-bold text-caption">Notifikasi Upload</v-list-subheader>
          <v-divider></v-divider>
          
          <template v-if="uploadJobs.length > 0">
            <v-list-item v-for="job in uploadJobs" :key="job.id" class="py-3">
              <template v-slot:prepend>
                <v-icon :color="getJobColor(job.status)" :icon="getJobIcon(job.status)"></v-icon>
              </template>
              <v-list-item-title class="text-body-2 font-weight-bold">{{ job.filename }}</v-list-item-title>
              <v-list-item-subtitle class="text-caption">{{ job.status_text }}</v-list-item-subtitle>
              <template v-slot:append>
                <span class="text-caption text-disabled">{{ formatTime(job.created_at) }}</span>
              </template>
            </v-list-item>
          </template>
          <v-list-item v-else>
            <v-list-item-title class="text-center text-caption text-disabled py-4">Tidak ada aktivitas terbaru</v-list-item-title>
          </v-list-item>
        </v-list>
      </v-card>
    </v-menu>

    <!-- User Menu -->
    <v-menu offset-y transition="scale-transition">
      <template v-slot:activator="{ props }">
        <v-btn v-bind="props" variant="text" class="rounded-lg px-2">
          <v-avatar size="32" color="primary" class="mr-2">
            <span class="text-white text-caption">{{ user?.username?.charAt(0).toUpperCase() }}</span>
          </v-avatar>
          <span class="d-none d-sm-inline">{{ user?.username }}</span>
          <v-icon small class="ml-1">mdi-chevron-down</v-icon>
        </v-btn>
      </template>
      <v-list class="pa-2 rounded-lg elevation-4">
        <v-list-item @click="settingsDialog = true" rounded class="mb-1">
           <template v-slot:prepend>
             <v-icon color="primary">mdi-account-cog-outline</v-icon>
           </template>
           <v-list-item-title>Pengaturan Akun</v-list-item-title>
        </v-list-item>
        
        <v-list-item @click="handleLogout" rounded class="mb-1">
          <template v-slot:prepend>
            <v-icon color="error">mdi-logout-variant</v-icon>
          </template>
          <v-list-item-title class="text-error">Keluar</v-list-item-title>
        </v-list-item>
      </v-list>
    </v-menu>

    <!-- Account Settings Dialog -->
    <AccountSettingsDialog
      v-model="settingsDialog"
      :user="user"
      @user-updated="handleUserUpdated"
    />
  </v-app-bar>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '../api'
import ThemeToggle from './ThemeToggle.vue'
import AccountSettingsDialog from './AccountSettingsDialog.vue'
import { toggleSidebar } from '../utils/sidebarState'

const router = useRouter()
const route = useRoute()
const user = ref(null)
const settingsDialog = ref(false)
const currentTime = ref('')
const currentDate = ref('')
const searchQuery = ref('')
const uploadJobs = ref([])
let clockInterval = null
let pollInterval = null

// Breadcrumb Logic
const breadcrumbItems = computed(() => {
  const items = [{ title: 'Home', disabled: false, to: '/' }]
  if (route.meta && route.meta.breadcrumb) {
    items.push({
      title: route.meta.breadcrumb,
      disabled: true,
      to: route.path
    })
  }
  return items
})

const pendingJobsCount = computed(() => {
  return uploadJobs.value.filter(j => j.status === 'pending' || j.status === 'processing').length
})

const updateClock = () => {
  const now = new Date()
  currentTime.value = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
  currentDate.value = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
}

const fetchUploadJobs = async () => {
  try {
    const res = await api.get('/upload-jobs?limit=5')
    if (res.data.success) {
      uploadJobs.value = res.data.data
    }
  } catch (e) {
    console.error('Failed to fetch notifications', e)
  }
}

const handleGlobalSearch = () => {
  if (!searchQuery.value) return
  // Logic simple search: cari di daftar pegawai
  router.push({ name: 'Employees', query: { search: searchQuery.value } })
  searchQuery.value = ''
}

const getJobColor = (status) => {
  if (status === 'completed') return 'success'
  if (status === 'failed') return 'error'
  return 'primary'
}

const getJobIcon = (status) => {
  if (status === 'completed') return 'mdi-check-circle'
  if (status === 'failed') return 'mdi-alert-circle'
  return 'mdi-progress-clock'
}

const formatTime = (dateStr) => {
  const date = new Date(dateStr)
  return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
}

onMounted(() => {
  const userData = localStorage.getItem('user')
  if (userData) {
    user.value = JSON.parse(userData)
  }
  updateClock()
  clockInterval = setInterval(updateClock, 1000)
  
  fetchUploadJobs()
  pollInterval = setInterval(fetchUploadJobs, 15000) // Poll every 15s
})

onUnmounted(() => {
  if (clockInterval) clearInterval(clockInterval)
  if (pollInterval) clearInterval(pollInterval)
})

const handleUserUpdated = (updatedUser) => {
  localStorage.setItem('user', JSON.stringify(updatedUser))
  user.value = updatedUser
}

const handleLogout = async () => {
  try {
    await api.post('/logout')
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    router.push('/login')
  } catch (e) {
    console.error('Logout failed', e)
    // force logout anyway
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    router.push('/login')
  }
}

defineEmits(['show-coming-soon'])
</script>

<style scoped>
</style>

