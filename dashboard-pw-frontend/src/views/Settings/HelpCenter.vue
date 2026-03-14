<template>
  <div class="modern-dashboard">
    <Navbar />
    <Sidebar />
    <v-main class="bg-light">
      <v-container fluid class="pa-6">
        <!-- Header -->
        <div class="d-flex align-center mb-6">
          <div class="icon-box mr-4 shadow-sm">
            <v-icon icon="mdi-help-box" color="primary" size="32"></v-icon>
          </div>
          <div>
            <h1 class="text-h4 font-weight-bold mb-1">Pusat Bantuan</h1>
            <p class="text-subtitle-1 text-medium-emphasis">
              Panel navigasi panduan dan dokumentasi teknis.
            </p>
          </div>
        </div>

        <!-- Main Layout: Sidebar + List -->
        <v-row>
          <!-- Left: Categories Sidebar -->
          <v-col cols="12" md="3" lg="2">
            <v-card class="glass-card mb-4 border-0 overflow-hidden" elevation="0">
              <v-list bg-color="transparent" density="compact" class="category-list pa-2">
                <v-list-subheader class="font-weight-bold text-primary mb-2">KATEGORI</v-list-subheader>
                <v-list-item
                  v-for="cat in uniqueCategories"
                  :key="cat"
                  :value="cat"
                  :active="selectedCategory === cat"
                  rounded="lg"
                  color="primary"
                  class="mb-1"
                  @click="toggleCategory(cat)"
                >
                  <template v-slot:prepend>
                    <v-icon :icon="getCategoryIcon(cat)" size="18" class="mr-2"></v-icon>
                  </template>
                  <v-list-item-title class="text-body-2 font-weight-medium">{{ cat }}</v-list-item-title>
                </v-list-item>
                
                <v-divider class="my-2 opacity-10"></v-divider>
                
                <v-list-item
                  rounded="lg"
                  class="text-error"
                  @click="resetFilters"
                >
                  <template v-slot:prepend>
                    <v-icon icon="mdi-refresh" size="18" class="mr-2"></v-icon>
                  </template>
                  <v-list-item-title class="text-body-2">Reset Filter</v-list-item-title>
                </v-list-item>
              </v-list>
            </v-card>
          </v-col>

          <!-- Right: Search + Compact Articles -->
          <v-col cols="12" md="9" lg="10">
            <!-- Compact Search -->
            <v-text-field
              v-model="search"
              placeholder="Cari solusi atau panduan..."
              prepend-inner-icon="mdi-magnify"
              variant="flat"
              density="comfortable"
              hide-details
              rounded="lg"
              class="compact-search-field mb-4 shadow-sm"
              @input="debouncedSearch"
              clearable
            ></v-text-field>

            <!-- Loading State -->
            <v-card v-if="loading" class="glass-card pa-12 text-center border-0" elevation="0">
              <v-progress-circular indeterminate color="primary" size="40"></v-progress-circular>
            </v-card>

            <!-- Compact List -->
            <v-card v-else-if="filteredArticles.length > 0" class="glass-card border-0 overflow-hidden" elevation="0">
              <v-list class="pa-0 bg-transparent article-compact-list" theme="inherit">
                <template v-for="(article, index) in filteredArticles" :key="article.id">
                  <v-list-item
                    class="py-3 px-6 hover-compact-item clickable"
                    @click="viewDetail(article)"
                  >
                    <template v-slot:prepend>
                      <v-avatar color="primary-lighten-5" size="36" class="mr-3">
                        <v-icon :icon="getCategoryIcon(article.category)" color="primary" size="18"></v-icon>
                      </v-avatar>
                    </template>

                    <v-list-item-title class="font-weight-bold text-body-1 text-high-emphasis">
                      {{ article.title }}
                    </v-list-item-title>
                    
                    <v-list-item-subtitle class="text-caption text-medium-emphasis">
                      {{ stripHtml(article.content).substring(0, 120) }}...
                    </v-list-item-subtitle>

                    <template v-slot:append>
                      <v-chip size="x-small" :color="getCategoryColor(article.category)" variant="tonal" class="mr-4 font-weight-bold d-none d-sm-flex">
                        {{ article.category }}
                      </v-chip>
                      <v-icon icon="mdi-chevron-right" size="20" color="medium-emphasis"></v-icon>
                    </template>
                  </v-list-item>
                  <v-divider v-if="index < filteredArticles.length - 1" class="opacity-05"></v-divider>
                </template>
              </v-list>
            </v-card>

            <!-- Empty State -->
            <v-card v-else class="glass-card pa-12 text-center border-0" elevation="0">
              <v-icon icon="mdi-text-search-variant" size="48" color="disabled" class="mb-3"></v-icon>
              <h3 class="text-h6 text-disabled">Tidak ada artikel ditemukan</h3>
              <p class="text-caption text-medium-emphasis mb-4">Silakan ganti kata kunci atau kategori.</p>
              <v-btn color="primary" variant="tonal" rounded="pill" size="small" @click="resetFilters">
                Reset Semua Filter
              </v-btn>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>

    <!-- Detail Dialog -->
    <v-dialog v-model="dialog" max-width="850" scrollable transition="dialog-bottom-transition">
      <v-card v-if="selectedArticle" class="rounded-xl overflow-hidden shadow-xl border-0 bg-surface">
        <v-card-title class="bg-primary text-white d-flex align-center pa-5">
          <div class="d-flex flex-column">
            <span class="text-caption text-uppercase font-weight-bold opacity-80">{{ selectedArticle.category }}</span>
            <span class="text-h6 font-weight-bold">{{ selectedArticle.title }}</span>
          </div>
          <v-spacer></v-spacer>
          <v-btn icon="mdi-close" variant="text" color="white" @click="dialog = false"></v-btn>
        </v-card-title>
        <v-card-text class="pa-10 help-content-rich text-high-emphasis">
          <div v-html="renderMarkdown(selectedArticle.content)"></div>
        </v-card-text>
        <v-divider class="opacity-10"></v-divider>
        <v-card-actions class="pa-4 bg-light">
          <v-spacer></v-spacer>
          <v-btn variant="flat" color="primary" rounded="pill" class="px-8 font-weight-bold shadow-sm" @click="dialog = false">Selesai Membaca</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '../../api'
import Sidebar from '../../components/Sidebar.vue'
import Navbar from '../../components/Navbar.vue'

const loading = ref(true)
const articles = ref([])
const search = ref('')
const dialog = ref(false)
const selectedArticle = ref(null)
const selectedCategory = ref(null)

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

const uniqueCategories = computed(() => {
  const cats = articles.value.map(a => a.category)
  return [...new Set(cats)].sort()
})

const filteredArticles = computed(() => {
  if (!selectedCategory.value) return articles.value
  return articles.value.filter(a => a.category === selectedCategory.value)
})

const toggleCategory = (cat) => {
  selectedCategory.value = selectedCategory.value === cat ? null : cat
}

const resetFilters = () => {
  search.value = ''
  selectedCategory.value = null
  fetchArticles()
}

const getCategoryIcon = (category) => {
  const icons = {
    'Financial': 'mdi-calculator',
    'Developer': 'mdi-code-braces',
    'Keamanan': 'mdi-shield-check',
    'Sistem': 'mdi-cog',
    'Mapping': 'mdi-map-marker-path',
    'Import': 'mdi-database-import',
    'Laporan': 'mdi-file-chart',
    'Management': 'mdi-account-group'
  }
  return icons[category] || 'mdi-file-document-outline'
}

const getCategoryColor = (category) => {
  const colors = {
    'Financial': 'success',
    'Developer': 'deep-purple',
    'Keamanan': 'error',
    'Sistem': 'primary',
    'Mapping': 'warning',
    'Import': 'info',
    'Laporan': 'indigo',
    'Management': 'blue-grey'
  }
  return colors[category] || 'grey'
}

const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchArticles()
  }, 400)
}

const viewDetail = (article) => {
  selectedArticle.value = article
  dialog.value = true
}

const stripHtml = (text) => {
  if (!text) return ''
  return text.replace(/#+ /g, '').replace(/\*/g, '').replace(/\[.*\]/g, '').replace(/\n/g, ' ')
}

const renderMarkdown = (text) => {
  if (!text) return ''
  return text
    .replace(/^### (.*$)/gim, '<h3 class="text-h6 font-weight-bold mt-4 mb-2">$1</h3>')
    .replace(/^## (.*$)/gim, '<h2 class="text-h5 font-weight-bold mt-6 mb-3">$1</h2>')
    .replace(/^\* (.*$)/gim, '<li class="ml-4 mb-1">$1</li>')
    .replace(/^- (.*$)/gim, '<li class="ml-4 mb-1">$1</li>')
    .replace(/\*\*(.*)\*\*/gim, '<strong class="text-primary">$1</strong>')
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
  width: 52px;
  height: 52px;
  background: rgb(var(--v-theme-surface));
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(var(--v-border-color), 0.1);
}

.glass-card {
  background: rgb(var(--v-theme-surface)) !important;
  border: 1px solid rgba(var(--v-border-color), 0.1) !important;
  border-radius: 16px !important;
}

.compact-search-field :deep(.v-field) {
  background: rgb(var(--v-theme-surface)) !important;
  border-radius: 12px !important;
}

.hover-compact-item {
  transition: all 0.2s ease;
  border-left: 3px solid transparent;
}

.hover-compact-item:hover {
  background: rgba(var(--v-theme-primary), 0.05) !important;
  border-left-color: rgb(var(--v-theme-primary));
  cursor: pointer;
}

.help-content-rich {
  line-height: 1.8;
  font-size: 1.05rem;
}

.help-content-rich :deep(h3) {
  color: rgb(var(--v-theme-primary));
  border-bottom: 2px solid rgba(var(--v-border-color), 0.1);
  padding-bottom: 8px;
  margin-top: 24px;
  margin-bottom: 12px;
}

.help-content-rich :deep(li) {
  margin-left: 1.5rem;
  margin-bottom: 8px;
}

.category-list :deep(.v-list-item--active) {
  background: rgba(var(--v-theme-primary), 0.1) !important;
  font-weight: bold;
}

.position-sticky {
  position: sticky;
  top: 24px;
}

.opacity-05 {
  opacity: 0.05;
}
</style>
