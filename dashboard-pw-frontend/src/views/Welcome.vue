<template>
  <div class="welcome-wrapper">
    <Navbar />
    <Sidebar />

    <v-main class="bg-background">
      <v-container fluid class="welcome-container fill-height pa-4 pa-md-8">
        <!-- Subtle Animated Background (inside container) -->
        <div class="background-overlay"></div>
        <div class="subtle-blob blob-1"></div>
        <div class="subtle-blob blob-2"></div>

        <v-row align="center" justify="center" class="ma-0 w-100 position-relative">
          <v-col cols="12" sm="11" md="10" lg="9" xl="8">
            <!-- Main Glass Card -->
            <v-card class="glass-card pa-6 pa-md-10" elevation="0">
              <v-row>
                <v-col cols="12" class="d-flex align-center mb-6">
                  <v-avatar size="64" class="mr-4 glass-avatar">
                    <v-icon size="40" color="primary">mdi-account-circle</v-icon>
                  </v-avatar>
                  <div>
                    <h1 class="text-h4 font-weight-bold mb-1">
                      Selamat Datang, {{ userName }}
                    </h1>
                    <p class="text-subtitle-1 text-medium-emphasis mb-0">
                      Pusat Informasi & Navigasi Sistem
                    </p>
                  </div>
                </v-col>

                <!-- Announcements Section -->
                <v-col cols="12" md="7" class="mb-4">
                  <div class="d-flex align-center mb-4">
                    <v-icon color="primary" class="mr-2">mdi-bullhorn-variant</v-icon>
                    <h2 class="text-h5 font-weight-medium">Informasi Terbaru</h2>
                  </div>
                  
                  <div class="announcement-list-container">
                    <v-fade-transition group>
                      <div v-if="loading" key="loading" class="d-flex justify-center py-4">
                        <v-progress-circular indeterminate color="primary"></v-progress-circular>
                      </div>
                      
                      <div v-else-if="announcements.length === 0" key="empty" class="info-item glass-item pa-4">
                        <div class="d-flex align-center">
                          <v-icon color="grey" class="mr-3">mdi-information-outline</v-icon>
                          <span class="text-medium-emphasis">Belum ada pengumuman terbaru saat ini.</span>
                        </div>
                      </div>

                      <div 
                        v-for="item in announcements" 
                        :key="item.id" 
                        class="info-item glass-item pa-4 mb-3"
                        :class="item.type"
                      >
                        <div class="d-flex align-start">
                          <v-icon :color="getTypeColor(item.type)" class="mr-3 mt-1" size="24">
                            {{ getTypeIcon(item.type) }}
                          </v-icon>
                          <div class="flex-grow-1 min-width-0">
                            <div class="mb-2">
                              <h3 class="font-weight-bold text-subtitle-1 leading-tight mb-1" style="line-height: 1.25">
                                {{ item.title }}
                              </h3>
                              <div class="d-flex align-center text-caption text-disabled opacity-70">
                                <v-icon size="12" class="mr-1">mdi-calendar-clock</v-icon>
                                {{ formatDate(item.created_at) }}
                              </div>
                            </div>
                            <p class="text-body-2 text-medium-emphasis pre-line break-word mb-0">
                              {{ item.content }}
                            </p>
                          </div>
                        </div>
                      </div>
                    </v-fade-transition>
                  </div>
                </v-col>

                <!-- Navigation Section -->
                <v-col cols="12" md="5">
                  <div class="d-flex align-center mb-4">
                    <v-icon color="primary" class="mr-2">mdi-view-dashboard-outline</v-icon>
                    <h2 class="text-h5 font-weight-medium">Akses Utama</h2>
                  </div>
                  
                  <v-row>
                    <!-- Dashboard PPPK-PW -->
                    <v-col cols="12">
                      <v-hover v-slot="{ isHovering, props }">
                        <v-card
                          v-bind="props"
                          class="nav-card glass-item pa-5 d-flex align-center clickable"
                          :class="{ 'on-hover': isHovering }"
                          @click="goTo('/dashboard-pppk-pw')"
                        >
                          <v-avatar color="blue-lighten-4" class="mr-4" rounded="lg">
                            <v-icon color="blue-darken-2">mdi-view-dashboard</v-icon>
                          </v-avatar>
                          <div class="text-left">
                            <h3 class="text-subtitle-1 font-weight-bold">Dashboard PPPK-PW</h3>
                            <div class="text-caption text-medium-emphasis">Monitoring Gaji & Estimasi</div>
                          </div>
                          <v-spacer></v-spacer>
                          <v-icon color="grey">mdi-chevron-right</v-icon>
                        </v-card>
                      </v-hover>
                    </v-col>

                    <!-- Dashboard PNS -->
                    <v-col cols="12">
                      <v-hover v-slot="{ isHovering, props }">
                        <v-card
                          v-bind="props"
                          class="nav-card glass-item pa-5 d-flex align-center clickable"
                          :class="{ 'on-hover': isHovering }"
                          @click="goTo('/pns')"
                        >
                          <v-avatar color="purple-lighten-4" class="mr-4" rounded="lg">
                            <v-icon color="purple-darken-2">mdi-office-building</v-icon>
                          </v-avatar>
                          <div class="text-left">
                            <h3 class="text-subtitle-1 font-weight-bold">Dashboard PNS</h3>
                            <div class="text-caption text-medium-emphasis">Rincian Gaji & Laporan PNS</div>
                          </div>
                          <v-spacer></v-spacer>
                          <v-icon color="grey">mdi-chevron-right</v-icon>
                        </v-card>
                      </v-hover>
                    </v-col>

                    <!-- User Guide / Help -->
                    <v-col cols="12">
                      <v-hover v-slot="{ isHovering, props }">
                        <v-card
                          v-bind="props"
                          class="nav-card glass-item pa-5 d-flex align-center clickable"
                          :class="{ 'on-hover': isHovering }"
                          @click="goTo('/help')"
                        >
                          <v-avatar color="amber-lighten-4" class="mr-4" rounded="lg">
                            <v-icon color="amber-darken-2">mdi-help-circle</v-icon>
                          </v-avatar>
                          <div class="text-left">
                            <h3 class="text-subtitle-1 font-weight-bold">Pusat Bantuan</h3>
                            <div class="text-caption text-medium-emphasis">Panduan Penggunaan Sistem</div>
                          </div>
                          <v-spacer></v-spacer>
                          <v-icon color="grey">mdi-chevron-right</v-icon>
                        </v-card>
                      </v-hover>
                    </v-col>
                  </v-row>
                </v-col>
              </v-row>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </div>
</template>

<script>
import api from '@/api'
import Navbar from '../components/Navbar.vue'
import Sidebar from '../components/Sidebar.vue'

export default {
  name: 'Welcome',
  components: {
    Navbar,
    Sidebar
  },
  data: () => ({
    announcements: [],
    loading: true,
    userName: '',
    role: ''
  }),
  mounted() {
    this.userName = localStorage.getItem('user_name') || 'User'
    this.role = localStorage.getItem('user_role') || 'operator'
    this.fetchAnnouncements()
  },
  methods: {
    async fetchAnnouncements() {
      try {
        const response = await api.get('/announcements')
        this.announcements = response.data
      } catch (error) {
        console.error('Error fetching announcements:', error)
      } finally {
        this.loading = false
      }
    },
    goTo(path) {
      this.$router.push(path)
    },
    formatDate(dateStr) {
      if (!dateStr) return ''
      const date = new Date(dateStr)
      return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
      }).format(date)
    },
    getTypeColor(type) {
      const colors = {
        info: 'blue',
        success: 'green',
        warning: 'amber',
        error: 'red'
      }
      return colors[type] || 'blue'
    },
    getTypeIcon(type) {
      const icons = {
        info: 'mdi-information',
        success: 'mdi-check-circle',
        warning: 'mdi-alert',
        error: 'mdi-alert-circle'
      }
      return icons[type] || 'mdi-information'
    }
  }
}
</script>

<style scoped>
.welcome-wrapper {
  min-height: 100vh;
}

.welcome-container {
  position: relative;
  overflow: hidden;
}

.background-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: radial-gradient(circle at 50% 50%, rgba(var(--v-theme-primary), 0.05) 0%, rgba(0, 0, 0, 0) 70%);
  z-index: 1;
}

.subtle-blob {
  position: absolute;
  filter: blur(80px);
  z-index: 0;
  border-radius: 50%;
  opacity: 0.15;
}

.blob-1 {
  width: 300px;
  height: 300px;
  background: rgb(var(--v-theme-primary));
  top: 10%;
  left: 5%;
}

.blob-2 {
  width: 350px;
  height: 350px;
  background: rgb(var(--v-theme-secondary));
  bottom: 10%;
  right: 5%;
}

.glass-card {
  background: rgba(var(--v-theme-surface), 0.7) !important;
  backdrop-filter: blur(12px);
  border: 1px solid rgba(var(--v-border-color), 0.12);
  border-radius: 20px !important;
  z-index: 2;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.05) !important;
}

.glass-avatar {
  background: rgba(var(--v-theme-primary), 0.05);
  border: 1px solid rgba(var(--v-theme-primary), 0.1);
}

.glass-item {
  background: rgba(var(--v-theme-on-surface), 0.03);
  border: 1px solid rgba(var(--v-theme-on-surface), 0.08);
  border-radius: 12px;
  transition: all 0.2s ease;
}

.nav-card.clickable:hover {
  background: rgba(var(--v-theme-primary), 0.08);
  border-color: rgba(var(--v-theme-primary), 0.2);
  transform: translateX(4px);
}

.info-item {
  border-left: 3px solid rgb(var(--v-theme-primary));
}

.info-item.success { border-left-color: rgb(var(--v-theme-success)); }
.info-item.warning { border-left-color: rgb(var(--v-theme-warning)); }
.info-item.error { border-left-color: rgb(var(--v-theme-error)); }

.position-relative {
  position: relative;
  z-index: 3;
}

.min-width-0 {
  min-width: 0;
}

.break-word {
  word-break: break-word;
  overflow-wrap: break-word;
}

.leading-tight {
  line-height: 1.25 !important;
}

.opacity-70 {
  opacity: 0.7;
}
</style>
