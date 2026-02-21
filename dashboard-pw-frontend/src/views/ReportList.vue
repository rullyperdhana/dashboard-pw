<template>
  <v-app class="modern-bg">
    <Navbar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />
    <Sidebar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />

    <v-main>
      <v-container fluid class="pa-8">
        <!-- Header Section -->
        <v-row class="mb-6 align-center">
          <v-col cols="12" md="6">
            <h1 class="text-h4 font-weight-bold">Analytics Hub</h1>
            <p class="text-grey-darken-1">Deep insights into payroll trends and institutional performance.</p>
          </v-col>
          <v-col cols="12" md="6" class="text-md-right">
             <v-btn color="primary" variant="flat" prepend-icon="mdi-export-variant" class="rounded-lg mr-2" @click="showComingSoon('Full Report Export')">EXPORT PDF</v-btn>
             <v-btn variant="tonal" color="primary" prepend-icon="mdi-calendar-month" class="rounded-lg">{{ currentYear }}</v-btn>
          </v-col>
        </v-row>

        <!-- Loading State -->
        <v-row v-if="loading">
          <v-col v-for="i in 3" :key="i" cols="12" md="4">
            <v-skeleton-loader type="card" class="rounded-xl"></v-skeleton-loader>
          </v-col>
          <v-col cols="12" md="8">
             <v-skeleton-loader type="image" height="400" class="rounded-xl"></v-skeleton-loader>
          </v-col>
          <v-col cols="12" md="4">
             <v-skeleton-loader type="image" height="400" class="rounded-xl"></v-skeleton-loader>
          </v-col>
        </v-row>

        <div v-else>
          <!-- Summary Cards -->
          <v-row class="mb-6">
            <v-col cols="12" md="4">
              <v-card class="glass-card rounded-xl pa-4 stat-card-premium blue-glow" elevation="0">
                <v-card-text>
                  <div class="d-flex align-center mb-4">
                    <v-avatar color="blue-lighten-5" rounded="lg" size="48">
                      <v-icon color="blue">mdi-currency-usd</v-icon>
                    </v-avatar>
                    <v-spacer></v-spacer>
                    <v-chip color="blue" size="x-small" variant="flat">ANNUAL BUDGET</v-chip>
                  </div>
                  <div class="text-h4 font-weight-black mb-1">{{ formatCurrencyShort(reportData.summary.annual_budget) }}</div>
                  <div class="text-caption text-grey">Total disbursed in {{ currentYear }}</div>
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
                    <v-chip color="purple" size="x-small" variant="flat">AVG PER PERSON</v-chip>
                  </div>
                  <div class="text-h4 font-weight-black mb-1">{{ formatCurrency(reportData.summary.avg_per_employee) }}</div>
                  <div class="text-caption text-grey">Average monthly payroll cost per staff</div>
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
                    <v-chip color="teal" size="x-small" variant="flat">ACTIVE UNITS</v-chip>
                  </div>
                  <div class="text-h4 font-weight-black mb-1">{{ reportData.summary.active_units }}</div>
                  <div class="text-caption text-grey">Institutions with active payroll entries</div>
                </v-card-text>
              </v-card>
            </v-col>
          </v-row>

          <!-- Main Charts -->
          <v-row class="mb-6">
            <v-col cols="12" md="8">
              <v-card class="glass-card rounded-xl pa-6 shadow-premium" elevation="0">
                <div class="d-flex align-center mb-6">
                   <h2 class="text-h6 font-weight-bold">Payroll Growth Trend</h2>
                   <v-spacer></v-spacer>
                   <v-chip color="success" size="small" variant="tonal" prepend-icon="mdi-trending-up">Live</v-chip>
                </div>
                <apexchart type="area" height="350" :options="trendChartOptions" :series="trendSeries"></apexchart>
              </v-card>
            </v-col>
            <v-col cols="12" md="4">
              <v-card class="glass-card rounded-xl pa-6 shadow-premium h-100" elevation="0">
                <div class="d-flex align-center mb-6">
                  <h2 class="text-h6 font-weight-bold">Budget Distribution</h2>
                  <v-spacer></v-spacer>
                  <v-chip color="primary" size="x-small" variant="flat">TOP 5</v-chip>
                </div>
                <v-table density="comfortable" class="modern-report-table">
                  <thead>
                    <tr>
                      <th class="text-left py-2 font-weight-bold">INSTITUTION</th>
                      <th class="text-right py-2 font-weight-bold">BUDGET</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="item in reportData.performance.slice(0, 5)" :key="item.kode_skpd">
                      <td class="text-caption font-weight-bold text-truncate" style="max-width: 150px;">{{ item.nama_skpd }}</td>
                      <td class="text-right text-body-2 font-weight-black text-primary">
                        {{ formatCurrencyShort(item.total_budget) }}
                      </td>
                    </tr>
                  </tbody>
                </v-table>
                <div class="mt-4 text-center">
                  <v-btn variant="text" color="primary" size="small" rounded="lg" to="/reports">VIEW ALL</v-btn>
                </div>
              </v-card>
            </v-col>
          </v-row>

          <!-- Payroll Expenditure Trend -->
          <v-row class="mb-6">
            <v-col cols="12">
              <v-card class="glass-card rounded-xl pa-6 shadow-premium" elevation="0">
                <div class="d-flex align-center mb-4">
                  <v-icon class="mr-2" color="primary">mdi-chart-multiline</v-icon>
                  <h2 class="text-h6 font-weight-bold">Payroll Expenditure Trend</h2>
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
                <div v-else class="text-center py-8 text-grey">
                  <v-icon size="48" color="grey-lighten-2" class="mb-2">mdi-chart-line-variant</v-icon>
                  <div>No historical payroll data available</div>
                </div>
              </v-card>
            </v-col>
          </v-row>

          <!-- Missing Payrolls - Full Width -->
          <v-row class="mb-6">

            <v-col cols="12">
              <v-card class="glass-card rounded-xl overflow-hidden shadow-premium" elevation="0">
                <v-toolbar color="error-lighten-5" flat class="px-6 py-4">
                  <v-toolbar-title class="font-weight-bold text-h6 text-error">
                    <v-icon start color="error" size="28">mdi-alert-circle-outline</v-icon>
                    Missing Payrolls
                  </v-toolbar-title>
                  <v-spacer></v-spacer>
                  <v-btn color="success" variant="tonal" size="small" class="mr-2" prepend-icon="mdi-microsoft-excel" @click="exportUnpaid('excel')" :loading="exportLoading === 'excel'">EXCEL</v-btn>
                  <v-btn color="error" variant="tonal" size="small" class="mr-2" prepend-icon="mdi-file-pdf-box" @click="exportUnpaid('pdf')" :loading="exportLoading === 'pdf'">PDF</v-btn>
                  <v-menu v-model="menu" :close-on-content-click="false">
                    <template v-slot:activator="{ props }">
                      <v-btn color="error" variant="text" size="small" v-bind="props" prepend-icon="mdi-calendar">
                        {{ selectedMonthName }} {{ selectedYear }}
                      </v-btn>
                    </template>
                    <v-card min-width="300" class="pa-4 rounded-xl">
                      <v-row dense>
                        <v-col cols="6">
                          <v-select
                            v-model="selectedMonth"
                            :items="months"
                            item-title="title"
                            item-value="value"
                            label="Month"
                            density="compact"
                            variant="outlined"
                            hide-details
                          ></v-select>
                        </v-col>
                        <v-col cols="6">
                          <v-select
                            v-model="selectedYear"
                            :items="years"
                            label="Year"
                            density="compact"
                            variant="outlined"
                            hide-details
                          ></v-select>
                        </v-col>
                        <v-col cols="12" class="mt-2 text-right">
                          <v-btn block color="primary" @click="fetchUnpaidData(); menu = false">APPLY FILTER</v-btn>
                        </v-col>
                      </v-row>
                    </v-card>
                  </v-menu>
                </v-toolbar>
                
                <!-- View Toggle -->
                <div class="px-4 pb-2">
                  <v-btn-toggle v-model="viewBy" mandatory density="compact" color="primary" variant="outlined" class="d-flex w-100 rounded-lg">
                    <v-btn value="skpd" class="flex-grow-1" size="small">BY SKPD</v-btn>
                    <v-btn value="upt" class="flex-grow-1" size="small">BY UPT</v-btn>
                    <v-btn value="employees" class="flex-grow-1" size="small">BY EMPLOYEES</v-btn>
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

                <!-- Unpaid Employees by SKPD -->
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
                    <span v-if="viewBy === 'employees'">All employees have payroll entries for {{ selectedMonthName }} {{ selectedYear }}!</span>
                    <span v-else>All {{ viewBy.toUpperCase() }}s have processed payroll for {{ selectedMonthName }} {{ selectedYear }}!</span>
                  </div>
                </div>
              </v-card>
            </v-col>
          </v-row>

          <!-- Paid Payrolls Section -->
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
                    <v-btn value="skpd" size="small">BY SKPD</v-btn>
                    <v-btn value="employees" size="small">BY PEGAWAI</v-btn>
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
                          <v-select
                            v-model="paidMonth"
                            :items="months"
                            item-title="title"
                            item-value="value"
                            label="Month"
                            density="compact"
                            variant="outlined"
                            hide-details
                          ></v-select>
                        </v-col>
                        <v-col cols="6">
                          <v-select
                            v-model="paidYear"
                            :items="years"
                            label="Year"
                            density="compact"
                            variant="outlined"
                            hide-details
                          ></v-select>
                        </v-col>
                        <v-col cols="12" class="mt-2 text-right">
                          <v-btn block color="primary" @click="fetchPaidData(); paidMenu = false">APPLY FILTER</v-btn>
                        </v-col>
                      </v-row>
                    </v-card>
                  </v-menu>
                </v-toolbar>
                
                <div v-if="paidLoading" class="pa-8 text-center">
                  <v-progress-circular indeterminate color="success"></v-progress-circular>
                </div>

                <!-- SKPD View -->
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

                <!-- Employees View -->
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

          <!-- Top Earners Table -->
          <v-row>
            <v-col cols="12">
              <v-card class="glass-card rounded-xl overflow-hidden shadow-premium" elevation="0">
                <v-toolbar color="transparent" flat class="px-6 py-4">
                  <v-toolbar-title class="font-weight-bold text-subtitle-1">Top Staff Earnings (Historical Peak)</v-toolbar-title>
                  <v-spacer></v-spacer>
                  <v-chip color="amber-darken-2" variant="flat" size="small" prepend-icon="mdi-medal-outline">TOP 10 RANKING</v-chip>
                </v-toolbar>
                <v-data-table
                  :headers="topEarnersHeaders"
                  :items="reportData.top_earners"
                  class="modern-report-table"
                  hover
                  :items-per-page="10"
                >
                  <template v-slot:item.rank="{ index }">
                    <v-avatar 
                      v-if="index < 3" 
                      :color="index === 0 ? 'amber' : index === 1 ? 'grey-lighten-2' : 'brown-lighten-2'" 
                      size="28" 
                      class="font-weight-black text-caption"
                    >
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

          <!-- Retirement Monitor Section -->
          <v-row class="mt-6">
            <v-col cols="12">
              <v-card class="glass-card rounded-xl overflow-hidden shadow-premium" elevation="0">
                <v-toolbar color="transparent" flat class="px-6 py-4">
                  <v-toolbar-title class="font-weight-bold text-subtitle-1">Retirement Monitor (Threshold: 58 Years)</v-toolbar-title>
                  <v-spacer></v-spacer>
                  <v-chip color="error" variant="flat" size="small" prepend-icon="mdi-clock-alert-outline">CRITICAL PLANNING</v-chip>
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
                    <v-chip 
                      :color="item.age >= 58 ? 'red' : 'orange'" 
                      variant="tonal" 
                      size="small" 
                      class="font-weight-bold"
                    >
                      {{ item.age }} Years
                    </v-chip>
                  </template>
                  <template v-slot:item.retirement_date="{ item }">
                    <span class="font-weight-medium">{{ formatDate(item.retirement_date) }}</span>
                  </template>
                  <template v-slot:item.status="{ item }">
                    <v-btn 
                      v-if="item.age >= 58" 
                      color="error" 
                      size="x-small" 
                      variant="flat" 
                      class="font-weight-bold"
                    >PROCESS NOW</v-btn>
                    <v-chip 
                      v-else-if="item.is_critical" 
                      color="warning" 
                      size="x-small" 
                      variant="flat"
                    >APPROACHING</v-chip>
                    <v-chip 
                      v-else 
                      color="info" 
                      size="x-small" 
                      variant="tonal"
                    >MONITORING</v-chip>
                  </template>
                  <template v-slot:no-data>
                    <div class="text-center py-8 text-grey">No personnel currently meeting the monitoring threshold (55+).</div>
                  </template>
                </v-data-table>
              </v-card>
            </v-col>
          </v-row>
        </div>
      </v-container>
    </v-main>

    <!-- Snackbar -->
    <v-snackbar v-model="snackbar" :timeout="3000" color="primary" rounded="lg">
      <div class="d-flex align-center">
        <v-icon class="mr-3">mdi-chart-bell-curve</v-icon>
        <div>
          <div class="font-weight-bold">{{ snackbarTitle }}</div>
          <div class="text-caption">This feature is coming soon in the next update.</div>
        </div>
      </div>
      <template v-slot:actions>
        <v-btn variant="text" @click="snackbar = false">CLOSE</v-btn>
      </template>
    </v-snackbar>
  </v-app>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useTheme } from 'vuetify'
import api from '../api'
import ThemeToggle from '../components/ThemeToggle.vue'
import Navbar from '../components/Navbar.vue'
import Sidebar from '../components/Sidebar.vue'

const theme = useTheme()

const loading = ref(true)
const unpaidLoading = ref(true)
const reportData = ref(null)
const unpaidSkpds = ref([])
const unpaidUpts = ref([])
const unpaidEmployees = ref([])
const exportLoading = ref(null)
const snackbar = ref(false)
const snackbarTitle = ref('')
const currentYear = new Date().getFullYear()

const showComingSoon = (feature) => {
  snackbarTitle.value = feature
  snackbar.value = true
}

const exportUnpaid = async (format) => {
  exportLoading.value = format
  try {
    const params = {
      month: selectedMonth.value,
      year: selectedYear.value,
      view_by: viewBy.value,
      format: format
    }
    const response = await api.get('/reports/unpaid-export', {
      params,
      responseType: 'blob'
    })
    const blob = new Blob([response.data], {
      type: format === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `missing_payrolls_${viewBy.value}_${selectedMonth.value}_${selectedYear.value}.${format === 'pdf' ? 'pdf' : 'xlsx'}`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Export failed:', err)
  } finally {
    exportLoading.value = null
  }
}

const formatCurrencyShort = (val) => {
  if (!val) return 'Rp 0'
  // Show full number format instead of abbreviated
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val)
}

const formatCurrency = (val) => {
  if (!val && val !== 0) return '-'
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(val)
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const options = { year: 'numeric', month: 'long', day: 'numeric' }
  return new Date(dateStr).toLocaleDateString('id-ID', options)
}

// Chart Options
const trendSeries = computed(() => {
  if (!reportData.value) return []
  return [{
    name: 'Total Budget',
    data: reportData.value.growth.map(i => i.value)
  }]
})

const trendChartOptions = computed(() => ({
  chart: {
    height: 350,
    type: 'area',
    toolbar: { show: false },
    zoom: { enabled: false },
    foreColor: theme.global.name.value === 'dark' ? '#94a3b8' : '#64748b'
  },
  theme: {
    mode: theme.global.name.value
  },
  colors: ['#1867C0'],
  dataLabels: { enabled: false },
  stroke: { curve: 'smooth', width: 3 },
  xaxis: {
    categories: reportData.value?.growth.map(i => i.label) || [],
    axisBorder: { show: false },
    axisTicks: { show: false }
  },
  yaxis: {
    labels: {
      formatter: (val) => formatCurrencyShort(val)
    }
  },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.4,
      opacityTo: 0.05,
      stops: [0, 90, 100]
    }
  },
  grid: {
    borderColor: '#f1f1f1',
    strokeDashArray: 4
  }
}))

const performanceHeaders = [
  { title: 'INSTITUTION', key: 'nama_skpd', sortable: true },
  { title: 'TOTAL STAFF', key: 'staff_count', sortable: true, align: 'center' },
  { title: 'MONTHLY BUDGET', key: 'total_budget', sortable: true, align: 'end' },
  { title: 'AVG/STAFF', key: 'avg_staff', sortable: false, align: 'end' },
]

const topEarnersHeaders = [
  { title: 'RANK', key: 'rank', sortable: false, align: 'center', width: '80px' },
  { title: 'EMPLOYEE', key: 'nama', sortable: true },
  { title: 'POSITION / INSTITUTION', key: 'jabatan_info', sortable: false },
  { title: 'NET INCOME', key: 'total_amoun', sortable: true, align: 'end' },
]

const retirementHeaders = [
  { title: 'STAFF MEMBER', key: 'nama', sortable: true },
  { title: 'CURRENT AGE', key: 'age', sortable: true, align: 'center' },
  { title: 'ESTIMATED RETIREMENT DATE', key: 'retirement_date', sortable: true },
  { title: 'PLANNING STATUS', key: 'status', sortable: false, align: 'center' },
]

onMounted(async () => {
  try {
    const response = await api.get('/reports')
    reportData.value = response.data.data
  } catch (err) {
    console.error('Error fetching reports:', err)
  } finally {
    loading.value = false
  }

  fetchUnpaidData()
  fetchPaidData()
})

// Filter Logic
const menu = ref(false)
const viewBy = ref('skpd') // 'skpd' or 'upt'
const selectedMonth = ref(new Date().getMonth() + 1)
const selectedYear = ref(new Date().getFullYear())
const months = [
  { title: 'January', value: 1 }, { title: 'February', value: 2 }, { title: 'March', value: 3 },
  { title: 'April', value: 4 }, { title: 'May', value: 5 }, { title: 'June', value: 6 },
  { title: 'July', value: 7 }, { title: 'August', value: 8 }, { title: 'September', value: 9 },
  { title: 'October', value: 10 }, { title: 'November', value: 11 }, { title: 'December', value: 12 }
]
const years = computed(() => {
  const current = new Date().getFullYear()
  return [current, current - 1, current - 2]
})
const selectedMonthName = computed(() => months.find(m => m.value === selectedMonth.value)?.title)

const fetchUnpaidData = async () => {
  unpaidLoading.value = true
  try {
    const params = { month: selectedMonth.value, year: selectedYear.value }
    
    const [skpdsRes, uptsRes, employeesRes] = await Promise.all([
      api.get('/reports/unpaid-skpds', { params }),
      api.get('/reports/unpaid-upts', { params }),
      api.get('/reports/unpaid-employees', { params })
    ])
    
    if (skpdsRes.data.success) {
      unpaidSkpds.value = skpdsRes.data.data
    }
    if (uptsRes.data.success) {
      unpaidUpts.value = uptsRes.data.data
    }
    if (employeesRes.data.success) {
      unpaidEmployees.value = employeesRes.data.data
    }
  } catch (err) {
    console.error('Failed to fetch unpaid data:', err)
  } finally {
    unpaidLoading.value = false
  }
}

// Watch for viewBy changes to refetch
import { watch } from 'vue'
watch(viewBy, () => {
  fetchUnpaidData()
})

// Paid Payrolls Logic
const paidLoading = ref(true)
const paidSkpds = ref([])
const paidEmployees = ref([])
const paidExportLoading = ref(null)
const paidMenu = ref(false)
const paidMonth = ref(new Date().getMonth() + 1)
const paidYear = ref(new Date().getFullYear())
const paidViewBy = ref('skpd')
const paidMonthName = computed(() => months.find(m => m.value === paidMonth.value)?.title)

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
    if (skpdsRes.data.success) {
      paidSkpds.value = skpdsRes.data.data
    }
    if (employeesRes.data.success) {
      paidEmployees.value = employeesRes.data.data
    }
  } catch (err) {
    console.error('Failed to fetch paid data:', err)
  } finally {
    paidLoading.value = false
  }
}

const exportPaid = async (format) => {
  paidExportLoading.value = format
  try {
    const params = {
      month: paidMonth.value,
      year: paidYear.value,
      format: format
    }
    const endpoint = paidViewBy.value === 'employees' ? '/reports/paid-employees-export' : '/reports/paid-export'
    const response = await api.get(endpoint, {
      params,
      responseType: 'blob'
    })
    const blob = new Blob([response.data], {
      type: format === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `skpd_daftar_gaji_${paidMonth.value}_${paidYear.value}.${format === 'pdf' ? 'pdf' : 'xlsx'}`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (err) {
    console.error('Export failed:', err)
  } finally {
    paidExportLoading.value = null
  }
}
</script>

<style scoped>
.modern-bg {
  background-color: rgb(var(--v-theme-background)) !important;
}

.glass-nav {
  backdrop-filter: blur(12px);
  border-bottom: 1px solid rgba(var(--v-border-color), 0.05) !important;
  background-color: rgba(var(--v-theme-surface), 0.8) !important;
  z-index: 1000;
}

.glass-card {
  background-color: rgb(var(--v-theme-surface)) !important;
  border: 1px solid rgba(var(--v-border-color), 0.08) !important;
}

.text-primary-gradient {
  background: linear-gradient(45deg, #1867C0, #5CBBF6);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  font-weight: 800;
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

/* Table Styling */
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
