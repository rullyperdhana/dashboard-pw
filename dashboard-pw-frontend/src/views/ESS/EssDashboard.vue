<template>
  <div class="ess-dashboard min-h-screen">
    <!-- Navbar Custom ESS -->
    <v-app-bar flat color="surface" class="border-b" height="72">
      <div class="d-flex align-center px-6 w-100">
        <div class="d-flex align-center ga-3">
          <v-avatar color="primary" variant="tonal" size="48">
            <v-icon color="primary" icon="mdi-shield-account"></v-icon>
          </v-avatar>
          <div>
            <div class="text-h6 font-weight-black text-primary">SIP-Gaji</div>
            <div class="text-caption text-medium-emphasis mt-n1 font-weight-medium">Portal Pegawai (ESS)</div>
          </div>
        </div>

        <v-spacer></v-spacer>

        <!-- User Info & Logout -->
        <div class="d-flex align-center ga-4">
          <ThemeToggle />
          <v-divider vertical class="mx-2 my-4"></v-divider>
          <div class="text-right d-none d-sm-block">
            <div class="text-subtitle-2 font-weight-bold">{{ user.nama || 'Pengguna ESS' }}</div>
            <div class="text-caption text-medium-emphasis mt-n1">{{ user.nip || '-' }}</div>
          </div>
          <v-btn
            icon="mdi-logout-variant"
            color="error"
            variant="tonal"
            rounded="lg"
            @click="handleLogout"
          ></v-btn>
        </div>
      </div>
    </v-app-bar>

    <v-main class="bg-light pb-12">
      <v-container class="pt-8 max-w-7xl">
        <!-- Dashboard Header Profile Card -->
        <v-row>
          <v-col cols="12">
            <v-card class="glass-card mb-8 overflow-hidden rounded-xl" elevation="0">
              <div class="card-bg-decoration"></div>
              <v-card-text class="pa-8 position-relative">
                <v-row align="center">
                  <v-col cols="12" md="8">
                    <h1 class="text-h4 font-weight-black text-white mb-2">Selamat Datang, {{ firstName }}</h1>
                    <p class="text-white opacity-80 mb-6 font-weight-medium">
                      Anda login sebagai {{ user.type }} di lingkungan Pemerintah Provinsi.
                    </p>
                    
                    <div class="d-flex flex-wrap ga-4 mt-4">
                      <v-chip color="white" variant="tonal" class="font-weight-bold" prepend-icon="mdi-briefcase">
                        {{ user.jabatan || 'Fungsional Umum' }}
                      </v-chip>
                      <v-chip color="white" variant="tonal" class="font-weight-bold" prepend-icon="mdi-domain">
                        {{ user.skpd || 'Sekretariat Daerah' }}
                      </v-chip>
                    </div>
                  </v-col>
                  <v-col cols="12" md="4" class="text-right d-none d-md-block">
                    <v-icon icon="mdi-account-circle" size="120" color="rgba(255,255,255,0.2)"></v-icon>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <!-- Slips Section -->
        <v-row>
          <v-col cols="12">
            <div class="d-flex align-center mb-6">
              <div class="icon-box-small mr-4">
                <v-icon icon="mdi-history" color="primary" size="24"></v-icon>
              </div>
              <div>
                <h2 class="text-h6 font-weight-bold mb-0">Riwayat Penggajian Terakhir</h2>
                <div class="text-caption text-medium-emphasis">12 Slip Gaji atau Tunjangan Terakhir Anda</div>
              </div>
              <v-spacer></v-spacer>
              <v-btn prepend-icon="mdi-refresh" variant="tonal" color="primary" rounded="lg" @click="fetchSlips" :loading="loading" class="text-none">
                Segarkan
              </v-btn>
            </div>

            <!-- Year Selector Tabs -->
            <v-tabs v-model="selectedYear" color="primary" class="mb-6 bg-transparent" align-tabs="center">
              <v-tab v-for="year in availableYears" :key="year" :value="year" class="font-weight-bold text-h6">
                Tahun {{ year }}
              </v-tab>
            </v-tabs>

            <v-card class="glass-card-light rounded-xl pa-0 border">
              <v-data-iterator :items="groupedSlips[selectedYear] || []" :items-per-page="12">
                <template v-slot:default="{ items }">
                  <v-row dense class="pa-4">
                    <template v-for="(item, i) in items" :key="i">
                      <v-col cols="12" sm="6" md="4" lg="3">
                        <v-card class="slip-card fill-height" elevation="0" variant="flat" hover @click="viewSlip(item.raw)">
                          <div class="slip-header pa-4" :class="'bg-' + getSlipColor(item.raw.jenis_gaji) + '-lighten-4'">
                            <div class="d-flex justify-space-between align-start mb-2">
                              <v-chip size="x-small" :color="getSlipColor(item.raw.jenis_gaji)" class="font-weight-bold text-uppercase">
                                {{ item.raw.jenis_gaji }}
                              </v-chip>
                              <div class="text-caption font-weight-bold text-medium-emphasis">{{ item.raw.tipe }}</div>
                            </div>
                            <h3 class="text-subtitle-1 font-weight-black text-high-emphasis">
                              {{ getMonthName(item.raw.bulan) }} {{ item.raw.tahun }}
                            </h3>
                          </div>
                          
                          <v-divider class="opacity-20"></v-divider>
                          
                          <div class="pa-4">
                            <!-- Detail Nominal Breakdown -->
                            <div class="d-flex justify-space-between mb-1">
                              <span class="text-caption text-medium-emphasis">Gaji Pokok & Tunj.</span>
                              <span class="text-caption font-weight-medium">{{ formatCurrency(item.raw.kotor - (item.raw.tunj_tpp || 0)) }}</span>
                            </div>
                            <div class="d-flex justify-space-between mb-1">
                              <span class="text-caption text-medium-emphasis">Tambahan TPP</span>
                              <span class="text-caption font-weight-medium text-teal">{{ formatCurrency(item.raw.tunj_tpp || 0) }}</span>
                            </div>
                            
                            <v-divider class="mb-1 opacity-10" border-style="dashed"></v-divider>

                            <div class="d-flex justify-space-between mb-1">
                              <span class="text-caption font-weight-bold">Total Bruto</span>
                              <span class="text-caption font-weight-bold">{{ formatCurrency(item.raw.kotor) }}</span>
                            </div>
                            <div class="d-flex justify-space-between mb-3">
                              <span class="text-caption text-medium-emphasis">Total Potongan</span>
                              <span class="text-caption font-weight-medium text-error">{{ item.raw.kotor - item.raw.bersih > 0 ? '-' + formatCurrency(item.raw.kotor - item.raw.bersih) : 'Rp 0' }}</span>
                            </div>
                            
                            <v-divider class="mb-3 opacity-10" border-style="dashed"></v-divider>
                            
                            <div class="d-flex justify-space-between align-center">
                              <span class="text-caption font-weight-bold">Take Home Pay</span>
                              <span class="text-subtitle-2 font-weight-black text-success">{{ formatCurrency(item.raw.bersih) }}</span>
                            </div>
                          </div>
                        </v-card>
                      </v-col>
                    </template>
                  </v-row>
                </template>

                <template v-slot:no-data>
                  <div class="text-center pa-12">
                     <v-icon icon="mdi-file-hidden" size="80" color="disabled" class="mb-4"></v-icon>
                     <p class="text-subtitle-1 font-weight-medium text-disabled">Belum ada riwayat slip pada tahun ini</p>
                  </div>
                </template>
              </v-data-iterator>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>

    <!-- Pay Slip Detail Dialog -->
    <v-dialog v-model="slipDialog" max-width="700" persistent scrollable>
      <v-card class="rounded-xl overflow-hidden shadow-2xl">
         <v-card-title class="pa-6 d-flex align-center bg-surface border-b">
            <div>
              <div class="text-h6 font-weight-black">Rincian Slip Gaji</div>
              <div class="text-caption text-medium-emphasis mt-n1" v-if="selectedSlipDetail">
                Periode {{ getMonthName(selectedSlipDetail.bulan) }} {{ selectedSlipDetail.tahun }}
              </div>
            </div>
            <v-spacer></v-spacer>
            <v-btn icon="mdi-close" variant="text" size="small" @click="slipDialog = false" :disabled="downloading"></v-btn>
         </v-card-title>

         <v-card-text class="pa-0 bg-light">
            <div v-if="!selectedSlipDetail" class="pa-12 text-center">
              <v-progress-circular indeterminate color="primary"></v-progress-circular>
              <div class="mt-4 text-caption text-medium-emphasis">Memuat rincian slip...</div>
            </div>
            <div v-else class="pa-6">
              <!-- Employee Info Header -->
              <div class="mb-6 pa-4 rounded-xl bg-surface border">
                <v-row dense>
                  <v-col cols="12" sm="6">
                    <div class="text-overline text-medium-emphasis">Nama Pegawai</div>
                    <div class="text-subtitle-1 font-weight-bold">{{ selectedSlipDetail.nama }}</div>
                    <div class="text-caption font-weight-medium text-primary">{{ selectedSlipDetail.nip }}</div>
                  </v-col>
                  <v-col cols="12" sm="6" class="text-sm-right">
                    <div class="text-overline text-medium-emphasis">Unit Kerja / SKPD</div>
                    <div class="text-body-2 font-weight-bold">{{ selectedSlipDetail.skpd }}</div>
                    <div class="text-caption">{{ selectedSlipDetail.jabatan }}</div>
                  </v-col>
                </v-row>
              </div>

              <!-- Breakdown Sections -->
              <v-row>
                <!-- Income Section -->
                <v-col cols="12" md="6">
                  <div class="d-flex align-center mb-3">
                    <v-icon icon="mdi-cash-plus" color="success" size="20" class="mr-2"></v-icon>
                    <span class="text-overline font-weight-black">Penghasilan (Income)</span>
                  </div>
                  <v-card variant="flat" border class="rounded-xl overflow-hidden">
                    <v-table density="compact">
                      <tbody>
                        <tr><td class="text-caption">Gaji Pokok</td><td class="text-right text-caption font-weight-bold">{{ formatCurrency(selectedSlipDetail.gaji_pokok) }}</td></tr>
                        <tr v-if="selectedSlipDetail.tunj_istri > 0"><td class="text-caption">Tunj. Istri / Suami</td><td class="text-right text-caption font-weight-bold">{{ formatCurrency(selectedSlipDetail.tunj_istri) }}</td></tr>
                        <tr v-if="selectedSlipDetail.tunj_anak > 0"><td class="text-caption">Tunj. Anak</td><td class="text-right text-caption font-weight-bold">{{ formatCurrency(selectedSlipDetail.tunj_anak) }}</td></tr>
                        <tr v-if="selectedSlipDetail.tunj_fungsional > 0"><td class="text-caption">Tunj. Fungsional</td><td class="text-right text-caption font-weight-bold">{{ formatCurrency(selectedSlipDetail.tunj_fungsional) }}</td></tr>
                        <tr v-if="selectedSlipDetail.tunj_struktural > 0"><td class="text-caption">Tunj. Struktural</td><td class="text-right text-caption font-weight-bold">{{ formatCurrency(selectedSlipDetail.tunj_struktural) }}</td></tr>
                        <tr v-if="selectedSlipDetail.tunj_umum > 0"><td class="text-caption">Tunj. Umum</td><td class="text-right text-caption font-weight-bold">{{ formatCurrency(selectedSlipDetail.tunj_umum) }}</td></tr>
                        <tr v-if="selectedSlipDetail.tunj_beras > 0"><td class="text-caption">Tunj. Beras</td><td class="text-right text-caption font-weight-bold">{{ formatCurrency(selectedSlipDetail.tunj_beras) }}</td></tr>
                        <tr v-if="selectedSlipDetail.tunj_tpp > 0"><td class="text-caption">Tambahan TPP</td><td class="text-right text-caption font-weight-bold text-teal">{{ formatCurrency(selectedSlipDetail.tunj_tpp) }}</td></tr>
                        <tr class="bg-grey-lighten-4">
                          <td class="text-caption font-weight-black">TOTAL KOTOR</td>
                          <td class="text-right text-caption font-weight-black">{{ formatCurrency(selectedSlipDetail.kotor) }}</td>
                        </tr>
                      </tbody>
                    </v-table>
                  </v-card>
                </v-col>

                <!-- Deduction Section -->
                <v-col cols="12" md="6">
                  <div class="d-flex align-center mb-3">
                    <v-icon icon="mdi-cash-minus" color="error" size="20" class="mr-2"></v-icon>
                    <span class="text-overline font-weight-black">Potongan (Deductions)</span>
                  </div>
                  <v-card variant="flat" border class="rounded-xl overflow-hidden">
                    <v-table density="compact">
                      <tbody>
                        <tr v-if="selectedSlipDetail.pot_iwp > 0"><td class="text-caption">IWP (1%)</td><td class="text-right text-caption font-weight-bold text-error">{{ formatCurrency(selectedSlipDetail.pot_iwp) }}</td></tr>
                        <tr v-if="selectedSlipDetail.pot_iwp1 > 0"><td class="text-caption">IWP (2%)</td><td class="text-right text-caption font-weight-bold text-error">{{ formatCurrency(selectedSlipDetail.pot_iwp1) }}</td></tr>
                        <tr v-if="selectedSlipDetail.pot_iwp8 > 0"><td class="text-caption">IWP (8%)</td><td class="text-right text-caption font-weight-bold text-error">{{ formatCurrency(selectedSlipDetail.pot_iwp8) }}</td></tr>
                        <tr v-if="selectedSlipDetail.pot_askes > 0"><td class="text-caption">BPJS Kes / Askes</td><td class="text-right text-caption font-weight-bold text-error">{{ formatCurrency(selectedSlipDetail.pot_askes) }}</td></tr>
                        <tr v-if="selectedSlipDetail.pot_pph > 0"><td class="text-caption">PPh 21</td><td class="text-right text-caption font-weight-bold text-error">{{ formatCurrency(selectedSlipDetail.pot_pph) }}</td></tr>
                        <tr v-if="selectedSlipDetail.pot_taperum > 0"><td class="text-caption">Taperum</td><td class="text-right text-caption font-weight-bold text-error">{{ formatCurrency(selectedSlipDetail.pot_taperum) }}</td></tr>
                        <tr v-if="selectedSlipDetail.pot_jkk > 0"><td class="text-caption">JKK</td><td class="text-right text-caption font-weight-bold text-error">{{ formatCurrency(selectedSlipDetail.pot_jkk) }}</td></tr>
                        <tr v-if="selectedSlipDetail.pot_jkm > 0"><td class="text-caption">JKM</td><td class="text-right text-caption font-weight-bold text-error">{{ formatCurrency(selectedSlipDetail.pot_jkm) }}</td></tr>
                        <tr v-if="selectedSlipDetail.pot_koperasi > 0"><td class="text-caption">Koperasi</td><td class="text-right text-caption font-weight-bold text-error">{{ formatCurrency(selectedSlipDetail.pot_koperasi) }}</td></tr>
                        <tr class="bg-grey-lighten-4">
                          <td class="text-caption font-weight-black">TOTAL POTONGAN</td>
                          <td class="text-right text-caption font-weight-black text-error">{{ formatCurrency(selectedSlipDetail.total_potongan) }}</td>
                        </tr>
                      </tbody>
                    </v-table>
                  </v-card>
                </v-col>
              </v-row>

              <!-- Final Net Pay Footer -->
              <v-card color="primary" variant="flat" class="mt-6 pa-4 rounded-xl d-flex align-center justify-space-between">
                <div>
                  <div class="text-caption text-white opacity-80">Gaji Bersih Diterima (Take Home Pay)</div>
                  <div class="text-h5 font-weight-black text-white">{{ formatCurrency(selectedSlipDetail.bersih) }}</div>
                </div>
                <div class="d-flex align-center justify-center bg-white rounded-lg pa-1" style="width: 70px; height: 70px;">
                  <v-icon icon="mdi-qrcode" color="primary" size="60"></v-icon>
                </div>
              </v-card>
            </div>
         </v-card-text>

         <v-card-actions class="pa-6 border-t bg-surface">
             <v-btn variant="text" color="error" rounded="lg" @click="slipDialog = false" :disabled="downloading" class="px-6 text-none">Tutup</v-btn>
             <v-spacer></v-spacer>
             <v-btn 
              block 
              sm="auto"
              color="primary" 
              variant="flat" 
              rounded="pill" 
              prepend-icon="mdi-file-pdf-box"
              @click="handleDownloadPdf" 
              :loading="downloading"
              class="px-8 text-none font-weight-black shadow-lg"
              style="letter-spacing: 0.5px"
             >
                Unduh Slip Gaji (PDF)
             </v-btn>
         </v-card-actions>
      </v-card>
    </v-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import ThemeToggle from '../../components/ThemeToggle.vue'
import api from '../../api'

const router = useRouter()
const user = ref(JSON.parse(localStorage.getItem('ess_user') || '{}'))
const essToken = localStorage.getItem('ess_token')
const slips = ref([])
const loading = ref(false)
const downloading = ref(false)
const slipDialog = ref(false)
const selectedYear = ref(null)
const selectedSlipDetail = ref(null)

const groupedSlips = computed(() => {
  const groups = {}
  slips.value.forEach(slip => {
    const year = slip.tahun || new Date().getFullYear();
    if (!groups[year]) groups[year] = []
    groups[year].push(slip)
  })
  return groups
})

const availableYears = computed(() => {
  return Object.keys(groupedSlips.value).sort((a, b) => b - a)
})

const firstName = computed(() => {
  if (!user.value.nama) return ''
  return user.value.nama.split(' ')[0]
})

const getMonthName = (monthNum) => {
  const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']
  return months[monthNum - 1] || 'Unknown'
}

const getSlipColor = (jenis) => {
  jenis = (jenis || '').toLowerCase()
  if (jenis.includes('thr')) return 'orange'
  if (jenis.includes('13')) return 'indigo'
  if (jenis.includes('tpp')) return 'teal'
  return 'primary'
}

const formatCurrency = (value) => {
  if (!value) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(value)
}

const handleLogout = () => {
    localStorage.removeItem('ess_token')
    localStorage.removeItem('ess_user')
    router.push('/ess/login')
}

const fetchSlips = async () => {
  loading.value = true
  try {
    // We pass nip via headers so API knows who is requesting
    const response = await api.get('/ess/slips', {
      headers: {
        'X-ESS-NIP': user.value.nip
      }
    })
    if (response.data.success) {
      slips.value = response.data.data
      if (availableYears.value.length > 0 && !selectedYear.value) {
        selectedYear.value = availableYears.value[0]
      }
    }
  } catch (error) {
    if (error.response?.status === 401) handleLogout()
  } finally {
    loading.value = false
  }
}

const viewSlip = async (slipRow) => {
    selectedSlipDetail.value = null;
    slipDialog.value = true;
    
    try {
        const response = await api.get(`/ess/slips/${slipRow.id}/detail`, {
            params: { type: slipRow.tipe || user.value.type },
            headers: { 'X-ESS-NIP': user.value.nip }
        })
        if (response.data.success) {
            selectedSlipDetail.value = response.data.data;
        }
    } catch (error) {
        console.error('Error fetching detail:', error)
    }
}

const handleDownloadPdf = async () => {
    if (!selectedSlipDetail.value) return;
    downloading.value = true;
    try {
        const response = await api.get(`/ess/slips/${selectedSlipDetail.value.id}/pdf`, {
            params: { 
                type: user.value.type,
                nip: user.value.nip
            },
            responseType: 'blob',
            headers: { 'X-ESS-NIP': user.value.nip }
        });
        
        const blob = new Blob([response.data], { type: 'application/pdf' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `Slip_Gaji_${user.value.nip}_${selectedSlipDetail.value.bulan}_${selectedSlipDetail.value.tahun}.pdf`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (error) {
        alert('Gagal mendownload PDF. Silakan coba lagi.');
    } finally {
        downloading.value = false;
    }
}

onMounted(() => {
    if (!essToken) {
        handleLogout()
        return
    }
    fetchSlips()
})
</script>

<style scoped>
.ess-dashboard {
  background-color: rgb(var(--v-theme-background));
  min-height: 100vh;
}

.max-w-7xl {
  max-width: 1200px;
  margin: 0 auto;
}

.glass-card {
  background: linear-gradient(135deg, rgb(var(--v-theme-primary)) 0%, #06B6D4 100%) !important;
  border-radius: 24px !important;
  position: relative;
}

.card-bg-decoration {
  position: absolute;
  top: -50%;
  right: -20%;
  width: 100%;
  height: 200%;
  background: radial-gradient(circle at center, rgba(255,255,255,0.1) 0%, transparent 60%);
  pointer-events: none;
}

.icon-box-small {
  width: 48px;
  height: 48px;
  background: rgba(var(--v-theme-primary), 0.1);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.glass-card-light {
  background: rgba(var(--v-theme-surface), 0.7) !important;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(var(--v-theme-primary), 0.1);
}

.slip-card {
  border-radius: 16px !important;
  border: 1px solid rgba(var(--v-border-color), 0.08);
  transition: all 0.3s ease;
  overflow: hidden;
  background-color: rgb(var(--v-theme-surface)) !important;
}

.slip-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(var(--v-theme-primary), 0.1) !important;
  border-color: rgba(var(--v-theme-primary), 0.3);
}

.slip-header {
  border-bottom: 1px solid rgba(var(--v-border-color), 0.05);
  background-color: rgba(var(--v-theme-surface-variant), 0.3);
}

/* Animations */
.v-data-iterator > div {
  animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px) }
  to { opacity: 1; transform: translateY(0) }
}
</style>
