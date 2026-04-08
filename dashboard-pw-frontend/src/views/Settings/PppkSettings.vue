<template>
  <div class="modern-dashboard">
    <!-- Navbar with Glassmorphism -->
    <Navbar @show-coming-soon="showComingSoon" />
    
    <!-- Modern Sidebar -->
    <Sidebar @show-coming-soon="showComingSoon" />
    
    <v-main class="bg-dashboard">
      <v-container fluid class="pa-6 pa-md-10">
        <!-- Page Header -->
        <header class="dashboard-header mb-8">
          <v-row align="center">
            <v-col cols="12" sm="auto">
              <v-avatar color="primary-lighten-4" size="48" class="mr-4">
                <v-icon color="primary" size="28">mdi-calculator-variant</v-icon>
              </v-avatar>
            </v-col>
            <v-col>
              <h1 class="text-h4 font-weight-black tracking-tight text-high-emphasis">Estimasi Iuran JKK, JKM & BPJS</h1>
              <p class="text-subtitle-1 text-medium-emphasis">Proyeksi iuran ketenagakerjaan dan kesehatan periodik.</p>
            </v-col>
            <v-col cols="12" sm="auto" class="d-flex ga-3">
              <v-btn variant="tonal" color="primary" rounded="pill" @click="fetchAllEstimations" :loading="loadingEstimation" flat>
                <v-icon start icon="mdi-refresh"></v-icon>
                Refresh Data
              </v-btn>
              <v-btn variant="flat" color="primary" rounded="pill" @click="showSettings = !showSettings" class="font-weight-bold" flat>
                <v-icon start icon="mdi-cog-outline"></v-icon>
                Pengaturan Iuran
                <v-icon end icon="mdi-chevron-down" v-if="!showSettings"></v-icon>
                <v-icon end icon="mdi-chevron-up" v-else></v-icon>
              </v-btn>
            </v-col>
          </v-row>
        </header>

        <!-- Collapsible Settings Panel -->
        <v-expand-transition>
          <v-card v-show="showSettings" class="glass-panel mb-8 pa-2" elevation="0">
            <v-card-text>
              <div class="text-overline font-weight-black mb-4 text-primary px-2">KONFIGURASI PERSENTASE IURAN</div>
              <v-row align="center">
                <v-col cols="12" md="4">
                  <v-text-field
                    v-model="settings.pppk_jkk_percentage"
                    label="Persentase JKK (%)"
                    type="number" step="0.01" min="0"
                    hint="Default: 0.24" persistent-hint
                    variant="filled" flat rounded="lg"
                    :loading="loading"
                  ></v-text-field>
                </v-col>
                <v-col cols="12" md="4">
                  <v-text-field
                    v-model="settings.pppk_jkm_percentage"
                    label="Persentase JKM (%)"
                    type="number" step="0.01" min="0"
                    hint="Default: 0.72" persistent-hint
                    variant="filled" flat rounded="lg"
                    :loading="loading"
                  ></v-text-field>
                </v-col>
              </v-row>
              <!-- THR PPPK PW Settings (Superadmin Only) -->
              <div v-if="user?.role === 'superadmin'">
                <v-divider class="my-4 border-opacity-10"></v-divider>
                <div class="text-overline font-weight-black mb-4 text-primary px-2">KONFIGURASI PERHITUNGAN THR PPPK-PW</div>
                <v-row align="center">
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="settings.thr_pppk_pw_method"
                      :items="[
                        { title: 'Proporsional Bulan (n/12)', value: 'proporsional' },
                        { title: 'Bernilai Tetap (Nominal Spesifik)', value: 'tetap' }
                      ]"
                      label="Metode Perhitungan THR"
                      variant="filled" flat rounded="lg"
                      :loading="loading"
                    ></v-select>
                  </v-col>
                  <v-col cols="12" md="4" v-if="settings.thr_pppk_pw_method === 'proporsional'">
                    <v-text-field
                      v-model="settings.thr_pppk_pw_multiplier"
                      label="Parameter Multiplier (n/12)"
                      type="number" min="1" max="12"
                      hint="Default: 2 (Artinya 2/12)" persistent-hint
                      variant="filled" flat rounded="lg"
                      :loading="loading"
                      prefix="n ="
                      suffix="/ 12"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4" v-if="settings.thr_pppk_pw_method === 'tetap'">
                    <v-text-field
                      v-model="settings.thr_pppk_pw_amount"
                      label="Nominal THR (Bernilai Tetap)"
                      type="number" min="0"
                      hint="Contoh: 600000" persistent-hint
                      variant="filled" flat rounded="lg"
                      :loading="loading"
                    ></v-text-field>
                  </v-col>
                </v-row>

                <!-- Gaji 13 PPPK PW Settings -->
                <v-divider class="my-4 border-opacity-10"></v-divider>
                <div class="text-overline font-weight-black mb-4 text-primary px-2">KONFIGURASI PERHITUNGAN GAJI 13 PPPK-PW</div>
                <v-row align="center">
                  <v-col cols="12" md="4">
                    <v-select
                      v-model="settings.gaji13_pppk_pw_method"
                      :items="[
                        { title: 'Proporsional Bulan (n/12)', value: 'proporsional' },
                        { title: 'Bernilai Tetap (Nominal Spesifik)', value: 'tetap' }
                      ]"
                      label="Metode Perhitungan Gaji 13"
                      variant="filled" flat rounded="lg"
                      :loading="loading"
                    ></v-select>
                  </v-col>
                  <v-col cols="12" md="4" v-if="settings.gaji13_pppk_pw_method === 'proporsional'">
                    <v-text-field
                      v-model="settings.gaji13_pppk_pw_multiplier"
                      label="Parameter Multiplier (n/12)"
                      type="number" min="1" max="12"
                      hint="Default: 2 (Artinya 2/12)" persistent-hint
                      variant="filled" flat rounded="lg"
                      :loading="loading"
                      prefix="n ="
                      suffix="/ 12"
                    ></v-text-field>
                  </v-col>
                  <v-col cols="12" md="4" v-if="settings.gaji13_pppk_pw_method === 'tetap'">
                    <v-text-field
                      v-model="settings.gaji13_pppk_pw_amount"
                      label="Nominal Gaji 13 (Bernilai Tetap)"
                      type="number" min="0"
                      hint="Contoh: 600000" persistent-hint
                      variant="filled" flat rounded="lg"
                      :loading="loading"
                    ></v-text-field>
                  </v-col>
                </v-row>
              </div>

              <v-row align="center" class="mt-4">
                <v-col cols="12" md="4" class="d-flex align-center pt-0">
                  <v-btn color="primary" :loading="saving" prepend-icon="mdi-content-save-outline" flat rounded="pill" block size="large" @click="saveSettings" class="font-weight-black">
                    Simpan Parameter
                  </v-btn>
                </v-col>
              </v-row>
              <v-alert v-if="successMessage" type="success" variant="tonal" class="mt-4 rounded-xl border-0" density="compact" closable @click:close="successMessage = ''">
                {{ successMessage }}
              </v-alert>
            </v-card-text>
          </v-card>
        </v-expand-transition>

        <!-- Quick Filters -->
        <div class="d-flex align-center mb-6 flex-wrap ga-4">
          <v-card class="glass-panel pa-3 d-flex align-center flex-grow-1" elevation="0">
            <v-icon color="primary" class="mr-3">mdi-filter-variant</v-icon>
            <div class="text-caption font-weight-black text-primary mr-4 border-e pe-4">KOMPONEN GAJI</div>
            <v-chip-group
              v-model="selectedJenisGaji"
              multiple
              selected-class="text-primary"
              column
            >
              <v-chip
                v-for="jg in jenisGajiOptions"
                :key="jg.value"
                :value="jg.value"
                filter
                variant="tonal"
                size="small"
                rounded="lg"
                class="font-weight-bold"
              >{{ jg.title }}</v-chip>
            </v-chip-group>
            <v-divider vertical class="mx-2"></v-divider>
            <v-btn 
              variant="text" 
              size="small" 
              color="primary" 
              class="font-weight-bold" 
              rounded="pill"
              @click="toggleAllJenisGaji"
            >
              {{ selectedJenisGaji.length === jenisGajiOptions.length ? 'Bersihkan' : 'Pilih Semua' }}
            </v-btn>
          </v-card>

          <v-card class="glass-panel pa-3 d-flex align-center" elevation="0" style="min-width: 250px">
            <v-icon color="secondary" class="mr-3">mdi-calendar-clock</v-icon>
            <v-select
              v-model="selectedMonth"
              :items="months"
              item-title="text"
              item-value="value"
              density="compact"
              variant="plain"
              hide-details
              class="font-weight-bold"
              @update:modelValue="fetchAllEstimations"
            ></v-select>
            <v-select
              v-model="selectedYear"
              :items="years"
              density="compact"
              variant="plain"
              hide-details
              class="ml-2 font-weight-bold"
              @update:modelValue="fetchAllEstimations"
            ></v-select>
          </v-card>
        </div>

        <!-- Full-width Estimation Card -->
        <v-card class="glass-panel overflow-hidden" elevation="0">
          <v-tabs
            v-model="activeTab"
            bg-color="transparent"
            color="primary"
            class="header-tabs px-4 border-b"
            grow
          >
              <v-tab value="pns" class="text-subtitle-2 font-weight-black py-4">
                <v-icon start icon="mdi-account-tie" class="mr-2"></v-icon>
                PNS
              </v-tab>
              <v-tab value="full_time" class="text-subtitle-2 font-weight-black py-4 border-s">
                <v-icon start icon="mdi-account-clock" class="mr-2"></v-icon>
                PPPK Penuh Waktu
              </v-tab>
              <v-tab value="part_time" class="text-subtitle-2 font-weight-black py-4 border-s">
                <v-icon start icon="mdi-account-group" class="mr-2"></v-icon>
                PPPK Paruh Waktu
              </v-tab>
          </v-tabs>
    
          <v-window v-model="activeTab">
            <!-- PNS Tab -->
            <v-window-item value="pns">
                <v-card-text class="pa-8">
                    <v-row class="mb-8 align-center">
                        <v-col cols="12" md="3">
                             <v-select
                                v-model="selectedMonth"
                                :items="months"
                                item-title="text"
                                item-value="value"
                                label="Bulan"
                                variant="filled"
                                flat
                                rounded="lg"
                                hide-details
                                prepend-inner-icon="mdi-calendar-month"
                                @update:modelValue="fetchEstimationPns"
                            ></v-select>
                        </v-col>
                        <v-col cols="12" md="3">
                             <v-select
                                v-model="selectedYear"
                                :items="years"
                                label="Tahun"
                                variant="filled"
                                flat
                                rounded="lg"
                                hide-details
                                prepend-inner-icon="mdi-calendar-range"
                                @update:modelValue="fetchEstimationPns"
                            ></v-select>
                        </v-col>
                        <v-col cols="12" md="6" class="d-flex align-center ga-3">
                              <v-chip color="primary" size="large" class="font-weight-black">
                                  {{ getMonthName(selectedMonth) }} {{ selectedYear }}
                              </v-chip>
                              <v-spacer></v-spacer>
                              <v-btn v-if="estimationPns" color="success" size="large" variant="flat" rounded="pill" @click="exportData('pns')" :loading="exporting" class="px-6 font-weight-black">
                                <v-icon start icon="mdi-file-excel-outline"></v-icon>
                                EXPORT XLS
                              </v-btn>
                        </v-col>
                    </v-row>

                    <div v-if="estimationPns">
                        <v-row class="mb-8 ga-y-4">
                          <v-col cols="12" md="4">
                             <div class="pa-6 border rounded-xl h-100 bg-surface">
                                <div class="text-overline text-medium-emphasis mb-1">DATA PEGAWAI</div>
                                <div class="text-h4 font-weight-black text-high-emphasis">{{ formatNumber(estimationPns.employees_count) }}</div>
                                <div class="text-caption text-medium-emphasis">Aktif Periode {{ getMonthName(estimationPns.period.month) }}</div>
                             </div>
                          </v-col>
                          <v-col cols="12" md="8">
                             <div class="pa-6 border rounded-xl h-100 bg-surface">
                                <div class="text-overline text-medium-emphasis mb-1">TOTAL DASAR PERHITUNGAN (GAJI POKOK)</div>
                                <div class="text-h4 font-weight-black text-primary">{{ formatCurrency(estimationPns.total_gaji_pokok) }}</div>
                                <div class="text-caption text-medium-emphasis">Aggregasi seluruh SKPD</div>
                             </div>
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
                <v-card-text class="pa-8">
                    <v-row class="mb-8 align-center">
                        <v-col cols="12" md="3">
                             <v-select
                                v-model="selectedMonth"
                                :items="months"
                                item-title="text"
                                item-value="value"
                                label="Bulan"
                                variant="filled"
                                flat
                                rounded="lg"
                                hide-details
                                prepend-inner-icon="mdi-calendar-month"
                                @update:modelValue="fetchEstimation"
                            ></v-select>
                        </v-col>
                        <v-col cols="12" md="3">
                             <v-select
                                v-model="selectedYear"
                                :items="years"
                                label="Tahun"
                                variant="filled"
                                flat
                                rounded="lg"
                                hide-details
                                prepend-inner-icon="mdi-calendar-range"
                                @update:modelValue="fetchEstimation"
                            ></v-select>
                        </v-col>
                        <v-col cols="12" md="6" class="d-flex align-center ga-3">
                              <v-chip color="primary" size="large" class="font-weight-black">
                                  {{ getMonthName(selectedMonth) }} {{ selectedYear }}
                              </v-chip>
                              <v-spacer></v-spacer>
                              <v-btn v-if="estimation" color="success" size="large" variant="flat" rounded="pill" @click="exportData('pppk')" :loading="exporting" class="px-6 font-weight-black">
                                <v-icon start icon="mdi-file-excel-outline"></v-icon>
                                EXPORT XLS
                              </v-btn>
                        </v-col>
                    </v-row>

                    <div v-if="estimation">
                        <v-row class="mb-8 ga-y-4">
                          <v-col cols="12" md="4">
                             <div class="pa-6 border rounded-xl h-100 bg-surface">
                                <div class="text-overline text-medium-emphasis mb-1">DATA PEGAWAI</div>
                                <div class="text-h4 font-weight-black text-high-emphasis">{{ formatNumber(estimation.employees_count) }}</div>
                                <div class="text-caption text-medium-emphasis">Aktif Periode {{ getMonthName(estimation.period.month) }}</div>
                             </div>
                          </v-col>
                          <v-col cols="12" md="8">
                             <div class="pa-6 border rounded-xl h-100 bg-surface">
                                <div class="text-overline text-medium-emphasis mb-1">TOTAL DASAR PERHITUNGAN (GAJI POKOK)</div>
                                <div class="text-h4 font-weight-black text-primary">{{ formatCurrency(estimation.total_gaji_pokok) }}</div>
                                <div class="text-caption text-medium-emphasis">Aggregasi seluruh SKPD</div>
                             </div>
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
    
                        <v-divider class="my-8 opacity-10"></v-divider>
    
                        <h3 class="text-h6 font-weight-black mb-4 text-high-emphasis">Rincian per SKPD</h3>
                        <p class="text-body-2 text-medium-emphasis mb-3">Klik baris SKPD untuk melihat rincian per pegawai</p>
                        
                        <v-data-table
                            :headers="pwHeaders"
                            :items="estimation.details"
                            items-per-page="10"
                            class="bg-transparent"
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
                <v-card-text class="pa-8">
                    <v-row class="mb-8 align-center">
                        <v-col cols="12" md="3">
                             <v-select
                                v-model="selectedMonth"
                                :items="months"
                                item-title="text"
                                item-value="value"
                                label="Bulan"
                                variant="filled"
                                flat
                                rounded="lg"
                                hide-details
                                prepend-inner-icon="mdi-calendar-month"
                                @update:modelValue="fetchEstimationPw"
                            ></v-select>
                        </v-col>
                        <v-col cols="12" md="3">
                             <v-select
                                v-model="selectedYear"
                                :items="years"
                                label="Tahun"
                                variant="filled"
                                flat
                                rounded="lg"
                                hide-details
                                prepend-inner-icon="mdi-calendar-range"
                                @update:modelValue="fetchEstimationPw"
                            ></v-select>
                        </v-col>
                        <v-col cols="12" md="6" class="d-flex align-center ga-3">
                              <v-chip color="primary" size="large" class="font-weight-black">
                                  {{ getMonthName(selectedMonth) }} {{ selectedYear }}
                              </v-chip>
                              <v-spacer></v-spacer>
                              <v-btn v-if="estimationPw" color="success" size="large" variant="flat" rounded="pill" @click="exportData('pppk_pw')" :loading="exporting" class="px-6 font-weight-black">
                                <v-icon start icon="mdi-file-excel-outline"></v-icon>
                                EXPORT XLS
                              </v-btn>
                        </v-col>
                    </v-row>
    
                    <div v-if="estimationPw">
                        <v-row class="mb-8 ga-y-4">
                          <v-col cols="12" md="3">
                             <div class="pa-6 border rounded-xl h-100 bg-surface">
                                <div class="text-overline text-medium-emphasis mb-1">REFERENSI DATA</div>
                                <div class="text-h6 font-weight-black text-high-emphasis">{{ getMonthName(estimationPw.period.month) }} {{ estimationPw.period.year }}</div>
                             </div>
                          </v-col>
                          <v-col cols="12" md="3">
                             <div class="pa-6 border rounded-xl h-100 bg-surface">
                                <div class="text-overline text-medium-emphasis mb-1">TOTAL PEGAWAI</div>
                                <div class="text-h6 font-weight-black text-high-emphasis">{{ formatNumber(estimationPw.employees_count) }} <span class="text-caption">Orang</span></div>
                             </div>
                          </v-col>
                          <v-col cols="12" md="3">
                             <div class="pa-6 border rounded-xl h-100 bg-surface">
                                <div class="text-overline text-medium-emphasis mb-1">GAJI POKOK</div>
                                <div class="text-h6 font-weight-black text-primary">{{ formatCurrency(estimationPw.total_gaji_pokok) }}</div>
                             </div>
                          </v-col>
                          <v-col cols="12" md="3">
                             <div class="pa-6 border rounded-xl h-100 bg-surface">
                                <div class="text-overline text-medium-emphasis mb-1">TUNJANGAN</div>
                                <div class="text-h6 font-weight-black text-primary">{{ formatCurrency(estimationPw.total_tunjangan) }}</div>
                             </div>
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
              <tr class="group-header-row bg-surface">
                <td :colspan="3" class="py-3 px-4 cursor-pointer" @click="toggleGroup(item)">
                  <div class="d-flex align-center">
                    <v-btn
                      :icon="isGroupOpen(item) ? 'mdi-chevron-down' : 'mdi-chevron-right'"
                      variant="text"
                      size="small"
                      density="compact"
                      class="mr-2"
                    ></v-btn>
                    <v-icon color="primary" class="mr-2" size="20">mdi-briefcase-outline</v-icon>
                    <span class="font-weight-black text-body-2">{{ item.value || 'Tanpa Jabatan' }}</span>
                    <v-chip size="x-small" color="primary" class="ml-3 font-weight-bold" variant="flat">
                      {{ item.items.length }} Pegawai
                    </v-chip>
                  </div>
                </td>
                <td class="text-right font-weight-bold py-3">
                    <span class="text-medium-emphasis">{{ formatCurrency(item.items.reduce((acc, row) => acc + (row.raw.gaji_pokok || 0), 0)) }}</span>
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
import { ref, onMounted, watch } from 'vue'
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

const getUserFromStorage = () => {
  try {
    const stored = localStorage.getItem('user')
    return (stored && stored !== 'null') ? JSON.parse(stored) : null
  } catch (e) {
    return null
  }
}
const user = ref(getUserFromStorage())

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

const jenisGajiOptions = [
  { title: 'Induk', value: 'Induk' },
  { title: 'Susulan', value: 'Susulan' },
  { title: 'Kekurangan', value: 'Kekurangan' },
  { title: 'Terusan', value: 'Terusan' },
]
const selectedJenisGaji = ref(['Induk', 'Susulan', 'Kekurangan', 'Terusan'])

const toggleAllJenisGaji = () => {
  if (selectedJenisGaji.value.length === jenisGajiOptions.length) {
    selectedJenisGaji.value = []
  } else {
    selectedJenisGaji.value = jenisGajiOptions.map(o => o.value)
  }
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
  pppk_jkm_percentage: 0.72,
  thr_pppk_pw_method: 'proporsional',
  thr_pppk_pw_amount: 600000,
  thr_pppk_pw_multiplier: 2,
  gaji13_pppk_pw_method: 'proporsional',
  gaji13_pppk_pw_amount: 600000,
  gaji13_pppk_pw_multiplier: 2
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
            if (data.thr_pppk_pw_method) settings.value.thr_pppk_pw_method = data.thr_pppk_pw_method.value
            if (data.thr_pppk_pw_amount) settings.value.thr_pppk_pw_amount = parseFloat(data.thr_pppk_pw_amount.value)
            if (data.thr_pppk_pw_multiplier) settings.value.thr_pppk_pw_multiplier = parseInt(data.thr_pppk_pw_multiplier.value)
            if (data.gaji13_pppk_pw_method) settings.value.gaji13_pppk_pw_method = data.gaji13_pppk_pw_method.value
            if (data.gaji13_pppk_pw_amount) settings.value.gaji13_pppk_pw_amount = parseFloat(data.gaji13_pppk_pw_amount.value)
            if (data.gaji13_pppk_pw_multiplier) settings.value.gaji13_pppk_pw_multiplier = parseInt(data.gaji13_pppk_pw_multiplier.value)
        }
    } catch (error) {
        console.error('Error fetching settings:', error)
    } finally {
        loading.value = false
    }
}

const saveSettings = async () => {
    saving.value = true
    successMessage.value = ''
    try {
        const payload = [
            { key: 'pppk_jkk_percentage', value: settings.value.pppk_jkk_percentage },
            { key: 'pppk_jkm_percentage', value: settings.value.pppk_jkm_percentage }
        ]
        
        if (user.value?.role === 'superadmin') {
            payload.push({ key: 'thr_pppk_pw_method', value: settings.value.thr_pppk_pw_method })
            payload.push({ key: 'thr_pppk_pw_amount', value: settings.value.thr_pppk_pw_amount })
            payload.push({ key: 'thr_pppk_pw_multiplier', value: settings.value.thr_pppk_pw_multiplier })
            payload.push({ key: 'gaji13_pppk_pw_method', value: settings.value.gaji13_pppk_pw_method })
            payload.push({ key: 'gaji13_pppk_pw_amount', value: settings.value.gaji13_pppk_pw_amount })
            payload.push({ key: 'gaji13_pppk_pw_multiplier', value: settings.value.gaji13_pppk_pw_multiplier })
        }

        const response = await api.post('/settings', { settings: payload })
        if (response.data.success) {
            successMessage.value = 'Parameter berhasil disimpan!'
            fetchAllEstimations() // Refresh estimation with new settings
        }
    } catch (error) {
        console.error('Error saving settings:', error)
        alert('Gagal menyimpan parameter.')
    } finally {
        saving.value = false
    }
}

const fetchEstimation = async () => {
    try {
        const response = await api.get('/settings/pppk-estimation', {
            params: {
                month: selectedMonth.value,
                year: selectedYear.value,
                jenis_gaji: selectedJenisGaji.value
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
                year: selectedYear.value,
                jenis_gaji: selectedJenisGaji.value
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
                kdskpd: item.id_skpd,
                jenis_gaji: type !== 'pppk_pw' ? selectedJenisGaji.value : undefined
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
    if (type !== 'pppk_pw') {
        selectedJenisGaji.value.forEach(jg => params.append('jenis_gaji[]', jg))
    }
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

watch(selectedJenisGaji, () => {
    fetchAllEstimations()
})

onMounted(() => {
    fetchSettings()
    fetchAllEstimations()
})
</script>

<style scoped>
.modern-dashboard {
  min-height: 100vh;
}

.bg-dashboard {
  background-color: rgb(var(--v-theme-background));
  background-image: 
    radial-gradient(at 0% 0%, rgba(var(--v-theme-primary), 0.05) 0, transparent 50%),
    radial-gradient(at 100% 100%, rgba(var(--v-theme-info), 0.05) 0, transparent 50%);
}

.glass-panel {
  background: rgba(var(--v-theme-surface), 0.7) !important;
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(var(--v-border-color), 0.1) !important;
  border-radius: 28px !important;
}

.header-tabs :deep(.v-slide-group__content) {
  border-bottom: 0 !important;
}

.header-tabs :deep(.v-tab--selected) {
  background: rgba(var(--v-theme-primary), 0.05);
}

.border-dashed {
  border-style: dashed !important;
}

.group-header-row {
  transition: background-color 0.2s ease;
}

.group-header-row:hover {
  background-color: rgba(var(--v-theme-primary), 0.05) !important;
}

.text-wrap {
  white-space: normal !important;
}

:deep(.v-data-table) {
  background: transparent !important;
}

:deep(.v-data-table-header) {
  background: rgba(var(--v-theme-on-surface), 0.02);
}

:deep(.v-data-table__tr:hover) {
  background: rgba(var(--v-theme-primary), 0.03) !important;
}
</style>
