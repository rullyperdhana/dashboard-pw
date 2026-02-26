<template>
  <v-app class="modern-bg">
    <Navbar @show-coming-soon="() => {}" />
    <Sidebar @show-coming-soon="() => {}" />

    <v-main>
      <v-container fluid class="pa-8">
        <!-- Header -->
        <v-row class="mb-6">
          <v-col cols="12">
            <h1 class="text-h4 font-weight-bold mb-1">
              <v-icon start color="teal" size="36">mdi-cash-multiple</v-icon>
              Setting Sumber Dana Per SKPD
            </h1>
            <p class="text-subtitle-1 text-grey-darken-1">Atur sumber dana (APBD / BLUD) untuk setiap SKPD. Perubahan akan diterapkan ke semua pegawai di SKPD tersebut.</p>
          </v-col>
        </v-row>

        <!-- Search & Summary -->
        <v-row>
          <v-col cols="12" md="6">
            <v-text-field
              v-model="search"
              label="Cari SKPD..."
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
              density="compact"
              hide-details
              clearable
            ></v-text-field>
          </v-col>
          <v-col cols="12" md="6" class="d-flex align-center justify-end gap-4">
            <v-chip color="blue" variant="tonal" size="small">
              APBD: {{ apbdCount }} SKPD
            </v-chip>
            <v-chip color="orange" variant="tonal" size="small" class="ml-2">
              BLUD: {{ bludCount }} SKPD
            </v-chip>
            <v-btn
              v-if="hasChanges"
              color="teal"
              variant="elevated"
              prepend-icon="mdi-content-save"
              :loading="saving"
              @click="saveAll"
              class="ml-4"
            >
              Simpan Perubahan ({{ changedCount }})
            </v-btn>
          </v-col>
        </v-row>

        <!-- SKPD Table -->
        <v-row class="mt-4">
          <v-col cols="12">
            <v-card class="glass-card rounded-xl" elevation="0">
              <v-data-table
                :headers="headers"
                :items="filteredList"
                :loading="loading"
                class="modern-table"
                hover
                density="compact"
                :items-per-page="50"
              >
                <template v-slot:item.no="{ index }">{{ index + 1 }}</template>

                <template v-slot:item.sumber_dana="{ item }">
                  <v-btn-toggle
                    v-model="localChanges[item.skpd]"
                    mandatory
                    density="compact"
                    color="teal"
                    class="rounded-lg"
                  >
                    <v-btn value="APBD" size="small" :variant="(localChanges[item.skpd] || item.sumber_dana) === 'APBD' ? 'flat' : 'outlined'" :color="(localChanges[item.skpd] || item.sumber_dana) === 'APBD' ? 'blue' : undefined">
                      APBD
                    </v-btn>
                    <v-btn value="BLUD" size="small" :variant="(localChanges[item.skpd] || item.sumber_dana) === 'BLUD' ? 'flat' : 'outlined'" :color="(localChanges[item.skpd] || item.sumber_dana) === 'BLUD' ? 'orange' : undefined">
                      BLUD
                    </v-btn>
                  </v-btn-toggle>
                </template>

                <template v-slot:item.status="{ item }">
                  <v-chip v-if="isChanged(item)" color="warning" size="x-small" variant="tonal">
                    Berubah
                  </v-chip>
                </template>
              </v-data-table>
            </v-card>
          </v-col>
        </v-row>

        <!-- Snackbar -->
        <v-snackbar v-model="snackbar.show" :color="snackbar.color" timeout="4000" location="top right">
          {{ snackbar.message }}
          <template v-slot:actions>
            <v-btn variant="text" @click="snackbar.show = false">Tutup</v-btn>
          </template>
        </v-snackbar>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../api'
import Navbar from '../components/Navbar.vue'
import Sidebar from '../components/Sidebar.vue'

const loading = ref(false)
const saving = ref(false)
const search = ref('')
const skpdList = ref([])
const localChanges = ref({})
const snackbar = ref({ show: false, message: '', color: 'success' })

const headers = [
  { title: 'No', key: 'no', width: '50px', sortable: false },
  { title: 'SKPD', key: 'skpd' },
  { title: 'Jumlah Pegawai', key: 'jumlah_pegawai', align: 'center', width: '140px' },
  { title: 'Sumber Dana', key: 'sumber_dana', align: 'center', width: '200px', sortable: false },
  { title: '', key: 'status', width: '80px', sortable: false },
]

const filteredList = computed(() => {
  if (!search.value) return skpdList.value
  const q = search.value.toLowerCase()
  return skpdList.value.filter(s => s.skpd.toLowerCase().includes(q))
})

const apbdCount = computed(() =>
  skpdList.value.filter(s => (localChanges.value[s.skpd] || s.sumber_dana || 'APBD') === 'APBD').length
)
const bludCount = computed(() =>
  skpdList.value.filter(s => (localChanges.value[s.skpd] || s.sumber_dana || 'APBD') === 'BLUD').length
)

const isChanged = (item) => {
  const local = localChanges.value[item.skpd]
  return local && local !== (item.sumber_dana || 'APBD')
}

const hasChanges = computed(() => skpdList.value.some(s => isChanged(s)))
const changedCount = computed(() => skpdList.value.filter(s => isChanged(s)).length)

const fetchData = async () => {
  loading.value = true
  try {
    const res = await api.get('/sumber-dana')
    skpdList.value = res.data.data
    // Initialize localChanges with current values
    localChanges.value = {}
    skpdList.value.forEach(s => {
      localChanges.value[s.skpd] = s.sumber_dana || 'APBD'
    })
  } catch (e) {
    showSnackbar('Gagal memuat data SKPD', 'error')
  } finally {
    loading.value = false
  }
}

const saveAll = async () => {
  const updates = skpdList.value
    .filter(s => isChanged(s))
    .map(s => ({
      skpd: s.skpd,
      sumber_dana: localChanges.value[s.skpd],
    }))

  if (!updates.length) return

  saving.value = true
  try {
    const res = await api.put('/sumber-dana/bulk', { updates })
    showSnackbar(res.data.message, 'success')
    // Refresh data
    await fetchData()
  } catch (e) {
    showSnackbar('Gagal menyimpan: ' + (e.response?.data?.message || e.message), 'error')
  } finally {
    saving.value = false
  }
}

const showSnackbar = (msg, color = 'success') => {
  snackbar.value = { show: true, message: msg, color }
}

onMounted(fetchData)
</script>

<style scoped>
.modern-bg {
  background-color: rgb(var(--v-theme-background)) !important;
}
.glass-card {
  background-color: rgb(var(--v-theme-surface)) !important;
  border: 1px solid rgba(var(--v-border-color), 0.08) !important;
}
:deep(.modern-table) {
  background: transparent !important;
}
:deep(.v-data-table-header) {
  background: rgba(var(--v-border-color), 0.05) !important;
}
:deep(.v-data-table-header th) {
  font-weight: 700 !important;
  text-transform: uppercase;
  font-size: 0.7rem !important;
  letter-spacing: 0.05em;
}
</style>
