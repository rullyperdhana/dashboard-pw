<template>
  <v-app class="modern-dashboard">
    <Navbar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />
    <Sidebar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />

    <v-main class="bg-light">
      <v-container fluid class="pa-8">
        <!-- Header -->
        <v-row class="mb-4 align-center">
          <v-col cols="12" md="6">
            <h1 class="text-h4 font-weight-bold mb-1">
              <v-icon start color="teal" size="36">mdi-account-tie</v-icon>
              Dashboard Gaji Pegawai
            </h1>
            <p class="text-subtitle-1 text-grey-darken-1">Kelola dan analisis data gaji PNS & PPPK.</p>
          </v-col>
          <v-col cols="12" md="6" class="text-right">
            <!-- Date Filter -->
            <v-menu v-model="menu" :close-on-content-click="false">
              <template v-slot:activator="{ props }">
                <v-btn color="teal" variant="tonal" v-bind="props" prepend-icon="mdi-calendar">
                  {{ selectedMonthName }} {{ selectedYear }}
                </v-btn>
              </template>
              <v-card min-width="300" class="pa-4 rounded-xl">
                <v-row dense>
                  <v-col cols="6">
                    <v-select v-model="selectedMonth" :items="months" item-title="title" item-value="value" label="Bulan" density="compact" variant="outlined" hide-details></v-select>
                  </v-col>
                  <v-col cols="6">
                    <v-select v-model="selectedYear" :items="years" label="Tahun" density="compact" variant="outlined" hide-details></v-select>
                  </v-col>
                  <v-col cols="12" class="mt-2 text-right">
                    <v-btn block color="teal" @click="fetchData(); menu = false">TERAPKAN</v-btn>
                  </v-col>
                </v-row>
              </v-card>
            </v-menu>
            
            <v-btn color="primary" class="ml-2" prepend-icon="mdi-upload" @click="uploadDialog = true">
              Upload Data
            </v-btn>
          </v-col>
        </v-row>

        <!-- Employee Type Tabs -->
        <v-row class="mb-4">
          <v-col cols="12">
            <v-tabs v-model="employeeType" color="teal" align-tabs="start" @update:model-value="fetchData">
              <v-tab value="pns">
                <v-icon start>mdi-account-tie</v-icon>
                PNS
              </v-tab>
              <v-tab value="pppk">
                <v-icon start>mdi-account-clock</v-icon>
                PPPK Penuh Waktu
              </v-tab>
              <v-tab value="combined">
                <v-icon start>mdi-view-dashboard</v-icon>
                Gabungan (PNS + PPPK)
              </v-tab>
            </v-tabs>
          </v-col>
        </v-row>
        
        <div v-if="loading" class="text-center pa-12">
          <v-progress-circular indeterminate color="teal" size="64"></v-progress-circular>
        </div>

        <template v-else>
          <!-- Combined View -->
          <template v-if="employeeType === 'combined'">
            <!-- Combined Stats Cards -->
            <v-row class="mb-4">
              <v-col cols="12">
                <v-card class="glass-card rounded-xl pa-6" elevation="0">
                  <h3 class="text-h6 font-weight-bold mb-4">Ringkasan Gabungan - {{ selectedMonthName }} {{ selectedYear }}</h3>
                  <v-row>
                    <v-col cols="12" md="6">
                      <div class="pa-4 rounded-lg" style="background: rgba(var(--v-theme-teal), 0.1)">
                        <div class="d-flex align-center mb-3">
                          <v-icon color="teal" size="32" class="mr-2">mdi-account-tie</v-icon>
                          <h4 class="text-h6 font-weight-bold">PNS</h4>
                        </div>
                        <v-row dense>
                          <v-col cols="6">
                            <div class="text-caption text-grey-darken-1">Total Pegawai</div>
                            <div class="text-h5 font-weight-bold">{{ pnsStats?.total_employees?.toLocaleString() || 0 }}</div>
                          </v-col>
                          <v-col cols="6">
                            <div class="text-caption text-grey-darken-1">Gaji Bersih</div>
                            <div class="text-h6 font-weight-bold">{{ formatCurrencyShort(pnsStats?.total_net_salary) }}</div>
                          </v-col>
                          <v-col cols="6">
                            <div class="text-caption text-grey-darken-1">Gaji Kotor</div>
                            <div class="text-body-2 font-weight-medium">{{ formatCurrencyShort(pnsStats?.total_gross_salary) }}</div>
                          </v-col>
                          <v-col cols="6">
                            <div class="text-caption text-grey-darken-1">Potongan</div>
                            <div class="text-body-2 font-weight-medium">{{ formatCurrencyShort(pnsStats?.total_deductions) }}</div>
                          </v-col>
                        </v-row>
                      </div>
                    </v-col>
                    
                    <v-col cols="12" md="6">
                      <div class="pa-4 rounded-lg" style="background: rgba(var(--v-theme-purple), 0.1)">
                        <div class="d-flex align-center mb-3">
                          <v-icon color="purple" size="32" class="mr-2">mdi-account-clock</v-icon>
                          <h4 class="text-h6 font-weight-bold">PPPK</h4>
                        </div>
                        <v-row dense>
                          <v-col cols="6">
                            <div class="text-caption text-grey-darken-1">Total Pegawai</div>
                            <div class="text-h5 font-weight-bold">{{ pppkStats?.total_employees?.toLocaleString() || 0 }}</div>
                          </v-col>
                          <v-col cols="6">
                            <div class="text-caption text-grey-darken-1">Gaji Bersih</div>
                            <div class="text-h6 font-weight-bold">{{ formatCurrencyShort(pppkStats?.total_net_salary) }}</div>
                          </v-col>
                          <v-col cols="6">
                            <div class="text-caption text-grey-darken-1">Gaji Kotor</div>
                            <div class="text-body-2 font-weight-medium">{{ formatCurrencyShort(pppkStats?.total_gross_salary) }}</div>
                          </v-col>
                          <v-col cols="6">
                            <div class="text-caption text-grey-darken-1">Potongan</div>
                            <div class="text-body-2 font-weight-medium">{{ formatCurrencyShort(pppkStats?.total_deductions) }}</div>
                          </v-col>
                        </v-row>
                      </div>
                    </v-col>
                  </v-row>
                  
                  <!-- Total Combined -->
                  <v-divider class="my-4"></v-divider>
                  <v-row>
                    <v-col cols="12" sm="6" md="2">
                      <div class="text-center pa-3">
                        <div class="text-overline text-grey-darken-1">Total Pegawai</div>
                        <div class="text-h4 font-weight-bold text-primary">{{ combinedTotal.employees.toLocaleString() }}</div>
                      </div>
                    </v-col>
                    <v-col cols="12" sm="6" md="3">
                      <div class="text-center pa-3">
                        <div class="text-overline text-grey-darken-1">Total Gaji Bersih</div>
                        <div class="text-h5 font-weight-bold text-success">{{ formatCurrencyShort(combinedTotal.net) }}</div>
                      </div>
                    </v-col>
                    <v-col cols="12" sm="6" md="3">
                      <div class="text-center pa-3">
                        <div class="text-overline text-grey-darken-1">Total TPP</div>
                        <div class="text-h5 font-weight-bold text-info">{{ formatCurrencyShort(combinedTotal.tpp) }}</div>
                      </div>
                    </v-col>
                    <v-col cols="12" sm="6" md="2">
                      <div class="text-center pa-3">
                        <div class="text-overline text-grey-darken-1">Total Gaji Kotor</div>
                        <div class="text-h5 font-weight-bold text-grey-darken-2">{{ formatCurrencyShort(combinedTotal.gross) }}</div>
                      </div>
                    </v-col>
                    <v-col cols="12" sm="6" md="2">
                      <div class="text-center pa-3">
                        <div class="text-overline text-grey-darken-1">Total Potongan</div>
                        <div class="text-h5 font-weight-bold text-warning">{{ formatCurrencyShort(combinedTotal.deductions) }}</div>
                      </div>
                    </v-col>
                  </v-row>
                </v-card>
              </v-col>
            </v-row>
          </template>

          <!-- Single View (PNS or PPPK) -->
          <template v-else>
          <!-- Stats Cards -->
          <v-row>
            <v-col cols="12" sm="6" md="3">
              <v-card class="glass-card rounded-xl pa-4" elevation="0">
                <div class="text-overline text-grey-darken-1">Total {{ employeeType === 'pns' ? 'PNS' : 'PPPK' }}</div>
                <div class="text-h4 font-weight-bold">{{ stats?.total_employees?.toLocaleString() || 0 }}</div>
                <v-icon color="teal" class="float-right mt-n8" size="48">mdi-account-group</v-icon>
              </v-card>
            </v-col>
            <v-col cols="12" sm="6" md="3">
              <v-card class="glass-card rounded-xl pa-4" elevation="0">
                <div class="text-overline text-grey-darken-1">Net Payroll</div>
                <div class="text-h5 font-weight-bold">{{ formatCurrencyShort(stats?.total_net_salary) }}</div>
                <v-icon color="success" class="float-right mt-n8" size="48">mdi-cash-multiple</v-icon>
              </v-card>
            </v-col>
            <v-col cols="12" sm="6" md="3">
              <v-card class="glass-card rounded-xl pa-4" elevation="0">
                <div class="text-overline text-grey-darken-1">Total Deductions</div>
                <div class="text-h5 font-weight-bold">{{ formatCurrencyShort(stats?.total_deductions) }}</div>
                <v-icon color="warning" class="float-right mt-n8" size="48">mdi-content-cut</v-icon>
              </v-card>
            </v-col>
            <v-col cols="12" sm="6" md>
              <v-card class="glass-card rounded-xl pa-4" elevation="0">
                <div class="text-overline text-grey-darken-1">Gross Total</div>
                <div class="text-h5 font-weight-bold">{{ formatCurrencyShort(stats?.total_gross_salary) }}</div>
                <v-icon color="grey" class="float-right mt-n8" size="48">mdi-chart-line</v-icon>
              </v-card>
            </v-col>
            <v-col cols="12" sm="6" md>
              <v-card class="glass-card rounded-xl pa-4" elevation="0">
                <div class="text-overline text-grey-darken-1">Total TPP</div>
                <div class="text-h5 font-weight-bold text-info">{{ formatCurrencyShort(stats?.total_tunj_tpp) }}</div>
                <v-icon color="info" class="float-right mt-n8" size="48">mdi-cash-plus</v-icon>
              </v-card>
            </v-col>
          </v-row>

          <!-- Budget Intelligence -->
          <v-row class="mt-6 mb-2">
            <v-col cols="12">
              <h2 class="text-h5 font-weight-bold mb-4">Budget Intelligence</h2>
            </v-col>
            
            <!-- Projected Budget -->
            <v-col cols="12" md="4">
              <v-row>
                <v-col cols="12">
                    <v-card class="glass-card rounded-xl" elevation="0">
                        <v-card-text class="pa-6">
                            <div class="text-overline text-grey-darken-1 mb-2">Projected Annual Budget</div>
                            <div class="text-h4 font-weight-bold text-primary mb-1">
                            {{ formatCurrencyShort(stats?.projected_annual_budget) }}
                            </div>
                            <div class="text-caption text-grey">
                            Based on current month × 14
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>
                <v-col cols="12">
                    <v-card class="glass-card rounded-xl" elevation="0">
                        <v-card-text class="pa-6">
                            <div class="text-overline text-grey-darken-1 mb-2">TPP Budget Forecast (Next Year)</div>
                            <div class="text-h4 font-weight-bold text-info mb-1">
                            {{ formatCurrencyShort(stats?.tpp_forecast_next_year) }}
                            </div>
                            <div class="text-caption text-grey">
                            Estimated TPP Budget for next fiscal year
                            </div>
                        </v-card-text>
                    </v-card>
                </v-col>
              </v-row>
            </v-col>

            <!-- Cost by Rank -->
            <v-col cols="12" md="8">
              <v-card class="glass-card rounded-xl h-100" elevation="0">
                <v-card-title class="px-6 pt-6 font-weight-bold">Cost Distribution by Rank (Golongan)</v-card-title>
                <v-card-text class="pa-6">
                  <v-row v-if="golonganStats && golonganStats.length">
                    <v-col v-for="(item, i) in golonganStats" :key="i" cols="12" sm="6">
                       <div class="mb-4">
                          <div class="d-flex justify-space-between mb-1">
                            <span class="font-weight-medium">Golongan {{ item.golongan }}</span>
                            <span class="font-weight-bold">{{ formatCurrencyShort(item.cost) }}</span>
                          </div>
                          <v-progress-linear
                            :model-value="(item.cost / stats.total_gross_salary) * 100"
                            color="indigo"
                            height="8"
                            rounded
                            class="mb-1"
                          ></v-progress-linear>
                          <div class="text-caption text-grey text-right">{{ item.total }} staff</div>
                       </div>
                    </v-col>
                  </v-row>
                  <div v-else class="text-center py-8 text-grey">No rank data available</div>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>

          <!-- Combined Allowance/Deduction Breakdown (shows PNS vs PPPK) -->
          <template v-if="employeeType === 'combined'">
            <v-row class="mt-6 mb-2">
              <v-col cols="12">
                <h2 class="text-h5 font-weight-bold mb-4">Rincian Tunjangan Bulanan - Gabungan PNS + PPPK</h2>
              </v-col>
              <v-col cols="12">
                <v-card class="glass-card rounded-xl" elevation="0">
                  <v-card-text class="pa-4">
                    <div style="overflow-x: auto">
                      <table class="annual-table" style="width: 100%; border-collapse: collapse; font-size: 0.85rem">
                        <thead>
                          <tr style="background: rgba(var(--v-theme-primary), 0.08)">
                            <th class="text-left pa-2" style="min-width: 180px">Jenis Tunjangan</th>
                            <th class="text-right pa-2" style="min-width: 130px; color: teal">PNS</th>
                            <th class="text-right pa-2" style="min-width: 130px; color: purple">PPPK</th>
                            <th class="text-right pa-2" style="min-width: 130px; font-weight: 900">Total</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(item, idx) in combinedAllowanceBreakdown" :key="idx" :style="{ background: idx % 2 === 0 ? 'transparent' : 'rgba(0,0,0,0.02)' }">
                            <td class="pa-2 font-weight-medium">{{ item.label }}</td>
                            <td class="text-right pa-2" style="color: teal">{{ formatCurrencyShort(item.pns) }}</td>
                            <td class="text-right pa-2" style="color: purple">{{ formatCurrencyShort(item.pppk) }}</td>
                            <td class="text-right pa-2 font-weight-bold">{{ formatCurrencyShort(item.total) }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>

            <v-row class="mt-6 mb-2">
              <v-col cols="12">
                <h2 class="text-h5 font-weight-bold mb-4">Rincian Potongan Bulanan - Gabungan PNS + PPPK</h2>
              </v-col>
              <v-col cols="12">
                <v-card class="glass-card rounded-xl" elevation="0">
                  <v-card-text class="pa-4">
                    <div style="overflow-x: auto">
                      <table class="annual-table" style="width: 100%; border-collapse: collapse; font-size: 0.85rem">
                        <thead>
                          <tr style="background: rgba(var(--v-theme-error), 0.08)">
                            <th class="text-left pa-2" style="min-width: 180px">Jenis Potongan</th>
                            <th class="text-right pa-2" style="min-width: 130px; color: teal">PNS</th>
                            <th class="text-right pa-2" style="min-width: 130px; color: purple">PPPK</th>
                            <th class="text-right pa-2" style="min-width: 130px; font-weight: 900">Total</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr v-for="(item, idx) in combinedDeductionBreakdown" :key="idx" :style="{ background: idx % 2 === 0 ? 'transparent' : 'rgba(0,0,0,0.02)' }">
                            <td class="pa-2 font-weight-medium">{{ item.label }}</td>
                            <td class="text-right pa-2" style="color: teal">{{ formatCurrencyShort(item.pns) }}</td>
                            <td class="text-right pa-2" style="color: purple">{{ formatCurrencyShort(item.pppk) }}</td>
                            <td class="text-right pa-2 font-weight-bold text-error">{{ formatCurrencyShort(item.total) }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>
          </template>

          <!-- Single-type Allowance/Deduction Cards (PNS or PPPK only) -->
          <template v-else>
            <!-- Allowance Breakdown -->
            <v-row class="mt-6 mb-2">
              <v-col cols="12">
                <h2 class="text-h5 font-weight-bold mb-4">Rincian Tunjangan</h2>
              </v-col>
              <v-col cols="12">
                <v-card class="glass-card rounded-xl" elevation="0">
                  <v-card-text class="pa-6">
                    <v-row>
                      <v-col cols="12" sm="6" md="3" v-for="(allowance, key) in allowanceBreakdown" :key="key">
                        <div class="pa-4 rounded-lg" style="background: rgba(var(--v-theme-primary), 0.05)">
                          <div class="text-caption text-grey-darken-1 mb-1">{{ allowance.label }}</div>
                          <div class="text-h6 font-weight-bold mb-1">{{ formatCurrencyShort(allowance.value) }}</div>
                          <div class="text-caption text-grey">
                            {{ stats?.total_gross_salary > 0 ? ((allowance.value / stats?.total_gross_salary) * 100).toFixed(1) : '0.0' }}% dari total
                          </div>
                        </div>
                      </v-col>
                    </v-row>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>

            <!-- Deduction Breakdown -->
            <v-row class="mt-6 mb-2">
              <v-col cols="12">
                <h2 class="text-h5 font-weight-bold mb-4">Rincian Potongan</h2>
              </v-col>
              <v-col cols="12">
                <v-card class="glass-card rounded-xl" elevation="0">
                  <v-card-text class="pa-6">
                    <v-row>
                      <v-col cols="12" sm="6" md="3" v-for="(deduction, key) in deductionBreakdown" :key="key">
                        <div class="pa-4 rounded-lg" style="background: rgba(var(--v-theme-error), 0.05)">
                          <div class="text-caption text-grey-darken-1 mb-1">{{ deduction.label }}</div>
                          <div class="text-h6 font-weight-bold mb-1 text-error">{{ formatCurrencyShort(deduction.value) }}</div>
                          <div class="text-caption text-grey">
                            {{ stats?.total_deductions > 0 ? ((deduction.value / stats?.total_deductions) * 100).toFixed(1) : '0.0' }}% dari total potongan
                          </div>
                        </div>
                      </v-col>
                      <v-col cols="12" sm="6" md="3">
                        <div class="pa-4 rounded-lg" style="background: rgba(var(--v-theme-info), 0.05)">
                          <div class="text-caption text-grey-darken-1 mb-1">Pembulatan</div>
                          <div class="text-h6 font-weight-bold mb-1 text-info">{{ formatCurrencyShort(stats?.total_pembulatan || 0) }}</div>
                        </div>
                      </v-col>
                    </v-row>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>
          </template>

          <!-- Yearly Trend Chart -->
          <v-row class="mt-6 mb-2">
            <v-col cols="12">
              <h2 class="text-h5 font-weight-bold mb-4">Trend Tahunan {{ selectedYear }}</h2>
            </v-col>
            
            <v-col cols="12">
              <v-card class="glass-card rounded-xl" elevation="0">
                <v-card-text class="pa-6">
                  <div v-if="yearlyTrend.length" style="height: 300px">
                    <canvas ref="trendChart"></canvas>
                  </div>
                  <div v-else class="text-center text-grey py-12">
                    <v-icon size="64" color="grey-lighten-1">mdi-chart-line</v-icon>
                    <div class="mt-4">Belum ada data trend untuk tahun ini</div>
                  </div>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>

          <v-row class="mt-4">
            <!-- Top Earners -->
            <v-col cols="12" md="6">
              <v-card class="glass-card rounded-xl" elevation="0">
                <v-card-title class="font-weight-bold px-6 pt-6">Top Earners (Net)</v-card-title>
                <v-table class="bg-transparent px-4">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>SKPD</th>
                      <th class="text-right">Net Salary</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="p in topEarners" :key="p.nip">
                      <td>
                        <div class="font-weight-medium">{{ p.nama }}</div>
                        <div class="text-caption text-grey">{{ p.jabatan }}</div>
                      </td>
                      <td class="text-caption">{{ p.skpd }}</td>
                      <td class="text-right font-weight-bold">{{ formatCurrency(p.bersih) }}</td>
                    </tr>
                    <tr v-if="!topEarners.length">
                      <td colspan="3" class="text-center text-grey py-4">No data available</td>
                    </tr>
                  </tbody>
                </v-table>
              </v-card>
            </v-col>
            
            <!-- SKPD Distribution -->
            <v-col cols="12" md="6">
              <v-card class="glass-card rounded-xl" elevation="0">
                <v-card-title class="font-weight-bold px-6 pt-6">Cost by Unit (Top 10)</v-card-title>
                <v-list class="bg-transparent px-4" lines="two">
                  <v-list-item v-for="(item, i) in skpdStats" :key="i" class="mb-2 rounded-lg">
                    <template v-slot:prepend>
                      <v-avatar color="teal-lighten-5" class="text-teal font-weight-bold">{{ i + 1 }}</v-avatar>
                    </template>
                    <v-list-item-title class="font-weight-medium">{{ item.skpd }}</v-list-item-title>
                    <v-list-item-subtitle>{{ item.total }} staff</v-list-item-subtitle>
                    <template v-slot:append>
                      <div class="font-weight-bold">{{ formatCurrencyShort(item.cost) }}</div>
                    </template>
                  </v-list-item>
                  <div v-if="!skpdStats.length" class="text-center text-grey py-4">No data available</div>
                </v-list>
              </v-card>
            </v-col>
          </v-row>
        </template>
        </template>

        <!-- Annual Report Section - Only in Combined View -->
        <v-row class="mt-6 mb-2" v-if="employeeType === 'combined'">
          <v-col cols="12">
            <h2 class="text-h5 font-weight-bold mb-4">Laporan Tahunan {{ selectedYear }}</h2>
          </v-col>
          
          <!-- Summary Cards -->
          <v-col cols="12" v-if="annualReport">
            <v-row class="mb-4">
              <v-col cols="12" md="4">
                <v-card class="glass-card rounded-xl pa-4" elevation="0">
                  <div class="text-overline text-grey-darken-1">Rata-rata Pegawai/Bulan</div>
                  <div class="text-h4 font-weight-bold">{{ annualReport.summary?.avg_employees_per_month?.toLocaleString() || 0 }}</div>
                  <div class="text-caption text-grey">Dari {{ annualReport.summary?.months_with_data || 0 }} bulan dengan data</div>
                </v-card>
              </v-col>
              <v-col cols="12" md="4">
                <v-card class="glass-card rounded-xl pa-4" elevation="0">
                  <div class="text-overline text-grey-darken-1">Total Gaji Dibayarkan</div>
                  <div class="text-h5 font-weight-bold">{{ formatCurrencyShort(annualReport.yearly_total?.total_bersih) }}</div>
                  <div class="text-caption text-grey">Selama {{ selectedYear }}</div>
                </v-card>
              </v-col>
              <v-col cols="12" md="4">
                <v-card class="glass-card rounded-xl pa-4" elevation="0">
                  <div class="text-overline text-grey-darken-1">Rata-rata Gaji/Pegawai</div>
                  <div class="text-h5 font-weight-bold">{{ formatCurrency(annualReport.summary?.avg_salary_per_employee) }}</div>
                  <div class="text-caption text-grey">Per pegawai per bulan</div>
                </v-card>
              </v-col>
            </v-row>
          </v-col>

          <!-- Detailed Allowance Table -->
          <v-col cols="12">
            <v-card class="glass-card rounded-xl" elevation="0">
              <v-card-title class="font-weight-bold px-6 pt-6 d-flex align-center">
                <span>Rincian Tunjangan Bulanan - Gabungan PNS + PPPK</span>
                <v-spacer></v-spacer>
                <v-btn-toggle v-model="reportViewMode" mandatory density="compact" color="primary" variant="outlined" class="mr-2">
                  <v-btn value="combined" size="small">
                    <v-icon start size="small">mdi-view-dashboard</v-icon>
                    Gabungan
                  </v-btn>
                  <v-btn value="skpd" size="small">
                    <v-icon start size="small">mdi-office-building</v-icon>
                    Per SKPD
                  </v-btn>
                </v-btn-toggle>
                <v-select
                  v-if="reportViewMode === 'skpd'"
                  v-model="selectedSkpd"
                  :items="skpdList"
                  item-title="nama_skpd"
                  item-value="id_skpd"
                  label="Pilih SKPD"
                  density="compact"
                  variant="outlined"
                  hide-details
                  style="max-width: 300px;"
                  class="mr-2"
                ></v-select>
                <v-chip color="primary" size="small" variant="tonal" @click="$router.push('/reports/skpd-monthly')" style="cursor: pointer;">
                  <v-icon start size="small">mdi-table</v-icon>
                  Detail Lengkap
                </v-chip>
              </v-card-title>
              <v-card-text class="pa-0">
                <div v-if="loadingAnnual" class="text-center pa-12">
                  <v-progress-circular indeterminate color="primary"></v-progress-circular>
                </div>
                <div v-else-if="annualReport" style="overflow-x: auto;">
                  <table class="annual-report-table">
                    <thead>
                      <tr>
                        <th class="sticky-col">Bulan</th>
                        <th class="text-center" style="min-width: 60px">Tipe</th>
                        <th class="text-right">Pegawai</th>
                        <th class="text-right">Gaji Pokok</th>
                        <th class="text-right">Tunj. Istri</th>
                        <th class="text-right">Tunj. Anak</th>
                        <th class="text-right">Tunj. TPP</th>
                        <th class="text-right">Tunj. Fungsional</th>
                        <th class="text-right">Tunj. Eselon</th>
                        <th class="text-right">Tunj. Umum</th>
                        <th class="text-right">Tunj. Beras</th>
                        <th class="text-right">Tunj. PPh</th>
                        <th class="text-right highlight-col">Total Tunjangan</th>
                        <th class="text-right">Potongan</th>
                        <th class="text-right highlight-col">Gaji Bersih</th>
                      </tr>
                    </thead>
                    <tbody>
                      <template v-for="(month, mIdx) in annualReport.monthly" :key="month.month">
                        <!-- PNS Row -->
                        <tr v-if="pnsAnnual" :class="{ 'no-data': (pnsAnnual.monthly[mIdx]?.total_employees || 0) === 0 }" style="background: rgba(0,128,128,0.04)">
                          <td class="sticky-col font-weight-bold" :rowspan="3" style="vertical-align: middle; border-bottom: 2px solid #e0e0e0">{{ getMonthName(month.month) }}</td>
                          <td class="text-center"><v-chip size="x-small" color="teal" variant="tonal">PNS</v-chip></td>
                          <td class="text-right">{{ (pnsAnnual.monthly[mIdx]?.total_employees || 0) > 0 ? pnsAnnual.monthly[mIdx].total_employees.toLocaleString() : '-' }}</td>
                          <td class="text-right">{{ (pnsAnnual.monthly[mIdx]?.total_employees || 0) > 0 ? formatCurrencyShort(pnsAnnual.monthly[mIdx].total_gaji_pokok) : '-' }}</td>
                          <td class="text-right">{{ (pnsAnnual.monthly[mIdx]?.total_employees || 0) > 0 ? formatCurrencyShort(pnsAnnual.monthly[mIdx].total_tunj_istri) : '-' }}</td>
                          <td class="text-right">{{ (pnsAnnual.monthly[mIdx]?.total_employees || 0) > 0 ? formatCurrencyShort(pnsAnnual.monthly[mIdx].total_tunj_anak) : '-' }}</td>
                          <td class="text-right">{{ (pnsAnnual.monthly[mIdx]?.total_employees || 0) > 0 ? formatCurrencyShort(pnsAnnual.monthly[mIdx].total_tunj_tpp) : '-' }}</td>
                          <td class="text-right">{{ (pnsAnnual.monthly[mIdx]?.total_employees || 0) > 0 ? formatCurrencyShort(pnsAnnual.monthly[mIdx].total_tunj_fungsional) : '-' }}</td>
                          <td class="text-right">{{ (pnsAnnual.monthly[mIdx]?.total_employees || 0) > 0 ? formatCurrencyShort(pnsAnnual.monthly[mIdx].total_tunj_struktural) : '-' }}</td>
                          <td class="text-right">{{ (pnsAnnual.monthly[mIdx]?.total_employees || 0) > 0 ? formatCurrencyShort(pnsAnnual.monthly[mIdx].total_tunj_umum) : '-' }}</td>
                          <td class="text-right">{{ (pnsAnnual.monthly[mIdx]?.total_employees || 0) > 0 ? formatCurrencyShort(pnsAnnual.monthly[mIdx].total_tunj_beras) : '-' }}</td>
                          <td class="text-right">{{ (pnsAnnual.monthly[mIdx]?.total_employees || 0) > 0 ? formatCurrencyShort(pnsAnnual.monthly[mIdx].total_tunj_pph) : '-' }}</td>
                          <td class="text-right highlight-col">{{ (pnsAnnual.monthly[mIdx]?.total_employees || 0) > 0 ? formatCurrencyShort(pnsAnnual.monthly[mIdx].total_tunjangan) : '-' }}</td>
                          <td class="text-right">{{ (pnsAnnual.monthly[mIdx]?.total_employees || 0) > 0 ? formatCurrencyShort(pnsAnnual.monthly[mIdx].total_potongan) : '-' }}</td>
                          <td class="text-right highlight-col">{{ (pnsAnnual.monthly[mIdx]?.total_employees || 0) > 0 ? formatCurrencyShort(pnsAnnual.monthly[mIdx].total_bersih) : '-' }}</td>
                        </tr>
                        <!-- PPPK Row -->
                        <tr v-if="pppkAnnual" :class="{ 'no-data': (pppkAnnual.monthly[mIdx]?.total_employees || 0) === 0 }" style="background: rgba(128,0,128,0.04)">
                          <td class="text-center"><v-chip size="x-small" color="purple" variant="tonal">PPPK</v-chip></td>
                          <td class="text-right">{{ (pppkAnnual.monthly[mIdx]?.total_employees || 0) > 0 ? pppkAnnual.monthly[mIdx].total_employees.toLocaleString() : '-' }}</td>
                          <td class="text-right">{{ (pppkAnnual.monthly[mIdx]?.total_employees || 0) > 0 ? formatCurrencyShort(pppkAnnual.monthly[mIdx].total_gaji_pokok) : '-' }}</td>
                          <td class="text-right">{{ (pppkAnnual.monthly[mIdx]?.total_employees || 0) > 0 ? formatCurrencyShort(pppkAnnual.monthly[mIdx].total_tunj_istri) : '-' }}</td>
                          <td class="text-right">{{ (pppkAnnual.monthly[mIdx]?.total_employees || 0) > 0 ? formatCurrencyShort(pppkAnnual.monthly[mIdx].total_tunj_anak) : '-' }}</td>
                          <td class="text-right">{{ (pppkAnnual.monthly[mIdx]?.total_employees || 0) > 0 ? formatCurrencyShort(pppkAnnual.monthly[mIdx].total_tunj_tpp) : '-' }}</td>
                          <td class="text-right">{{ (pppkAnnual.monthly[mIdx]?.total_employees || 0) > 0 ? formatCurrencyShort(pppkAnnual.monthly[mIdx].total_tunj_fungsional) : '-' }}</td>
                          <td class="text-right">{{ (pppkAnnual.monthly[mIdx]?.total_employees || 0) > 0 ? formatCurrencyShort(pppkAnnual.monthly[mIdx].total_tunj_struktural) : '-' }}</td>
                          <td class="text-right">{{ (pppkAnnual.monthly[mIdx]?.total_employees || 0) > 0 ? formatCurrencyShort(pppkAnnual.monthly[mIdx].total_tunj_umum) : '-' }}</td>
                          <td class="text-right">{{ (pppkAnnual.monthly[mIdx]?.total_employees || 0) > 0 ? formatCurrencyShort(pppkAnnual.monthly[mIdx].total_tunj_beras) : '-' }}</td>
                          <td class="text-right">{{ (pppkAnnual.monthly[mIdx]?.total_employees || 0) > 0 ? formatCurrencyShort(pppkAnnual.monthly[mIdx].total_tunj_pph) : '-' }}</td>
                          <td class="text-right highlight-col">{{ (pppkAnnual.monthly[mIdx]?.total_employees || 0) > 0 ? formatCurrencyShort(pppkAnnual.monthly[mIdx].total_tunjangan) : '-' }}</td>
                          <td class="text-right">{{ (pppkAnnual.monthly[mIdx]?.total_employees || 0) > 0 ? formatCurrencyShort(pppkAnnual.monthly[mIdx].total_potongan) : '-' }}</td>
                          <td class="text-right highlight-col">{{ (pppkAnnual.monthly[mIdx]?.total_employees || 0) > 0 ? formatCurrencyShort(pppkAnnual.monthly[mIdx].total_bersih) : '-' }}</td>
                        </tr>
                        <!-- Combined Row -->
                        <tr :class="{ 'current-month': month.month === selectedMonth, 'no-data': month.total_employees === 0 }" style="font-weight: 700; border-bottom: 2px solid #e0e0e0">
                          <td class="text-center"><v-chip size="x-small" color="primary" variant="flat">Total</v-chip></td>
                          <td class="text-right">{{ month.total_employees > 0 ? month.total_employees.toLocaleString() : '-' }}</td>
                          <td class="text-right">{{ month.total_employees > 0 ? formatCurrencyShort(month.total_gaji_pokok) : '-' }}</td>
                          <td class="text-right">{{ month.total_employees > 0 ? formatCurrencyShort(month.total_tunj_istri) : '-' }}</td>
                          <td class="text-right">{{ month.total_employees > 0 ? formatCurrencyShort(month.total_tunj_anak) : '-' }}</td>
                          <td class="text-right">{{ month.total_employees > 0 ? formatCurrencyShort(month.total_tunj_tpp) : '-' }}</td>
                          <td class="text-right">{{ month.total_employees > 0 ? formatCurrencyShort(month.total_tunj_fungsional) : '-' }}</td>
                          <td class="text-right">{{ month.total_employees > 0 ? formatCurrencyShort(month.total_tunj_eselon) : '-' }}</td>
                          <td class="text-right">{{ month.total_employees > 0 ? formatCurrencyShort(month.total_tunj_umum) : '-' }}</td>
                          <td class="text-right">{{ month.total_employees > 0 ? formatCurrencyShort(month.total_tunj_beras) : '-' }}</td>
                          <td class="text-right">{{ month.total_employees > 0 ? formatCurrencyShort(month.total_tunj_pph) : '-' }}</td>
                          <td class="text-right highlight-col font-weight-bold">{{ month.total_employees > 0 ? formatCurrencyShort(month.total_tunjangan) : '-' }}</td>
                          <td class="text-right">{{ month.total_employees > 0 ? formatCurrencyShort(month.total_potongan) : '-' }}</td>
                          <td class="text-right highlight-col font-weight-bold">{{ month.total_employees > 0 ? formatCurrencyShort(month.total_bersih) : '-' }}</td>
                        </tr>
                      </template>
                      <!-- TOTAL Rows -->
                      <tr style="background: rgba(0,128,128,0.06)">
                        <td class="sticky-col font-weight-black" rowspan="3" style="vertical-align: middle; border-top: 3px solid #333">TOTAL</td>
                        <td class="text-center"><v-chip size="x-small" color="teal" variant="tonal">PNS</v-chip></td>
                        <td class="text-right font-weight-bold">{{ lastMonthPnsEmployees.toLocaleString() }}</td>
                        <td class="text-right font-weight-bold">{{ formatCurrencyShort(pnsAnnual?.yearly_total?.total_gaji_pokok) }}</td>
                        <td class="text-right font-weight-bold">{{ formatCurrencyShort(pnsAnnual?.yearly_total?.total_tunj_istri) }}</td>
                        <td class="text-right font-weight-bold">{{ formatCurrencyShort(pnsAnnual?.yearly_total?.total_tunj_anak) }}</td>
                        <td class="text-right font-weight-bold">{{ formatCurrencyShort(pnsAnnual?.yearly_total?.total_tunj_tpp) }}</td>
                        <td class="text-right font-weight-bold">{{ formatCurrencyShort(pnsAnnual?.yearly_total?.total_tunj_fungsional) }}</td>
                        <td class="text-right font-weight-bold">{{ formatCurrencyShort(pnsAnnual?.yearly_total?.total_tunj_eselon) }}</td>
                        <td class="text-right font-weight-bold">{{ formatCurrencyShort(pnsAnnual?.yearly_total?.total_tunj_umum) }}</td>
                        <td class="text-right font-weight-bold">{{ formatCurrencyShort(pnsAnnual?.yearly_total?.total_tunj_beras) }}</td>
                        <td class="text-right font-weight-bold">{{ formatCurrencyShort(pnsAnnual?.yearly_total?.total_tunj_pph) }}</td>
                        <td class="text-right highlight-col font-weight-bold">{{ formatCurrencyShort(pnsAnnual?.yearly_total?.total_tunjangan) }}</td>
                        <td class="text-right font-weight-bold">{{ formatCurrencyShort(pnsAnnual?.yearly_total?.total_potongan) }}</td>
                        <td class="text-right highlight-col font-weight-bold">{{ formatCurrencyShort(pnsAnnual?.yearly_total?.total_bersih) }}</td>
                      </tr>
                      <tr style="background: rgba(128,0,128,0.06)">
                        <td class="text-center"><v-chip size="x-small" color="purple" variant="tonal">PPPK</v-chip></td>
                        <td class="text-right font-weight-bold">{{ lastMonthPppkEmployees.toLocaleString() }}</td>
                        <td class="text-right font-weight-bold">{{ formatCurrencyShort(pppkAnnual?.yearly_total?.total_gaji_pokok) }}</td>
                        <td class="text-right font-weight-bold">{{ formatCurrencyShort(pppkAnnual?.yearly_total?.total_tunj_istri) }}</td>
                        <td class="text-right font-weight-bold">{{ formatCurrencyShort(pppkAnnual?.yearly_total?.total_tunj_anak) }}</td>
                        <td class="text-right font-weight-bold">{{ formatCurrencyShort(pppkAnnual?.yearly_total?.total_tunj_tpp) }}</td>
                        <td class="text-right font-weight-bold">{{ formatCurrencyShort(pppkAnnual?.yearly_total?.total_tunj_fungsional) }}</td>
                        <td class="text-right font-weight-bold">{{ formatCurrencyShort(pppkAnnual?.yearly_total?.total_tunj_eselon) }}</td>
                        <td class="text-right font-weight-bold">{{ formatCurrencyShort(pppkAnnual?.yearly_total?.total_tunj_umum) }}</td>
                        <td class="text-right font-weight-bold">{{ formatCurrencyShort(pppkAnnual?.yearly_total?.total_tunj_beras) }}</td>
                        <td class="text-right font-weight-bold">{{ formatCurrencyShort(pppkAnnual?.yearly_total?.total_tunj_pph) }}</td>
                        <td class="text-right highlight-col font-weight-bold">{{ formatCurrencyShort(pppkAnnual?.yearly_total?.total_tunjangan) }}</td>
                        <td class="text-right font-weight-bold">{{ formatCurrencyShort(pppkAnnual?.yearly_total?.total_potongan) }}</td>
                        <td class="text-right highlight-col font-weight-bold">{{ formatCurrencyShort(pppkAnnual?.yearly_total?.total_bersih) }}</td>
                      </tr>
                      <tr class="total-row" style="border-top: 2px solid #333">
                        <td class="text-center"><v-chip size="x-small" color="primary" variant="flat">Total</v-chip></td>
                        <td class="text-right font-weight-black">{{ lastMonthTotalEmployees.toLocaleString() }}</td>
                        <td class="text-right font-weight-black">{{ formatCurrencyShort(annualReport.yearly_total.total_gaji_pokok) }}</td>
                        <td class="text-right font-weight-black">{{ formatCurrencyShort(annualReport.yearly_total.total_tunj_istri) }}</td>
                        <td class="text-right font-weight-black">{{ formatCurrencyShort(annualReport.yearly_total.total_tunj_anak) }}</td>
                        <td class="text-right font-weight-black">{{ formatCurrencyShort(annualReport.yearly_total.total_tunj_tpp) }}</td>
                        <td class="text-right font-weight-black">{{ formatCurrencyShort(annualReport.yearly_total.total_tunj_fungsional) }}</td>
                        <td class="text-right font-weight-black">{{ formatCurrencyShort(annualReport.yearly_total.total_tunj_eselon) }}</td>
                        <td class="text-right font-weight-black">{{ formatCurrencyShort(annualReport.yearly_total.total_tunj_umum) }}</td>
                        <td class="text-right font-weight-black">{{ formatCurrencyShort(annualReport.yearly_total.total_tunj_beras) }}</td>
                        <td class="text-right font-weight-black">{{ formatCurrencyShort(annualReport.yearly_total.total_tunj_pph) }}</td>
                        <td class="text-right highlight-col font-weight-black">{{ formatCurrencyShort(annualReport.yearly_total.total_tunjangan) }}</td>
                        <td class="text-right font-weight-black">{{ formatCurrencyShort(annualReport.yearly_total.total_potongan) }}</td>
                        <td class="text-right highlight-col font-weight-black">{{ formatCurrencyShort(annualReport.yearly_total.total_bersih) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div v-else class="text-center text-grey py-12">
                  <v-icon size="64" color="grey-lighten-1">mdi-table-off</v-icon>
                  <div class="mt-4">Belum ada data laporan tahunan</div>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>


        <!-- Upload Dialog -->
        <v-dialog v-model="uploadDialog" max-width="500">
          <v-card class="rounded-xl">
            <v-card-title class="pa-4 bg-teal text-white">Upload PNS Data</v-card-title>
            <v-card-text class="pa-6">
              <v-form @submit.prevent="uploadData">
                <v-radio-group v-model="uploadType" inline label="Tipe Pegawai" class="mb-4">
                  <v-radio label="PNS" value="pns" color="teal"></v-radio>
                  <v-radio label="PPPK Penuh Waktu" value="pppk" color="teal"></v-radio>
                </v-radio-group>

                <v-select
                  v-model="jenisGaji"
                  :items="['Induk', 'Susulan', 'Kekurangan', 'Terusan']"
                  label="Jenis Gaji"
                  variant="outlined"
                  class="mb-4"
                ></v-select>

                <v-file-input
                  v-model="file"
                  label="Pilih File XLS/XLSX"
                  prepend-icon="mdi-microsoft-excel"
                  variant="outlined"
                  accept=".xls,.xlsx"
                  show-size
                  :rules="[v => !!v || 'File wajib dipilih']"
                ></v-file-input>
                
                <div class="text-caption text-grey mb-4">
                  <v-icon size="small" color="info" class="mr-1">mdi-information</v-icon>
                  Uploading will REPLACE existing data for {{ selectedMonthName }} {{ selectedYear }}.
                </div>
                
                <v-progress-linear v-if="uploading" indeterminate color="teal" height="6" class="mb-4 rounded-pill"></v-progress-linear>
                
                <v-alert v-if="uploadError" type="error" variant="tonal" class="mb-4 text-caption">{{ uploadError }}</v-alert>
                <v-alert v-if="uploadSuccess" type="success" variant="tonal" class="mb-4 text-caption">{{ uploadSuccess }}</v-alert>

                <v-btn block color="teal" size="large" type="submit" :loading="uploading" :disabled="!file">START UPLOAD</v-btn>
              </v-form>
            </v-card-text>
          </v-card>
        </v-dialog>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import axios from 'axios'
import api from '../api'
import Sidebar from '../components/Sidebar.vue'
import Navbar from '../components/Navbar.vue'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

const loading = ref(true)
const loadingAnnual = ref(false)
const stats = ref(null)
const pnsStats = ref(null)
const pppkStats = ref(null)
const topEarners = ref([])
const skpdStats = ref([])
const golonganStats = ref([])
const yearlyTrend = ref([])
const annualReport = ref(null)
const pnsAnnual = ref(null)
const pppkAnnual = ref(null)
const trendChart = ref(null)
let chartInstance = null

const employeeType = ref('pns') // 'pns', 'pppk', or 'combined'
const reportViewMode = ref('combined') // 'combined' or 'skpd'
const selectedSkpd = ref(null)
const skpdList = ref([])
const menu = ref(false)
const selectedMonth = ref(new Date().getMonth() + 1)
const selectedYear = ref(new Date().getFullYear())
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

// Combined Total for Combined View
const combinedTotal = computed(() => {
  return {
    employees: (Number(pnsStats.value?.total_employees) || 0) + (Number(pppkStats.value?.total_employees) || 0),
    net: (Number(pnsStats.value?.total_net_salary) || 0) + (Number(pppkStats.value?.total_net_salary) || 0),
    gross: (Number(pnsStats.value?.total_gross_salary) || 0) + (Number(pppkStats.value?.total_gross_salary) || 0),
    tpp: (Number(pnsStats.value?.total_tunj_tpp) || 0) + (Number(pppkStats.value?.total_tunj_tpp) || 0),
    deductions: (Number(pnsStats.value?.total_deductions) || 0) + (Number(pppkStats.value?.total_deductions) || 0)
  }
})

// Last month's employee count helpers (instead of cumulative sum)
const lastMonthPnsEmployees = computed(() => {
  if (!pnsAnnual.value?.monthly) return 0
  const monthsWithData = pnsAnnual.value.monthly.filter(m => (m.total_employees || 0) > 0)
  return monthsWithData.length > 0 ? monthsWithData[monthsWithData.length - 1].total_employees : 0
})
const lastMonthPppkEmployees = computed(() => {
  if (!pppkAnnual.value?.monthly) return 0
  const monthsWithData = pppkAnnual.value.monthly.filter(m => (m.total_employees || 0) > 0)
  return monthsWithData.length > 0 ? monthsWithData[monthsWithData.length - 1].total_employees : 0
})
const lastMonthTotalEmployees = computed(() => {
  return lastMonthPnsEmployees.value + lastMonthPppkEmployees.value
})

// Allowance Breakdown
const allowanceBreakdown = computed(() => {
  if (!stats.value) return []
  return [
    { label: 'Tunjangan Istri', value: stats.value.total_tunj_istri || 0 },
    { label: 'Tunjangan Anak', value: stats.value.total_tunj_anak || 0 },
    { label: 'Tunjangan Fungsional', value: stats.value.total_tunj_fungsional || 0 },
    { label: 'Tunjangan Struktural', value: stats.value.total_tunj_struktural || 0 },
    { label: 'Tunjangan Umum', value: stats.value.total_tunj_umum || 0 },
    { label: 'Tunjangan Beras', value: stats.value.total_tunj_beras || 0 },
    { label: 'Tunjangan PPh', value: stats.value.total_tunj_pph || 0 },
    { label: 'Tunjangan TPP', value: stats.value.total_tunj_tpp || 0 },
    { label: 'Tunjangan Eselon', value: stats.value.total_tunj_eselon || 0 },
    { label: 'Tunjangan Guru', value: stats.value.total_tunj_guru || 0 },
    { label: 'Tunjangan Langka', value: stats.value.total_tunj_langka || 0 },
    { label: 'Tunjangan TKD', value: stats.value.total_tunj_tkd || 0 },
    { label: 'Tunjangan Terpencil', value: stats.value.total_tunj_terpencil || 0 },
    { label: 'Tunjangan Khusus', value: stats.value.total_tunj_khusus || 0 },
    { label: 'Tunjangan Askes', value: stats.value.total_tunj_askes || 0 },
    { label: 'Tunjangan JKK', value: stats.value.total_tunj_kk || 0 },
    { label: 'Tunjangan JKM', value: stats.value.total_tunj_km || 0 },
  ]
})

// Deduction Breakdown
const deductionBreakdown = computed(() => {
  if (!stats.value) return []
  return [
    { label: 'IWP (Total)', value: stats.value.total_pot_iwp || 0 },
    { label: 'IWP 1%', value: stats.value.total_pot_iwp1 || 0 },
    { label: 'IWP 8%', value: stats.value.total_pot_iwp8 || 0 },
    { label: 'Askes/BPJS', value: stats.value.total_pot_askes || 0 },
    { label: 'PPh 21', value: stats.value.total_pot_pph || 0 },
    { label: 'Bulog', value: stats.value.total_pot_bulog || 0 },
    { label: 'Taperum', value: stats.value.total_pot_taperum || 0 },
    { label: 'Sewa Rumah', value: stats.value.total_pot_sewa || 0 },
    { label: 'Hutang', value: stats.value.total_pot_hutang || 0 },
    { label: 'Korpri', value: stats.value.total_pot_korpri || 0 },
    { label: 'Irdhata', value: stats.value.total_pot_irdhata || 0 },
    { label: 'Koperasi', value: stats.value.total_pot_koperasi || 0 },
    { label: 'JKK', value: stats.value.total_pot_jkk || 0 },
    { label: 'JKM', value: stats.value.total_pot_jkm || 0 },
  ]
})

// Combined Allowance Breakdown (PNS vs PPPK)
const tunjanganFields = [
  { key: 'total_tunj_istri', label: 'Tunjangan Istri' },
  { key: 'total_tunj_anak', label: 'Tunjangan Anak' },
  { key: 'total_tunj_fungsional', label: 'Tunjangan Fungsional' },
  { key: 'total_tunj_struktural', label: 'Tunjangan Struktural' },
  { key: 'total_tunj_umum', label: 'Tunjangan Umum' },
  { key: 'total_tunj_beras', label: 'Tunjangan Beras' },
  { key: 'total_tunj_pph', label: 'Tunjangan PPh' },
  { key: 'total_tunj_tpp', label: 'Tunjangan TPP' },
  { key: 'total_tunj_eselon', label: 'Tunjangan Eselon' },
  { key: 'total_tunj_guru', label: 'Tunjangan Guru' },
  { key: 'total_tunj_langka', label: 'Tunjangan Langka' },
  { key: 'total_tunj_tkd', label: 'Tunjangan TKD' },
  { key: 'total_tunj_terpencil', label: 'Tunjangan Terpencil' },
  { key: 'total_tunj_khusus', label: 'Tunjangan Khusus' },
  { key: 'total_tunj_askes', label: 'Tunjangan Askes' },
  { key: 'total_tunj_kk', label: 'Tunjangan JKK' },
  { key: 'total_tunj_km', label: 'Tunjangan JKM' },
]

const potonganFields = [
  { key: 'total_pot_iwp', label: 'IWP (Total)' },
  { key: 'total_pot_iwp1', label: 'IWP 1%' },
  { key: 'total_pot_iwp8', label: 'IWP 8%' },
  { key: 'total_pot_askes', label: 'Askes/BPJS' },
  { key: 'total_pot_pph', label: 'PPh 21' },
  { key: 'total_pot_bulog', label: 'Bulog' },
  { key: 'total_pot_taperum', label: 'Taperum' },
  { key: 'total_pot_sewa', label: 'Sewa Rumah' },
  { key: 'total_pot_hutang', label: 'Hutang' },
  { key: 'total_pot_korpri', label: 'Korpri' },
  { key: 'total_pot_irdhata', label: 'Irdhata' },
  { key: 'total_pot_koperasi', label: 'Koperasi' },
  { key: 'total_pot_jkk', label: 'JKK' },
  { key: 'total_pot_jkm', label: 'JKM' },
]

const combinedAllowanceBreakdown = computed(() => {
  if (!pnsStats.value && !pppkStats.value) return []
  return tunjanganFields.map(f => ({
    label: f.label,
    pns: pnsStats.value?.[f.key] || 0,
    pppk: pppkStats.value?.[f.key] || 0,
    total: (pnsStats.value?.[f.key] || 0) + (pppkStats.value?.[f.key] || 0),
  }))
})

const combinedDeductionBreakdown = computed(() => {
  if (!pnsStats.value && !pppkStats.value) return []
  return potonganFields.map(f => ({
    label: f.label,
    pns: pnsStats.value?.[f.key] || 0,
    pppk: pppkStats.value?.[f.key] || 0,
    total: (pnsStats.value?.[f.key] || 0) + (pppkStats.value?.[f.key] || 0),
  }))
})

// Upload State
const uploadDialog = ref(false)
const uploading = ref(false)
const uploadType = ref('pns') // 'pns' or 'pppk'
const jenisGaji = ref('Induk')
const file = ref(null)
const uploadError = ref('')
const uploadSuccess = ref('')

const fetchData = async () => {
  loading.value = true
  try {
    if (employeeType.value === 'combined') {
      // Fetch both PNS and PPPK data
      const [pnsResponse, pppkResponse] = await Promise.all([
        api.get('/pns/dashboard', {
          params: { month: selectedMonth.value, year: selectedYear.value }
        }),
        api.get('/pppk/dashboard', {
          params: { month: selectedMonth.value, year: selectedYear.value }
        })
      ])
      
      if (pnsResponse.data.success) {
        pnsStats.value = pnsResponse.data.data.summary
      }
      if (pppkResponse.data.success) {
        pppkStats.value = pppkResponse.data.data.summary
      }
    } else {
      // Fetch single type data
      const endpoint = employeeType.value === 'pns' ? '/pns/dashboard' : '/pppk/dashboard'
      const response = await api.get(endpoint, {
        params: { month: selectedMonth.value, year: selectedYear.value }
      })
      if (response.data.success) {
        stats.value = response.data.data.summary
        topEarners.value = response.data.data.top_earners
        skpdStats.value = response.data.data.skpd_breakdown
        golonganStats.value = response.data.data.golongan_breakdown || []
      }
    }
  } catch (err) {
    console.error(err)
  } finally {
    loading.value = false
  }
}

const fetchYearlyTrend = async () => {
  try {
    const endpoint = employeeType.value === 'pns' ? '/pns/trend' : '/pppk/trend'
    const response = await api.get(endpoint, {
      params: { year: selectedYear.value }
    })
    if (response.data.success) {
      yearlyTrend.value = response.data.data.trend
      await nextTick()
      renderChart()
    }
  } catch (err) {
    console.error('Failed to fetch yearly trend:', err)
  }
}

const renderChart = () => {
  if (!trendChart.value || !yearlyTrend.value.length) return
  
  // Destroy existing chart
  if (chartInstance) {
    chartInstance.destroy()
  }
  
  const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']
  const labels = monthNames
  const employeeData = new Array(12).fill(0)
  const grossData = new Array(12).fill(0)
  const netData = new Array(12).fill(0)
  const tppData = new Array(12).fill(0)
  
  yearlyTrend.value.forEach(item => {
    const index = item.bulan - 1
    employeeData[index] = item.total_employees
    grossData[index] = item.total_gross / 1000000 // Convert to millions
    netData[index] = item.total_net / 1000000
    tppData[index] = (item.total_tpp || 0) / 1000000
  })
  
  const ctx = trendChart.value.getContext('2d')
  chartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          label: 'Jumlah Pegawai',
          data: employeeData,
          borderColor: 'rgb(75, 192, 192)',
          backgroundColor: 'rgba(75, 192, 192, 0.1)',
          yAxisID: 'y',
          tension: 0.4
        },
        {
          label: 'Gaji Kotor (Juta)',
          data: grossData,
          borderColor: 'rgb(255, 99, 132)',
          backgroundColor: 'rgba(255, 99, 132, 0.1)',
          yAxisID: 'y1',
          tension: 0.4
        },

        {
          label: 'Total TPP (Juta)',
          data: tppData,
          borderColor: 'rgb(54, 162, 235)',
          backgroundColor: 'rgba(54, 162, 235, 0.1)',
          yAxisID: 'y1',
          tension: 0.4
        },
        {
          label: 'Gaji Bersih (Juta)',
          data: netData,
          borderColor: 'rgb(153, 102, 255)',
          backgroundColor: 'rgba(153, 102, 255, 0.1)',
          yAxisID: 'y1',
          tension: 0.4
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        mode: 'index',
        intersect: false,
      },
      plugins: {
        legend: {
          position: 'top',
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              let label = context.dataset.label || ''
              if (label) {
                label += ': '
              }
              if (context.parsed.y !== null) {
                if (context.datasetIndex === 0) {
                  label += context.parsed.y.toLocaleString()
                } else {
                  label += 'Rp ' + context.parsed.y.toFixed(2) + ' Juta'
                }
              }
              return label
            }
          }
        }
      },
      scales: {
        y: {
          type: 'linear',
          display: true,
          position: 'left',
          title: {
            display: true,
            text: 'Jumlah Pegawai'
          }
        },
        y1: {
          type: 'linear',
          display: true,
          position: 'right',
          title: {
            display: true,
            text: 'Gaji (Juta Rupiah)'
          },
          grid: {
            drawOnChartArea: false,
          },
        },
      }
    }
  })
}

const uploadData = async () => {
  if (!file.value) return
  
  uploading.value = true
  uploadError.value = ''
  uploadSuccess.value = ''
  
  const formData = new FormData()
  const fileToUpload = Array.isArray(file.value) ? file.value[0] : file.value
  
  if (!fileToUpload) {
     uploadError.value = "Silakan pilih file terlebih dahulu"
     uploading.value = false
     return
  }
  
  formData.append('file', fileToUpload)
  formData.append('month', selectedMonth.value)
  formData.append('year', selectedYear.value)
  formData.append('jenis_gaji', jenisGaji.value)
  
  try {
    const endpoint = uploadType.value === 'pns' ? '/pns/upload' : '/pppk/upload'
    const response = await api.post(endpoint, formData, {
      headers: { 
        'Content-Type': 'multipart/form-data'
      }
    })
    uploadSuccess.value = `Berhasil! ${response.data.meta.total_records} data diimport.`
    file.value = null
    setTimeout(() => {
      uploadDialog.value = false
      fetchData() // Refresh data
    }, 2000)
  } catch (err) {
    uploadError.value = err.response?.data?.message || 'Upload gagal'
    if (err.response?.data?.errors) {
      uploadError.value += ': ' + Object.values(err.response.data.errors).flat().join(', ')
    }
  } finally {
    uploading.value = false
  }
}

const fetchAnnualReport = async () => {
  if (employeeType.value !== 'combined') return
  
  loadingAnnual.value = true
  try {
    const params = { year: selectedYear.value }
    if (reportViewMode.value === 'skpd' && selectedSkpd.value) {
      params.skpd = selectedSkpd.value
    }

    // Fetch both PNS and PPPK data
    const [pnsResponse, pppkResponse] = await Promise.all([
      api.get('/pns/annual-report', { params: { ...params, type: 'pns' } }),
      api.get('/pns/annual-report', { params: { ...params, type: 'pppk' } })
    ])
    
    if (pnsResponse.data.success && pppkResponse.data.success) {
      const pnsData = pnsResponse.data.data
      const pppkData = pppkResponse.data.data
      
      // Merge monthly data
      const mergedMonthly = pnsData.monthly.map((pnsMonth, index) => {
        const pppkMonth = pppkData.monthly[index]
        const merged = { month: pnsMonth.month, month_name: pnsMonth.month_name }
          const numKeys = Object.keys(pnsMonth).filter(k => k !== 'month' && k !== 'month_name')
          numKeys.forEach(k => { merged[k] = (pnsMonth[k] || 0) + (pppkMonth[k] || 0) })
          return merged
      })
      
      // Merge yearly totals
      const mergedYearlyTotal = {}
      const yKeys = Object.keys(pnsData.yearly_total)
      yKeys.forEach(k => { mergedYearlyTotal[k] = (pnsData.yearly_total[k] || 0) + (pppkData.yearly_total[k] || 0) })
      
      // Calculate merged summary
      const monthsWithData = mergedMonthly.filter(m => m.total_employees > 0).length
      const avgEmployees = monthsWithData > 0 ? mergedYearlyTotal.total_employees / monthsWithData : 0
      const avgSalaryPerEmployee = mergedYearlyTotal.total_employees > 0 ? mergedYearlyTotal.total_bersih / mergedYearlyTotal.total_employees : 0
      
      annualReport.value = {
        monthly: mergedMonthly,
        yearly_total: mergedYearlyTotal,
        summary: {
          avg_employees_per_month: Math.round(avgEmployees),
          avg_salary_per_employee: Math.round(avgSalaryPerEmployee),
          months_with_data: monthsWithData
        }
      }
      pnsAnnual.value = pnsData
      pppkAnnual.value = pppkData
      
      // Populate SKPD list if empty
      if (skpdList.value.length === 0 && pnsResponse.data.data.meta.skpd_list) {
        skpdList.value = pnsResponse.data.data.meta.skpd_list.map(s => ({
          id_skpd: s,
          nama_skpd: s
        }))
      }
    }
  } catch (err) {
    console.error('Failed to fetch annual report:', err)
  } finally {
    loadingAnnual.value = false
  }
}

const getMonthName = (monthNum) => {
  const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des']
  return monthNames[monthNum - 1] || ''
}

const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val || 0)
const formatCurrencyShort = (val) => {
  if (!val) return 'Rp 0'
  // Always show full format, no abbreviations
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(val)
}

onMounted(() => {
  fetchData()
  fetchYearlyTrend()
  fetchAnnualReport()
})

watch(selectedYear, () => {
  fetchYearlyTrend()
  fetchAnnualReport()
})

watch(employeeType, () => {
  fetchYearlyTrend()
  fetchAnnualReport()
})

watch(selectedSkpd, () => {
  if (reportViewMode.value === 'skpd') {
    fetchAnnualReport()
  }
})

watch(reportViewMode, (val) => {
  if (val === 'combined') {
    selectedSkpd.value = null
    fetchAnnualReport()
  } else {
    // If switching to SKPD mode and no SKPD selected, wait for selection
    if (selectedSkpd.value) fetchAnnualReport()
  }
})
</script>

<style scoped>
.modern-dashboard {
  background-color: rgb(var(--v-theme-background)) !important;
}

.bg-light {
  background-color: rgb(var(--v-theme-background));
}

.glass-card {
  background-color: rgba(255, 255, 255, 0.9) !important;
  border: 1px solid rgba(0, 0, 0, 0.05);
}

/* Annual Report Table Styles */
.annual-report-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

.annual-report-table thead {
  background: rgba(var(--v-theme-primary), 0.1);
  position: sticky;
  top: 0;
  z-index: 10;
}

.annual-report-table th {
  padding: 12px 16px;
  font-weight: 600;
  white-space: nowrap;
  border-bottom: 2px solid rgba(var(--v-theme-primary), 0.3);
}

.annual-report-table td {
  padding: 10px 16px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  white-space: nowrap;
}

.annual-report-table .sticky-col {
  position: sticky;
  left: 0;
  background: white;
  z-index: 5;
  box-shadow: 2px 0 4px rgba(0, 0, 0, 0.05);
}

.annual-report-table thead .sticky-col {
  z-index: 15;
  background: rgba(var(--v-theme-primary), 0.1);
}

.annual-report-table .highlight-col {
  background: rgba(var(--v-theme-success), 0.05);
  font-weight: 600;
}

.annual-report-table .current-month {
  background: rgba(var(--v-theme-info), 0.08);
}

.annual-report-table .no-data {
  opacity: 0.4;
}

.annual-report-table .total-row {
  background: rgba(var(--v-theme-primary), 0.15);
  font-weight: 700;
  border-top: 3px solid rgba(var(--v-theme-primary), 0.5);
}

.annual-report-table .total-row td {
  padding: 14px 16px;
}

.annual-report-table tbody tr:hover:not(.total-row) {
  background: rgba(var(--v-theme-primary), 0.05);
}
</style>
