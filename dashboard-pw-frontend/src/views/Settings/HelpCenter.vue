<template>
  <div class="modern-dashboard">
    <Navbar />
    <Sidebar />
    <v-main class="bg-light">
      <v-container fluid class="pa-6">
        <!-- Header -->
        <div class="d-flex align-center mb-6">
          <div class="icon-box mr-4">
            <v-icon icon="mdi-help-circle-outline" color="primary" size="32"></v-icon>
          </div>
          <div>
            <h1 class="text-h4 font-weight-bold mb-1">Pusat Bantuan</h1>
            <p class="text-subtitle-1 text-medium-emphasis">
              Cari panduan dan solusi teknis penggunaan aplikasi.
            </p>
          </div>
        </div>

        <!-- Search Card -->
        <v-card class="glass-card mb-8 pa-6 border-0 text-center" elevation="0">
          <h2 class="text-h5 font-weight-bold mb-4">Apa yang bisa kami bantu?</h2>
          <v-row justify="center">
            <v-col cols="12" md="8">
              <v-text-field
                v-model="search"
                placeholder="Ketik kata kunci (misal: import, mapping, audit)..."
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
                density="comfortable"
                hide-details
                rounded="pill"
                class="bg-white search-input shadow-sm"
                @input="debouncedSearch"
                clearable
              ></v-text-field>
            </v-col>
          </v-row>
        </v-card>

        <!-- Loading State -->
        <div v-if="loading" class="text-center py-12">
          <v-progress-circular indeterminate color="primary" size="64"></v-progress-circular>
          <div class="mt-4 text-medium-emphasis">Mencari artikel...</div>
        </div>

        <!-- Articles Grid -->
        <v-row v-else-if="articles.length > 0">
          <v-col v-for="article in articles" :key="article.id" cols="12" md="6" lg="4">
            <v-card class="glass-card h-100 border-0 d-flex flex-column hover-card" elevation="0" @click="viewDetail(article)">
              <v-card-text class="pa-6">
                <v-chip size="x-small" color="primary" variant="tonal" class="mb-3">
                  {{ article.category }}
                </v-chip>
                <h3 class="text-h6 font-weight-bold mb-3">{{ article.title }}</h3>
                <p class="text-body-2 text-medium-emphasis line-clamp-3">
                  {{ stripHtml(article.content) }}
                </p>
              </v-card-text>
              <v-spacer></v-spacer>
              <v-divider class="opacity-10"></v-divider>
              <v-card-actions class="px-6 py-4">
                <span class="text-caption text-primary font-weight-bold">BACA SELENGKAPNYA</span>
                <v-spacer></v-spacer>
                <v-icon icon="mdi-arrow-right" size="18" color="primary"></v-icon>
              </v-card-actions>
            </v-card>
          </v-col>
        </v-row>

        <!-- Empty State -->
        <div v-else class="text-center py-12">
          <v-icon icon="mdi-text-search-variant" size="80" color="disabled" class="mb-4"></v-icon>
          <h3 class="text-h5 text-disabled mb-2">Artikel tidak ditemukan</h3>
          <p class="text-medium-emphasis">Coba kata kunci lain atau hubungi Tim Teknis.</p>
          <v-btn color="primary" variant="text" class="mt-2" @click="search = ''; fetchArticles()">
            Tampilkan Semua Artikel
          </v-btn>
        </div>
      </v-container>
    </v-main>

    <!-- Detail Dialog -->
    <v-dialog v-model="dialog" max-width="800" scrollable>
      <v-card v-if="selectedArticle" class="rounded-xl overflow-hidden shadow-xl">
        <v-card-title class="bg-primary text-white d-flex align-center pa-5">
          <v-icon icon="mdi-book-open-page-variant" class="mr-3"></v-icon>
          <span class="text-truncate">{{ selectedArticle.title }}</span>
          <v-spacer></v-spacer>
          <v-btn icon="mdi-close" variant="text" color="white" @click="dialog = false"></v-btn>
        </v-card-title>
        <v-card-text class="pa-8 help-content">
          <v-chip size="small" color="primary" variant="tonal" class="mb-5">
            Kategori: {{ selectedArticle.category }}
          </v-chip>
          <div v-html="renderMarkdown(selectedArticle.content)"></div>
        </v-card-text>
        <v-divider></v-divider>
        <v-card-actions class="pa-4 bg-light">
          <v-spacer></v-spacer>
          <v-btn variant="tonal" color="primary" rounded="pill" @click="dialog = false">Tutup Panduan</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../api'
import Sidebar from '../../components/Sidebar.vue'
import Navbar from '../../components/Navbar.vue'

const loading = ref(true)
const articles = ref([])
const search = ref('')
const dialog = ref(false)
const selectedArticle = ref(null)

let searchTimeout = null

const fetchArticles = async () => {
  loading.value = true
  try {
    const response = await api.get('/help', {
      params: { search: search.value }
    })
    if (response.data.success) {
      articles.value = response.data.data
    }
  } catch (error) {
    console.error('Error fetching articles:', error)
  } finally {
    loading.value = false
  }
}

const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchArticles()
  }, 500)
}

const viewDetail = (article) => {
  selectedArticle.value = article
  dialog.value = true
}

const stripHtml = (text) => {
  return text.replace(/#+ /g, '').replace(/\*/g, '').replace(/\[.*\]/g, '')
}

const renderMarkdown = (text) => {
  // Simple markdown to HTML conversion for bullet points and headers
  return text
    .replace(/^### (.*$)/gim, '<h3 class="text-h6 font-weight-bold mt-4 mb-2">$1</h3>')
    .replace(/^## (.*$)/gim, '<h2 class="text-h5 font-weight-bold mt-6 mb-3">$1</h2>')
    .replace(/^\* (.*$)/gim, '<li class="ml-4 mb-1">$1</li>')
    .replace(/^- (.*$)/gim, '<li class="ml-4 mb-1">$1</li>')
    .replace(/\*\*(.*)\*\*/gim, '<strong>$1</strong>')
    .replace(/\n/gim, '<br>')
}

onMounted(fetchArticles)
</script>

<style scoped>
.modern-dashboard {
  min-height: 100vh;
  background-color: rgb(var(--v-theme-background));
}

.icon-box {
  width: 56px;
  height: 56px;
  background: rgba(var(--v-theme-primary), 0.1);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.glass-card {
  background: rgba(var(--v-theme-surface), 0.85) !important;
  backdrop-filter: blur(12px);
  border: 1px solid rgba(var(--v-border-color), 0.08) !important;
  border-radius: 20px !important;
  transition: all 0.3s ease;
}

.hover-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 24px rgba(0,0,0,0.1) !important;
  cursor: pointer;
  background: rgb(var(--v-theme-surface)) !important;
}

.search-input :deep(.v-field__outline) {
  border-color: rgba(var(--v-theme-primary), 0.2) !important;
}

.search-input :deep(.v-field--focused .v-field__outline) {
  border-color: rgb(var(--v-theme-primary)) !important;
  border-width: 2px !important;
}

.line-clamp-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  line-clamp: 3;
  overflow: hidden;
}

.help-content {
  line-height: 1.6;
}

.help-content :deep(strong) {
  color: rgb(var(--v-theme-primary));
}
</style>
