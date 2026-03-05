<template>
  <v-app-bar flat class="glass-nav px-4">
    <v-app-bar-title class="font-weight-bold primary--text">
      <v-icon color="primary" class="mr-2">mdi-view-dashboard-outline</v-icon>
      Payroll<span class="text-primary-gradient">PPPK</span>
    </v-app-bar-title>
    
    <v-spacer></v-spacer>
    
    <div class="d-flex align-center mr-4">
      <v-icon size="18" class="mr-2 text-medium-emphasis">mdi-clock-outline</v-icon>
      <div style="line-height: 1.2;">
        <div class="text-caption font-weight-bold">{{ currentTime }}</div>
        <div class="text-caption text-medium-emphasis" style="font-size: 10px;">{{ currentDate }}</div>
      </div>
    </div>

    <ThemeToggle />
    
    <v-btn icon flat class="mr-2" @click="$emit('show-coming-soon', 'Notifications')">
      <v-badge dot color="error">
        <v-icon>mdi-bell-outline</v-icon>
      </v-badge>
    </v-btn>

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
        <v-list-item @click="$emit('show-coming-soon', 'Account Settings')" rounded class="mb-1">
           <template v-slot:prepend>
             <v-icon color="primary">mdi-account-cog-outline</v-icon>
           </template>
           <v-list-item-title>Account Settings</v-list-item-title>
        </v-list-item>
        
        <v-list-item @click="handleLogout" rounded class="mb-1">
          <template v-slot:prepend>
            <v-icon color="error">mdi-logout-variant</v-icon>
          </template>
          <v-list-item-title class="error--text">Logout</v-list-item-title>
        </v-list-item>
      </v-list>
    </v-menu>
  </v-app-bar>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import ThemeToggle from './ThemeToggle.vue'

const router = useRouter()
const user = ref(null)
const currentTime = ref('')
const currentDate = ref('')
let clockInterval = null

const updateClock = () => {
  const now = new Date()
  currentTime.value = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
  currentDate.value = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
}

onMounted(() => {
  const userData = localStorage.getItem('user')
  if (userData) {
    user.value = JSON.parse(userData)
  }
  updateClock()
  clockInterval = setInterval(updateClock, 1000)
})

onUnmounted(() => {
  if (clockInterval) clearInterval(clockInterval)
})

const handleLogout = () => {
  localStorage.removeItem('token')
  localStorage.removeItem('user')
  router.push('/login')
}

defineEmits(['show-coming-soon'])
</script>

<style scoped>
.glass-nav {
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  background-color: rgba(var(--v-theme-surface), 0.8) !important;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.05) !important;
  z-index: 1000;
}

.text-primary-gradient {
  background: linear-gradient(45deg, #1867C0, #5CBBF6);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  font-weight: 800;
}
</style>
