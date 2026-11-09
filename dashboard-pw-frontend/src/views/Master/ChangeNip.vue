<template>
  <div class="modern-dashboard">
    <Navbar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />
    <Sidebar />

    <v-main class="bg-dashboard">
      <v-container fluid class="pa-6 pa-md-10">
        <!-- Header -->
        <header class="dashboard-header mb-8">
          <v-row align="center">
            <v-col cols="12">
              <div class="d-flex align-center mb-2">
                <div class="header-icon-wrapper mr-5">
                  <v-icon color="primary" size="32">mdi-card-account-details-outline</v-icon>
                </div>
                <div>
                  <h1 class="text-h4 font-weight-black tracking-tight text-high-emphasis">Ubah NIP Pegawai</h1>
                  <p class="text-subtitle-1 text-medium-emphasis">
                    Perbarui NIP pegawai (PPPK, PNS, maupun PW) secara otomatis di seluruh tabel riwayat gaji, master, TPP, dan pajak.
                  </p>
                </div>
              </div>
            </v-col>
          </v-row>
        </header>

        <v-row>
          <!-- Form Input NIP -->
          <v-col cols="12" md="6">
            <v-card class="glass-panel pa-6 mb-6" elevation="0">
              <div class="text-overline font-weight-black mb-2 text-primary">LANGKAH 1: CARI DATA NIP LAMA</div>
              <p class="text-caption text-medium-emphasis mb-4">
                Masukkan NIP lama yang ingin diubah untuk memeriksa semua data dan riwayat penggajian terkait di database.
              </p>

              <v-form @submit.prevent="checkNip" ref="checkForm">
                <v-text-field
                  v-model="oldNip"
                  label="NIP Lama Pegawai *"
                  placeholder="Contoh: 198501012023211001"
                  variant="outlined"
                  density="comfortable"
                  prepend-inner-icon="mdi-account-search"
                  :rules="[v => !!v || 'NIP lama harus diisi']"
                  class="mb-3"
                  clearable
                ></v-text-field>

                <v-btn
                  color="primary"
                  variant="flat"
                  size="large"
                  block
                  prepend-icon="mdi-magnify"
                  :loading="checking"
                  @click="checkNip"
                  class="font-weight-bold"
                >
                  Cek Data Terkait
                </v-btn>
              </v-form>
            </v-card>

            <!-- Input NIP Baru (Tampil setelah data lama ditemukan) -->
            <v-card v-if="employeeData" class="glass-panel pa-6" elevation="0">
              <div class="text-overline font-weight-black mb-2 text-teal">LANGKAH 2: MASUKKAN NIP BARU</div>
              <p class="text-caption text-medium-emphasis mb-4">
                Pastikan NIP baru sudah benar sesuai SK Kepegawaian. NIP baru tidak boleh sudah terdaftar di sistem.
              </p>

              <v-form @submit.prevent="confirmChange" ref="changeForm">
                <v-text-field
                  v-model="newNip"
                  label="NIP Baru Pegawai *"
                  placeholder="Contoh: 198501012023211002"
                  variant="outlined"
                  density="comfortable"
                  color="teal"
                  prepend-inner-icon="mdi-card-account-details"
                  :rules="[
                    v => !!v || 'NIP baru harus diisi',
                    v => v !== oldNip || 'NIP baru tidak boleh sama dengan NIP lama'
                  ]"
                  class="mb-4"
                ></v-text-field>

                <v-btn
                  color="teal"
                  variant="flat"
                  size="large"
                  block
                  prepend-icon="mdi-check-circle-outline"
                  :loading="updating"
                  :disabled="!newNip || newNip === oldNip"
                  @click="confirmChange"
                  class="font-weight-bold text-white"
                >
                  Proses Perubahan NIP
                </v-btn>
              </v-form>
            </v-card>
          </v-col>

          <!-- Detail Temuan Data -->
          <v-col cols="12" md="6">
            <v-card v-if="employeeData" class="glass-panel pa-6" elevation="0">
              <div class="d-flex align-center justify-space-between mb-4">
                <div>
                  <div class="text-overline font-weight-black text-primary">DATA PEGAWAI DITEMUKAN</div>
                  <h2 class="text-h6 font-weight-bold">{{ employeeData.name }}</h2>
                  <div class="text-caption text-medium-emphasis">Unit Kerja: {{ employeeData.skpd }}</div>
                </div>
                <v-chip color="primary" variant="tonal" class="font-weight-bold">
                  {{ employeeData.total_records }} Baris Terkait
                </v-chip>
              </div>

              <v-divider class="mb-4"></v-divider>

              <div class="text-subtitle-2 font-weight-bold mb-2">Rincian Data yang Akan Diperbarui:</div>
              <v-list density="compact" class="bg-transparent pa-0">
                <v-list-item
                  v-for="(item, idx) in employeeData.details"
                  :key="idx"
                  class="px-2 py-1 rounded-lg mb-1"
                  style="background: rgba(var(--v-theme-surface), 0.5);"
                >
                  <template v-slot:prepend>
                    <v-icon color="teal" size="18" class="mr-2">mdi-database-check</v-icon>
                  </template>
                  <v-list-item-title class="text-body-2 font-weight-medium">
                    {{ item.label }}
                  </v-list-item-title>
                  <v-list-item-subtitle class="text-caption text-disabled">
                    Tabel: <code>{{ item.table }}</code>
                  </v-list-item-subtitle>
                  <template v-slot:append>
                    <v-chip size="x-small" color="teal" variant="flat" class="font-weight-bold">
                      {{ item.count }} baris
                    </v-chip>
                  </template>
                </v-list-item>
              </v-list>
            </v-card>

            <!-- Petunjuk Informasi jika belum cari -->
            <v-card v-else class="glass-panel pa-6 text-center py-12" elevation="0">
              <v-icon size="64" color="grey-lighten-1" class="mb-3">mdi-shield-sync-outline</v-icon>
              <div class="text-h6 font-weight-bold mb-1">Informasi Sinkronisasi NIP</div>
              <p class="text-caption text-medium-emphasis mx-auto" style="max-width: 420px;">
                Saat NIP diperbarui, sistem akan otomatis mengupdate riwayat gaji bulanan (PPPK/PNS), master pegawai, rincian TPP, dan perhitungan PPh 21 menggunakan Database Transaction yang aman.
              </p>
            </v-card>

            <!-- Hasil Sukses -->
            <v-card v-if="updateResult" class="glass-panel pa-6 mt-6 border-teal" elevation="0">
              <div class="d-flex align-center mb-3">
                <v-icon color="success" size="32" class="mr-3">mdi-check-circle</v-icon>
                <div>
                  <div class="text-subtitle-1 font-weight-bold text-success">Perubahan NIP Berhasil!</div>
                  <div class="text-caption text-medium-emphasis">
                    {{ updateResult.message }}
                  </div>
                </div>
              </div>
              <v-divider class="my-3"></v-divider>
              <div class="text-caption text-medium-emphasis mb-2">Ringkasan Tabel Diperbarui:</div>
              <div class="d-flex flex-wrap gap-2">
                <v-chip
                  v-for="(t, ti) in updateResult.data.updated_tables"
                  :key="ti"
                  size="small"
                  color="success"
                  variant="tonal"
                  class="mr-2 mb-2 font-weight-medium"
                >
                  {{ t.label }}: {{ t.affected }} baris
                </v-chip>
              </div>
            </v-card>
          </v-col>
        </v-row>

        <!-- Dialog Konfirmasi -->
        <v-dialog v-model="confirmDialog" max-width="500px">
          <v-card class="rounded-xl pa-4">
            <v-card-title class="d-flex align-center">
              <v-icon color="warning" class="mr-2">mdi-alert-circle</v-icon>
              <span>Konfirmasi Perubahan NIP</span>
            </v-card-title>
            <v-card-text>
              <p class="text-body-2 mb-3">
                Anda akan mengubah NIP pegawai berikut di seluruh database:
              </p>
              <div class="pa-3 rounded-lg mb-3" style="background: rgba(var(--v-theme-surface-variant), 0.3);">
                <div><strong>Nama:</strong> {{ employeeData?.name }}</div>
                <div><strong>NIP Lama:</strong> <code>{{ oldNip }}</code></div>
                <div><strong>NIP Baru:</strong> <code class="text-success font-weight-bold">{{ newNip }}</code></div>
                <div><strong>Total Data:</strong> {{ employeeData?.total_records }} baris</div>
              </div>
              <div class="text-caption text-medium-emphasis">
                Tindakan ini akan mengupdate riwayat gaji dan master pegawai. Pastikan nomor NIP baru sudah sesuai.
              </div>
            </v-card-text>
            <v-card-actions class="justify-end">
              <v-btn variant="text" @click="confirmDialog = false">Batal</v-btn>
              <v-btn color="teal" variant="flat" :loading="updating" @click="executeChange">
                Ya, Perbarui Sekarang
              </v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>

        <!-- Snackbar Notifikasi -->
        <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="4000" location="top right">
          {{ snackbarText }}
          <template v-slot:actions>
            <v-btn variant="text" @click="snackbar = false">Tutup</v-btn>
          </template>
        </v-snackbar>
      </v-container>
    </v-main>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Navbar from '../../components/Navbar.vue'
import Sidebar from '../../components/Sidebar.vue'
import api from '../../api'

const oldNip = ref('')
const newNip = ref('')
const checking = ref(false)
const updating = ref(false)
const confirmDialog = ref(false)

const employeeData = ref(null)
const updateResult = ref(null)

const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')

const showSnackbar = (text, color = 'success') => {
  snackbarText.value = text
  snackbarColor.value = color
  snackbar.value = true
}

const checkNip = async () => {
  if (!oldNip.value) {
    showSnackbar('Masukkan NIP lama terlebih dahulu', 'warning')
    return
  }

  checking.value = true
  employeeData.value = null
  updateResult.value = null

  try {
    const res = await api.post('/employees/check-nip', {
      old_nip: oldNip.value.trim()
    })

    if (res.data.success) {
      employeeData.value = res.data.data
      showSnackbar(`Data ditemukan: ${res.data.data.name}`, 'success')
    }
  } catch (err) {
    console.error('Error checking NIP:', err)
    const msg = err.response?.data?.message || 'Data pegawai tidak ditemukan'
    showSnackbar(msg, 'error')
  } finally {
    checking.value = false
  }
}

const confirmChange = () => {
  if (!newNip.value || newNip.value === oldNip.value) {
    showSnackbar('NIP baru harus diisi dan berbeda dengan NIP lama', 'warning')
    return
  }
  confirmDialog.value = true
}

const executeChange = async () => {
  updating.value = true
  try {
    const res = await api.post('/employees/change-nip', {
      old_nip: oldNip.value.trim(),
      new_nip: newNip.value.trim()
    })

    if (res.data.success) {
      confirmDialog.value = false
      updateResult.value = res.data
      showSnackbar(res.data.message, 'success')
      // Reset input form
      oldNip.value = newNip.value
      newNip.value = ''
      employeeData.value = null
    }
  } catch (err) {
    console.error('Error changing NIP:', err)
    const msg = err.response?.data?.message || 'Gagal mengubah NIP pegawai'
    showSnackbar(msg, 'error')
  } finally {
    updating.value = false
  }
}
</script>

<style scoped>
.modern-dashboard {
  min-height: 100vh;
}
.bg-dashboard {
  background-color: rgb(var(--v-theme-background));
}
.glass-panel {
  background: rgb(var(--v-theme-surface));
  border: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
  border-radius: 16px;
}
.border-teal {
  border: 1px solid rgba(0, 150, 136, 0.4) !important;
}
.gap-2 {
  gap: 8px;
}
</style>
