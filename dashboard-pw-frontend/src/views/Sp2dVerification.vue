<template>
  <div class="modern-dashboard">
    <Navbar @show-coming-soon="showComingSoon" />
    <Sidebar @show-coming-soon="showComingSoon" />
    
    <v-main class="bg-light min-vh-100">
      <v-container fluid class="pa-8">
        <v-row>
          <v-col cols="12">
            <div class="d-flex align-center mb-6">
              <v-btn icon="mdi-arrow-left" variant="text" @click="$router.push('/dashboard')" class="mr-2"></v-btn>
              <div>
                <h1 class="text-h4 font-weight-bold mb-1">Verifikasi Realisasi SP2D</h1>
                <p class="text-subtitle-1 text-medium-emphasis mb-0">Monitor pencairan Gaji & TPP berdasarkan data SIPD</p>
              </div>
            </div>
          </v-col>
        </v-row>

    <!-- Top Controls Bar -->
    <v-row>
      <v-col cols="12">
        <v-card class="glass-card rounded-xl pa-4 mb-6" elevation="0">
          <v-row align="center">
            <!-- Period & Type Selection -->
            <v-col cols="12" md="4">
              <div class="d-flex gap-2">
                <v-select
                  v-model="selectedMonth"
                  :items="months"
                  label="Bulan"
                  density="compact"
                  variant="outlined"
                  rounded="lg"
                  hide-details
                  style="width: 120px"
                  @update:model-value="fetchData"
                ></v-select>
                <v-select
                  v-model="selectedYear"
                  :items="years"
                  label="Tahun"
                  density="compact"
                  variant="outlined"
                  rounded="lg"
                  hide-details
                  style="width: 90px"
                  @update:model-value="fetchData"
                ></v-select>
                <v-select
                  v-model="selectedJenisGaji"
                  :items="['Induk', 'Susulan', 'Kekurangan', 'Terusan']"
                  label="Jenis Gaji"
                  density="compact"
                  variant="outlined"
                  rounded="lg"
                  hide-details
                  clearable
                  class="flex-grow-1"
                  @update:model-value="fetchData"
                ></v-select>
              </div>
            </v-col>

            <!-- TPP Mode Selection -->
            <v-col cols="12" md="2">
              <v-btn-toggle
                v-model="tppReconMode"
                mandatory
                color="teal"
                density="compact"
                rounded="lg"
                variant="outlined"
                class="w-100"
              >
                <v-btn value="bruto" size="x-small" title="Bandingkan dengan Bruto SIPD" class="flex-grow-1">BRUTO</v-btn>
                <v-btn value="netto" size="x-small" title="Bandingkan dengan Netto SIPD" class="flex-grow-1">NETTO</v-btn>
              </v-btn-toggle>
              <div class="text-center text-overline mt-1" style="font-size: 8px !important; line-height: 1; letter-spacing: 0.1em; opacity: 0.7">MODE REKON TPP</div>
            </v-col>

            <!-- Upload Zone -->
            <v-col cols="12" md="3">
              <div 
                class="upload-zone-compact pa-2 px-4 d-flex align-center rounded-xl border-dashed cursor-pointer"
                :class="{ 'is-dragging': isDragging }"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop"
                @click="$refs.fileInput.click()"
              >
                <input type="file" ref="fileInput" class="d-none" @change="handleFileSelect" accept=".xlsx,.xls">
                <v-icon size="24" color="primary" class="mr-3">mdi-file-excel-outline</v-icon>
                <div class="flex-grow-1">
                  <div class="text-caption font-weight-bold">Impor Register SIPD</div>
                  <div class="text-overline" style="font-size: 8px !important; line-height: 1">Tarik file ke sini atau klik</div>
                </div>
                <v-progress-circular v-if="uploading" indeterminate size="16" width="2" color="primary" class="ml-2"></v-progress-circular>
              </div>
            </v-col>

            <!-- View Mode Switcher -->
            <v-col cols="12" md="3" class="d-flex justify-end">
              <v-btn-toggle
                v-model="viewMode"
                mandatory
                color="primary"
                density="compact"
                rounded="pill"
                variant="tonal"
              >
                <v-btn value="summary" size="small" prepend-icon="mdi-view-list">Ringkasan</v-btn>
                <v-btn value="details" size="small" prepend-icon="mdi-table">Detail Data</v-btn>
                <v-btn value="recon" size="small" prepend-icon="mdi-compare">Table Rekon</v-btn>
              </v-btn-toggle>
            </v-col>
          </v-row>
        </v-card>
      </v-col>
    </v-row>

    <!-- Results Table (Full Width) -->
    <v-row>
      <v-col cols="12">
        <!-- Summary View -->
        <v-card v-if="viewMode === 'summary'" class="glass-card rounded-xl overflow-hidden" elevation="0">
          <div class="pa-6 border-bottom d-flex align-center justify-space-between bg-surface-variant-light">
            <h2 class="text-h6 font-weight-bold mb-0">Status Realisasi per SKPD</h2>
              <v-text-field
                v-model="search"
                prepend-inner-icon="mdi-magnify"
                label="Cari SKPD..."
                single-line
                hide-details
                density="compact"
                variant="outlined"
                rounded="pill"
                class="search-bar-300"
              ></v-text-field>
          </div>
          
          <v-data-table
            :headers="headers"
            :items="items"
            :loading="loading"
            :search="search"
            class="bg-transparent"
            hover
          >
            <template v-slot:item.nama_skpd="{ item }">
              <div class="font-weight-medium text-truncate" style="max-width: 250px;">
                {{ item.nama_skpd }}
              </div>
            </template>

            <template v-slot:item.jenis_gaji="{ item }">
              <v-chip size="x-small" color="secondary" variant="tonal" class="font-weight-bold">
                {{ item.jenis_gaji }}
              </v-chip>
            </template>

            <template v-slot:item.pns="{ item }">
              <status-chip :status="item.pns" />
            </template>

            <template v-slot:item.pppk="{ item }">
              <status-chip :status="item.pppk" />
            </template>

            <template v-slot:item.pppk_pw="{ item }">
              <status-chip :status="item.pppk_pw" />
            </template>

            <template v-slot:item.tpp="{ item }">
              <status-chip :status="item.tpp" />
            </template>

            <template v-slot:tfoot>
              <tr class="font-weight-bold bg-surface-variant-light">
                <td colspan="2" class="text-right pa-4">TOTAL KESELURUHAN</td>
                <td class="text-center pa-2">
                  <div class="text-caption">SIPD: {{ formatCurrency(summaryTotals.pns.netto) }}</div>
                  <div class="text-caption text-primary">Peg: {{ summaryTotals.pns.count }}</div>
                </td>
                <td class="text-center pa-2">
                  <div class="text-caption">SIPD: {{ formatCurrency(summaryTotals.pppk.netto) }}</div>
                  <div class="text-caption text-primary">Peg: {{ summaryTotals.pppk.count }}</div>
                </td>
                <td class="text-center pa-2">
                  <div class="text-caption">SIPD: {{ formatCurrency(summaryTotals.pppk_pw.netto) }}</div>
                  <div class="text-caption text-primary">Peg: {{ summaryTotals.pppk_pw.count }}</div>
                </td>
                <td class="text-center pa-2">
                  <div class="text-caption">SIPD: {{ formatCurrency(summaryTotals.tpp.netto) }}</div>
                  <div class="text-caption text-primary">Peg: {{ summaryTotals.tpp.count }}</div>
                </td>
              </tr>
            </template>
          </v-data-table>
        </v-card>

        <!-- Detailed View (Raw Transactions) -->
        <v-card v-else-if="viewMode === 'details'" class="glass-card rounded-xl overflow-hidden" elevation="0">
          <div class="pa-6 border-bottom d-flex align-center justify-space-between bg-surface-variant-light">
            <h2 class="text-h6 font-weight-bold mb-0">Daftar Transaksi Hasil Impor</h2>
            <div class="d-flex align-center gap-2">
              <v-text-field
                v-model="searchDetail"
                prepend-inner-icon="mdi-magnify"
                label="Cari..."
                single-line
                hide-details
                density="compact"
                variant="outlined"
                rounded="pill"
                class="search-bar-300"
              ></v-text-field>
              <v-btn 
                color="primary" 
                prepend-icon="mdi-plus" 
                variant="flat" 
                rounded="pill" 
                size="small"
                @click="openCreateDialog"
              >Tambah Data Manual</v-btn>
            </div>
          </div>
          
          <v-data-table
            :headers="detailHeaders"
            :items="transactions"
            :loading="loading"
            :search="searchDetail"
            class="bg-transparent"
            hover
          >
            <template v-slot:item.nomor_sp2d="{ item }">
              <div class="d-flex align-center gap-2">
                <div class="text-caption font-weight-bold">{{ item.nomor_sp2d }}</div>
                <v-chip v-if="item.is_manual" size="x-small" color="orange" variant="flat">Manual</v-chip>
              </div>
            </template>
            
            <template v-slot:item.tanggal_sp2d="{ item }">
              {{ formatDate(item.tanggal_sp2d) }}
            </template>

            <template v-slot:item.jenis_data="{ item }">
              <v-chip size="x-small" :color="getTypeColor(item.jenis_data)" variant="flat">
                {{ item.jenis_data }}
              </v-chip>
            </template>

            <template v-slot:item.netto="{ item }">
              <div class="text-right font-weight-bold">{{ formatCurrency(item.netto) }}</div>
            </template>
            
            <template v-slot:item.nama_skpd_sipd="{ item }">
              <div class="d-flex align-center gap-2">
                <div class="text-caption" style="max-width: 150px;">{{ item.nama_skpd_sipd }}</div>
                <v-tooltip v-if="!item.skpd_id" text="Hubungkan SKPD">
                  <template v-slot:activator="{ props }">
                    <v-btn 
                      v-bind="props"
                      icon="mdi-link-plus" 
                      size="x-small" 
                      color="error" 
                      variant="text" 
                      @click="openResolveDialog(item)"
                    ></v-btn>
                  </template>
                </v-tooltip>
              </div>
            </template>

            <template v-slot:item.actions="{ item }">
              <div class="d-flex justify-end gap-1">
                <v-btn icon="mdi-pencil" size="x-small" variant="text" color="primary" @click="openEditDialog(item)"></v-btn>
                <v-btn icon="mdi-delete" size="x-small" variant="text" color="error" @click="confirmDelete(item)"></v-btn>
              </div>
            </template>
          </v-data-table>
        </v-card>

        <!-- Reconciliation Table View -->
        <v-card v-else-if="viewMode === 'recon'" class="glass-card rounded-xl overflow-hidden" elevation="0">
          <div class="pa-6 border-bottom d-flex align-center justify-space-between bg-surface-variant-light">
            <h2 class="text-h6 font-weight-bold mb-0">Tabel Rekonsiliasi SIMGAJI vs SIPD</h2>
            <div class="d-flex align-center gap-2">
              <v-text-field
                v-model="searchRecon"
                prepend-inner-icon="mdi-magnify"
                label="Cari SKPD..."
                single-line
                hide-details
                density="compact"
                variant="outlined"
                rounded="pill"
                class="search-bar-300"
              ></v-text-field>
              <v-btn 
                color="primary" 
                variant="tonal" 
                prepend-icon="mdi-export" 
                size="small" 
                rounded="pill"
                @click="exportExcel"
                :loading="exporting"
              >Export Excel</v-btn>
            </div>
          </div>
          
          <div class="recon-table-container">
            <v-table density="compact" class="recon-table" fixed-header hover>
              <thead>
                <tr class="header-group-row">
                  <th colspan="12" class="text-center simgaji-header">SIMGAJI</th>
                  <th colspan="9" class="text-center sipd-header">SIPD</th>
                </tr>
                <tr class="header-main-row">
                  <th rowspan="2" class="border-right">No</th>
                  <th rowspan="2" class="border-right cursor-pointer" style="min-width: 200px" @click="toggleSort('nama_skpd')">
                    SKPD SIMGAJI <v-icon size="14">{{ getSortIcon('nama_skpd') }}</v-icon>
                  </th>
                  <th rowspan="2" class="border-right cursor-pointer" style="min-width: 100px" @click="toggleSort('jenis_gaji')">
                    Kategori <v-icon size="14">{{ getSortIcon('jenis_gaji') }}</v-icon>
                  </th>
                  <th rowspan="2" class="border-right text-center" style="min-width: 80px">Pegawai</th>
                  <th rowspan="2" class="border-right cursor-pointer" style="min-width: 120px" @click="toggleSort('brutto')">
                    Brutto <v-icon size="14">{{ getSortIcon('brutto') }}</v-icon>
                  </th>
                  <th rowspan="2" class="border-right cursor-pointer" style="min-width: 120px" @click="toggleSort('potongan')">
                    Potongan <v-icon size="14">{{ getSortIcon('potongan') }}</v-icon>
                  </th>
                  <th rowspan="2" class="border-right cursor-pointer" style="min-width: 120px" @click="toggleSort('netto')">
                    Netto <v-icon size="14">{{ getSortIcon('netto') }}</v-icon>
                  </th>
                  <th rowspan="2" class="border-right" style="min-width: 80px">Aksi</th>
                  <th colspan="2" class="text-center border-right">GAJI</th>
                  <th colspan="2" class="text-center border-right">TPP</th>
                  <th colspan="2" class="text-center border-right">Tanggal SP2D</th>
                  <th rowspan="2" class="border-right" style="min-width: 180px">Nomor SP2D</th>
                  <th rowspan="2" class="border-right" style="min-width: 180px">SKPD SIPD</th>
                  <th rowspan="2" class="border-right" style="min-width: 250px">Keterangan</th>
                  <th rowspan="2" class="border-right" style="min-width: 120px">Brutto</th>
                  <th rowspan="2" class="border-right" style="min-width: 120px">Potongan</th>
                  <th rowspan="2" class="border-right" style="min-width: 120px">Netto</th>
                  <th rowspan="2" style="min-width: 120px" class="text-center">Selisih Netto</th>
                </tr>
                <tr class="header-sub-row">
                  <th class="text-center border-right">PNS</th>
                  <th class="text-center border-right">PPPK</th>
                  <th class="text-center border-right">PNS</th>
                  <th class="text-center border-right">PPPK</th>
                  <th class="text-center border-right">Pembuatan</th>
                  <th class="text-center border-right">Pencairan</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="loading" class="text-center">
                  <td colspan="21" class="pa-10">
                    <v-progress-circular indeterminate color="primary"></v-progress-circular>
                  </td>
                </tr>
                <tr v-else-if="filteredReconData.length === 0" class="text-center">
                  <td colspan="21" class="pa-10 text-medium-emphasis">Tidak ada data yang cocok dengan pencarian</td>
                </tr>
                <tr v-for="(row, idx) in paginatedReconData" :key="idx">
                  <td class="text-center border-right">{{ (reconPage - 1) * reconItemsPerPage + idx + 1 }}</td>
                  <td class="border-right text-caption truncate d-flex align-center gap-2">
                    {{ row.simgaji.nama_skpd }}
                    <v-chip 
                      v-if="row.sipd.is_realized && Math.abs(row.simgaji.netto - row.sipd.netto) < 100" 
                      size="x-small" color="success" variant="flat" class="px-1" style="height: 16px"
                    >Klop</v-chip>
                    <v-chip 
                      v-else-if="row.sipd.is_realized" 
                      size="x-small" color="error" variant="flat" class="px-1" style="height: 16px"
                    >Selisih</v-chip>
                  </td>
                  <td class="border-right text-caption">
                    <v-chip size="x-small" color="secondary" variant="tonal" class="font-weight-bold">
                      {{ row.simgaji.jenis_gaji }}
                    </v-chip>
                  </td>
                  <td class="border-right text-center text-caption font-weight-bold">
                    {{ row.simgaji.emp_count }} Peg
                  </td>
                  <td class="border-right text-right text-caption">{{ formatCurrency(row.simgaji.brutto) }}</td>
                  <td class="border-right text-right text-caption">{{ formatCurrency(row.simgaji.potongan) }}</td>
                  <td class="border-right text-right text-caption font-weight-bold">{{ formatCurrency(row.simgaji.netto) }}</td>
                  <td class="border-right text-center">
                    <div class="d-flex align-center justify-center gap-1">
                      <v-btn
                        icon="mdi-eye"
                        size="x-small"
                        color="primary"
                        variant="text"
                        :disabled="!row.sipd.is_realized"
                        @click="fetchReconDetail(row)"
                      ></v-btn>
                      <v-tooltip text="Koreksi Nominal SP2D">
                        <template v-slot:activator="{ props }">
                          <v-btn
                            v-bind="props"
                            icon="mdi-pencil-outline"
                            size="x-small"
                            color="orange"
                            variant="text"
                            @click="openEditFromRecon(row)"
                          ></v-btn>
                        </template>
                      </v-tooltip>
                    </div>
                  </td>
                  <td class="border-right text-right text-caption">{{ formatCurrency(row.simgaji.gaji_pns) }}</td>
                  <td class="border-right text-right text-caption">{{ formatCurrency(row.simgaji.gaji_pppk) }}</td>
                  <td class="border-right text-right text-caption">{{ formatCurrency(row.simgaji.tpp_pns) }}</td>
                  <td class="border-right text-right text-caption">{{ formatCurrency(row.simgaji.tpp_pppk) }}</td>
                  
                  <td class="border-right text-center text-caption">{{ formatDate(row.sipd.tanggal_sp2d) }}</td>
                  <td class="border-right text-center text-caption">{{ formatDate(row.sipd.tanggal_cair) }}</td>
                  <td class="border-right text-caption font-weight-bold">{{ row.sipd.nomor_sp2d }}</td>
                  <td class="border-right text-caption overflow-hidden" style="max-width: 15rem">{{ row.sipd.nama_skpd }}</td>
                  <td class="border-right text-caption truncate">{{ row.sipd.keterangan }}</td>
                  <td class="border-right text-right text-caption">{{ formatCurrency(row.sipd.brutto) }}</td>
                  <td class="border-right text-right text-caption">{{ formatCurrency(row.sipd.potongan) }}</td>
                  <td class="border-right text-right text-caption font-weight-bold">{{ formatCurrency(row.sipd.netto) }}</td>
                  <td class="text-right text-caption font-weight-bold" :class="Math.abs(row.simgaji.netto - row.sipd.netto) < 100 ? 'text-success' : 'text-error'">
                    {{ formatCurrency(row.simgaji.netto - row.sipd.netto) }}
                  </td>
                </tr>
              </tbody>
              <tfoot v-if="filteredReconData.length > 0">
                <tr class="font-weight-bold bg-surface-variant-light">
                  <td colspan="3" class="text-center">TOTAL PAGE</td>
                  <td class="text-center pa-2">{{ reconTotals.emp_count }} Peg</td>
                  <td class="text-right pa-2">{{ formatCurrency(reconTotals.simgaji_brutto) }}</td>
                  <td class="text-right pa-2">{{ formatCurrency(reconTotals.simgaji_potongan) }}</td>
                  <td class="text-right pa-2">{{ formatCurrency(reconTotals.simgaji_netto) }}</td>
                  <td class="border-right"></td>
                  <td class="text-right pa-2">{{ formatCurrency(reconTotals.gaji_pns) }}</td>
                  <td class="text-right pa-2">{{ formatCurrency(reconTotals.gaji_pppk) }}</td>
                  <td class="text-right pa-2">{{ formatCurrency(reconTotals.tpp_pns) }}</td>
                  <td class="text-right pa-2">{{ formatCurrency(reconTotals.tpp_pppk) }}</td>
                  <td colspan="5" class="border-right"></td>
                  <td class="text-right pa-2">{{ formatCurrency(reconTotals.sipd_brutto) }}</td>
                  <td class="text-right pa-2">{{ formatCurrency(reconTotals.sipd_potongan) }}</td>
                  <td class="text-right pa-2">{{ formatCurrency(reconTotals.sipd_netto) }}</td>
                  <td class="text-right pa-2" :class="Math.abs(reconTotals.simgaji_netto - reconTotals.sipd_netto) < 100 ? 'text-success' : 'text-error'">
                    {{ formatCurrency(reconTotals.simgaji_netto - reconTotals.sipd_netto) }}
                  </td>
                </tr>
              </tfoot>
            </v-table>
          </div>
          
          <!-- Pagination -->
          <div class="pa-4 d-flex align-center justify-space-between border-top">
            <div class="d-flex align-center gap-4">
              <div class="text-caption text-medium-emphasis">
                Menampilkan {{ Math.min(filteredReconData.length, (reconPage - 1) * reconItemsPerPage + 1) }} - 
                {{ Math.min(filteredReconData.length, reconPage * reconItemsPerPage) }} dari {{ filteredReconData.length }} SKPD
              </div>
              <div class="d-flex align-center gap-2" style="width: 150px">
                <span class="text-caption text-medium-emphasis">Baris:</span>
                <v-select
                  v-model="reconItemsPerPage"
                  :items="[10, 15, 25, 50, 100]"
                  density="compact"
                  variant="plain"
                  hide-details
                  class="items-per-page-select"
                  @update:model-value="reconPage = 1"
                ></v-select>
              </div>
            </div>
            <v-pagination
              v-model="reconPage"
              :length="Math.ceil(filteredReconData.length / reconItemsPerPage)"
              :total-visible="7"
              density="compact"
              variant="tonal"
              active-color="primary"
            ></v-pagination>
          </div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Edit Dialog -->
    <v-dialog v-model="editDialog" max-width="500px">
      <v-card class="rounded-xl pa-4">
        <v-card-title class="text-h5 font-weight-bold">{{ isEdit ? 'Atur Ulang Nilai SP2D' : 'Tambah Register Manual' }}</v-card-title>
        <v-card-text>
          <p class="text-body-2 text-medium-emphasis mb-4">
            {{ isEdit ? 'Sesuaikan nilai SP2D jika data register SIPD tergabung dengan kegiatan lain.' : 'Tambahkan data SP2D yang tidak terdeteksi otomatis oleh sistem.' }}
          </p>
          <v-form ref="editForm" v-model="isFormValid">
            <v-text-field
              v-model="editItem.nomor_sp2d"
              label="Nomor SP2D"
              variant="outlined"
              density="comfortable"
              rounded="lg"
              class="mb-2"
              :rules="[v => !!v || 'Wajib diisi']"
            ></v-text-field>

            <v-text-field
              v-model="editItem.tanggal_sp2d"
              label="Tanggal SP2D"
              type="date"
              variant="outlined"
              density="comfortable"
              rounded="lg"
              class="mb-2"
              v-if="!isEdit"
              :rules="[v => !!v || 'Wajib diisi']"
            ></v-text-field>

            <v-autocomplete
              v-model="editItem.skpd_id"
              :items="skpds"
              item-title="nama_skpd"
              item-value="id_skpd"
              label="Pilih SKPD"
              variant="outlined"
              density="comfortable"
              rounded="lg"
              class="mb-2"
              v-if="!isEdit"
              :rules="[v => !!v || 'Wajib diisi']"
            ></v-autocomplete>
            
            <v-select
              v-model="editItem.jenis_data"
              :items="['PNS', 'PPPK', 'TPP']"
              label="Kategori Data"
              variant="outlined"
              density="comfortable"
              rounded="lg"
              class="mb-2"
              :rules="[v => !!v || 'Wajib diisi']"
            ></v-select>

            <v-text-field
              v-model.number="editItem.netto"
              label="Nilai Netto (Nominal)"
              variant="outlined"
              density="comfortable"
              rounded="lg"
              type="number"
              prefix="Rp"
              :rules="[v => !!v || 'Wajib diisi']"
            ></v-text-field>

            <v-textarea
              v-model="editItem.keterangan"
              label="Keterangan"
              variant="outlined"
              density="comfortable"
              rounded="lg"
              class="mb-2"
              rows="2"
              v-if="!isEdit"
            ></v-textarea>
          </v-form>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn variant="text" rounded="pill" @click="editDialog = false">Batal</v-btn>
          <v-btn color="primary" variant="flat" rounded="pill" :loading="saving" :disabled="!isFormValid" @click="saveTransaction">
            {{ isEdit ? 'Simpan Perubahan' : 'Simpan Data' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Success Snackbar -->
    <v-snackbar v-model="snackbar" :color="snackbarColor" timeout="3000" rounded="lg">
      <div class="d-flex align-center">
        <v-icon class="mr-3" v-if="snackbarTitle">mdi-information-outline</v-icon>
        <div>
          <div class="font-weight-bold" v-if="snackbarTitle">{{ snackbarTitle }}</div>
          <div class="text-body-2">{{ snackbarText }}</div>
        </div>
      </div>
    </v-snackbar>
    <!-- Import Preview Dialog -->
    <v-dialog v-model="importDialog" persistent max-width="900px">
      <v-card class="rounded-xl pa-2">
        <v-card-title class="pa-4 d-flex align-center">
          <v-icon color="primary" class="mr-3">mdi-file-import-outline</v-icon>
          <span class="font-weight-bold">Preview Impor Data SP2D</span>
          <v-spacer></v-spacer>
          <v-btn icon="mdi-close" variant="text" @click="importDialog = false"></v-btn>
        </v-card-title>
        
        <v-card-text class="pa-4">
          <v-row>
            <v-col cols="12" md="6">
              <v-select
                v-model="importTargetType"
                :items="importTargetTypes"
                label="Jenis Data Target"
                variant="outlined"
                density="compact"
                rounded="lg"
                hint="Pilih jenis data jika deteksi otomatis kurang akurat"
                persistent-hint
                @update:model-value="fetchImportPreview"
              ></v-select>
            </v-col>
            <v-col cols="12" md="6" v-if="importSummary" class="d-flex align-center">
              <v-chip color="primary" variant="tonal" class="mr-2">Total: {{ importSummary.total_rows }}</v-chip>
              <v-chip :color="importSummary.unmapped_rows > 0 ? 'error' : 'success'" variant="tonal">
                Belum Terhubung: {{ importSummary.unmapped_rows }}
              </v-chip>
            </v-col>
          </v-row>

          <v-divider class="my-4 border-opacity-10"></v-divider>

          <div v-if="previewLoading" class="pa-10 text-center">
            <v-progress-circular indeterminate color="primary"></v-progress-circular>
            <div class="mt-4 text-caption">Membaca data file...</div>
          </div>
          
          <v-data-table
            v-else
            :headers="[
              { title: 'No SP2D', key: 'nomor_sp2d' },
              { title: 'Unit SIPD', key: 'nama_skpd' },
              { title: 'Mapping Internal', key: 'skpd_match' },
              { title: 'Jenis', key: 'jenis_data' },
              { title: 'Netto', key: 'netto', align: 'end' },
            ]"
            :items="importPreview"
            density="compact"
            max-height="400px"
            fixed-header
            class="rounded-lg border"
          >
            <template v-slot:item.skpd_match="{ item }">
              <div v-if="item.skpd_match" class="text-success d-flex align-center">
                <v-icon size="small" class="mr-1">mdi-link-variant</v-icon>
                <div class="text-caption">{{ item.skpd_match }}</div>
              </div>
              <div v-else class="text-error font-weight-bold text-caption d-flex align-center">
                <v-icon size="small" class="mr-1">mdi-link-variant-off</v-icon>
                Tidak Terdeteksi
              </div>
            </template>
            <template v-slot:item.netto="{ item }">
              {{ formatCurrency(item.netto) }}
            </template>
            <template v-slot:item.jenis_data="{ item }">
              <v-chip size="x-small" :color="getTypeColor(item.jenis_data)" variant="flat">
                {{ item.jenis_data }}
              </v-chip>
            </template>
          </v-data-table>
        </v-card-text>

        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn variant="text" color="grey" @click="importDialog = false">Batal</v-btn>
          <v-btn 
            color="primary" 
            variant="flat" 
            rounded="lg" 
            :loading="uploading"
            :disabled="previewLoading"
            @click="processImport"
          >Konfirmasi & Impor Data</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Resolve Mapping Dialog -->
    <v-dialog v-model="resolveDialog" max-width="500px">
      <v-card class="rounded-xl pa-2">
        <v-card-title class="pa-4 font-weight-bold">Hubungkan SKPD SIPD</v-card-title>
        <v-card-text>
          <div class="mb-4">
            <div class="text-caption text-medium-emphasis mb-1">Nama Unit di SIPD:</div>
            <div class="text-body-1 font-weight-black color-primary">{{ resolveItem?.nama_skpd_sipd }}</div>
          </div>
          
          <v-autocomplete
            v-model="selectedSkpdId"
            :items="skpds"
            item-title="nama_skpd"
            item-value="id_skpd"
            label="Pilih SKPD Internal yang Sesuai"
            placeholder="Ketik untuk mencari SKPD..."
            variant="outlined"
            density="compact"
            rounded="lg"
            clearable
            persistent-hint
            hint="Sistem akan mengingat pilihan ini untuk impor berikutnya"
          ></v-autocomplete>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer></v-spacer>
          <v-btn variant="text" color="grey" @click="resolveDialog = false">Batal</v-btn>
          <v-btn 
            color="primary" 
            variant="flat" 
            rounded="lg"
            :loading="mappingLoading"
            :disabled="!selectedSkpdId"
            @click="saveMapping"
          >Simpan Mapping</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Detail Recon Modal -->
    <v-dialog v-model="reconDetailDialog" max-width="800px" scrollable>
      <v-card class="rounded-xl pa-2">
        <v-card-title class="pa-4 d-flex align-center">
          <v-icon color="primary" class="mr-3">mdi-account-details</v-icon>
          <div>
            <div class="font-weight-bold">Rincian Personil</div>
            <div class="text-caption text-medium-emphasis">SP2D: {{ activeReconSp2d?.nomor || 'N/A' }} ({{ formatCurrency(activeReconSp2d?.nominal || 0) }})</div>
          </div>
          <v-spacer></v-spacer>
          <v-btn icon="mdi-close" variant="text" @click="reconDetailDialog = false"></v-btn>
        </v-card-title>
        
        <v-divider></v-divider>
        
        <v-card-text style="height: 500px" class="pa-0">
          <div v-if="detailLoading" class="pa-10 text-center">
            <v-progress-circular indeterminate color="primary"></v-progress-circular>
            <div class="mt-2 text-caption">Memuat detail personil...</div>
          </div>
          <v-table v-else density="compact" fixed-header hover>
            <thead>
              <tr>
                <th class="text-left">NIP</th>
                <th class="text-left">Nama</th>
                <th class="text-center">Tipe</th>
                <th class="text-right">Nominal</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in reconDetails" :key="p.nip">
                <td class="text-caption">{{ p.nip }}</td>
                <td class="text-caption font-weight-medium">{{ p.nama }}</td>
                <td class="text-center">
                    <v-chip size="x-small" label :color="getTypeColor(p.tipe)">{{ p.tipe }}</v-chip>
                </td>
                <td class="text-right text-caption font-weight-bold">{{ formatCurrency(p.nominal) }}</td>
              </tr>
              <tr v-if="reconDetails.length === 0">
                <td colspan="4" class="text-center pa-10 text-medium-emphasis">Tidak ada data rincian tersedia untuk kategori ini.</td>
              </tr>
            </tbody>
          </v-table>
        </v-card-text>
        
        <v-divider></v-divider>
        
        <v-card-actions class="pa-4 bg-light">
          <v-spacer></v-spacer>
          <v-btn variant="flat" color="primary" rounded="lg" @click="reconDetailDialog = false">Tutup</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    </v-container>
    </v-main>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import api from '../api'
import StatusChip from '../components/Sp2dStatusChip.vue'
import Sidebar from '../components/Sidebar.vue'
import Navbar from '../components/Navbar.vue'

const selectedMonth = ref(new Date().getMonth() + 1)
const selectedYear = ref(new Date().getFullYear())
const tppReconMode = ref('bruto')
const search = ref('')
const searchDetail = ref('')
const searchRecon = ref('')
const selectedJenisGaji = ref(null)
const sortBy = ref('nama_skpd')
const sortDesc = ref(false)
const reconPage = ref(1)
const reconItemsPerPage = ref(15)
const loading = ref(false)
const uploading = ref(false)
const isDragging = ref(false)
const viewMode = ref('summary')
const items = ref([])
const transactions = ref([])
const reconData = ref([])
const skpds = ref([])
const isEdit = ref(false)

// Recon Detail State
const reconDetailDialog = ref(false)
const detailLoading = ref(false)
const reconDetails = ref([])
const activeReconSp2d = ref(null)

const fetchReconDetail = async (row) => {
    if (!row.id) return
    
    activeReconSp2d.value = {
        nomor: row.sipd.nomor_sp2d,
        nominal: row.sipd.netto,
        jenis: row.sipd.jenis_data
    }
    
    detailLoading.value = true
    reconDetailDialog.value = true
    reconDetails.value = []

    try {
        const response = await api.get(`/sp2d/recon/${row.id}`)
        reconDetails.value = response.data.details
    } catch (err) {
        showSnackbar('Gagal mengambil detail personil', 'error')
        reconDetailDialog.value = false
    } finally {
        detailLoading.value = false
    }
}

const openEditFromRecon = (row) => {
  isEdit.value = true
  // Extract base type (PNS, PPPK, TPP) from potentially longer strings like PNS-INDUK
  let baseType = row.sipd.jenis_data || 'PNS'
  if (baseType.includes('PNS')) baseType = 'PNS'
  else if (baseType.includes('PPPK') || baseType.includes('P3K')) baseType = 'PPPK'
  else if (baseType.includes('TPP')) baseType = 'TPP'

  editItem.value = { 
    id: row.id,
    nomor_sp2d: row.sipd.nomor_sp2d,
    netto: row.sipd.netto,
    jenis_data: baseType,
    tanggal_sp2d: row.sipd.tanggal_sp2d,
    skpd_id: row.simgaji.id_skpd, // Not used in edit but good for context
    keterangan: row.sipd.keterangan
  }
  editDialog.value = true
}

// Resolve Mapping Logic
const resolveDialog = ref(false)
const resolveItem = ref(null)
const selectedSkpdId = ref(null)
const mappingLoading = ref(false)

const openResolveDialog = (item) => {
  resolveItem.value = item
  selectedSkpdId.value = null
  resolveDialog.value = true
}

const saveMapping = async () => {
    if (!selectedSkpdId.value || !resolveItem.value) return
    
    mappingLoading.value = true
    try {
        await api.post('/skpd-mapping', {
            source_name: resolveItem.value.nama_skpd_sipd,
            skpd_id: selectedSkpdId.value,
            type: 'all'
        })
        showSnackbar('Mapping berhasil disimpan dan data diperbarui')
        resolveDialog.value = false
        fetchData()
    } catch (err) {
        showSnackbar('Gagal menyimpan mapping', 'error')
    } finally {
        mappingLoading.value = false
    }
}

const toggleSort = (key) => {
  if (sortBy.value === key) {
    sortDesc.value = !sortDesc.value
  } else {
    sortBy.value = key
    sortDesc.value = false
  }
}

const getSortIcon = (key) => {
  if (sortBy.value !== key) return 'mdi-minus-variant'
  return sortDesc.value ? 'mdi-sort-descending' : 'mdi-sort-ascending'
}

const filteredReconData = computed(() => {
  let data = [...reconData.value]
  
  if (searchRecon.value) {
    const s = searchRecon.value.toLowerCase()
    data = data.filter(row => 
      (row.simgaji?.nama_skpd?.toLowerCase().includes(s)) ||
      (row.sipd?.nama_skpd?.toLowerCase().includes(s))
    )
  }

  // Apply Sorting
  data.sort((a, b) => {
    let valA, valB
    
    if (sortBy.value === 'nama_skpd') {
      valA = a.simgaji.nama_skpd
      valB = b.simgaji.nama_skpd
    } else if (sortBy.value === 'jenis_gaji') {
      valA = a.simgaji.jenis_gaji || ''
      valB = b.simgaji.jenis_gaji || ''
    } else {
      // Numbers (brutto, netto, etc)
      valA = a.simgaji[sortBy.value] || 0
      valB = b.simgaji[sortBy.value] || 0
    }

    if (valA < valB) return sortDesc.value ? 1 : -1
    if (valA > valB) return sortDesc.value ? -1 : 1
    return 0
  })

  return data
})

const paginatedReconData = computed(() => {
  const start = (reconPage.value - 1) * reconItemsPerPage.value
  const end = start + reconItemsPerPage.value
  return filteredReconData.value.slice(start, end)
})
const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')
const snackbarTitle = ref('')
const exporting = ref(false)

const exportExcel = async () => {
    exporting.value = true
    try {
        const response = await api.get('/sp2d/export-recon', {
            params: {
                bulan: selectedMonth.value,
                tahun: selectedYear.value,
                jenis_gaji: selectedJenisGaji.value || undefined
            },
            responseType: 'blob'
        })
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `rekon-sp2d-${selectedMonth.value}-${selectedYear.value}.xlsx`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        showSnackbar('Excel berhasil di-unduh')
    } catch (err) {
        console.error(err)
        showSnackbar('Gagal mengunduh Excel', 'error')
    } finally {
        exporting.value = false
    }
}

const showComingSoon = (feature) => {
  snackbarTitle.value = feature
  snackbarText.value = 'Fitur ini akan segera hadir.'
  snackbarColor.value = 'primary'
  snackbar.value = true
}

// Edit Logic
const editDialog = ref(false)
const saving = ref(false)
const isFormValid = ref(false)
const editItem = ref({ id: null, nomor_sp2d: '', netto: 0, jenis_data: '' })

const months = [
  { title: 'Januari', value: 1 }, { title: 'Februari', value: 2 },
  { title: 'Maret', value: 3 }, { title: 'April', value: 4 },
  { title: 'Mei', value: 5 }, { title: 'Juni', value: 6 },
  { title: 'Juli', value: 7 }, { title: 'Agustus', value: 8 },
  { title: 'September', value: 9 }, { title: 'Oktober', value: 10 },
  { title: 'November', value: 11 }, { title: 'Desember', value: 12 }
]
const years = [2024, 2025, 2026]

// Import Preview & Type selection
const importDialog = ref(false)
const importFile = ref(null)
const importTargetType = ref('AUTO') // AUTO, PNS-INDUK, PPPK-INDUK, PPPK-PW-INDUK, TPP-INDUK
const importPreview = ref([])
const importSummary = ref(null)
const previewLoading = ref(false)
const importTargetTypes = [
  { title: 'Deteksi Otomatis', value: 'AUTO' },
  { title: 'Gaji PNS (Induk)', value: 'PNS-INDUK' },
  { title: 'Gaji PPPK (Induk)', value: 'PPPK-INDUK' },
  { title: 'Gaji PPPK-PW', value: 'PPPK-PW-INDUK' },
  { title: 'TPP / Tukin', value: 'TPP-INDUK' },
  { title: 'Register Potongan (Pajak/IWP)', value: 'POTONGAN' },
]

const openImportDialog = (file) => {
  importFile.value = file
  importDialog.value = true
  fetchImportPreview()
}

const fetchImportPreview = async () => {
  if (!importFile.value) return
  
  const formData = new FormData()
  formData.append('file', importFile.value)
  formData.append('bulan', selectedMonth.value)
  formData.append('tahun', selectedYear.value)
  formData.append('preview', '1')
  if (importTargetType.value !== 'AUTO') {
    formData.append('target_type', importTargetType.value)
  }

  previewLoading.value = true
  try {
    const response = await api.post('/sp2d/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    importPreview.value = response.data.preview
    importSummary.value = response.data.summary
  } catch (err) {
    showSnackbar(err.response?.data?.message || 'Gagal mengambil preview', 'error')
    importDialog.value = false
  } finally {
    previewLoading.value = false
  }
}

const processImport = async () => {
  if (!importFile.value) return

  const formData = new FormData()
  formData.append('file', importFile.value)
  formData.append('bulan', selectedMonth.value)
  formData.append('tahun', selectedYear.value)
  if (importTargetType.value !== 'AUTO') {
    formData.append('target_type', importTargetType.value)
  }
  
  uploading.value = true
  try {
    const response = await api.post('/sp2d/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    showSnackbar(response.data.message || 'File berhasil diunggah')
    importDialog.value = false
    fetchData()
  } catch (err) {
    showSnackbar(err.response?.data?.message || 'Gagal mengunggah file', 'error')
  } finally {
    uploading.value = false
  }
}

const headers = [
  { title: 'Unit SKPD', key: 'nama_skpd', align: 'start', sortable: true },
  { title: 'Kategori Gaji', key: 'jenis_gaji', align: 'center', sortable: true },
  { title: 'Gaji PNS', key: 'pns', align: 'center', sortable: false },
  { title: 'Gaji PPPK', key: 'pppk', align: 'center', sortable: false },
  { title: 'Gaji PPPK-PW', key: 'pppk_pw', align: 'center', sortable: false },
  { title: 'TPP', key: 'tpp', align: 'center', sortable: false },
]

const detailHeaders = [
  { title: 'No. SP2D', key: 'nomor_sp2d', width: '180px' },
  { title: 'Tanggal', key: 'tanggal_sp2d' },
  { title: 'SKPD (SIPD)', key: 'nama_skpd_sipd' },
  { title: 'Kategori', key: 'jenis_data', align: 'center' },
  { title: 'Nilai Netto', key: 'netto', align: 'end' },
  { title: 'Aksi', key: 'actions', align: 'end', sortable: false },
]



watch([selectedMonth, selectedYear, tppReconMode], () => {
  if (viewMode.value === 'summary') fetchData()
  else if (viewMode.value === 'recon') fetchReconData()
})

watch(viewMode, (newVal) => {
  if (newVal === 'summary') fetchData()
  else if (newVal === 'details') fetchTransactions()
  else if (newVal === 'recon') fetchReconData()
})

const fetchData = async () => {
  loading.value = true
  try {
    const params = {
      bulan: selectedMonth.value,
      tahun: selectedYear.value,
      jenis_gaji: selectedJenisGaji.value || undefined,
      tpp_recon_mode: tppReconMode.value
    }
    const res = await api.get('/sp2d/status', { params })
    items.value = res.data.data
  } catch (e) {
    showSnackbar('Gagal memuat data status', 'error')
  } finally {
    loading.value = false
  }
}

const fetchTransactions = async () => {
  loading.value = true
  try {
    const response = await api.get('/sp2d/transactions', {
      params: { 
        bulan: selectedMonth.value, 
        tahun: selectedYear.value,
        jenis_gaji: selectedJenisGaji.value || undefined
      }
    })
    transactions.value = response.data.data
  } catch (err) {
    console.error(err)
    showSnackbar('Gagal mengambil data transaksi', 'error')
  } finally {
    loading.value = false
  }
}

const fetchReconData = async () => {
  loading.value = true
  try {
    const params = {
      bulan: selectedMonth.value,
      tahun: selectedYear.value,
      jenis_gaji: selectedJenisGaji.value || undefined,
      tpp_recon_mode: tppReconMode.value
    }
    const res = await api.get('/sp2d/recon', { params })
    reconData.value = res.data.data
  } catch (e) {
    showSnackbar('Gagal memuat data rekonsiliasi', 'error')
  } finally {
    loading.value = false
  }
}

const openCreateDialog = () => {
    isEdit.value = false
    editItem.value = { 
        id: null, 
        nomor_sp2d: '', 
        netto: 0, 
        jenis_data: 'PNS',
        tanggal_sp2d: new Date().toISOString().substr(0, 10),
        skpd_id: null,
        keterangan: 'Input Manual'
    }
    fetchSkpds()
    editDialog.value = true
}

const openEditDialog = (item) => {
  isEdit.value = true
  editItem.value = { ...item }
  editDialog.value = true
}

const fetchSkpds = async () => {
    if (skpds.value.length > 0) return
    try {
        const response = await api.get('/skpd')
        skpds.value = response.data.data
    } catch (err) {
        console.error(err)
    }
}

const saveTransaction = async () => {
    if (isEdit.value) {
        await updateTransaction()
    } else {
        await createTransaction()
    }
}

const createTransaction = async () => {
    saving.value = true
    try {
        await api.post('/sp2d/realizations', {
            ...editItem.value,
            bulan: selectedMonth.value,
            tahun: selectedYear.value
        })
        showSnackbar('Data manual berhasil ditambahkan')
        editDialog.value = false
        fetchData()
    } catch (err) {
        showSnackbar(err.response?.data?.message || 'Gagal menambahkan data', 'error')
    } finally {
        saving.value = false
    }
}

const updateTransaction = async () => {
  saving.value = true
  try {
    await api.put(`/sp2d/realizations/${editItem.value.id}`, {
      netto: editItem.value.netto,
      nomor_sp2d: editItem.value.nomor_sp2d,
      jenis_data: editItem.value.jenis_data
    })
    showSnackbar('Data berhasil diperbarui')
    editDialog.value = false
    fetchData()
  } catch (err) {
    showSnackbar('Gagal memperbarui data', 'error')
  } finally {
    saving.value = false
  }
}

const confirmDelete = async (item) => {
  if (confirm('Apakah Anda yakin ingin menghapus data realisasi ini?')) {
    try {
      await api.delete(`/sp2d/realizations/${item.id}`)
      showSnackbar('Data berhasil dihapus')
      fetchData()
    } catch (err) {
      showSnackbar('Gagal menghapus data', 'error')
    }
  }
}

const handleFileSelect = (e) => {
  const file = e.target.files[0]
  if (file) openImportDialog(file)
}

const handleDrop = (e) => {
  isDragging.value = false
  const file = e.dataTransfer.files[0]
  if (file) openImportDialog(file)
}

const uploadFile = async () => {
  if (!importFile.value) return

  const formData = new FormData()
  formData.append('file', importFile.value)
  formData.append('bulan', selectedMonth.value)
  formData.append('tahun', selectedYear.value)
  if (importTargetType.value !== 'AUTO') {
    formData.append('target_type', importTargetType.value)
  }
  
  uploading.value = true
  try {
    const response = await api.post('/sp2d/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    showSnackbar(response.data.message || 'Data berhasil diimpor')
    importDialog.value = false
    fetchData()
  } catch (err) {
    showSnackbar(err.response?.data?.message || 'Gagal mengunggah file', 'error')
  } finally {
    uploading.value = false
    // Clear input
    const input = document.querySelector('input[type="file"]')
    if (input) input.value = ''
    importFile.value = null
  }
}

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

const formatCurrency = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(val || 0)
}

const getTypeColor = (type) => {
  if (!type) return 'grey'
  const t = type.toUpperCase()
  if (t.includes('PNS')) return 'blue'
  if (t.includes('PPPK') || t.includes('P3K')) return 'orange'
  if (t.includes('TPP')) return 'teal'
  if (t.includes('INDUK')) return 'indigo'
  if (t.includes('SUSULAN')) return 'deep-purple'
  if (t.includes('KEKURANGAN')) return 'amber'
  if (t.includes('TERUSAN')) return 'brown'
  return 'grey'
}

const showSnackbar = (text, color = 'success') => {
  snackbarTitle.value = ''
  snackbarText.value = text
  snackbarColor.value = color
  snackbar.value = true
}

onMounted(() => {
  fetchData()
})
const summaryTotals = computed(() => {
  const totals = {
    pns: { netto: 0, internal: 0, count: 0 },
    pppk: { netto: 0, internal: 0, count: 0 },
    pppk_pw: { netto: 0, internal: 0, count: 0 },
    tpp: { netto: 0, internal: 0, count: 0 }
  }
  
  items.value.forEach(item => {
    ['pns', 'pppk', 'pppk_pw', 'tpp'].forEach(key => {
      totals[key].netto += item[key]?.netto || 0
      totals[key].internal += item[key]?.internal_amount || 0
      totals[key].count += item[key]?.count || 0
    })
  })
  return totals
})

const reconTotals = computed(() => {
  const t = {
    simgaji_brutto: 0, simgaji_potongan: 0, simgaji_netto: 0,
    sipd_brutto: 0, sipd_potongan: 0, sipd_netto: 0,
    gaji_pns: 0, gaji_pppk: 0, tpp_pns: 0, tpp_pppk: 0,
    emp_count: 0
  }
  
  paginatedReconData.value.forEach(row => {
    t.simgaji_brutto += row.simgaji.brutto || 0
    t.simgaji_potongan += row.simgaji.potongan || 0
    t.simgaji_netto += row.simgaji.netto || 0
    t.gaji_pns += row.simgaji.gaji_pns || 0
    t.gaji_pppk += row.simgaji.gaji_pppk || 0
    t.tpp_pns += row.simgaji.tpp_pns || 0
    t.tpp_pppk += row.simgaji.tpp_pppk || 0
    t.emp_count += row.simgaji.emp_count || 0
    
    t.sipd_brutto += row.sipd.brutto || 0
    t.sipd_potongan += row.sipd.potongan || 0
    t.sipd_netto += row.sipd.netto || 0
  })
  return t
})
</script>

<style scoped>
.modern-dashboard {
  background-color: rgb(var(--v-theme-background)) !important;
  min-height: 100vh;
}

.glass-card {
  background-color: rgb(var(--v-theme-surface)) !important;
  border: 1px solid rgba(var(--v-border-color), 0.08) !important;
}

.upload-zone {
  border: 2px dashed rgba(var(--v-border-color), 0.2);
  cursor: pointer;
  transition: all 0.3s ease;
}

.cursor-pointer {
  cursor: pointer;
}

.upload-zone:hover, .upload-zone.is-dragging, .upload-zone-compact:hover, .upload-zone-compact.is-dragging {
  border-color: rgb(var(--v-theme-primary));
  background-color: rgba(var(--v-theme-primary), 0.05);
}
/*
- **Full-Screen Table**: Redesigned the layout to be full screen. Controls (Period, Upload, and View Mode) are now placed in a compact top-bar, allowing the table to expand to the full width of the container.
- **Dark Mode Support**: Replaced hardcoded light colors with theme-aware CSS. Table headers now automatically adapt their background and text colors for perfect readability in both light and dark modes.
- **Sidebar Integration**: The page is now correctly wrapped in the main application layout (`v-app` and `v-main`).
*/
.upload-zone-compact {
  border: 1px dashed rgba(var(--v-border-color), 0.3);
  transition: all 0.2s ease;
  min-height: 48px;
}

.search-bar-300 {
  width: 450px !important;
  max-width: 50% !important;
  flex: none !important;
}

.gap-1 {
  gap: 4px;
}

.gap-4 {
  gap: 16px;
}
.items-per-page-select {
  font-size: 0.75rem !important;
}
.items-per-page-select :deep(.v-field__input) {
  padding-top: 0 !important;
  min-height: 0 !important;
}
.border-top {
  border-top: 1px solid rgba(var(--v-border-color), 0.08);
}

.border-bottom {
  border-bottom: 1px solid rgba(var(--v-border-color), 0.08);
}

.bg-surface-variant-light {
  background-color: rgba(var(--v-theme-surface-variant), 0.05);
}

.recon-table-container {
  max-width: 100%;
  overflow-x: auto;
}

.recon-table {
  border-collapse: collapse;
}

.recon-table th, .recon-table td {
  border: 1px solid rgba(var(--v-border-color), 0.1) !important;
  white-space: nowrap !important;
}

.header-group-row th {
  font-size: 0.875rem !important;
  font-weight: 800 !important;
  letter-spacing: 0.05rem;
}

.v-theme--light .simgaji-header {
  background-color: #e8f5e9 !important;
  color: #1b5e20 !important;
}

.v-theme--dark .simgaji-header {
  background-color: #1b5e20 !important;
  color: #c8e6c9 !important;
}

.v-theme--light .sipd-header {
  background-color: #e3f2fd !important;
  color: #0d47a1 !important;
}

.v-theme--dark .sipd-header {
  background-color: #0d47a1 !important;
  color: #bbdefb !important;
}

.header-main-row th, .header-sub-row th {
  background-color: rgba(var(--v-theme-on-surface), 0.05) !important;
  color: rgb(var(--v-theme-on-surface)) !important;
  font-size: 0.75rem !important;
  font-weight: 700 !important;
}

.border-right {
  border-right: 1px solid rgba(var(--v-border-color), 0.1) !important;
}

/* Striped Row Styling */
.recon-table :deep(tbody tr:nth-of-type(even)) {
  background-color: rgba(var(--v-theme-on-surface), 0.03) !important;
}

.recon-table :deep(tbody tr:hover) {
  background-color: rgba(var(--v-theme-primary), 0.08) !important;
}

.truncate {
  max-width: 250px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
