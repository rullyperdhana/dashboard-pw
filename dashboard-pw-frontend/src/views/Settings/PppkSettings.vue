<template>
  <div class="modern-dashboard">
    <!-- Navbar with Glassmorphism -->
    <Navbar @show-coming-soon="showComingSoon" />
    
    <!-- Modern Sidebar -->
    <Sidebar @show-coming-soon="showComingSoon" />
    
    <v-main class="bg-light">
      <v-container fluid class="pa-8">
        <!-- Page Header -->
        <div class="d-flex align-center mb-6">
          <div>
            <h1 class="text-h4 font-weight-bold text-grey-darken-2">Perhitungan Estimasi JKK, JKM & BPJS Kesehatan</h1>
            <p class="text-body-2 text-medium-emphasis mt-1">Estimasi pembayaran iuran ketenagakerjaan dan kesehatan per periode</p>
          </div>
          <v-spacer></v-spacer>
          <v-btn variant="tonal" color="info" rounded="pill" @click="fetchAllEstimations" :loading="loadingEstimation" class="mr-3">
            <v-icon start icon="mdi-refresh"></v-icon>
            Refresh
          </v-btn>
          <v-btn variant="tonal" color="primary" rounded="pill" @click="showSettings = !showSettings">
            <v-icon start icon="mdi-cog"></v-icon>
            Parameter JKK & JKM
            <v-icon end :icon="showSettings ? 'mdi-chevron-up' : 'mdi-chevron-down'"></v-icon>
          </v-btn>
        </div>

        <!-- Collapsible Settings Panel -->
        <v-expand-transition>
          <v-card v-show="showSettings" class="rounded-xl glass-card mb-6" elevation="0">
            <v-card-text class="pa-6">
              <v-row align="center">
                <v-col cols="12" md="3">
                  <v-text-field
                    v-model="settings.pppk_jkk_percentage"
                    label="Persentase Iuran JKK (%)"
                    type="number" step="0.01" min="0"
                    hint="Contoh: 0.24" persistent-hint
                    variant="outlined" color="primary" bg-color="surface" density="compact"
                    :loading="loading"
                  ></v-text-field>
                </v-col>
                <v-col cols="12" md="3">
                  <v-text-field
                    v-model="settings.pppk_jkm_percentage"
                    label="Persentase Iuran JKM (%)"
                    type="number" step="0.01" min="0"
                    hint="Contoh: 0.72" persistent-hint
                    variant="outlined" color="primary" bg-color="surface" density="compact"
                    :loading="loading"
                  ></v-text-field>
                </v-col>
                <v-col cols="12" md="3" class="d-flex align-center">
                  <v-btn color="primary" :loading="saving" prepend-icon="mdi-content-save" class="rounded-lg" @click="saveSettings">
                    Simpan Perubahan
                  </v-btn>
                </v-col>
                <v-col cols="12" md="3">
                  <v-alert v-if="successMessage" type="success" variant="tonal" class="rounded-lg" density="compact" closable @click:close="successMessage = ''">
                    {{ successMessage }}
                  </v-alert>
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>
        </v-expand-transition>

        <!-- Full-width Estimation Card -->
        <v-card class="rounded-xl glass-card" elevation="0">
          <v-card-title class="bg-secondary text-white py-4 px-6">
            <v-icon start icon="mdi-calculator" class="mr-2"></v-icon>
            Estimasi Pembayaran
          </v-card-title>
          
          <v-tabs
            v-model="activeTab"
            bg-color="secondary"
            slider-color="white"
            density="comfortable"
          >
              <v-tab value="pns" class="text-white">PNS</v-tab>
              <v-tab value="full_time" class="text-white">PPPK Penuh Waktu</v-tab>
              <v-tab value="part_time" class="text-white">PPPK Paruh Waktu</v-tab>
          </v-tabs>
    
          <v-window v-model="activeTab">
            <!-- PNS Tab -->
            <v-window-item value="pns">
                <v-card-text class="pt-6 px-6">
                    <v-row class="mb-6">
                        <v-col cols="12" md="3">
                             <v-select
                                v-model="selectedMonth"
                                :items="months"
                                item-title="text"
                                item-value="value"
                                label="Bulan"
                                variant="outlined"
                                density="comfortable"
                                bg-color="surface"
                                hide-details
                                color="primary"
                                @update:modelValue="fetchEstimationPns"
                            ></v-select>
                        </v-col>
                        <v-col cols="12" md="3">
                             <v-select
                                v-model="selectedYear"
                                :items="years"
                                label="Tahun"
                                variant="outlined"
                                density="comfortable"
                                bg-color="surface"
                                hide-details
                                color="primary"
                                @update:modelValue="fetchEstimationPns"
                            ></v-select>
                        </v-col>
                        <v-col cols="12" md="6" class="d-flex align-center gap-2">
                              <v-chip color="info" label class="font-weight-medium">
                                  {{ getMonthName(selectedMonth) }} {{ selectedYear }}
                              </v-chip>
                              <v-btn v-if="estimationPns" variant="tonal" color="success" size="small" rounded="pill" @click="exportData('pns')" :loading="exporting">
                                <v-icon start icon="mdi-file-excel"></v-icon>
                                Export XLS
                              </v-btn>
                        </v-col>
                    </v-row>

                    <div v-if="estimationPns">
                        <v-row class="mb-4">
                          <v-col cols="12" md="3">
                            <div class="text-caption text-medium-emphasis mb-1">Periode Data</div>
                            <div class="text-subtitle-1 font-weight-bold">{{ getMonthName(estimationPns.period.month) }} {{ estimationPns.period.year }}</div>
                          </v-col>
                          <v-col cols="12" md="3">
                            <div class="text-caption text-medium-emphasis mb-1">Total Pegawai</div>
                            <div class="text-subtitle-1 font-weight-bold">{{ formatNumber(estimationPns.employees_count) }} Orang</div>
                          </v-col>
                          <v-col cols="12" md="3">
                            <div class="text-caption text-medium-emphasis mb-1">Total Gaji Pokok</div>
                            <div class="text-h6 font-weight-bold text-grey-darken-3">{{ formatCurrency(estimationPns.total_gaji_pokok) }}</div>
                          </v-col>
                        </v-row>

                        <v-divider class="mb-4"></v-divider>

                        <v-row class="mb-4">
                        <v-col cols="12" md="3">
                            <v-card variant="tonal" color="warning" class="pa-4 rounded-lg">
                            <div class="text-caption text-medium-emphasis mb-1">Estimasi JKK ({{ estimationPns.settings.jkk_percent }}%)</div>
                            <div class="text-h6 font-weight-bold">{{ formatCurrency(estimationPns.estimation.jkk_amount) }}</div>
                            </v-card>
                        </v-col>
                        <v-col cols="12" md="3">
                            <v-card variant="tonal" color="error" class="pa-4 rounded-lg">
                            <div class="text-caption text-medium-emphasis mb-1">Estimasi JKM ({{ estimationPns.settings.jkm_percent }}%)</div>
                            <div class="text-h6 font-weight-bold">{{ formatCurrency(estimationPns.estimation.jkm_amount) }}</div>
                            </v-card>
                        </v-col>
                        <v-col cols="12" md="3">
                            <v-card variant="tonal" color="info" class="pa-4 rounded-lg">
                            <div class="text-caption text-medium-emphasis mb-1">Estimasi BPJS Kes ({{ estimationPns.settings.bpjs_percent }}%)</div>
                            <div class="text-h6 font-weight-bold">{{ formatCurrency(estimationPns.estimation.bpjs_kesehatan_amount) }}</div>
                            </v-card>
                        </v-col>
                        <v-col cols="12" md="3">
                            <v-card variant="flat" color="success" class="pa-4 rounded-lg">
                            <div class="text-caption mb-1 text-white" style="opacity:0.8">Total Estimasi</div>
                            <div class="text-h6 font-weight-black text-white">{{ formatCurrency(estimationPns.estimation.total_amount) }}</div>
                            </v-card>
                        </v-col>
                        </v-row>
    
                        <v-divider class="my-4 border-opacity-10"></v-divider>
    
                        <h3 class="text-h6 font-weight-bold mb-4 text-grey-darken-2">Rincian per SKPD</h3>
                        <p class="text-body-2 text-medium-emphasis mb-3">Klik baris SKPD untuk melihat rincian per pegawai</p>
                        
                        <v-data-table
                            :headers="pwHeaders"
                            :items="estimationPns.details"
                            items-per-page="10"
                            class="elevation-0 border rounded-lg cursor-pointer"
                            density="comfortable"
                            hover
                            @click:row="(event, { item }) => openDetail('pns', item)"
                        >
                            <template v-slot:item.total_gaji_pokok="{ item }">
                                {{ formatCurrency(item.total_gaji_pokok) }}
                            </template>
                            <template v-slot:item.total_tunjangan="{ item }">
                                {{ formatCurrency(item.total_tunjangan) }}
                            </template>
                            <template v-slot:item.tunjangan_jkk="{ item }">
                                {{ formatCurrency(item.tunjangan_jkk) }}
                            </template>
                            <template v-slot:item.tunjangan_jkm="{ item }">
                                {{ formatCurrency(item.tunjangan_jkm) }}
                            </template>
                            <template v-slot:item.bpjs_kesehatan="{ item }">
                                {{ formatCurrency(item.bpjs_kesehatan) }}
                            </template>
                            <template v-slot:item.total_estimation="{ item }">
                                <span class="font-weight-bold text-success">{{ formatCurrency(item.total_estimation) }}</span>
                            </template>
                        </v-data-table>
                    </div>
                    <div v-else class="pa-8 text-center">
                        <v-progress-circular indeterminate color="primary" v-if="loadingEstimation"></v-progress-circular>
                        <v-alert type="info" variant="tonal" icon="mdi-information" v-else>
                            Belum ada data gaji PNS untuk periode ini.
                        </v-alert>
                    </div>
                </v-card-text>
            </v-window-item>

            <!-- Full Time Tab -->
            <v-window-item value="full_time">
                <v-card-text class="pt-6 px-6">
                    <v-row class="mb-6">
                        <v-col cols="12" md="3">
                             <v-select
                                v-model="selectedMonth"
                                :items="months"
                                item-title="text"
                                item-value="value"
                                label="Bulan"
                                variant="outlined"
                                density="comfortable"
                                bg-color="surface"
                                hide-details
                                color="primary"
                                @update:modelValue="fetchEstimation"
                            ></v-select>
                        </v-col>
                        <v-col cols="12" md="3">
                             <v-select
                                v-model="selectedYear"
                                :items="years"
                                label="Tahun"
                                variant="outlined"
                                density="comfortable"
                                bg-color="surface"
                                hide-details
                                color="primary"
                                @update:modelValue="fetchEstimation"
                            ></v-select>
                        </v-col>
                        <v-col cols="12" md="6" class="d-flex align-center gap-2">
                              <v-chip color="info" label class="font-weight-medium">
                                  {{ getMonthName(selectedMonth) }} {{ selectedYear }}
                              </v-chip>
                              <v-btn v-if="estimation" variant="tonal" color="success" size="small" rounded="pill" @click="exportData('pppk')" :loading="exporting">
                                <v-icon start icon="mdi-file-excel"></v-icon>
                                Export XLS
                              </v-btn>
                        </v-col>
                    </v-row>

                    <div v-if="estimation">
                        <v-row class="mb-4">
                          <v-col cols="12" md="3">
                            <div class="text-caption text-medium-emphasis mb-1">Periode Data</div>
                            <div class="text-subtitle-1 font-weight-bold">{{ getMonthName(estimation.period.month) }} {{ estimation.period.year }}</div>
                          </v-col>
                          <v-col cols="12" md="3">
                            <div class="text-caption text-medium-emphasis mb-1">Total Pegawai</div>
                            <div class="text-subtitle-1 font-weight-bold">{{ formatNumber(estimation.employees_count) }} Orang</div>
                          </v-col>
                          <v-col cols="12" md="3">
                            <div class="text-caption text-medium-emphasis mb-1">Total Gaji Pokok</div>
                            <div class="text-h6 font-weight-bold text-grey-darken-3">{{ formatCurrency(estimation.total_gaji_pokok) }}</div>
                          </v-col>
                        </v-row>
    
                        <v-divider class="mb-4"></v-divider>

                        <v-row class="mb-4">
                        <v-col cols="12" md="3">
                            <v-card variant="tonal" color="warning" class="pa-4 rounded-lg">
                            <div class="text-caption text-medium-emphasis mb-1">Estimasi JKK ({{ estimation.settings.jkk_percent }}%)</div>
                            <div class="text-h6 font-weight-bold">{{ formatCurrency(estimation.estimation.jkk_amount) }}</div>
                            </v-card>
                        </v-col>
                        <v-col cols="12" md="3">
                            <v-card variant="tonal" color="error" class="pa-4 rounded-lg">
                            <div class="text-caption text-medium-emphasis mb-1">Estimasi JKM ({{ estimation.settings.jkm_percent }}%)</div>
                            <div class="text-h6 font-weight-bold">{{ formatCurrency(estimation.estimation.jkm_amount) }}</div>
                            </v-card>
                        </v-col>
                        <v-col cols="12" md="3">
                            <v-card variant="tonal" color="info" class="pa-4 rounded-lg">
                            <div class="text-caption text-medium-emphasis mb-1">Estimasi BPJS Kes ({{ estimation.settings.bpjs_percent }}%)</div>
                            <div class="text-h6 font-weight-bold">{{ formatCurrency(estimation.estimation.bpjs_kesehatan_amount) }}</div>
                            </v-card>
                        </v-col>
                        <v-col cols="12" md="3">
                            <v-card variant="flat" color="success" class="pa-4 rounded-lg">
                            <div class="text-caption mb-1 text-white" style="opacity:0.8">Total Estimasi</div>
                            <div class="text-h6 font-weight-black text-white">{{ formatCurrency(estimation.estimation.total_amount) }}</div>
                            </v-card>
                        </v-col>
                        </v-row>
    
                        <v-divider class="my-4 border-opacity-10"></v-divider>
    
                        <h3 class="text-h6 font-weight-bold mb-4 text-grey-darken-2">Rincian per SKPD</h3>
                        <p class="text-body-2 text-medium-emphasis mb-3">Klik baris SKPD untuk melihat rincian per pegawai</p>
                        
                        <v-data-table
                            :headers="pwHeaders"
                            :items="estimation.details"
                            items-per-page="10"
                            class="elevation-0 border rounded-lg cursor-pointer"
                            density="comfortable"
                            hover
                            @click:row="(event, { item }) => openDetail('pppk', item)"
                        >
                            <template v-slot:item.total_gaji_pokok="{ item }">
                                {{ formatCurrency(item.total_gaji_pokok) }}
                            </template>
                            <template v-slot:item.total_tunjangan="{ item }">
                                {{ formatCurrency(item.total_tunjangan) }}
                            </template>
                            <template v-slot:item.tunjangan_jkk="{ item }">
                                {{ formatCurrency(item.tunjangan_jkk) }}
                            </template>
                            <template v-slot:item.tunjangan_jkm="{ item }">
                                {{ formatCurrency(item.tunjangan_jkm) }}
                            </template>
                            <template v-slot:item.bpjs_kesehatan="{ item }">
                                {{ formatCurrency(item.bpjs_kesehatan) }}
                            </template>
                            <template v-slot:item.total_estimation="{ item }">
                                <span class="font-weight-bold text-success">{{ formatCurrency(item.total_estimation) }}</span>
                            </template>
                        </v-data-table>
                    </div>
                     <div v-else class="pa-8 text-center">
                        <v-progress-circular indeterminate color="primary" v-if="loadingEstimation"></v-progress-circular>
                        <v-alert type="info" variant="tonal" icon="mdi-information" v-else>
                            Belum ada data gaji PPPK untuk periode ini.
                        </v-alert>
                    </div>
                </v-card-text>
            </v-window-item>
    
            <!-- Part Time Tab -->
            <v-window-item value="part_time">
                <v-card-text class="pt-6 px-6">
                    <v-row class="mb-6">
                        <v-col cols="12" md="3">
                             <v-select
                                v-model="selectedMonth"
                                :items="months"
                                item-title="text"
                                item-value="value"
                                label="Bulan"
                                variant="outlined"
                                density="comfortable"
                                bg-color="surface"
                                hide-details
                                color="primary"
                                @update:modelValue="fetchEstimationPw"
                            ></v-select>
                        </v-col>
                        <v-col cols="12" md="3">
                             <v-select
                                v-model="selectedYear"
                                :items="years"
                                label="Tahun"
                                variant="outlined"
                                density="comfortable"
                                bg-color="surface"
                                hide-details
                                color="primary"
                                @update:modelValue="fetchEstimationPw"
                            ></v-select>
                        </v-col>
                        <v-col cols="12" md="6" class="d-flex align-center gap-2">
                              <v-chip color="info" label class="font-weight-medium">
                                  {{ getMonthName(selectedMonth) }} {{ selectedYear }}
                              </v-chip>
                              <v-btn v-if="estimationPw" variant="tonal" color="success" size="small" rounded="pill" @click="exportData('pppk_pw')" :loading="exporting">
                                <v-icon start icon="mdi-file-excel"></v-icon>
                                Export XLS
                              </v-btn>
                        </v-col>
                    </v-row>
    
                    <div v-if="estimationPw">
                        <v-row class="mb-4">
                          <v-col cols="12" md="3">
                            <div class="text-caption text-medium-emphasis mb-1">Referensi Data</div>
                            <div class="text-subtitle-1 font-weight-bold">{{ getMonthName(estimationPw.period.month) }} {{ estimationPw.period.year }}</div>
                          </v-col>
                          <v-col cols="12" md="3">
                            <div class="text-caption text-medium-emphasis mb-1">Total Pegawai</div>
                            <div class="text-subtitle-1 font-weight-bold">{{ formatNumber(estimationPw.employees_count) }} Orang <span v-if="estimationPw.employees_count < 6399" class="text-caption text-warning ml-2 font-weight-medium">(Dikurangi pensiun)</span></div>
                          </v-col>
                          <v-col cols="12" md="3">
                            <div class="text-caption text-medium-emphasis mb-1">Total Gaji Pokok</div>
                            <div class="text-h6 font-weight-bold text-grey-darken-3">{{ formatCurrency(estimationPw.total_gaji_pokok) }}</div>
                          </v-col>
                          <v-col cols="12" md="3">
                            <div class="text-caption text-medium-emphasis mb-1">Total Tunjangan</div>
                            <div class="text-h6 font-weight-bold text-grey-darken-3">{{ formatCurrency(estimationPw.total_tunjangan) }}</div>
                          </v-col>
                        </v-row>
    
                        <v-divider class="mb-4"></v-divider>

                        <v-row class="mb-4">
                        <v-col cols="12" md="3">
                            <v-card variant="tonal" color="warning" class="pa-4 rounded-lg">
                            <div class="text-caption text-medium-emphasis mb-1">Estimasi JKK ({{ estimationPw.settings.jkk_percent }}%)</div>
                            <div class="text-h6 font-weight-bold">{{ formatCurrency(estimationPw.estimation.jkk_amount) }}</div>
                            </v-card>
                        </v-col>
                        <v-col cols="12" md="3">
                            <v-card variant="tonal" color="error" class="pa-4 rounded-lg">
                            <div class="text-caption text-medium-emphasis mb-1">Estimasi JKM ({{ estimationPw.settings.jkm_percent }}%)</div>
                            <div class="text-h6 font-weight-bold">{{ formatCurrency(estimationPw.estimation.jkm_amount) }}</div>
                            </v-card>
                        </v-col>
                        <v-col cols="12" md="3">
                            <v-card variant="tonal" color="info" class="pa-4 rounded-lg">
                            <div class="text-caption text-medium-emphasis mb-1">Estimasi BPJS Kes ({{ estimationPw.settings.bpjs_percent }}%)</div>
                            <div class="text-h6 font-weight-bold">{{ formatCurrency(estimationPw.estimation.bpjs_kesehatan_amount) }}</div>
                            </v-card>
                        </v-col>
                        <v-col cols="12" md="3">
                            <v-card variant="flat" color="success" class="pa-4 rounded-lg">
                            <div class="text-caption mb-1 text-white" style="opacity:0.8">Total Estimasi</div>
                            <div class="text-h6 font-weight-black text-white">{{ formatCurrency(estimationPw.estimation.total_amount) }}</div>
                            </v-card>
                        </v-col>
                        </v-row>
    
                        <v-divider class="my-4 border-opacity-10"></v-divider>
    
                        <h3 class="text-h6 font-weight-bold mb-4 text-grey-darken-2">Rincian per SKPD</h3>
                        <p class="text-body-2 text-medium-emphasis mb-3">Klik baris SKPD untuk melihat rincian per pegawai</p>
                        
                        <v-data-table
                            :headers="pwHeaders"
                            :items="estimationPw.details"
                            items-per-page="10"
                            class="elevation-0 border rounded-lg cursor-pointer"
                            density="comfortable"
                            hover
                            @click:row="(event, { item }) => openDetail('pppk_pw', item)"
                        >
                            <template v-slot:item.total_gaji_pokok="{ item }">
                                {{ formatCurrency(item.total_gaji_pokok) }}
                            </template>
                            <template v-slot:item.total_tunjangan="{ item }">
                                {{ formatCurrency(item.total_tunjangan) }}
                            </template>
                            <template v-slot:item.tunjangan_jkk="{ item }">
                                {{ formatCurrency(item.tunjangan_jkk) }}
                            </template>
                            <template v-slot:item.tunjangan_jkm="{ item }">
                                {{ formatCurrency(item.tunjangan_jkm) }}
                            </template>
                            <template v-slot:item.bpjs_kesehatan="{ item }">
                                {{ formatCurrency(item.bpjs_kesehatan) }}
                            </template>
                            <template v-slot:item.total_estimation="{ item }">
                                <span class="font-weight-bold text-success">{{ formatCurrency(item.total_estimation) }}</span>
                            </template>
                        </v-data-table>
                    </div>
                    <div v-else class="pa-8 text-center">
                        <v-alert type="info" variant="tonal" icon="mdi-information">
                            Belum ada data pegawai PPPK Paruh Waktu.
                        </v-alert>
                    </div>
                </v-card-text>
            </v-window-item>
          </v-window>
        </v-card>
      </v-container>
    </v-main>

    <!-- Employee Detail Dialog -->
    <v-dialog v-model="detailDialog" max-width="1400" scrollable>
      <v-card class="rounded-xl">
        <v-card-title class="bg-secondary text-white py-4 px-6 d-flex align-center">
          <v-icon start icon="mdi-account-group" class="mr-2"></v-icon>
          Rincian Per Pegawai
          <v-spacer></v-spacer>
          <v-btn variant="tonal" color="white" size="small" rounded="pill" class="mr-2" @click="exportDetailData" :loading="exporting">
            <v-icon start icon="mdi-file-excel"></v-icon>
            Export SKPD
          </v-btn>
          <v-btn icon="mdi-close" variant="text" color="white" @click="detailDialog = false"></v-btn>
        </v-card-title>

        <v-card-text class="pa-6">
          <!-- SKPD Info -->
          <v-row class="mb-4">
            <v-col cols="12" md="4">
              <div class="text-caption text-medium-emphasis mb-1">SKPD</div>
              <div class="text-subtitle-1 font-weight-bold">{{ detailSkpdName }}</div>
            </v-col>
            <v-col cols="12" md="4">
              <div class="text-caption text-medium-emphasis mb-1">Jumlah Pegawai</div>
              <div class="text-subtitle-1 font-weight-bold">{{ formatNumber(detailEmployees.length) }} Orang</div>
            </v-col>
            <v-col cols="12" md="4">
              <div class="text-caption text-medium-emphasis mb-1">Periode</div>
              <div class="text-subtitle-1 font-weight-bold">{{ getMonthName(selectedMonth) }} {{ selectedYear }}</div>
            </v-col>
          </v-row>

          <v-divider class="mb-4"></v-divider>

          <!-- Loading -->
          <div v-if="loadingDetail" class="pa-8 text-center">
            <v-progress-circular indeterminate color="primary"></v-progress-circular>
            <div class="text-body-2 text-medium-emphasis mt-2">Memuat data pegawai...</div>
          </div>

          <!-- Employee Table -->
          <v-data-table
            v-else
            :headers="detailType === 'pppk_pw' ? detailHeadersPw : detailHeaders"
            :items="detailEmployees"
            :group-by="detailType === 'pppk_pw' ? [{ key: 'jabatan', order: 'asc' }] : []"
            items-per-page="20"
            class="elevation-0 border rounded-lg"
            density="compact"
            hover
          >
            <!-- Custom Group Header for PPPK-PW with Tree Model and Sums -->
            <template v-slot:group-header="{ item, columns, toggleGroup, isGroupOpen }">
              <tr class="bg-blue-lighten-5 group-header-row">
                <td :colspan="3" class="py-2 px-4 cursor-pointer" @click="toggleGroup(item)">
                  <div class="d-flex align-center">
                    <v-btn
                      :icon="isGroupOpen(item) ? 'mdi-chevron-down' : 'mdi-chevron-right'"
                      variant="text"
                      size="small"
                      density="compact"
                      class="mr-2"
                    ></v-btn>
                    <v-icon color="primary" class="mr-2" size="20">mdi-briefcase-outline</v-icon>
                    <span class="font-weight-bold text-subtitle-2">{{ item.value || 'Tanpa Jabatan' }}</span>
                    <v-chip size="x-small" color="primary" class="ml-3 font-weight-bold" variant="flat">
                      {{ item.items.length }} Pegawai
                    </v-chip>
                  </div>
                </td>
                <td class="text-right font-weight-bold py-2">
                  {{ formatCurrency(item.items.reduce((acc, row) => acc + (row.raw.gaji_pokok || 0), 0)) }}
                </td>
                <td v-if="detailType === 'pppk_pw'" class="text-right font-weight-bold py-2">
                  {{ formatCurrency(item.items.reduce((acc, row) => acc + (row.raw.tunjangan || 0), 0)) }}
                </td>
                <td v-else :colspan="4"></td> <!-- Placeholder for other types -->
                <td class="text-right font-weight-bold py-2"></td> <!-- BPJS Base placeholder -->
                <td class="text-right font-weight-bold py-2">
                  {{ formatCurrency(item.items.reduce((acc, row) => acc + (row.raw.jkk || 0), 0)) }}
                </td>
                <td class="text-right font-weight-bold py-2">
                  {{ formatCurrency(item.items.reduce((acc, row) => acc + (row.raw.jkm || 0), 0)) }}
                </td>
                <td class="text-right font-weight-bold py-2">
                  {{ formatCurrency(item.items.reduce((acc, row) => acc + (row.raw.bpjs_kesehatan || 0), 0)) }}
                </td>
                <td class="text-right font-weight-bold py-2 text-success">
                  {{ formatCurrency(item.items.reduce((acc, row) => acc + (row.raw.total_estimation || 0), 0)) }}
                </td>
              </tr>
            </template>

            <template v-slot:item.gaji_pokok="{ item }">
                {{ formatCurrency(item.gaji_pokok) }}
            </template>
            <template v-slot:item.tunj_keluarga="{ item }">
                {{ formatCurrency(item.tunj_keluarga) }}
            </template>
            <template v-slot:item.tunj_jabatan="{ item }">
                {{ formatCurrency(item.tunj_jabatan) }}
            </template>
            <template v-slot:item.tunj_tpp="{ item }">
                {{ formatCurrency(item.tunj_tpp) }}
            </template>
            <template v-slot:item.tunjangan="{ item }">
                {{ formatCurrency(item.tunjangan) }}
            </template>
            <template v-slot:item.bpjs_base="{ item }">
                {{ formatCurrency(item.bpjs_base) }}
            </template>
            <template v-slot:item.jkk="{ item }">
                {{ formatCurrency(item.jkk) }}
            </template>
            <template v-slot:item.jkm="{ item }">
                {{ formatCurrency(item.jkm) }}
            </template>
            <template v-slot:item.bpjs_kesehatan="{ item }">
                {{ formatCurrency(item.bpjs_kesehatan) }}
            </template>
            <template v-slot:item.total_estimation="{ item }">
                <span class="font-weight-bold text-success">{{ formatCurrency(item.total_estimation) }}</span>
            </template>
          </v-data-table>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Global Feedback Snackbar -->
    <v-snackbar v-model="snackbar" :timeout="3000" color="primary" rounded="lg" elevation="4">
      <div class="d-flex align-center">
        <v-icon class="mr-3">mdi-information-outline</v-icon>
        <div>
          <div class="font-weight-bold">{{ snackbarTitle }}</div>
          <div class="text-caption">Fitur ini akan segera hadir.</div>
        </div>
      </div>
      <template v-slot:actions>
        <v-btn variant="text" @click="snackbar = false">TUTUP</v-btn>
      </template>
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/api'
import Sidebar from '@/components/Sidebar.vue'
import Navbar from '@/components/Navbar.vue'

const loading = ref(false)
const saving = ref(false)
const loadingEstimation = ref(false)
const loadingDetail = ref(false)
const exporting = ref(false)
const successMessage = ref('')
const activeTab = ref('pns')
const showSettings = ref(false)
const snackbar = ref(false)
const snackbarTitle = ref('')

// Detail dialog state
const detailDialog = ref(false)
const detailEmployees = ref([])
const detailSkpdName = ref('')
const detailSkpdId = ref('')
const detailType = ref('')

const showComingSoon = (feature) => {
  snackbarTitle.value = feature
  snackbar.value = true
}

// Date Selection
const currentDate = new Date()
const selectedMonth = ref(currentDate.getMonth() + 1)
const selectedYear = ref(currentDate.getFullYear())

const months = [
    { text: 'Januari', value: 1 },
    { text: 'Februari', value: 2 },
    { text: 'Maret', value: 3 },
    { text: 'April', value: 4 },
    { text: 'Mei', value: 5 },
    { text: 'Juni', value: 6 },
    { text: 'Juli', value: 7 },
    { text: 'Agustus', value: 8 },
    { text: 'September', value: 9 },
    { text: 'Oktober', value: 10 },
    { text: 'November', value: 11 },
    { text: 'Desember', value: 12 }
]

const years = []
for (let i = 2024; i <= 2030; i++) {
    years.push(i)
}

const pwHeaders = [
  { title: 'SKPD', key: 'nama_skpd' },
  { title: 'Pegawai', key: 'employee_count', align: 'end' },
  { title: 'Gaji Pokok', key: 'total_gaji_pokok', align: 'end' },
  { title: 'JKK', key: 'tunjangan_jkk', align: 'end' },
  { title: 'JKM', key: 'tunjangan_jkm', align: 'end' },
  { title: 'BPJS Kes', key: 'bpjs_kesehatan', align: 'end' },
  { title: 'Total', key: 'total_estimation', align: 'end' },
]

// Detail table headers for PNS/PPPK
const detailHeaders = [
  { title: 'NIP', key: 'nip', width: '160px' },
  { title: 'Nama', key: 'nama' },
  { title: 'Jabatan', key: 'jabatan' },
  { title: 'Gaji Pokok', key: 'gaji_pokok', align: 'end' },
  { title: 'Tunj. Keluarga', key: 'tunj_keluarga', align: 'end' },
  { title: 'Tunj. Jabatan/Umum', key: 'tunj_jabatan', align: 'end' },
  { title: 'TPP', key: 'tunj_tpp', align: 'end' },
  { title: 'BPJS Base', key: 'bpjs_base', align: 'end' },
  { title: 'JKK', key: 'jkk', align: 'end' },
  { title: 'JKM', key: 'jkm', align: 'end' },
  { title: 'BPJS Kes', key: 'bpjs_kesehatan', align: 'end' },
  { title: 'Total', key: 'total_estimation', align: 'end' },
]

// Detail table headers for PPPK PW
const detailHeadersPw = [
  { title: 'NIP', key: 'nip', width: '160px' },
  { title: 'Nama', key: 'nama' },
  { title: 'Jabatan', key: 'jabatan' },
  { title: 'Gaji Pokok', key: 'gaji_pokok', align: 'end' },
  { title: 'Tunjangan', key: 'tunjangan', align: 'end' },
  { title: 'BPJS Base', key: 'bpjs_base', align: 'end' },
  { title: 'JKK', key: 'jkk', align: 'end' },
  { title: 'JKM', key: 'jkm', align: 'end' },
  { title: 'BPJS Kes', key: 'bpjs_kesehatan', align: 'end' },
  { title: 'Total', key: 'total_estimation', align: 'end' },
]

const settings = ref({
  pppk_jkk_percentage: 0.24,
  pppk_jkm_percentage: 0.72
})

const estimation = ref(null)
const estimationPw = ref(null)
const estimationPns = ref(null)

const fetchSettings = async () => {
    loading.value = true
    try {
        const response = await api.get('/settings')
        if (response.data.success) {
            const data = response.data.data
            if (data.pppk_jkk_percentage) settings.value.pppk_jkk_percentage = parseFloat(data.pppk_jkk_percentage.value)
            if (data.pppk_jkm_percentage) settings.value.pppk_jkm_percentage = parseFloat(data.pppk_jkm_percentage.value)
        }
    } catch (error) {
        console.error('Error fetching settings:', error)
    } finally {
        loading.value = false
    }
}

const fetchEstimation = async () => {
    try {
        const response = await api.get('/settings/pppk-estimation', {
            params: {
                month: selectedMonth.value,
                year: selectedYear.value
            }
        })
        if (response.data.success) {
            estimation.value = response.data.data
        }
    } catch (error) {
        console.error('Error fetching estimation:', error)
    }
}

const fetchEstimationPw = async () => {
    try {
        const response = await api.get('/settings/pppk-pw-estimation', {
            params: {
                month: selectedMonth.value,
                year: selectedYear.value
            }
        })
        if (response.data.success) {
            estimationPw.value = response.data.data
        }
    } catch (error) {
        console.error('Error fetching PW estimation:', error)
    }
}

const fetchEstimationPns = async () => {
    try {
        const response = await api.get('/settings/pns-estimation', {
             params: {
                month: selectedMonth.value,
                year: selectedYear.value
            }
        })
        if (response.data.success) {
            estimationPns.value = response.data.data
        }
    } catch (error) {
        console.error('Error fetching PNS estimation:', error)
    }
}

const fetchAllEstimations = async () => {
    loadingEstimation.value = true
    await Promise.all([fetchEstimation(), fetchEstimationPw(), fetchEstimationPns()])
    loadingEstimation.value = false
}

const saveSettings = async () => {
    saving.value = true
    successMessage.value = ''
    try {
        const payload = {
            settings: [
                { key: 'pppk_jkk_percentage', value: settings.value.pppk_jkk_percentage },
                { key: 'pppk_jkm_percentage', value: settings.value.pppk_jkm_percentage }
            ]
        }
        const response = await api.post('/settings', payload)
        if (response.data.success) {
            successMessage.value = 'Pengaturan berhasil disimpan!'
            fetchAllEstimations() // Refresh estimation with new settings
        }
    } catch (error) {
        console.error('Error saving settings:', error)
    } finally {
        saving.value = false
    }
}

// ========== Detail Dialog ==========

const openDetail = async (type, item) => {
    detailType.value = type
    detailSkpdName.value = item.nama_skpd
    detailSkpdId.value = item.id_skpd
    detailEmployees.value = []
    detailDialog.value = true
    loadingDetail.value = true

    const endpointMap = {
        pns: '/settings/pns-estimation-detail',
        pppk: '/settings/pppk-estimation-detail',
        pppk_pw: '/settings/pppk-pw-estimation-detail'
    }

    try {
        const response = await api.get(endpointMap[type], {
            params: {
                month: selectedMonth.value,
                year: selectedYear.value,
                kdskpd: item.id_skpd
            }
        })
        if (response.data.success) {
            detailEmployees.value = response.data.data
        }
    } catch (error) {
        console.error('Error fetching detail:', error)
    } finally {
        loadingDetail.value = false
    }
}

// ========== Export ==========

const getExportUrl = (type, kdskpd = null, skpdName = null) => {
    const endpointMap = {
        pns: '/settings/pns-estimation-export',
        pppk: '/settings/pppk-estimation-export',
        pppk_pw: '/settings/pppk-pw-estimation-export'
    }
    const params = new URLSearchParams({
        month: selectedMonth.value,
        year: selectedYear.value
    })
    if (kdskpd) params.append('kdskpd', kdskpd)
    if (skpdName) params.append('skpd_name', skpdName)
    return `/api${endpointMap[type]}?${params.toString()}`
}

const exportData = (type) => {
    const token = localStorage.getItem('token')
    const url = getExportUrl(type)
    
    exporting.value = true
    // Use fetch to download with auth token
    fetch(url, {
        headers: { 'Authorization': `Bearer ${token}` }
    })
    .then(response => response.blob())
    .then(blob => {
        const link = document.createElement('a')
        link.href = URL.createObjectURL(blob)
        link.download = `estimasi_${type}_${selectedMonth.value}_${selectedYear.value}.xlsx`
        link.click()
        URL.revokeObjectURL(link.href)
    })
    .catch(error => console.error('Export error:', error))
    .finally(() => { exporting.value = false })
}

const exportDetailData = () => {
    const token = localStorage.getItem('token')
    const url = getExportUrl(detailType.value, detailSkpdId.value, detailSkpdName.value)
    
    exporting.value = true
    fetch(url, {
        headers: { 'Authorization': `Bearer ${token}` }
    })
    .then(response => response.blob())
    .then(blob => {
        const link = document.createElement('a')
        link.href = URL.createObjectURL(blob)
        link.download = `estimasi_${detailType.value}_${detailSkpdName.value}_${selectedMonth.value}_${selectedYear.value}.xlsx`
        link.click()
        URL.revokeObjectURL(link.href)
    })
    .catch(error => console.error('Export error:', error))
    .finally(() => { exporting.value = false })
}

const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value || 0)
}

const formatNumber = (value) => {
  return new Intl.NumberFormat('id-ID').format(value || 0)
}

const getMonthName = (month) => {
    if (!month) return ''
    const date = new Date()
    date.setMonth(month - 1)
    return date.toLocaleString('id-ID', { month: 'long' })
}

onMounted(() => {
    fetchSettings()
    fetchAllEstimations()
})
</script>

<style scoped>
.glass-sidebar {
  border-right: 1px solid rgba(var(--v-border-color), 0.08) !important;
  background-color: rgba(var(--v-theme-surface), 0.95) !important;
  backdrop-filter: blur(10px);
}

.glass-card {
  background-color: rgb(var(--v-theme-surface)) !important;
  border: 1px solid rgba(var(--v-border-color), 0.08) !important;
  transition: box-shadow 0.2s ease;
}

.bg-light {
  background-color: rgb(var(--v-theme-background));
}

.modern-dashboard {
  background-color: rgb(var(--v-theme-background)) !important;
}

.cursor-pointer :deep(tbody tr) {
  cursor: pointer;
}

.cursor-pointer :deep(tbody tr:hover) {
  background-color: rgba(var(--v-theme-primary), 0.04) !important;
}
</style>
