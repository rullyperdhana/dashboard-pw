<template>
  <v-app class="modern-dashboard">
    <Navbar @show-coming-soon="showComingSoon" />
    <Sidebar @show-coming-soon="showComingSoon" />
    
    <v-main class="bg-light">
      <v-container fluid class="pa-8">
        <!-- Header Section -->
        <div class="d-flex align-center mb-6">
          <div>
            <h1 class="text-h4 font-weight-bold">Dashboard PPPK-PW</h1>
            <p class="text-body-2 text-medium-emphasis mt-1">Ringkasan data gaji PPPK Paruh Waktu & analitik tahunan</p>
          </div>
          <v-spacer></v-spacer>
          <v-btn variant="tonal" color="primary" rounded="pill" class="mr-3" @click="fetchAllData" :loading="loading">
            <v-icon start>mdi-refresh</v-icon> Refresh
          </v-btn>
          <v-btn variant="tonal" color="primary" rounded="pill">
            <v-icon start>mdi-calendar</v-icon> {{ currentYear }}
          </v-btn>
        </div>
        
        <v-alert v-if="error" type="error" variant="tonal" closable class="mb-6 rounded-lg">
          {{ error }}
        </v-alert>
        
        <!-- ═══════════════════════════════════════════ -->
        <!-- SECTION 1: OVERVIEW STATS                  -->
        <!-- ═══════════════════════════════════════════ -->
        <v-row v-if="!loading" class="mb-6">
          <v-col cols="12" sm="6" md="3">
            <v-card class="stat-card glass-card rounded-xl pa-4" elevation="0" to="/employees">
              <v-card-text>
                <div class="d-flex justify-space-between align-start">
                  <div>
                    <div class="text-overline text-grey-darken-1 mb-1">Total Pegawai</div>
                    <div class="text-h4 font-weight-bold">{{ stats.total_employees?.toLocaleString() || 0 }}</div>
                  </div>
                  <v-avatar color="blue-lighten-5" rounded="lg">
                    <v-icon color="blue">mdi-account-multiple</v-icon>
                  </v-avatar>
                </div>
                <div class="mt-3 text-caption text-grey">Pegawai PPPK Paruh Waktu</div>
              </v-card-text>
            </v-card>
          </v-col>
          
          <v-col cols="12" sm="6" md="3">
            <v-card class="stat-card glass-card rounded-xl pa-4" elevation="0" to="/employees">
              <v-card-text>
                <div class="d-flex justify-space-between align-start">
                  <div>
                    <div class="text-overline text-grey-darken-1 mb-1">PPPK Aktif</div>
                    <div class="text-h4 font-weight-bold">{{ stats.active_employees?.toLocaleString() || 0 }}</div>
                  </div>
                  <v-avatar color="green-lighten-5" rounded="lg">
                    <v-icon color="green">mdi-check-decagram-outline</v-icon>
                  </v-avatar>
                </div>
                <div class="mt-3 text-caption text-grey">Data status aktif</div>
              </v-card-text>
            </v-card>
          </v-col>
          
          <v-col cols="12" sm="6" md="3">
            <v-card class="stat-card glass-card rounded-xl pa-4" elevation="0" to="/payments">
              <v-card-text>
                <div class="d-flex justify-space-between align-start">
                  <div>
                    <div class="text-overline text-grey-darken-1 mb-1">Biaya Bulanan</div>
                    <div class="text-h4 font-weight-bold text-truncate">{{ formatCurrencyShort(stats.monthly_payment) }}</div>
                  </div>
                  <v-avatar color="amber-lighten-5" rounded="lg">
                    <v-icon color="amber-darken-2">mdi-cash-fast</v-icon>
                  </v-avatar>
                </div>
                <div class="mt-3 text-caption text-grey">Pengeluaran terakhir</div>
              </v-card-text>
            </v-card>
          </v-col>
          
          <v-col cols="12" sm="6" md="3">
            <v-card class="stat-card glass-card rounded-xl pa-4" elevation="0" to="/skpd">
              <v-card-text>
                <div class="d-flex justify-space-between align-start">
                  <div>
                    <div class="text-overline text-grey-darken-1 mb-1">Instansi (SKPD)</div>
                    <div class="text-h4 font-weight-bold">{{ stats.total_skpd || 0 }}</div>
                  </div>
                  <v-avatar color="purple-lighten-5" rounded="lg">
                    <v-icon color="purple">mdi-domain</v-icon>
                  </v-avatar>
                </div>
                <div class="mt-3 text-caption text-grey">Unit instansi terdaftar</div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
        
        <v-row v-else class="mb-6">
          <v-col v-for="i in 4" :key="i" cols="12" sm="6" md="3">
            <v-skeleton-loader type="article" class="rounded-xl"></v-skeleton-loader>
          </v-col>
        </v-row>
        
        <!-- ═══════════════════════════════════════════ -->
        <!-- SECTION 2: ANALYTICS SUMMARY CARDS         -->
        <!-- ═══════════════════════════════════════════ -->
        <v-row class="mb-6" v-if="reportData">
          <v-col cols="12" md="4">
            <v-card class="glass-card rounded-xl pa-4 stat-card-premium blue-glow" elevation="0">
              <v-card-text>
                <div class="d-flex align-center mb-4">
                  <v-avatar color="blue-lighten-5" rounded="lg" size="48">
                    <v-icon color="blue">mdi-currency-usd</v-icon>
                  </v-avatar>
                  <v-spacer></v-spacer>
                  <v-chip color="blue" size="x-small" variant="flat">ANGGARAN TAHUNAN</v-chip>
                </div>
                <div class="text-h4 font-weight-black mb-1">{{ formatCurrencyCompact(reportData.summary.annual_budget) }}</div>
                <div class="text-caption text-grey">Total dicairkan tahun {{ currentYear }}</div>
              </v-card-text>
            </v-card>
          </v-col>

          <v-col cols="12" md="4">
            <v-card class="glass-card rounded-xl pa-4 stat-card-premium purple-glow" elevation="0">
              <v-card-text>
                <div class="d-flex align-center mb-4">
                  <v-avatar color="purple-lighten-5" rounded="lg" size="48">
                    <v-icon color="purple">mdi-account-cash-outline</v-icon>
                  </v-avatar>
                  <v-spacer></v-spacer>
                  <v-chip color="purple" size="x-small" variant="flat">RATA-RATA/ORANG</v-chip>
                </div>
                <div class="text-h4 font-weight-black mb-1">{{ formatCurrency(reportData.summary.avg_per_employee) }}</div>
                <div class="text-caption text-grey">Rata-rata biaya gaji per pegawai</div>
              </v-card-text>
            </v-card>
          </v-col>

          <v-col cols="12" md="4">
            <v-card class="glass-card rounded-xl pa-4 stat-card-premium teal-glow" elevation="0">
              <v-card-text>
                <div class="d-flex align-center mb-4">
                  <v-avatar color="teal-lighten-5" rounded="lg" size="48">
                    <v-icon color="teal">mdi-office-building-marker-outline</v-icon>
                  </v-avatar>
                  <v-spacer></v-spacer>
                  <v-chip color="teal" size="x-small" variant="flat">UNIT AKTIF</v-chip>
                </div>
                <div class="text-h4 font-weight-black mb-1">{{ reportData.summary.active_units }}</div>
                <div class="text-caption text-grey">Instansi dengan data gaji aktif</div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
        
        <!-- ═══════════════════════════════════════════ -->
        <!-- SECTION 3: CHARTS + DISTRIBUTION           -->
        <!-- ═══════════════════════════════════════════ -->
        <v-row class="mb-6">
          <!-- Payroll Growth Trend (ApexChart) -->
          <v-col cols="12" md="8">
            <v-card class="glass-card rounded-xl pa-6 shadow-premium" elevation="0">
              <div class="d-flex align-center mb-6">
                <h2 class="text-h6 font-weight-bold">Tren Pertumbuhan Gaji</h2>
                <v-spacer></v-spacer>
                <v-chip color="success" size="small" variant="tonal" prepend-icon="mdi-trending-up">Live</v-chip>
              </div>
              <apexchart v-if="reportData" type="area" height="350" :options="trendChartOptions" :series="trendSeries"></apexchart>
              <div v-else class="text-center py-12">
                <v-progress-circular indeterminate color="primary" v-if="loadingReport"></v-progress-circular>
                <div v-else class="text-grey">Belum ada data tren</div>
              </div>
            </v-card>
          </v-col>
          
          <!-- Budget Distribution (Top 5) -->
          <v-col cols="12" md="4">
            <v-card class="glass-card rounded-xl pa-6 shadow-premium h-100" elevation="0">
              <div class="d-flex align-center mb-6">
                <h2 class="text-h6 font-weight-bold">Distribusi Anggaran</h2>
                <v-spacer></v-spacer>
                <v-chip color="primary" size="x-small" variant="flat">TOP 5</v-chip>
              </div>
              <v-table v-if="reportData" density="comfortable" class="modern-report-table">
                <thead>
                  <tr>
                    <th class="text-left py-2 font-weight-bold">INSTANSI</th>
                    <th class="text-right py-2 font-weight-bold">ANGGARAN</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in reportData.performance.slice(0, 5)" :key="item.kode_skpd">
                    <td class="text-caption font-weight-bold text-truncate" style="max-width: 150px;">{{ item.nama_skpd }}</td>
                    <td class="text-right text-body-2 font-weight-black text-primary">
                      {{ formatCurrencyCompact(item.total_budget) }}
                    </td>
                  </tr>
                </tbody>
              </v-table>
              <div v-else class="text-center py-12">
                <v-icon color="grey-lighten-2" size="48">mdi-chart-bar</v-icon>
              </div>
            </v-card>
          </v-col>
        </v-row>
        
        <!-- Staffing by Unit + Gender -->
        <v-row class="mb-6">
          <v-col cols="12" md="8">
            <v-card class="glass-card rounded-xl overflow-hidden" elevation="0">
              <v-card-title class="px-6 pt-6 d-flex align-center">
                <span class="text-h6 font-weight-bold">Pegawai per Instansi</span>
                <v-spacer></v-spacer>
                <v-btn variant="tonal" size="small" color="primary" rounded="pill" to="/skpd">Lihat Semua</v-btn>
              </v-card-title>
              <v-card-text class="px-6 pb-6">
                <div v-if="charts.employees_per_skpd && charts.employees_per_skpd.length">
                  <div class="mt-4">
                    <div v-for="(item, index) in charts.employees_per_skpd" :key="index" class="mb-4">
                      <div class="d-flex justify-space-between mb-1">
                        <span class="text-body-2 font-weight-medium">{{ item.nama_skpd }}</span>
                        <span class="text-body-2 text-grey-darken-1">{{ item.total }} pegawai</span>
                      </div>
                      <v-progress-linear
                        :model-value="(item.total / stats.total_employees) * 100"
                        rounded height="10" class="custom-progress"
                        :color="getProgressColor(index)"
                      ></v-progress-linear>
                    </div>
                  </div>
                </div>
                <div v-else class="text-center py-12">
                  <v-icon color="grey-lighten-2" size="64" class="mb-2">mdi-chart-bar</v-icon>
                  <div class="text-grey">Belum ada data distribusi</div>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
          
          <v-col cols="12" md="4">
            <v-card class="glass-card rounded-xl h-100" elevation="0">
              <v-card-title class="px-6 pt-6 font-weight-bold text-h6">Rasio Gender</v-card-title>
              <v-card-text class="pa-6">
                <div v-if="distribution.gender && distribution.gender.length" class="text-center">
                  <div class="d-flex justify-center mb-6 pt-4">
                    <template v-for="(g, idx) in distribution.gender" :key="idx">
                      <div class="gender-block mx-4">
                        <v-icon :color="g.jk?.includes('LAKI') ? 'blue' : 'pink'" size="48">
                          {{ g.jk?.includes('LAKI') ? 'mdi-gender-male' : 'mdi-gender-female' }}
                        </v-icon>
                        <div class="text-h5 font-weight-bold mt-2">{{ g.total }}</div>
                        <div class="text-caption text-grey">{{ g.jk }}</div>
                      </div>
                    </template>
                  </div>
                  <v-divider class="mb-6"></v-divider>
                  <div class="text-body-2 text-grey px-4">
                    Monitoring kesetaraan gender untuk pengelolaan sumber daya manusia yang lebih baik.
                  </div>
                </div>
                <div v-else class="text-center py-12">
                  <v-icon color="grey-lighten-2" size="64">mdi-account-heart-outline</v-icon>
                </div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
        
        <!-- ═══════════════════════════════════════════ -->
        <!-- SECTION 4: PAYROLL EXPENDITURE TREND       -->
        <!-- ═══════════════════════════════════════════ -->
        <v-row class="mb-6">
          <v-col cols="12">
            <v-card class="glass-card rounded-xl pa-6 shadow-premium" elevation="0">
              <div class="d-flex align-center mb-4">
                <v-icon class="mr-2" color="primary">mdi-chart-multiline</v-icon>
                <h2 class="text-h6 font-weight-bold">Tren Pengeluaran Gaji Bulanan</h2>
                <v-spacer></v-spacer>
                <v-chip color="info" size="small" variant="tonal">{{ currentYear }}</v-chip>
              </div>
              <v-row v-if="reportData?.trend?.length">
                <v-col v-for="(item, index) in reportData.trend" :key="index" cols="6" sm="4" md="2">
                  <div class="text-center pa-3 trend-item rounded-xl">
                    <div class="text-caption text-grey mb-1">{{ item.month }}</div>
                    <div class="text-body-2 font-weight-bold text-primary">{{ formatCurrency(item.total) }}</div>
                    <div class="text-caption text-success" v-if="item.employees">{{ item.employees }} pegawai</div>
                  </div>
                </v-col>
              </v-row>
              <v-row v-else-if="charts.payment_trend && charts.payment_trend.length">
                <v-col v-for="(item, index) in charts.payment_trend" :key="index" cols="6" sm="4" md="2">
                  <div class="text-center pa-2 trend-item rounded-lg">
                    <div class="text-caption text-grey mb-1">{{ item.month }}</div>
                    <div class="text-body-1 font-weight-bold">{{ formatCurrencyShort(item.total) }}</div>
                  </div>
                </v-col>
              </v-row>
              <div v-else class="text-center py-8 text-grey">
                <v-icon size="48" color="grey-lighten-2" class="mb-2">mdi-chart-line-variant</v-icon>
                <div>Belum ada data historis pengeluaran gaji</div>
              </div>
            </v-card>
          </v-col>
        </v-row>
        
        <!-- ═══════════════════════════════════════════ -->
        <!-- SECTION 5: MISSING PAYROLLS                -->
        <!-- ═══════════════════════════════════════════ -->
        <v-row class="mb-6">
          <v-col cols="12">
            <v-card class="glass-card rounded-xl overflow-hidden shadow-premium" elevation="0">
              <v-toolbar color="error-lighten-5" flat class="px-6 py-4">
                <v-toolbar-title class="font-weight-bold text-h6 text-error">
                  <v-icon start color="error" size="28">mdi-alert-circle-outline</v-icon>
                  Gaji Belum Masuk
                </v-toolbar-title>
                <v-spacer></v-spacer>
                <v-btn color="success" variant="tonal" size="small" class="mr-2" prepend-icon="mdi-microsoft-excel" @click="exportUnpaid('excel')" :loading="exportLoading === 'excel'">EXCEL</v-btn>
                <v-btn color="error" variant="tonal" size="small" class="mr-2" prepend-icon="mdi-file-pdf-box" @click="exportUnpaid('pdf')" :loading="exportLoading === 'pdf'">PDF</v-btn>
                <v-menu v-model="unpaidMenu" :close-on-content-click="false">
                  <template v-slot:activator="{ props }">
                    <v-btn color="error" variant="text" size="small" v-bind="props" prepend-icon="mdi-calendar">
                      {{ unpaidMonthName }} {{ unpaidYear }}
                    </v-btn>
                  </template>
                  <v-card min-width="300" class="pa-4 rounded-xl">
                    <v-row dense>
                      <v-col cols="6">
                        <v-select v-model="unpaidMonth" :items="monthList" item-title="title" item-value="value" label="Bulan" density="compact" variant="outlined" hide-details></v-select>
                      </v-col>
                      <v-col cols="6">
                        <v-select v-model="unpaidYear" :items="yearList" label="Tahun" density="compact" variant="outlined" hide-details></v-select>
                      </v-col>
                      <v-col cols="12" class="mt-2 text-right">
                        <v-btn block color="primary" @click="fetchUnpaidData(); unpaidMenu = false">TERAPKAN FILTER</v-btn>
                      </v-col>
                    </v-row>
                  </v-card>
                </v-menu>
              </v-toolbar>
              
              <div class="px-4 pb-2">
                <v-btn-toggle v-model="viewBy" mandatory density="compact" color="primary" variant="outlined" class="d-flex w-100 rounded-lg">
                  <v-btn value="skpd" class="flex-grow-1" size="small">PER SKPD</v-btn>
                  <v-btn value="upt" class="flex-grow-1" size="small">PER UPT</v-btn>
                  <v-btn value="employees" class="flex-grow-1" size="small">PER PEGAWAI</v-btn>
                </v-btn-toggle>
              </div>
              
              <div v-if="unpaidLoading" class="pa-8 text-center">
                <v-progress-circular indeterminate color="primary"></v-progress-circular>
              </div>

              <v-list v-else-if="viewBy === 'skpd' && unpaidSkpds.length" class="bg-transparent pa-2" lines="two">
                <v-list-item v-for="skpd in unpaidSkpds" :key="skpd.id_skpd" class="mb-1 rounded-lg">
                  <template v-slot:prepend>
                    <v-avatar color="error-lighten-5" size="32">
                      <v-icon color="error" size="18">mdi-office-building-remove</v-icon>
                    </v-avatar>
                  </template>
                  <v-list-item-title class="text-caption font-weight-bold text-wrap">{{ skpd.nama_skpd }}</v-list-item-title>
                  <v-list-item-subtitle class="text-overline text-error">PENDING</v-list-item-subtitle>
                </v-list-item>
              </v-list>

              <v-list v-else-if="viewBy === 'upt' && unpaidUpts.length" class="bg-transparent pa-2" lines="two">
                <v-list-item v-for="(upt, idx) in unpaidUpts" :key="idx" class="mb-1 rounded-lg">
                  <template v-slot:prepend>
                    <v-avatar color="orange-lighten-5" size="32">
                      <v-icon color="orange" size="18">mdi-domain-off</v-icon>
                    </v-avatar>
                  </template>
                  <v-list-item-title class="text-caption font-weight-bold text-wrap">{{ upt.upt }}</v-list-item-title>
                  <v-list-item-subtitle class="text-caption text-grey">{{ upt.nama_skpd }}</v-list-item-subtitle>
                </v-list-item>
              </v-list>

              <div v-else-if="viewBy === 'employees' && unpaidEmployees.length" class="pa-2" style="max-height: 500px; overflow-y: auto;">
                <v-expansion-panels variant="accordion">
                  <v-expansion-panel v-for="(skpdGroup, idx) in unpaidEmployees" :key="idx" class="mb-2">
                    <v-expansion-panel-title class="text-caption font-weight-bold">
                      <div class="d-flex align-center w-100">
                        <v-icon color="error" size="18" class="mr-2">mdi-office-building</v-icon>
                        <span class="flex-grow-1">{{ skpdGroup.skpd_name }}</span>
                        <v-chip color="error" size="x-small" variant="flat" class="mr-2">{{ skpdGroup.count }}</v-chip>
                      </div>
                    </v-expansion-panel-title>
                    <v-expansion-panel-text>
                      <v-list density="compact" class="bg-transparent">
                        <v-list-item v-for="emp in skpdGroup.employees" :key="emp.id" class="mb-1">
                          <template v-slot:prepend>
                            <v-avatar color="red-lighten-5" size="24">
                              <v-icon color="red" size="14">mdi-account-off</v-icon>
                            </v-avatar>
                          </template>
                          <v-list-item-title class="text-caption font-weight-medium">{{ emp.nama }}</v-list-item-title>
                          <v-list-item-subtitle class="text-caption">
                            <div>NIP: {{ emp.nip }}</div>
                            <div v-if="emp.jabatan">{{ emp.jabatan }}</div>
                            <div v-if="emp.upt" class="text-grey">UPT: {{ emp.upt }}</div>
                          </v-list-item-subtitle>
                        </v-list-item>
                      </v-list>
                    </v-expansion-panel-text>
                  </v-expansion-panel>
                </v-expansion-panels>
              </div>

              <div v-else class="pa-12 text-center text-grey">
                <v-icon size="48" color="success-lighten-4">mdi-check-all</v-icon>
                <div class="mt-2 text-caption">
                  <span v-if="viewBy === 'employees'">Semua pegawai telah memiliki data gaji untuk {{ unpaidMonthName }} {{ unpaidYear }}!</span>
                  <span v-else>Semua {{ viewBy.toUpperCase() }} telah memproses gaji untuk {{ unpaidMonthName }} {{ unpaidYear }}!</span>
                </div>
              </div>
            </v-card>
          </v-col>
        </v-row>
        
        <!-- ═══════════════════════════════════════════ -->
        <!-- SECTION 6: DAFTAR GAJI (PAID)              -->
        <!-- ═══════════════════════════════════════════ -->
        <v-row class="mb-6">
          <v-col cols="12">
            <v-card class="glass-card rounded-xl overflow-hidden shadow-premium" elevation="0">
              <v-toolbar color="success-lighten-5" flat class="px-6 py-4">
                <v-toolbar-title class="font-weight-bold text-h6 text-success">
                  <v-icon start color="success" size="28">mdi-check-circle-outline</v-icon>
                  Daftar Gaji
                </v-toolbar-title>
                <v-spacer></v-spacer>
                <v-btn-toggle v-model="paidViewBy" mandatory density="compact" class="mr-4">
                  <v-btn value="skpd" size="small">PER SKPD</v-btn>
                  <v-btn value="employees" size="small">PER PEGAWAI</v-btn>
                </v-btn-toggle>
                <v-btn color="success" variant="tonal" size="small" class="mr-2" prepend-icon="mdi-microsoft-excel" @click="exportPaid('excel')" :loading="paidExportLoading === 'excel'">EXCEL</v-btn>
                <v-btn color="error" variant="tonal" size="small" class="mr-2" prepend-icon="mdi-file-pdf-box" @click="exportPaid('pdf')" :loading="paidExportLoading === 'pdf'">PDF</v-btn>
                <v-menu v-model="paidMenu" :close-on-content-click="false">
                  <template v-slot:activator="{ props }">
                    <v-btn color="success" variant="text" size="small" v-bind="props" prepend-icon="mdi-calendar">
                      {{ paidMonthName }} {{ paidYear }}
                    </v-btn>
                  </template>
                  <v-card min-width="300" class="pa-4 rounded-xl">
                    <v-row dense>
                      <v-col cols="6">
                        <v-select v-model="paidMonth" :items="monthList" item-title="title" item-value="value" label="Bulan" density="compact" variant="outlined" hide-details></v-select>
                      </v-col>
                      <v-col cols="6">
                        <v-select v-model="paidYear" :items="yearList" label="Tahun" density="compact" variant="outlined" hide-details></v-select>
                      </v-col>
                      <v-col cols="12" class="mt-2 text-right">
                        <v-btn block color="primary" @click="fetchPaidData(); paidMenu = false">TERAPKAN FILTER</v-btn>
                      </v-col>
                    </v-row>
                  </v-card>
                </v-menu>
              </v-toolbar>
              
              <div v-if="paidLoading" class="pa-8 text-center">
                <v-progress-circular indeterminate color="success"></v-progress-circular>
              </div>

              <v-data-table
                v-else-if="paidViewBy === 'skpd' && paidSkpds.length"
                :headers="paidHeaders"
                :items="paidSkpds"
                class="modern-report-table"
                hover
                :items-per-page="10"
              >
                <template v-slot:item.nama_skpd="{ item }">
                  <router-link :to="`/employees?skpd_id=${item.id_skpd}`" class="text-decoration-none">
                    <span class="font-weight-bold text-success text-decoration-underline-hover">{{ item.nama_skpd }}</span>
                    <v-icon size="x-small" color="success" class="ml-1">mdi-open-in-new</v-icon>
                  </router-link>
                </template>
                <template v-slot:item.employee_count="{ item }">
                  <v-chip size="x-small" color="success" variant="tonal" class="rounded-lg">{{ item.employee_count }}</v-chip>
                </template>
                <template v-slot:item.total_gaji_pokok="{ item }">
                  <span class="font-weight-medium">{{ formatCurrency(item.total_gaji_pokok) }}</span>
                </template>
                <template v-slot:item.total_bersih="{ item }">
                  <router-link :to="`/payments?month=${paidMonth}&year=${paidYear}&skpd_id=${item.id_skpd}`" class="text-decoration-none">
                    <span class="font-weight-black text-success">{{ formatCurrency(item.total_bersih) }}</span>
                    <v-icon size="x-small" color="success" class="ml-1">mdi-receipt-text-outline</v-icon>
                  </router-link>
                </template>
              </v-data-table>

              <v-data-table
                v-else-if="paidViewBy === 'employees' && paidEmployees.length"
                :headers="paidEmployeesHeaders"
                :items="paidEmployees"
                class="modern-report-table"
                hover
                :items-per-page="15"
              >
                <template v-slot:item.nama="{ item }">
                  <router-link :to="`/employees?skpd_id=${item.id_skpd}`" class="text-decoration-none">
                    <div class="font-weight-bold text-primary">{{ item.nama }}</div>
                    <div class="text-caption text-grey">{{ item.nip }}</div>
                  </router-link>
                </template>
                <template v-slot:item.jabatan="{ item }">
                  <div class="text-body-2">{{ item.jabatan }}</div>
                  <div class="text-caption text-primary">{{ item.nama_skpd }}</div>
                </template>
                <template v-slot:item.gaji_pokok="{ item }">
                  <span class="font-weight-medium">{{ formatCurrency(item.gaji_pokok) }}</span>
                </template>
                <template v-slot:item.total_bersih="{ item }">
                  <router-link :to="`/payments?month=${paidMonth}&year=${paidYear}&skpd_id=${item.id_skpd}`" class="text-decoration-none">
                    <span class="font-weight-black text-success">{{ formatCurrency(item.total_bersih) }}</span>
                    <v-icon size="x-small" color="success" class="ml-1">mdi-receipt-text-outline</v-icon>
                  </router-link>
                </template>
              </v-data-table>

              <div v-else class="pa-12 text-center text-grey">
                <v-icon size="48" color="grey-lighten-2">mdi-file-document-remove-outline</v-icon>
                <div class="mt-2 text-caption">Belum ada data daftar gaji untuk {{ paidMonthName }} {{ paidYear }}</div>
              </div>
            </v-card>
          </v-col>
        </v-row>
        
        <!-- ═══════════════════════════════════════════ -->
        <!-- SECTION 7: TOP EARNERS                     -->
        <!-- ═══════════════════════════════════════════ -->
        <v-row class="mb-6" v-if="reportData">
          <v-col cols="12">
            <v-card class="glass-card rounded-xl overflow-hidden shadow-premium" elevation="0">
              <v-toolbar color="transparent" flat class="px-6 py-4">
                <v-toolbar-title class="font-weight-bold text-subtitle-1">Peringkat Gaji Tertinggi</v-toolbar-title>
                <v-spacer></v-spacer>
                <v-chip color="amber-darken-2" variant="flat" size="small" prepend-icon="mdi-medal-outline">TOP 10</v-chip>
              </v-toolbar>
              <v-data-table
                :headers="topEarnersHeaders"
                :items="reportData.top_earners"
                class="modern-report-table"
                hover
                :items-per-page="10"
              >
                <template v-slot:item.rank="{ index }">
                  <v-avatar v-if="index < 3" :color="index === 0 ? 'amber' : index === 1 ? 'grey-lighten-2' : 'brown-lighten-2'" size="28" class="font-weight-black text-caption">
                    {{ index + 1 }}
                  </v-avatar>
                  <span v-else class="text-grey font-weight-bold">{{ index + 1 }}</span>
                </template>
                <template v-slot:item.nama="{ item }">
                  <div class="font-weight-bold">{{ item.nama }}</div>
                  <div class="text-caption text-grey">{{ item.nip }}</div>
                </template>
                <template v-slot:item.jabatan_info="{ item }">
                  <div class="text-body-2">{{ item.jabatan }}</div>
                  <div class="text-caption text-primary">{{ item.nama_skpd }}</div>
                </template>
                <template v-slot:item.total_amoun="{ item }">
                  <span class="font-weight-black text-primary">{{ formatCurrency(item.total_amoun) }}</span>
                </template>
              </v-data-table>
            </v-card>
          </v-col>
        </v-row>
        
        <!-- ═══════════════════════════════════════════ -->
        <!-- SECTION 8: RETIREMENT MONITOR              -->
        <!-- ═══════════════════════════════════════════ -->
        <v-row v-if="reportData">
          <v-col cols="12">
            <v-card class="glass-card rounded-xl overflow-hidden shadow-premium" elevation="0">
              <v-toolbar color="transparent" flat class="px-6 py-4">
                <v-toolbar-title class="font-weight-bold text-subtitle-1">Monitoring Pensiun (Batas: 58 Tahun)</v-toolbar-title>
                <v-spacer></v-spacer>
                <v-chip color="error" variant="flat" size="small" prepend-icon="mdi-clock-alert-outline">PERENCANAAN</v-chip>
              </v-toolbar>
              <v-data-table
                :headers="retirementHeaders"
                :items="reportData.retirement_monitor"
                class="modern-report-table"
                hover
                :items-per-page="12"
              >
                <template v-slot:item.nama="{ item }">
                  <div class="font-weight-bold">{{ item.nama }}</div>
                  <div class="text-caption text-grey">{{ item.jabatan }}</div>
                  <div class="text-caption text-primary">{{ item.nama_skpd }}</div>
                </template>
                <template v-slot:item.age="{ item }">
                  <v-chip :color="item.age >= 58 ? 'red' : 'orange'" variant="tonal" size="small" class="font-weight-bold">
                    {{ item.age }} Tahun
                  </v-chip>
                </template>
                <template v-slot:item.retirement_date="{ item }">
                  <span class="font-weight-medium">{{ formatDate(item.retirement_date) }}</span>
                </template>
                <template v-slot:item.status="{ item }">
                  <v-btn v-if="item.age >= 58" color="error" size="x-small" variant="flat" class="font-weight-bold">PROSES</v-btn>
                  <v-chip v-else-if="item.is_critical" color="warning" size="x-small" variant="flat">MENDEKATI</v-chip>
                  <v-chip v-else color="info" size="x-small" variant="tonal">MONITORING</v-chip>
                </template>
                <template v-slot:no-data>
                  <div class="text-center py-8 text-grey">Tidak ada pegawai yang mendekati usia pensiun (55+).</div>
                </template>
              </v-data-table>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>

    <!-- Global Feedback Snackbar -->
    <v-snackbar v-model="snackbar" :timeout="3000" color="primary" rounded="lg" elevation="24">
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
  </v-app>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useTheme } from 'vuetify'
import api from '../api'
import Sidebar from '../components/Sidebar.vue'
import Navbar from '../components/Navbar.vue'

const theme = useTheme()
const router = useRouter()
const user = ref(null)
const loading = ref(true)
const loadingReport = ref(true)
const error = ref('')
const snackbar = ref(false)
const snackbarTitle = ref('')
const currentYear = new Date().getFullYear()

const showComingSoon = (feature) => {
  snackbarTitle.value = feature
  snackbar.value = true
}

// ═══════════════════════════════════════════
// DASHBOARD OVERVIEW DATA
// ═══════════════════════════════════════════
const stats = ref({
  total_employees: 0,
  active_employees: 0,
  monthly_payment: 0,
  total_skpd: 0,
})
const distribution = ref({ gender: [] })
const charts = ref({
  employees_per_skpd: [],
  payment_trend: [],
})

const fetchDashboardData = async () => {
  try {
    const response = await api.get('/dashboard')
    if (response.data.success) {
      stats.value = response.data.data.summary
      distribution.value = response.data.data.distribution || { gender: [] }
      charts.value = response.data.data.charts
    }
  } catch (err) {
    error.value = 'Gagal memuat data dashboard: ' + (err.response?.data?.message || err.message)
  } finally {
    loading.value = false
  }
}

// ═══════════════════════════════════════════
// REPORT / ANALYTICS DATA
// ═══════════════════════════════════════════
const reportData = ref(null)

const fetchReportData = async () => {
  loadingReport.value = true
  try {
    const response = await api.get('/reports')
    reportData.value = response.data.data
  } catch (err) {
    console.error('Error fetching reports:', err)
  } finally {
    loadingReport.value = false
  }
}

// ═══════════════════════════════════════════
// UNPAID (MISSING) PAYROLLS
// ═══════════════════════════════════════════
const unpaidLoading = ref(true)
const unpaidSkpds = ref([])
const unpaidUpts = ref([])
const unpaidEmployees = ref([])
const exportLoading = ref(null)
const unpaidMenu = ref(false)
const viewBy = ref('skpd')
const unpaidMonth = ref(new Date().getMonth() + 1)
const unpaidYear = ref(new Date().getFullYear())

const monthList = [
  { title: 'Januari', value: 1 }, { title: 'Februari', value: 2 }, { title: 'Maret', value: 3 },
  { title: 'April', value: 4 }, { title: 'Mei', value: 5 }, { title: 'Juni', value: 6 },
  { title: 'Juli', value: 7 }, { title: 'Agustus', value: 8 }, { title: 'September', value: 9 },
  { title: 'Oktober', value: 10 }, { title: 'November', value: 11 }, { title: 'Desember', value: 12 }
]
const yearList = computed(() => {
  const current = new Date().getFullYear()
  return [current, current - 1, current - 2]
})
const unpaidMonthName = computed(() => monthList.find(m => m.value === unpaidMonth.value)?.title)

const fetchUnpaidData = async () => {
  unpaidLoading.value = true
  try {
    const params = { month: unpaidMonth.value, year: unpaidYear.value }
    const [skpdsRes, uptsRes, employeesRes] = await Promise.all([
      api.get('/reports/unpaid-skpds', { params }),
      api.get('/reports/unpaid-upts', { params }),
      api.get('/reports/unpaid-employees', { params })
    ])
    if (skpdsRes.data.success) unpaidSkpds.value = skpdsRes.data.data
    if (uptsRes.data.success) unpaidUpts.value = uptsRes.data.data
    if (employeesRes.data.success) unpaidEmployees.value = employeesRes.data.data
  } catch (err) {
    console.error('Failed to fetch unpaid data:', err)
  } finally {
    unpaidLoading.value = false
  }
}

watch(viewBy, () => { fetchUnpaidData() })

const exportUnpaid = async (format) => {
  exportLoading.value = format
  try {
    const params = { month: unpaidMonth.value, year: unpaidYear.value, view_by: viewBy.value, format }
    const response = await api.get('/reports/unpaid-export', { params, responseType: 'blob' })
    downloadBlob(response.data, format, `missing_payrolls_${viewBy.value}_${unpaidMonth.value}_${unpaidYear.value}`)
  } catch (err) {
    console.error('Export failed:', err)
  } finally {
    exportLoading.value = null
  }
}

// ═══════════════════════════════════════════
// PAID PAYROLLS
// ═══════════════════════════════════════════
const paidLoading = ref(true)
const paidSkpds = ref([])
const paidEmployees = ref([])
const paidExportLoading = ref(null)
const paidMenu = ref(false)
const paidMonth = ref(new Date().getMonth() + 1)
const paidYear = ref(new Date().getFullYear())
const paidViewBy = ref('skpd')
const paidMonthName = computed(() => monthList.find(m => m.value === paidMonth.value)?.title)

const paidHeaders = [
  { title: 'SKPD', key: 'nama_skpd', sortable: true },
  { title: 'KODE', key: 'kode_skpd', sortable: true, width: '100px' },
  { title: 'PEGAWAI', key: 'employee_count', sortable: true, align: 'center', width: '100px' },
  { title: 'GAJI POKOK', key: 'total_gaji_pokok', sortable: true, align: 'end' },
  { title: 'TOTAL BERSIH', key: 'total_bersih', sortable: true, align: 'end' },
]

const paidEmployeesHeaders = [
  { title: 'PEGAWAI', key: 'nama', sortable: true },
  { title: 'JABATAN / SKPD', key: 'jabatan', sortable: true },
  { title: 'GAJI POKOK', key: 'gaji_pokok', sortable: true, align: 'end' },
  { title: 'PAJAK', key: 'pajak', sortable: true, align: 'end' },
  { title: 'IWP', key: 'iwp', sortable: true, align: 'end' },
  { title: 'TUNJANGAN', key: 'tunjangan', sortable: true, align: 'end' },
  { title: 'TOTAL BERSIH', key: 'total_bersih', sortable: true, align: 'end' },
]

const fetchPaidData = async () => {
  paidLoading.value = true
  try {
    const params = { month: paidMonth.value, year: paidYear.value }
    const [skpdsRes, employeesRes] = await Promise.all([
      api.get('/reports/paid-skpds', { params }),
      api.get('/reports/paid-employees', { params })
    ])
    if (skpdsRes.data.success) paidSkpds.value = skpdsRes.data.data
    if (employeesRes.data.success) paidEmployees.value = employeesRes.data.data
  } catch (err) {
    console.error('Failed to fetch paid data:', err)
  } finally {
    paidLoading.value = false
  }
}

const exportPaid = async (format) => {
  paidExportLoading.value = format
  try {
    const params = { month: paidMonth.value, year: paidYear.value, format }
    const endpoint = paidViewBy.value === 'employees' ? '/reports/paid-employees-export' : '/reports/paid-export'
    const response = await api.get(endpoint, { params, responseType: 'blob' })
    downloadBlob(response.data, format, `skpd_daftar_gaji_${paidMonth.value}_${paidYear.value}`)
  } catch (err) {
    console.error('Export failed:', err)
  } finally {
    paidExportLoading.value = null
  }
}

// ═══════════════════════════════════════════
// TABLE HEADERS
// ═══════════════════════════════════════════
const topEarnersHeaders = [
  { title: 'PERINGKAT', key: 'rank', sortable: false, align: 'center', width: '80px' },
  { title: 'PEGAWAI', key: 'nama', sortable: true },
  { title: 'JABATAN / INSTANSI', key: 'jabatan_info', sortable: false },
  { title: 'PENGHASILAN', key: 'total_amoun', sortable: true, align: 'end' },
]

const retirementHeaders = [
  { title: 'PEGAWAI', key: 'nama', sortable: true },
  { title: 'USIA', key: 'age', sortable: true, align: 'center' },
  { title: 'ESTIMASI PENSIUNAN', key: 'retirement_date', sortable: true },
  { title: 'STATUS', key: 'status', sortable: false, align: 'center' },
]

// ═══════════════════════════════════════════
// CHART OPTIONS
// ═══════════════════════════════════════════
const trendSeries = computed(() => {
  if (!reportData.value) return []
  return [{ name: 'Total Anggaran', data: reportData.value.growth.map(i => i.value) }]
})

const trendChartOptions = computed(() => ({
  chart: {
    height: 350, type: 'area', toolbar: { show: false }, zoom: { enabled: false },
    foreColor: theme.global.name.value === 'dark' ? '#94a3b8' : '#64748b'
  },
  theme: { mode: theme.global.name.value },
  colors: ['#1867C0'],
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 3 },
  xaxis: {
    categories: reportData.value?.growth.map(i => i.label) || [],
    axisBorder: { show: false }, axisTicks: { show: false }
  },
  yaxis: {
    labels: { formatter: (val) => formatCurrencyCompact(val) }
  },
  fill: {
    type: 'gradient',
    gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100] }
  },
  grid: { borderColor: '#f1f1f1', strokeDashArray: 4 }
}))

// ═══════════════════════════════════════════
// UTILITIES
// ═══════════════════════════════════════════
const formatCurrency = (value) => {
  if (!value && value !== 0) return '-'
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value || 0)
}

const formatCurrencyShort = (value) => {
  if (!value) return 'Rp 0'
  if (value >= 1000000000) return 'Rp ' + (value / 1000000000).toFixed(2) + ' M'
  if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(2) + ' Jt'
  return formatCurrency(value)
}

const formatCurrencyCompact = (val) => {
  if (!val) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val)
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' })
}

const getProgressColor = (index) => {
  const colors = ['primary', 'success', 'info', 'warning', 'purple', 'teal']
  return colors[index % colors.length]
}

const downloadBlob = (data, format, filename) => {
  const blob = new Blob([data], {
    type: format === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
  })
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `${filename}.${format === 'pdf' ? 'pdf' : 'xlsx'}`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  window.URL.revokeObjectURL(url)
}

// ═══════════════════════════════════════════
// LIFECYCLE
// ═══════════════════════════════════════════
const fetchAllData = async () => {
  loading.value = true
  loadingReport.value = true
  await Promise.all([fetchDashboardData(), fetchReportData()])
  fetchUnpaidData()
  fetchPaidData()
}

onMounted(async () => {
  const userData = localStorage.getItem('user')
  if (userData) user.value = JSON.parse(userData)
  
  await Promise.all([fetchDashboardData(), fetchReportData()])
  fetchUnpaidData()
  fetchPaidData()
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
  background-color: rgb(var(--v-theme-surface)) !important;
  border: 1px solid rgba(var(--v-border-color), 0.08) !important;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.08) !important;
}

.stat-card-premium {
  position: relative;
  overflow: hidden;
  transition: transform 0.3s;
}

.stat-card-premium:hover {
  transform: translateY(-4px);
}

.blue-glow { border-top: 4px solid #1867C0; }
.purple-glow { border-top: 4px solid #9C27B0; }
.teal-glow { border-top: 4px solid #009688; }

.shadow-premium {
  box-shadow: 0 10px 30px rgba(0,0,0,0.03) !important;
}

.custom-progress {
  border-radius: 99px;
  background: rgba(var(--v-border-color), 0.1);
}

.trend-item {
  background: rgba(var(--v-border-color), 0.02);
  transition: background 0.2s;
}

.trend-item:hover {
  background: rgba(var(--v-theme-primary), 0.1);
}

.gender-block {
  transition: transform 0.2s;
}

.gender-block:hover {
  transform: scale(1.1);
}

.modern-report-table {
  background: transparent !important;
}

:deep(.v-table__wrapper) {
  background: transparent !important;
}

.modern-report-table th {
  background: rgba(var(--v-border-color), 0.05);
  color: rgb(var(--v-theme-on-surface)) !important;
  opacity: 0.7;
  text-transform: uppercase;
  font-size: 0.7rem !important;
  height: 48px !important;
}

.modern-report-table td {
  height: 60px !important;
  border-bottom: 1px solid rgba(var(--v-border-color), 0.05) !important;
}
</style>
