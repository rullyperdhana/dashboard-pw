<template>
  <v-app class="modern-bg">
    <Navbar />
    <Sidebar />

    <v-main>
      <v-container fluid class="pa-8">
        <!-- Header -->
        <v-row class="mb-6">
          <v-col cols="12">
            <div>
              <h1 class="text-h4 font-weight-bold mb-1">
                <v-icon start color="primary" size="36">mdi-lock-check</v-icon>
                Posting Data Penggajian
              </h1>
              <p class="text-subtitle-1 text-medium-emphasis">Kunci atau buka kunci data penggajian untuk mencegah perubahan yang tidak sengaja.</p>
            </div>
          </v-col>
        </v-row>

        <!-- Filter -->
        <v-row class="mb-6">
          <v-col cols="12" md="4">
            <v-card class="glass-card rounded-xl pa-4" elevation="0">
              <v-select
                v-model="selectedYear"
                :items="years"
                label="Tahun"
                variant="outlined"
                density="compact"
                hide-details
                prepend-inner-icon="mdi-calendar"
                color="primary"
                @update:model-value="fetchPostings"
              ></v-select>
            </v-card>
          </v-col>
        </v-row>

        <!-- Posting Table -->
        <v-row>
          <v-col cols="12">
            <v-card class="glass-card rounded-xl" elevation="0">
              <v-data-table
                :headers="headers"
                :items="tableData"
                :loading="loading"
                class="modern-table"
                hover
                :items-per-page="-1"
                hide-default-footer
              >
                <template v-slot:item.month_name="{ item }">
                  <span class="font-weight-bold">{{ item.month_name }}</span>
                </template>

                <template v-slot:item.PNS="{ item }">
                  <PostingButton :status="item.PNS" type="PNS" :month="item.month" :year="selectedYear" @toggle="handleToggle" />
                </template>

                <template v-slot:item.PPPK="{ item }">
                  <PostingButton :status="item.PPPK" type="PPPK" :month="item.month" :year="selectedYear" @toggle="handleToggle" />
                </template>

                <template v-slot:item.PPPK_PW="{ item }">
                  <PostingButton :status="item.PPPK_PW" type="PPPK_PW" :month="item.month" :year="selectedYear" @toggle="handleToggle" />
                </template>

                <template v-slot:item.TPG="{ item }">
                  <PostingButton :status="item.TPG" type="TPG" :month="item.month" :year="selectedYear" @toggle="handleToggle" />
                </template>

                <template v-slot:item.TPP="{ item }">
                  <PostingButton :status="item.TPP" type="TPP" :month="item.month" :year="selectedYear" @toggle="handleToggle" />
                </template>
              </v-data-table>
            </v-card>
          </v-col>
        </v-row>

        <!-- Legend & Info -->
        <v-row class="mt-6">
          <v-col cols="12">
            <v-alert type="info" variant="tonal" class="rounded-xl">
              <template v-slot:prepend>
                <v-icon size="24" color="info">mdi-information</v-icon>
              </template>
              <div>
                <strong>Informasi:</strong> Data yang sudah di-<b>Posting</b> (ikon gembok merah) akan terkunci dan tidak dapat diubah melalui upload ulang atau menu edit. 
                Gunakan tombol <b>Unposting</b> untuk membuka kunci jika ingin melakukan perbaikan data.
              </div>
            </v-alert>
          </v-col>
        </v-row>
      </v-container>
    </v-main>

    <!-- Overlay Loading -->
    <v-overlay v-model="actionLoading" class="align-center justify-center" persistent>
      <v-progress-circular color="primary" indeterminate size="64"></v-progress-circular>
    </v-overlay>
  </v-app>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '../api'
import Navbar from '../components/Navbar.vue'
import Sidebar from '../components/Sidebar.vue'
import PostingButton from '../components/PostingButton.vue'

const loading = ref(false)
const actionLoading = ref(false)
const selectedYear = ref(new Date().getFullYear())
const years = [2024, 2025, 2026, 2027]
const postings = ref([])

const months = [
  { value: 1, name: 'Januari' }, { value: 2, name: 'Februari' }, { value: 3, name: 'Maret' },
  { value: 4, name: 'April' }, { value: 5, name: 'Mei' }, { value: 6, name: 'Juni' },
  { value: 7, name: 'Juli' }, { value: 8, name: 'Agustus' }, { value: 9, name: 'September' },
  { value: 10, name: 'Oktober' }, { value: 11, name: 'November' }, { value: 12, name: 'Desember' }
]

const headers = [
  { title: 'Bulan', key: 'month_name', width: '150px' },
  { title: 'Gaji PNS', key: 'PNS', align: 'center' },
  { title: 'Gaji PPPK', key: 'PPPK', align: 'center' },
  { title: 'PPPK Paruh Waktu', key: 'PPPK_PW', align: 'center' },
  { title: 'TPG', key: 'TPG', align: 'center' },
  { title: 'TPP', key: 'TPP', align: 'center' },
]

const tableData = computed(() => {
  return months.map(m => {
    const row = { month: m.value, month_name: m.name }
    const paymentTypes = ['PNS', 'PPPK', 'PPPK_PW', 'TPG', 'TPP']
    
    paymentTypes.forEach(type => {
      row[type] = postings.value.find(p => p.month === m.value && p.type === type) || {
        is_posted: false,
        month: m.value,
        type: type
      }
    })
    return row
  })
})

const fetchPostings = async () => {
  loading.value = true
  try {
    const res = await api.get('/payroll-postings', { params: { year: selectedYear.value } })
    if (res.data.success) {
      postings.value = res.data.data
    }
  } catch (e) {
    console.error('Error fetching postings:', e)
  } finally {
    loading.value = false
  }
}

const handleToggle = async ({ type, month, year, isPosted }) => {
  actionLoading.value = true
  const endpoint = isPosted ? '/payroll-postings/unpost' : '/payroll-postings/post'
  try {
    const res = await api.post(endpoint, { type, month, year })
    if (res.data.success) {
      await fetchPostings()
    }
  } catch (e) {
    alert('Gagal mengubah status: ' + (e.response?.data?.message || e.message))
  } finally {
    actionLoading.value = false
  }
}

onMounted(fetchPostings)
</script>

<style scoped>
.modern-bg {
  background-color: #f8f9fa !important;
}
.glass-card {
  background: white !important;
  border: 1px solid rgba(0,0,0,0.05) !important;
}
:deep(.modern-table) {
  background: transparent !important;
}
:deep(.v-data-table-header) {
  background: #f1f3f5 !important;
}
:deep(.v-data-table-header th) {
  font-weight: 700 !important;
  text-transform: uppercase;
  font-size: 0.75rem !important;
  color: #495057 !important;
}
</style>
