<template>
  <div class="announcement-mgmt-wrapper">
    <Navbar />
    <Sidebar />

    <v-main class="bg-background">
      <v-container fluid class="pa-6 pa-md-8">
        <v-row>
          <v-col cols="12" class="d-flex justify-space-between align-center mb-6">
            <div>
              <h1 class="text-h4 font-weight-bold mb-1">Kelola Pengumuman</h1>
              <p class="text-subtitle-1 text-medium-emphasis">Kelola informasi yang tampil di Welcome Hub.</p>
            </div>
            <v-btn color="primary" @click="openDialog()" prepend-icon="mdi-plus" rounded="lg" elevation="2">
              Tambah Pengumuman
            </v-btn>
          </v-col>

          <v-col cols="12">
            <v-card class="glass-card" elevation="0">
              <v-data-table
                :headers="headers"
                :items="announcements"
                :loading="loading"
                class="transparent-table"
                hover
              >
                <template v-slot:item.type="{ item }">
                  <v-chip :color="getTypeColor(item.type)" size="small" variant="tonal" class="text-uppercase font-weight-bold">
                    {{ item.type }}
                  </v-chip>
                </template>
                
                <template v-slot:item.is_active="{ item }">
                  <v-chip :color="item.is_active ? 'success' : 'grey'" size="small" variant="flat">
                    {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                  </v-chip>
                </template>

                <template v-slot:item.created_at="{ item }">
                  <span class="text-caption">{{ formatDate(item.created_at) }}</span>
                </template>

                <template v-slot:item.actions="{ item }">
                  <v-btn icon="mdi-pencil" size="x-small" variant="text" color="primary" @click="openDialog(item)" class="me-1"></v-btn>
                  <v-btn icon="mdi-delete" size="x-small" variant="text" color="error" @click="deleteItem(item)"></v-btn>
                </template>
              </v-data-table>
            </v-card>
          </v-col>
        </v-row>

        <!-- Create/Edit Dialog -->
        <v-dialog v-model="dialog" max-width="600px" persistent>
          <v-card class="glass-dialog rounded-xl">
            <v-card-title class="pa-6 d-flex align-center">
              <v-icon color="primary" class="me-3">{{ editedIndex === -1 ? 'mdi-plus-circle' : 'mdi-pencil-circle' }}</v-icon>
              <span class="text-h5 font-weight-bold">{{ formTitle }}</span>
              <v-spacer></v-spacer>
              <v-btn icon="mdi-close" variant="text" @click="closeDialog" size="small"></v-btn>
            </v-card-title>
            <v-divider></v-divider>
            <v-card-text class="pa-6">
              <v-form ref="form" v-model="valid">
                <v-text-field
                  v-model="editedItem.title"
                  label="Judul Pengumuman"
                  required
                  :rules="[v => !!v || 'Judul wajib diisi']"
                  variant="outlined"
                  rounded="lg"
                  density="comfortable"
                  placeholder="Contoh: Pemeliharaan Sistem Mendatang"
                ></v-text-field>

                <v-textarea
                  v-model="editedItem.content"
                  label="Isi Pengumuman"
                  required
                  :rules="[v => !!v || 'Isi wajib diisi']"
                  variant="outlined"
                  rounded="lg"
                  rows="4"
                  placeholder="Tuliskan detail pengumuman di sini..."
                ></v-textarea>

                <v-row>
                  <v-col cols="12" sm="6">
                    <v-select
                      v-model="editedItem.type"
                      :items="types"
                      item-title="title"
                      item-value="value"
                      label="Kategori/Tipe"
                      variant="outlined"
                      rounded="lg"
                      density="comfortable"
                    ></v-select>
                  </v-col>
                  <v-col cols="12" sm="6" class="d-flex align-center">
                    <v-switch
                      v-model="editedItem.is_active"
                      label="Aktifkan Sekarang"
                      color="success"
                      hide-details
                      inset
                    ></v-switch>
                  </v-col>
                </v-row>
              </v-form>
            </v-card-text>
            <v-divider></v-divider>
            <v-card-actions class="pa-6">
              <v-spacer></v-spacer>
              <v-btn variant="text" rounded="lg" @click="closeDialog" color="grey">Batal</v-btn>
              <v-btn color="primary" rounded="lg" @click="save" :disabled="!valid" elevation="0" class="px-6">Simpan</v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>

        <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="3000" rounded="lg">
          {{ snackbar.text }}
        </v-snackbar>
      </v-container>
    </v-main>
  </div>
</template>

<script>
import api from '@/api'
import Navbar from '../components/Navbar.vue'
import Sidebar from '../components/Sidebar.vue'

export default {
  name: 'AnnouncementManagement',
  components: {
    Navbar,
    Sidebar
  },
  data: () => ({
    loading: true,
    dialog: false,
    valid: false,
    announcements: [],
    headers: [
      { title: 'Judul', key: 'title', align: 'start' },
      { title: 'Tipe', key: 'type', align: 'center' },
      { title: 'Status', key: 'is_active', align: 'center' },
      { title: 'Dibuat', key: 'created_at', align: 'start' },
      { title: 'Penulis', key: 'user.username', align: 'start' },
      { title: 'Aksi', key: 'actions', sortable: false, align: 'end' }
    ],
    types: [
      { title: 'Informasi', value: 'info' },
      { title: 'Sukses', value: 'success' },
      { title: 'Peringatan', value: 'warning' },
      { title: 'Penting', value: 'error' }
    ],
    editedIndex: -1,
    editedItem: {
      title: '',
      content: '',
      type: 'info',
      is_active: true
    },
    defaultItem: {
      title: '',
      content: '',
      type: 'info',
      is_active: true
    },
    snackbar: {
      show: false,
      text: '',
      color: 'success'
    }
  }),
  computed: {
    formTitle() {
      return this.editedIndex === -1 ? 'Tambah Pengumuman Baru' : 'Edit Pengumuman'
    }
  },
  created() {
    this.fetchAnnouncements()
  },
  methods: {
    async fetchAnnouncements() {
      this.loading = true
      try {
        const response = await api.get('/announcements/list')
        this.announcements = response.data.data
      } catch (error) {
        this.showSnackbar('Gagal mengambil data', 'error')
      } finally {
        this.loading = false
      }
    },
    openDialog(item) {
      if (item) {
        this.editedIndex = this.announcements.indexOf(item)
        this.editedItem = Object.assign({}, item)
      } else {
        this.editedIndex = -1
        this.editedItem = Object.assign({}, this.defaultItem)
      }
      this.dialog = true
    },
    closeDialog() {
      this.dialog = false
      this.$nextTick(() => {
        this.editedItem = Object.assign({}, this.defaultItem)
        this.editedIndex = -1
      })
    },
    async save() {
      try {
        if (this.editedIndex > -1) {
          await api.put(`/announcements/${this.editedItem.id}`, this.editedItem)
          this.showSnackbar('Pengumuman berhasil diperbarui')
        } else {
          await api.post('/announcements', this.editedItem)
          this.showSnackbar('Pengumuman berhasil ditambahkan')
        }
        this.fetchAnnouncements()
        this.closeDialog()
      } catch (error) {
        this.showSnackbar('Gagal menyimpan data', 'error')
      }
    },
    async deleteItem(item) {
      if (confirm('Apakah Anda yakin ingin menghapus pengumuman ini?')) {
        try {
          await api.delete(`/announcements/${item.id}`)
          this.showSnackbar('Pengumuman berhasil dihapus')
          this.fetchAnnouncements()
        } catch (error) {
          this.showSnackbar('Gagal menghapus data', 'error')
        }
      }
    },
    getTypeColor(type) {
      const colors = {
        info: 'info',
        success: 'success',
        warning: 'warning',
        error: 'error'
      }
      return colors[type] || 'info'
    },
    formatDate(dateStr) {
      if (!dateStr) return ''
      return new Date(dateStr).toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    },
    showSnackbar(text, color = 'success') {
      this.snackbar.text = text
      this.snackbar.color = color
      this.snackbar.show = true
    }
  }
}
</script>

<style scoped>
.announcement-mgmt-wrapper {
  min-height: 100vh;
}

.glass-card {
  background: rgba(var(--v-theme-surface), 0.7) !important;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(var(--v-border-color), 0.12);
  border-radius: 20px !important;
}

.glass-dialog {
  background: rgba(var(--v-theme-surface), 1) !important;
  border: 1px solid rgba(var(--v-border-color), 1);
}

.transparent-table :deep(table) {
  background: transparent !important;
}

.transparent-table :deep(thead th) {
  background: rgba(var(--v-theme-on-surface), 0.04) !important;
  font-weight: bold;
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.5px;
}
</style>
