<template>
  <div class="modern-dashboard">
    <Navbar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />
    <Sidebar />

    <v-main class="bg-dashboard">
      <v-container fluid class="pa-6 pa-md-10">
        
        <!-- ═════════ HEADER SECTION ═════════ -->
        <header class="dashboard-header mb-8">
          <v-row align="center">
            <v-col cols="12" md="7">
              <div class="d-flex align-center mb-2">
                <v-avatar color="teal-lighten-4" size="48" class="mr-4">
                  <v-icon color="teal-darken-2" size="28">mdi-finance</v-icon>
                </v-avatar>
                <div>
                  <h1 class="text-h4 font-weight-black tracking-tight text-high-emphasis">PNS & PPPK Dashboard</h1>
                  <p class="text-subtitle-1 text-medium-emphasis">Financial analytics and personnel distribution trends</p>
                </div>
              </div>
            </v-col>
            <v-col cols="12" md="5" class="d-flex justify-md-end align-center gap-3">
              <!-- Advanced Filter Group -->
              <div class="filter-group glass-panel pa-2 d-flex align-center">
                <v-menu v-model="menu" :close-on-content-click="false" location="bottom end" transition="slide-y-transition">
                  <template v-slot:activator="{ props }">
                    <v-btn variant="text" class="px-4 font-weight-bold" v-bind="props" prepend-icon="mdi-calendar-range">
                      {{ selectedMonthName }} {{ selectedYear }}
                    </v-btn>
                  </template>
                  <v-card min-width="320" class="filter-dropdown rounded-xl elevation-24">
                    <v-card-text class="pa-5">
                      <div class="text-overline mb-3">Filter Periode</div>
                      <v-row dense>
                        <v-col cols="7">
                          <v-select v-model="selectedMonth" :items="months" item-title="title" item-value="value" label="Bulan" density="compact" variant="filled" hide-details flat rounded="lg"></v-select>
                        </v-col>
                        <v-col cols="5">
                          <v-select v-model="selectedYear" :items="years" label="Tahun" density="compact" variant="filled" hide-details flat rounded="lg"></v-select>
                        </v-col>
                        <v-col cols="12" class="mt-3">
                          <v-select v-model="selectedJenisGajiFilter" :items="jenisGajiOptions" label="Jenis Gaji" density="compact" variant="filled" hide-details clearable flat rounded="lg"></v-select>
                        </v-col>
                      </v-row>
                    </v-card-text>
                    <v-divider></v-divider>
                    <v-card-actions class="pa-4 bg-light">
                      <v-btn block color="teal-darken-1" variant="flat" size="large" rounded="lg" @click="fetchData(); fetchAnnualReport(); menu = false">
                        Terapkan Filter
                      </v-btn>
                    </v-card-actions>
                  </v-card>
                </v-menu>
              </div>

              <v-btn icon="mdi-microsoft-excel" color="success" variant="tonal" @click="exportCombinedAllowance" :loading="exporting" title="Ekspor Excel" class="rounded-lg"></v-btn>
              <v-btn color="primary" variant="flat" prepend-icon="mdi-plus" rounded="pill" class="px-6" @click="uploadDialog = true">
                Upload Data
              </v-btn>
            </v-col>
          </v-row>
        </header>

        <!-- ═════════ TYPE SELECTOR ═════════ -->
        <div class="type-selector-container mb-8">
          <v-tabs v-model="employeeType" color="teal-darken-1" align-tabs="start" class="type-tabs" @update:model-value="fetchData">
            <v-tab value="pns" class="text-capitalize px-6 h-100">
              <v-icon start size="18">mdi-account-tie</v-icon> PNS Standar
            </v-tab>
            <v-tab value="pppk" class="text-capitalize px-6 h-100">
              <v-icon start size="18">mdi-account-clock</v-icon> PPPK
            </v-tab>
            <v-tab value="combined" class="text-capitalize px-6 h-100">
              <v-icon start size="18">mdi-layers-triple</v-icon> Analisis Gabungan
            </v-tab>
          </v-tabs>
        </div>

        <div v-if="loading" class="dashboard-loader d-flex flex-column align-center justify-center py-16">
          <v-progress-circular indeterminate color="teal" size="64" width="6"></v-progress-circular>
          <span class="mt-4 text-overline text-slate-500">Synchronizing Financial Data...</span>
        </div>

        <v-fade-transition mode="out-in">
          <div v-if="!loading" :key="employeeType">
            
            <!-- ═════════ KEY METRICS ═════════ -->
            <v-row class="mb-6" dense>
              <v-col cols="12" sm="6" md="3">
                <v-card class="stat-card glass-panel" elevation="0">
                  <v-card-text class="pa-6">
                    <div class="d-flex justify-space-between align-center mb-4">
                      <span class="text-overline font-weight-black text-medium-emphasis">Total Personil</span>
                      <v-avatar color="teal-lighten-5" size="32">
                        <v-icon color="teal" size="18">mdi-account-group-outline</v-icon>
                      </v-avatar>
                    </div>
                    <div class="text-h3 font-weight-black mb-1 text-high-emphasis">
                      {{ (employeeType === 'combined' ? combinedTotal.employees : stats?.total_employees)?.toLocaleString() || 0 }}
                    </div>
                    <div class="text-caption text-medium-emphasis">Unit terdaftar bulan ini</div>
                  </v-card-text>
                </v-card>
              </v-col>

              <v-col cols="12" sm="6" md="3">
                <v-card class="stat-card glass-panel" elevation="0">
                  <v-card-text class="pa-6">
                    <div class="d-flex justify-space-between align-center mb-4">
                      <span class="text-overline font-weight-black text-medium-emphasis">Gaji Bersih</span>
                      <v-avatar color="success-lighten-5" size="32">
                        <v-icon color="success" size="18">mdi-cash-check</v-icon>
                      </v-avatar>
                    </div>
                    <div class="text-h4 font-weight-black text-success mb-1">
                      {{ formatCurrencyShort(employeeType === 'combined' ? combinedTotal.net : stats?.total_net_salary) }}
                    </div>
                    <div class="text-caption text-medium-emphasis">Net Payroll Disbursement</div>
                  </v-card-text>
                </v-card>
              </v-col>

              <v-col cols="12" sm="6" md="3">
                <v-card class="stat-card glass-panel" elevation="0">
                  <v-card-text class="pa-6">
                    <div class="d-flex justify-space-between align-center mb-4">
                      <span class="text-overline font-weight-black text-medium-emphasis">Total TPP</span>
                      <v-avatar color="blue-lighten-5" size="32">
                        <v-icon color="info" size="18">mdi-wallet-plus-outline</v-icon>
                      </v-avatar>
                    </div>
                    <div class="text-h4 font-weight-black text-info mb-1">
                      {{ formatCurrencyShort(employeeType === 'combined' ? combinedTotal.tpp : stats?.total_tunj_tpp) }}
                    </div>
                    <div class="text-caption text-medium-emphasis">Tunjangan Profesi / Kinerja</div>
                  </v-card-text>
                </v-card>
              </v-col>

              <v-col cols="12" sm="6" md="3">
                <v-card class="stat-card glass-panel" elevation="0">
                  <v-card-text class="pa-6">
                    <div class="d-flex justify-space-between align-center mb-4">
                      <span class="text-overline font-weight-black text-medium-emphasis">Potongan</span>
                      <v-avatar color="red-lighten-5" size="32">
                        <v-icon color="error" size="18">mdi-content-cut</v-icon>
                      </v-avatar>
                    </div>
                    <div class="text-h4 font-weight-black text-error mb-1">
                      {{ formatCurrencyShort(employeeType === 'combined' ? combinedTotal.deductions : stats?.total_deductions) }}
                    </div>
                    <div class="text-caption text-medium-emphasis">IWP, BPJS & Pajak</div>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>

            <v-row>
              <!-- ═════════ TREND ANALYTICS ═════════ -->
              <v-col cols="12" lg="8">
                <v-card class="glass-panel mb-6" elevation="0">
                  <v-card-title class="pa-6 pb-2 d-flex align-center">
                    <div>
                      <div class="text-h6 font-weight-bold text-high-emphasis">Financial Trend {{ selectedYear }}</div>
                      <div class="text-caption text-medium-emphasis">Pertumbuhan pengeluaran gaji dan tunjangan bulanan</div>
                    </div>
                    <v-spacer></v-spacer>
                    <v-chip size="small" variant="tonal" color="teal" class="font-weight-bold">Akumulasi Juta IDR</v-chip>
                  </v-card-title>
                  <v-card-text class="pa-6">
                    <div style="height: 380px">
                      <canvas v-show="yearlyTrend.length > 0" ref="trendChart"></canvas>
                      <div v-if="yearlyTrend.length === 0" class="empty-state d-flex flex-column align-center justify-center h-100 py-12">
                        <v-icon size="64" color="grey-lighten-2">mdi-chart-areaspline</v-icon>
                        <span class="mt-4 text-disabled font-weight-medium">Belum ada statistik trend tahunan</span>
                      </div>
                    </div>
                  </v-card-text>
                </v-card>

                <!-- Detailed Breakdowns -->
                <v-row v-if="employeeType === 'combined'">
                  <v-col cols="12" md="6">
                    <v-card class="glass-panel h-100" elevation="0">
                      <v-card-title class="pa-6 pb-2 d-flex align-center text-teal-darken-2">
                        <v-icon start size="20">mdi-cash-multiple</v-icon>
                        <span class="text-subtitle-1 font-weight-bold">Tunjangan Gabungan</span>
                      </v-card-title>
                      <v-card-text class="pa-0">
                        <v-table density="compact" class="ui-table">
                          <thead>
                            <tr>
                              <th class="text-overline">KOMPONEN</th>
                              <th class="text-right text-overline">PNS</th>
                              <th class="text-right text-overline">PPPK</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="(item, i) in combinedAllowanceBreakdown.slice(0, 7)" :key="i">
                              <td class="text-caption font-weight-medium">{{ item.label }}</td>
                              <td class="text-right text-caption">{{ formatCurrencyCompact(item.pns) }}</td>
                              <td class="text-right text-caption font-weight-bold">{{ formatCurrencyCompact(item.pppk) }}</td>
                            </tr>
                          </tbody>
                        </v-table>
                        <div class="pa-4 text-center">
                          <v-btn variant="text" color="teal" size="small" class="font-weight-bold" @click="openDetailDialog('tunjangan')">Tampilkan Semua Rincian</v-btn>
                        </div>
                      </v-card-text>
                    </v-card>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-card class="glass-panel h-100" elevation="0">
                      <v-card-title class="pa-6 pb-2 d-flex align-center text-error">
                        <v-icon start size="20">mdi-minus-circle-outline</v-icon>
                        <span class="text-subtitle-1 font-weight-bold">Potongan Gabungan</span>
                      </v-card-title>
                      <v-card-text class="pa-0">
                        <v-table density="compact" class="ui-table">
                          <thead>
                            <tr>
                              <th class="text-overline">KOMPONEN</th>
                              <th class="text-right text-overline">PNS</th>
                              <th class="text-right text-overline">PPPK</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="(item, i) in combinedDeductionBreakdown.slice(0, 7)" :key="i">
                              <td class="text-caption font-weight-medium">{{ item.label }}</td>
                              <td class="text-right text-caption">{{ formatCurrencyCompact(item.pns) }}</td>
                              <td class="text-right text-caption font-weight-bold text-error">{{ formatCurrencyCompact(item.pppk) }}</td>
                            </tr>
                          </tbody>
                        </v-table>
                        <div class="pa-4 text-center">
                          <v-btn variant="text" color="error" size="small" class="font-weight-bold" @click="openDetailDialog('potongan')">Tampilkan Semua Potongan</v-btn>
                        </div>
                      </v-card-text>
                    </v-card>
                  </v-col>
                </v-row>

                <!-- Single View Breakdown -->
                <v-row v-else>
                   <v-col cols="12" md="6">
                    <v-card class="glass-panel h-100" elevation="0">
                      <v-card-title class="pa-6 pb-4 font-weight-bold text-subtitle-1">Rincian Tunjangan</v-card-title>
                      <v-card-text class="pa-0">
                        <v-list density="compact" class="bg-transparent">
                          <v-list-item v-for="(item, i) in allowanceBreakdown.filter(a => a.value > 0).slice(0, 8)" :key="i" class="px-6 border-b">
                            <v-list-item-title class="text-caption">{{ item.label }}</v-list-item-title>
                            <template v-slot:append>
                              <span class="text-caption font-weight-black text-high-emphasis">{{ formatCurrencyShort(item.value) }}</span>
                            </template>
                          </v-list-item>
                        </v-list>
                      </v-card-text>
                    </v-card>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-card class="glass-panel h-100" elevation="0">
                      <v-card-title class="pa-6 pb-4 font-weight-bold text-subtitle-1">Rincian Potongan</v-card-title>
                      <v-card-text class="pa-0">
                        <v-list density="compact" class="bg-transparent">
                          <v-list-item v-for="(item, i) in deductionBreakdown.filter(a => a.value > 0).slice(0, 8)" :key="i" class="px-6 border-b">
                            <v-list-item-title class="text-caption">{{ item.label }}</v-list-item-title>
                            <template v-slot:append>
                              <span class="text-caption font-weight-black text-error">{{ formatCurrencyShort(item.value) }}</span>
                            </template>
                          </v-list-item>
                        </v-list>
                      </v-card-text>
                    </v-card>
                  </v-col>
                </v-row>
              </v-col>

              <!-- ═════════ INSIGHTS SIDEBAR ═════════ -->
              <v-col cols="12" lg="4">
                <!-- Projection Card -->
                <v-card class="glass-panel mb-6 premium-gradient text-white overflow-hidden" elevation="0">
                  <v-card-text class="pa-8">
                    <div class="d-flex align-center mb-6 opacity-80">
                      <v-icon start size="20">mdi-calendar-check</v-icon>
                      <span class="text-overline font-weight-bold tracking-widest">PROYEKSI ANGGARAN TAHUNAN</span>
                    </div>
                    <div class="text-h3 font-weight-black mb-1">
                      {{ formatCurrencyCompact(stats?.projected_annual_budget || 0) }}
                    </div>
                    <p class="text-caption opacity-70 mb-6">Berdasarkan data bulan berjalan × 14 periode</p>
                    
                    <div class="forecast-box pa-4 rounded-xl glass-overlay">
                      <div class="d-flex justify-space-between align-center mb-1">
                        <span class="text-caption font-weight-bold">Estimasi TPP {{ selectedYear + 1 }}</span>
                        <v-icon size="14">mdi-trending-up</v-icon>
                      </div>
                      <div class="text-h5 font-weight-black">{{ formatCurrencyCompact(stats?.tpp_forecast_next_year || 0) }}</div>
                    </div>
                  </v-card-text>
                </v-card>

                <!-- Rank Distribution -->
                <v-card class="glass-panel mb-6" elevation="0">
                  <v-card-title class="pa-6 pb-4 font-weight-bold text-subtitle-1 d-flex align-center">
                    <v-icon start size="20" color="primary">mdi-layers-outline</v-icon> Beban per Golongan
                  </v-card-title>
                  <v-card-text class="pa-6 pt-0">
                    <div v-for="(item, i) in golonganStats.slice(0, 5)" :key="i" class="mb-4">
                      <div class="d-flex justify-space-between mb-1">
                        <span class="text-caption font-weight-bold text-high-emphasis">Golongan {{ item.golongan }}</span>
                        <span class="text-caption font-weight-black text-primary">{{ formatCurrencyShort(item.cost) }}</span>
                      </div>
                      <v-progress-linear
                        :model-value="(item.cost / stats.total_gross_salary) * 100"
                        color="primary" height="6" rounded
                      ></v-progress-linear>
                      <div class="text-right text-caption text-disabled mt-1">{{ item.total }} staff</div>
                    </div>
                  </v-card-text>
                </v-card>

                <!-- Unit Expenditure -->
                <v-card class="glass-panel" elevation="0">
                  <v-card-title class="pa-6 pb-4 font-weight-bold text-subtitle-1 d-flex align-center">
                    <v-icon start size="20" color="teal">mdi-office-building-outline</v-icon> Top 5 Unit (Expenditure)
                  </v-card-title>
                  <v-card-text class="pa-0">
                    <v-list density="compact" class="bg-transparent" lines="two">
                      <v-list-item v-for="(item, i) in skpdStats.slice(0, 5)" :key="i" class="px-6 border-b py-3">
                        <v-list-item-title class="text-caption font-weight-bold text-high-emphasis">{{ item.skpd }}</v-list-item-title>
                        <v-list-item-subtitle class="text-overline text-disabled">{{ item.total }} PERSONIL</v-list-item-subtitle>
                        <template v-slot:append>
                          <div class="text-right">
                            <div class="text-caption font-weight-black text-high-emphasis">{{ formatCurrencyCompact(item.cost) }}</div>
                          </div>
                        </template>
                      </v-list-item>
                    </v-list>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>

            <!-- ═════════ ANNUAL DATA TABLE ═════════ -->
            <v-row class="mt-8 mb-12">
              <v-col cols="12">
                <div class="d-flex align-center mb-6">
                  <h2 class="text-h5 font-weight-black text-high-emphasis">Histori Transaksi {{ selectedYear }}</h2>
                  <v-spacer></v-spacer>
                  <v-switch
                    v-model="showAnnualTable"
                    color="teal"
                    label="Tampilkan Tabel Detail"
                    hide-details
                    inset
                  ></v-switch>
                </div>

                <v-expand-transition>
                  <div v-if="showAnnualTable">
                    <v-card class="glass-panel overflow-hidden" elevation="0">
                      <div class="table-responsive">
                        <v-table density="comfortable" class="premium-table">
                          <thead>
                            <tr>
                              <th class="sticky-left text-medium-emphasis">BULAN</th>
                              <th class="text-right text-medium-emphasis">PERSONIL</th>
                              <th class="text-right text-medium-emphasis">GAJI POKOK</th>
                              <th class="text-right text-medium-emphasis">FUNGSIONAL</th>
                              <th class="text-right text-medium-emphasis">STRUKTURAL</th>
                              <th class="text-right text-medium-emphasis">TPP</th>
                              <th class="text-right highlight-col font-weight-bold text-teal-darken-1">BERSIH (DISBURSED)</th>
                            </tr>
                          </thead>
                          <tbody>
                            <template v-for="(month, idx) in annualReport?.monthly" :key="idx">
                              <tr v-if="month.total_employees > 0" class="table-row-hover">
                                <td class="sticky-left font-weight-black text-high-emphasis">{{ getMonthName(month.month) }}</td>
                                <td class="text-right">{{ month.total_employees.toLocaleString() }}</td>
                                <td class="text-right">{{ formatCurrencyShort(month.total_gaji_pokok) }}</td>
                                <td class="text-right">{{ formatCurrencyShort(month.total_tunj_fungsional) }}</td>
                                <td class="text-right">{{ formatCurrencyShort(month.total_tunj_struktural) }}</td>
                                <td class="text-right">{{ formatCurrencyShort(month.total_tunj_tpp) }}</td>
                                <td class="text-right highlight-col font-weight-black text-teal-darken-2">{{ formatCurrencyShort(month.total_bersih) }}</td>
                              </tr>
                            </template>
                          </tbody>
                          <tfoot>
                            <tr class="footer-row">
                              <td class="sticky-left font-weight-black">TOTAL TAHUNAN</td>
                              <td class="text-right font-weight-black">{{ lastMonthTotalEmployees.toLocaleString() }}</td>
                              <td class="text-right font-weight-black">{{ formatCurrencyCompact(annualReport?.yearly_total?.total_gaji_pokok) }}</td>
                              <td class="text-right font-weight-black">{{ formatCurrencyCompact(annualReport?.yearly_total?.total_tunj_fungsional) }}</td>
                              <td class="text-right font-weight-black">{{ formatCurrencyCompact(annualReport?.yearly_total?.total_tunj_struktural) }}</td>
                              <td class="text-right font-weight-black">{{ formatCurrencyCompact(annualReport?.yearly_total?.total_tunj_tpp) }}</td>
                              <td class="text-right highlight-col font-weight-black">{{ formatCurrencyCompact(annualReport?.yearly_total?.total_bersih) }}</td>
                            </tr>
                          </tfoot>
                        </v-table>
                      </div>
                    </v-card>
                  </div>
                </v-expand-transition>
                
                <v-row v-if="!showAnnualTable" class="mt-2">
                   <v-col cols="12" md="4" v-for="(stat, i) in annualSummaryCards" :key="i">
                    <v-card class="glass-panel pa-8 text-center" elevation="0">
                      <div class="text-overline text-medium-emphasis font-weight-bold mb-2">{{ stat.label }}</div>
                      <div class="text-h3 font-weight-black mb-1" :class="stat.class">{{ stat.value }}</div>
                      <div class="text-caption text-disabled">{{ stat.sub }}</div>
                    </v-card>
                  </v-col>
                </v-row>
              </v-col>
            </v-row>

          </div>
        </v-fade-transition>

        <!-- ═════════ DIALOGS & OVERLAYS ═════════ -->
        
        <!-- Upload Modal -->
        <v-dialog v-model="uploadDialog" max-width="500" persistent transition="dialog-bottom-transition">
          <v-card class="rounded-2xl glass-modal">
            <v-card-title class="pa-6 pb-2 d-flex align-center">
              <span class="text-h6 font-weight-black text-high-emphasis">Import Master Data (DBF)</span>
              <v-spacer></v-spacer>
              <v-btn icon="mdi-close" variant="text" size="small" @click="closeUploadDialog" :disabled="uploading"></v-btn>
            </v-card-title>
            <v-card-text class="pa-6">
              <div v-if="!activeJobId">
                <p class="text-caption text-medium-emphasis mb-6">Pilih file DBF hasil ekspor SIMDA/Simgaji untuk memproses data gaji periode ini.</p>
                <v-select v-model="jenisGaji" :items="jenisGajiOptions" label="Jenis Pembayaran" variant="filled" density="comfortable" rounded="lg" class="mb-4"></v-select>
                <v-file-input v-model="file" label="Klik untuk pilih file" prepend-inner-icon="mdi-database-plus" variant="outlined" accept=".dbf" rounded="lg" show-size></v-file-input>
                <v-alert v-if="uploadError" type="error" variant="tonal" class="mt-6 rounded-lg text-caption border-0">
                  {{ uploadError }}
                </v-alert>
                <v-btn block color="teal-darken-1" size="large" rounded="pill" class="mt-8 font-weight-black" :loading="uploading" @click="uploadData">Mulai Proses Import</v-btn>
              </div>
              <div v-else class="py-10 text-center">
                <v-progress-circular :model-value="activeJobProgress" :rotate="-90" :size="140" :width="12" color="teal" class="font-weight-black mb-6">
                  {{ activeJobProgress }}%
                </v-progress-circular>
                <h3 class="text-h5 font-weight-black text-high-emphasis mb-1">{{ jobStatusLabel }}</h3>
                <p class="text-caption text-medium-emphasis">{{ activeJobFileName }}</p>
                
                <v-fade-transition>
                  <div v-if="activeJobStatus === 'completed'" class="mt-8">
                    <v-alert type="success" variant="tonal" class="border-0 rounded-xl text-left mb-6">
                      <div class="font-weight-bold">Import Selesai</div>
                      <div class="text-caption">{{ activeJobResult?.total_records?.toLocaleString() }} records berhasil dimigrasikan ke database.</div>
                    </v-alert>
                    <v-btn block color="primary" rounded="pill" size="large" @click="resetUpload">Upload File Lain</v-btn>
                  </div>
                </v-fade-transition>

                <v-fade-transition>
                  <div v-if="activeJobStatus === 'failed'" class="mt-8">
                    <v-alert type="error" variant="tonal" class="border-0 rounded-xl text-left mb-6">
                      <div class="font-weight-bold">Terjadi Kesalahan</div>
                      <div class="text-caption">{{ activeJobError }}</div>
                    </v-alert>
                    <v-btn block color="high-emphasis" rounded="pill" size="large" @click="resetUpload">Coba Lagi</v-btn>
                  </div>
                </v-fade-transition>
              </div>
            </v-card-text>
          </v-card>
        </v-dialog>

        <!-- Detail Listing Dialog -->
        <v-dialog v-model="detailDialog" max-width="800" scrollable transition="dialog-bottom-transition">
          <v-card class="rounded-2xl glass-modal">
            <v-card-title class="pa-6 border-b d-flex align-center">
              <span class="text-h6 font-weight-black">{{ detailDialogTitle }}</span>
              <v-spacer></v-spacer>
              <v-btn icon="mdi-close" variant="text" size="small" @click="detailDialog = false"></v-btn>
            </v-card-title>
            <v-card-text class="pa-0">
               <v-table density="comfortable" class="ui-table-flat">
                <thead>
                  <tr class="header-bg">
                    <th class="text-overline font-weight-black py-4">NAMA KOMPONEN</th>
                    <th class="text-right text-overline font-weight-black py-4">PNS</th>
                    <th class="text-right text-overline font-weight-black py-4">PPPK</th>
                    <th class="text-right text-overline font-weight-black py-4">TOTAL GABUNGAN</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(item, i) in detailItems" :key="i" class="table-row-hover">
                    <td class="text-body-2 font-weight-bold py-4">{{ item.label }}</td>
                    <td class="text-right text-body-2 text-medium-emphasis">{{ formatCurrencyShort(item.pns) }}</td>
                    <td class="text-right text-body-2 text-medium-emphasis">{{ formatCurrencyShort(item.pppk) }}</td>
                    <td class="text-right text-body-2 font-weight-black text-high-emphasis">{{ formatCurrencyShort(item.total) }}</td>
                  </tr>
                </tbody>
              </v-table>
            </v-card-text>
            <v-card-actions class="pa-4 border-t">
              <v-spacer></v-spacer>
              <v-btn variant="text" @click="detailDialog = false" class="font-weight-bold">Tutup Jendela</v-btn>
            </v-card-actions>
          </v-card>
        </v-dialog>

      </v-container>
    </v-main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { Chart, registerables } from 'chart.js'
import api from '../api'
import Sidebar from '../components/Sidebar.vue'
import Navbar from '../components/Navbar.vue'

Chart.register(...registerables)

// ═════════ CORE STATE ═════════
const loading = ref(true)
const loadingAnnual = ref(false)
const stats = ref(null)
const pnsStats = ref(null)
const pppkStats = ref(null)
const skpdStats = ref([])
const golonganStats = ref([])
const yearlyTrend = ref([])
const annualReport = ref(null)
const pnsAnnual = ref(null)
const pppkAnnual = ref(null)
const trendChart = ref(null)
let chartInstance = null

const employeeType = ref('pns')
const menu = ref(false)
const showAnnualTable = ref(false)
const selectedMonth = ref(new Date().getMonth() + 1)
const selectedYear = ref(new Date().getFullYear())
const selectedJenisGajiFilter = ref(null)
const jenisGajiOptions = ['Induk', 'Susulan', 'Kekurangan', 'Terusan']

const detailDialog = ref(false)
const detailType = ref('tunjangan')

const months = [
  { title: 'Januari', value: 1 }, { title: 'Februari', value: 2 }, { title: 'Maret', value: 3 },
  { title: 'April', value: 4 }, { title: 'Mei', value: 5 }, { title: 'Juni', value: 6 },
  { title: 'Juli', value: 7 }, { title: 'Agustus', value: 8 }, { title: 'September', value: 9 },
  { title: 'Oktober', value: 10 }, { title: 'November', value: 11 }, { title: 'Desember', value: 12 }
]
const years = computed(() => {
  const current = new Date().getFullYear()
  return [current, current - 1, current - 2]
})
const selectedMonthName = computed(() => months.find(m => m.value === selectedMonth.value)?.title)

// ═════════ ANALYTICS HELPERS ═════════
const combinedTotal = computed(() => ({
  employees: (Number(pnsStats.value?.total_employees) || 0) + (Number(pppkStats.value?.total_employees) || 0),
  net: (Number(pnsStats.value?.total_net_salary) || 0) + (Number(pppkStats.value?.total_net_salary) || 0),
  gross: (Number(pnsStats.value?.total_gross_salary) || 0) + (Number(pppkStats.value?.total_gross_salary) || 0),
  tpp: (Number(pnsStats.value?.total_tunj_tpp) || 0) + (Number(pppkStats.value?.total_tunj_tpp) || 0),
  deductions: (Number(pnsStats.value?.total_deductions) || 0) + (Number(pppkStats.value?.total_deductions) || 0)
}))

const annualSummaryCards = computed(() => [
  { label: 'Avg Personil / Bulan', value: annualReport.value?.summary?.avg_employees_per_month?.toLocaleString() || 0, sub: 'Estimasi beban kerja SDM', class: 'text-teal-600' },
  { label: 'Total Payroll Tahunan', value: formatCurrencyCompact(annualReport.value?.yearly_total?.total_bersih), sub: 'Total pencairan anggaran', class: 'text-success' },
  { label: 'Biaya per Personil', value: formatCurrencyCompact(annualReport.value?.summary?.avg_salary_per_employee), sub: 'Rata-rata remunerasi bulanan', class: 'text-primary' }
])

const detailDialogTitle = computed(() => detailType.value === 'tunjangan' ? 'Detail Tunjangan Seluruh Komponen' : 'Detail Potongan Seluruh Komponen')
const detailItems = computed(() => detailType.value === 'tunjangan' ? combinedAllowanceBreakdown.value : combinedDeductionBreakdown.value)

const openDetailDialog = (type) => {
  detailType.value = type
  detailDialog.value = true
}

const tunjanganFields = [
  { key: 'total_tunj_istri', label: 'Tunjangan Istri' }, { key: 'total_tunj_anak', label: 'Tunjangan Anak' },
  { key: 'total_tunj_fungsional', label: 'Tunjangan Fungsional' }, { key: 'total_tunj_struktural', label: 'Tunjangan Struktural' },
  { key: 'total_tunj_umum', label: 'Tunjangan Umum' }, { key: 'total_tunj_beras', label: 'Tunjangan Beras' },
  { key: 'total_tunj_pph', label: 'Tunjangan PPh' }, { key: 'total_tunj_tpp', label: 'Tunjangan TPP' },
  { key: 'total_tunj_eselon', label: 'Tunjangan Eselon' }, { key: 'total_tunj_guru', label: 'Tunjangan Guru' },
]

const potonganFields = [
  { key: 'total_pot_iwp', label: 'Potongan IWP' }, { key: 'total_pot_askes', label: 'Askes/BPJS' },
  { key: 'total_pot_pph', label: 'PPh 21' }, { key: 'total_pot_taperum', label: 'Taperum' },
  { key: 'total_pot_jkk', label: 'JKK' }, { key: 'total_pot_jkm', label: 'JKM' },
]

const combinedAllowanceBreakdown = computed(() => {
  if (!pnsStats.value && !pppkStats.value) return []
  return [
    { 
      label: 'Gaji Pokok', 
      pns: Number(pnsStats.value?.total_gaji_pokok || 0), 
      pppk: Number(pppkStats.value?.total_gaji_pokok || 0), 
      total: Number(pnsStats.value?.total_gaji_pokok || 0) + Number(pppkStats.value?.total_gaji_pokok || 0) 
    },
    ...tunjanganFields.map(f => {
      const pnsVal = Number(pnsStats.value?.[f.key] || 0)
      const pppkVal = Number(pppkStats.value?.[f.key] || 0)
      return { 
        label: f.label, 
        pns: pnsVal, 
        pppk: pppkVal, 
        total: pnsVal + pppkVal 
      }
    }),
  ]
})

const combinedDeductionBreakdown = computed(() => {
  if (!pnsStats.value && !pppkStats.value) return []
  return potonganFields.map(f => {
    const pnsVal = Number(pnsStats.value?.[f.key] || 0)
    const pppkVal = Number(pppkStats.value?.[f.key] || 0)
    return { 
      label: f.label, 
      pns: pnsVal, 
      pppk: pppkVal, 
      total: pnsVal + pppkVal 
    }
  })
})

const allowanceBreakdown = computed(() => tunjanganFields.map(f => ({ label: f.label, value: stats.value?.[f.key] || 0 })))
const deductionBreakdown = computed(() => potonganFields.map(f => ({ label: f.label, value: stats.value?.[f.key] || 0 })))

// ═════════ LAST MONTH HELPERS ═════════
const lastMonthPnsEmployees = computed(() => {
  if (!pnsAnnual.value?.monthly) return 0
  const active = pnsAnnual.value.monthly.filter(m => (m.total_employees || 0) > 0)
  return active.length > 0 ? active[active.length - 1].total_employees : 0
})
const lastMonthPppkEmployees = computed(() => {
  if (!pppkAnnual.value?.monthly) return 0
  const active = pppkAnnual.value.monthly.filter(m => (m.total_employees || 0) > 0)
  return active.length > 0 ? active[active.length - 1].total_employees : 0
})
const lastMonthTotalEmployees = computed(() => lastMonthPnsEmployees.value + lastMonthPppkEmployees.value)

// ═════════ API FETCHING ═════════
const fetchData = async (skipLoading = false) => {
  if (!skipLoading) loading.value = true
  try {
    const params = { month: selectedMonth.value, year: selectedYear.value, jenis_gaji: selectedJenisGajiFilter.value || undefined }
    if (employeeType.value === 'combined') {
      const [p1, p2] = await Promise.all([api.get('/pns/dashboard', { params }), api.get('/pppk/dashboard', { params })])
      pnsStats.value = p1.data.data.summary; pppkStats.value = p2.data.data.summary
      stats.value = combinedTotal.value
    } else {
      const endpoint = employeeType.value === 'pns' ? '/pns/dashboard' : '/pppk/dashboard'
      const res = await api.get(endpoint, { params })
      stats.value = res.data.data.summary; skpdStats.value = res.data.data.skpd_breakdown; golonganStats.value = res.data.data.golongan_breakdown || []
    }
  } catch (err) { console.error(err) } finally {
    if (!skipLoading) loading.value = false
  }
}

const fetchYearlyTrend = async () => {
  try {
    if (employeeType.value === 'combined') {
      const params = { 
        year: selectedYear.value, 
        jenis_gaji: selectedJenisGajiFilter.value || undefined 
      }
      const [res1, res2] = await Promise.all([
        api.get('/pns/trend', { params }),
        api.get('/pppk/trend', { params })
      ])
      
      if (res1.data.success && res2.data.success) {
        const trend1 = res1.data.data.trend || []
        const trend2 = res2.data.data.trend || []
        
        // Merge and sum by month
        const merged = []
        for (let m = 1; m <= 12; m++) {
          const item1 = trend1.find(i => i.bulan === m)
          const item2 = trend2.find(i => i.bulan === m)
          
          if (item1 || item2) {
            merged.push({
              bulan: m,
              total_net: (Number(item1?.total_net) || 0) + (Number(item2?.total_net) || 0),
              total_tpp: (Number(item1?.total_tpp) || 0) + (Number(item2?.total_tpp) || 0)
            })
          }
        }
        yearlyTrend.value = merged
      }
    } else {
      const endpoint = employeeType.value === 'pns' ? '/pns/trend' : '/pppk/trend'
      const res = await api.get(endpoint, { 
        params: { 
          year: selectedYear.value,
          jenis_gaji: selectedJenisGajiFilter.value || undefined
        } 
      })
      if (res.data.success) {
        yearlyTrend.value = res.data.data.trend || []
      }
    }
    
    await nextTick()
    setTimeout(renderChart, 300)
  } catch (err) { 
    console.error('Failed to fetch yearly trend:', err) 
  }
}

const fetchAnnualReport = async () => {
  loadingAnnual.value = true
  try {
    const params = { year: selectedYear.value, jenis_gaji: selectedJenisGajiFilter.value || undefined }
    const [p1, p2] = await Promise.all([api.get('/pns/annual-report', { params: { ...params, type: 'pns' } }), api.get('/pns/annual-report', { params: { ...params, type: 'pppk' } })])
    if (p1.data.success && p2.data.success) {
      pnsAnnual.value = p1.data.data; pppkAnnual.value = p2.data.data
      const mergedMonthly = pnsAnnual.value.monthly.map((m, i) => {
        const merged = { ...m }
        Object.keys(m).filter(k => !['month', 'month_name'].includes(k)).forEach(k => { merged[k] += (pppkAnnual.value.monthly[i][k] || 0) })
        return merged
      })
      const mergedYearly = { ...pnsAnnual.value.yearly_total }
      Object.keys(mergedYearly).forEach(k => { mergedYearly[k] += (pppkAnnual.value.yearly_total[k] || 0) })
      
      const activeMonths = mergedMonthly.filter(m => m.total_employees > 0).length
      annualReport.value = {
        monthly: mergedMonthly, yearly_total: mergedYearly,
        summary: { avg_employees_per_month: Math.round(activeMonths ? mergedYearly.total_employees / activeMonths : 0), avg_salary_per_employee: Math.round(mergedYearly.total_employees ? mergedYearly.total_bersih / mergedYearly.total_employees : 0) }
      }
    }
  } catch (err) { console.error(err) } finally { loadingAnnual.value = false }
}

// ═════════ CHARTING ═════════
const renderChart = () => {
  if (!trendChart.value) {
    console.warn('Canvas ref trendChart not found')
    return
  }
  
  if (!yearlyTrend.value.length) {
    if (chartInstance) {
      chartInstance.destroy()
      chartInstance = null
    }
    return
  }
  
  if (chartInstance) {
    chartInstance.destroy()
  }
  
  const ctx = trendChart.value.getContext('2d')
  const labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']
  const netValues = new Array(12).fill(0)
  const tppValues = new Array(12).fill(0)
  
  yearlyTrend.value.forEach(item => {
    if (item.bulan >= 1 && item.bulan <= 12) {
      netValues[item.bulan - 1] = (Number(item.total_net) || 0) / 1000000
      tppValues[item.bulan - 1] = (Number(item.total_tpp) || 0) / 1000000
    }
  })

  // Get current text colors for chart labels with fallback
  let themeColor = '100, 116, 139' // default slate
  try {
    const computed = getComputedStyle(document.body).getPropertyValue('--v-theme-on-background')
    if (computed && computed.trim()) {
      themeColor = computed.trim()
    }
  } catch (e) {}

  chartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [
        { 
          label: 'Gaji Bersih (Juta)', 
          data: netValues, 
          borderColor: '#059669', 
          backgroundColor: 'rgba(5,150,105,0.08)', 
          fill: true, 
          tension: 0.4, 
          borderWidth: 3, 
          pointRadius: 4, 
          pointBackgroundColor: '#059669' 
        },
        { 
          label: 'TPP/Kinerja (Juta)', 
          data: tppValues, 
          borderColor: '#3b82f6', 
          backgroundColor: 'transparent', 
          tension: 0.4, 
          borderWidth: 2, 
          borderDash: [5, 5] 
        }
      ]
    },
    options: { 
      responsive: true, 
      maintainAspectRatio: false, 
      plugins: { 
        legend: { 
          position: 'bottom', 
          labels: { 
            usePointStyle: true, 
            padding: 20, 
            font: { weight: 'bold', size: 11 }, 
            color: themeColor.includes(',') ? `rgb(${themeColor})` : themeColor 
          } 
        },
        tooltip: {
          backgroundColor: 'rgba(0,0,0,0.8)',
          padding: 12,
          bodyFont: { size: 13 },
          callbacks: {
            label: (ctx) => `${ctx.dataset.label}: Rp ${ctx.raw.toFixed(1)} Juta`
          }
        }
      },
      scales: { 
        y: { 
          beginAtZero: true, 
          grid: { color: 'rgba(var(--v-border-color), 0.1)' }, 
          ticks: { 
            font: { weight: '600' }, 
            color: themeColor.includes(',') ? `rgb(${themeColor})` : themeColor,
            callback: (val) => val + ' Jt'
          } 
        },
        x: { 
          grid: { display: false }, 
          ticks: { 
            font: { weight: '600' }, 
            color: themeColor.includes(',') ? `rgb(${themeColor})` : themeColor 
          } 
        }
      } 
    }
  })
}

// ═════════ UPLOAD HANDLER ═════════
const uploadDialog = ref(false); const uploading = ref(false); const file = ref(null); const uploadError = ref(''); const jenisGaji = ref('Induk')
const activeJobId = ref(null); const activeJobStatus = ref(''); const activeJobProgress = ref(0); const activeJobFileName = ref(''); const activeJobResult = ref(null); const activeJobError = ref('')
let pollInterval = null

const uploadData = async () => {
  if (!file.value) return uploadError.value = 'File wajib dipilih'
  uploading.value = true
  const fd = new FormData()
  fd.append('file', Array.isArray(file.value) ? file.value[0] : file.value)
  fd.append('type', 'payroll_dbf'); fd.append('month', selectedMonth.value); fd.append('year', selectedYear.value); fd.append('jenis_gaji', jenisGaji.value)
  try {
    const res = await api.post('/upload-jobs', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    activeJobId.value = res.data.data.job_id; activeJobFileName.value = res.data.data.file_name; startPolling()
  } catch (err) { uploadError.value = err.response?.data?.message || 'Upload failed' } finally { uploading.value = false }
}

const startPolling = () => {
  pollInterval = setInterval(async () => {
    try {
      const res = await api.get(`/upload-jobs/${activeJobId.value}`)
      const job = res.data.data; activeJobStatus.value = job.status; activeJobProgress.value = job.progress
      if (['completed', 'failed'].includes(job.status)) {
        clearInterval(pollInterval); activeJobResult.value = job.result_summary; activeJobError.value = job.error_message; fetchData()
      }
    } catch (e) { clearInterval(pollInterval) }
  }, 3000)
}

const resetUpload = () => { activeJobId.value = null; file.value = null; uploadError.value = '' }
const closeUploadDialog = () => { uploadDialog.value = false; if (!['processing', 'pending'].includes(activeJobStatus.value)) resetUpload() }

const exporting = ref(false)
const exportCombinedAllowance = async () => {
  exporting.value = true
  try {
    const res = await api.get('/reports/combined-allowance-export', { params: { month: selectedMonth.value, year: selectedYear.value }, responseType: 'blob' })
    const url = window.URL.createObjectURL(new Blob([res.data])); const link = document.createElement('a'); link.href = url; link.setAttribute('download', `PNS_PPPK_Report_${selectedMonth.value}_${selectedYear.value}.xlsx`); document.body.appendChild(link); link.click(); link.remove()
  } catch (err) { alert('Export failed') } finally { exporting.value = false }
}

// ═════════ UTILITIES ═════════
const getMonthName = (m) => months.find(item => item.value === m)?.title || ''
const formatCurrencyShort = (v) => {
  const val = typeof v === 'string' ? parseFloat(v.replace(/,/g, '')) : v
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0)
}
const formatCurrencyCompact = (v) => {
  const val = typeof v === 'string' ? parseFloat(v.replace(/,/g, '')) : v
  if (!val) return 'Rp 0'
  if (val >= 1e9) return `Rp ${(val / 1e9).toFixed(1)} M`
  if (val >= 1e6) return `Rp ${(val / 1e6).toFixed(1)} Jt`
  return formatCurrencyShort(val)
}

onMounted(async () => { 
  loading.value = true
  await Promise.all([
    fetchData(true), 
    fetchYearlyTrend(), 
    fetchAnnualReport()
  ])
  loading.value = false
})
watch([selectedYear, employeeType, selectedJenisGajiFilter], () => { 
  fetchYearlyTrend()
  fetchAnnualReport()
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
    radial-gradient(at 100% 100%, rgba(var(--v-theme-success), 0.05) 0, transparent 50%);
}

.glass-panel {
  background: rgba(var(--v-theme-surface), 0.7) !important;
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(var(--v-border-color), 0.1) !important;
  border-radius: 28px !important;
  box-shadow: 0 4px 10px -2px rgba(0, 0, 0, 0.03), 0 2px 5px -2px rgba(0, 0, 0, 0.02) !important;
}

.glass-modal {
  background: rgba(var(--v-theme-surface), 0.95) !important;
  backdrop-filter: blur(24px);
  border: 1px solid rgba(var(--v-border-color), 0.1);
}

.stat-card {
  transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.stat-card:hover { transform: translateY(-3px); }

.premium-gradient {
  background: linear-gradient(135deg, rgb(var(--v-theme-primary)) 0%, rgb(var(--v-theme-primary-darken-1)) 100%) !important;
}

.glass-overlay {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(4px);
}

.type-selector-container {
  border-bottom: 2px solid rgba(var(--v-border-color), 0.1);
}

.ui-table th {
  padding: 16px !important;
  font-weight: 800 !important;
  color: rgb(var(--v-theme-on-surface), 0.6) !important;
}

.ui-table td {
  padding: 14px 16px !important;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.05);
}

.premium-table th {
  background: rgba(var(--v-theme-on-surface), 0.03);
  padding: 16px 20px !important;
  font-size: 0.7rem;
  font-weight: 900;
  letter-spacing: 0.1em;
}

.premium-table td {
  padding: 16px 20px !important;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.05);
  font-size: 0.85rem;
}

.sticky-left {
  position: sticky;
  left: 0;
  background: rgb(var(--v-theme-surface));
  z-index: 10;
  box-shadow: 2px 0 8px rgba(0,0,0,0.03);
}

.highlight-col {
  background: rgba(var(--v-theme-success), 0.05);
}

.footer-row td {
  background: rgb(var(--v-theme-on-surface), 0.95) !important;
  color: rgb(var(--v-theme-surface)) !important;
  padding: 18px 20px !important;
}

.table-row-hover:hover {
  background: rgba(var(--v-theme-on-surface), 0.02);
}

.ui-table-flat th {
  background: rgba(var(--v-theme-on-surface), 0.02);
  border-bottom: 2px solid rgba(var(--v-border-color), 0.1) !important;
}

.header-bg {
  background: rgba(var(--v-theme-on-surface), 0.02);
}

.border-b { border-bottom: 1px solid rgba(var(--v-border-color), 0.05); }

/* Global theme adjustments for chart and surface elements */
:deep(.v-table) {
  background: transparent !important;
}
</style>
