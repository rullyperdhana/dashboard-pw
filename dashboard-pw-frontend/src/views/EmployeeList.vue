<template>
  <v-app class="modern-bg">
    <Navbar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />
    <Sidebar @show-coming-soon="(msg) => alert('Coming soon: ' + msg)" />

    <v-main>
      <v-container fluid class="pa-8">
        <v-row>
          <!-- Statistics Sidebar -->
          <v-col cols="12" md="3">
            <div class="sticky-top">
              <v-card class="glass-card rounded-xl mb-6 pa-4" elevation="0">
                <v-card-text>
                  <div class="text-overline text-grey mb-4">Quick Stats</div>
                  
                  <div class="stat-item mb-4">
                    <div class="d-flex align-center justify-space-between">
                      <span class="text-body-2 text-grey">Total Pegawai</span>
                      <v-chip size="small" color="primary" variant="flat">{{ stats.total }}</v-chip>
                    </div>
                  </div>

                  <div class="stat-item mb-4">
                    <div class="d-flex align-center justify-space-between">
                      <span class="text-body-2 text-grey">Laki-laki</span>
                      <v-chip size="small" color="blue" variant="tonal" class="font-weight-bold">
                        {{ stats.male }}
                      </v-chip>
                    </div>
                  </div>

                  <div class="stat-item">
                    <div class="d-flex align-center justify-space-between">
                      <span class="text-body-2 text-grey">Perempuan</span>
                      <v-chip size="small" color="pink" variant="tonal" class="font-weight-bold">
                        {{ stats.female }}
                      </v-chip>
                    </div>
                  </div>
                </v-card-text>
              </v-card>

              <v-card class="glass-card rounded-xl pa-4" elevation="0">
                <v-card-text>
                  <div class="text-overline text-grey mb-4">Filter</div>
                  <v-select
                    v-model="genderFilter"
                    label="Jenis Kelamin"
                    :items="['Semua', 'Laki-laki', 'Perempuan']"
                    variant="underlined"
                    density="compact"
                    class="mb-4"
                  ></v-select>
                  <v-select
                    v-model="selectedSkpd"
                    label="SKPD"
                    :items="skpdOptions"
                    item-title="nama_skpd"
                    item-value="id_skpd"
                    variant="underlined"
                    density="compact"
                    clearable
                  ></v-select>
                  <v-select
                    v-model="statusFilter"
                    label="Status Pegawai"
                    :items="['Semua', 'Aktif', 'Pensiun', 'Keluar', 'Diberhentikan', 'Meninggal']"
                    variant="underlined"
                    density="compact"
                    clearable
                  ></v-select>
                </v-card-text>
              </v-card>
            </div>
          </v-col>

          <!-- Main Directory Table -->
          <v-col cols="12" md="9">
            <v-card class="glass-card rounded-xl overflow-hidden" elevation="0">
              <v-toolbar color="transparent" flat class="px-6 py-4">
                <v-toolbar-title class="font-weight-bold text-h6">Daftar Pegawai</v-toolbar-title>
                <v-spacer></v-spacer>
                <v-text-field
                  v-model="search"
                  prepend-inner-icon="mdi-magnify"
                  label="Cari nama atau NIP..."
                  variant="solo-filled"
                  density="comfortable"
                  rounded="pill"
                  hide-details
                  flat
                  style="max-width: 300px;"
                  class="mr-4"
                  @keyup.enter="fetchEmployees"
                ></v-text-field>
                <v-btn color="success" variant="tonal" size="small" class="mr-2" prepend-icon="mdi-microsoft-excel" @click="exportEmployees('excel')" :loading="exportLoading === 'excel'">EXCEL</v-btn>
                <v-btn color="error" variant="tonal" size="small" class="mr-2" prepend-icon="mdi-file-pdf-box" @click="exportEmployees('pdf')" :loading="exportLoading === 'pdf'">PDF</v-btn>
                <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
                  TAMBAH
                </v-btn>
              </v-toolbar>

              <v-data-table
                :headers="headers"
                :items="filteredEmployees"
                :loading="loading"
                :items-per-page="15"
                class="modern-table"
                hover
              >
                <!-- Name Column -->
                <template v-slot:item.nama="{ item }">
                  <div class="d-flex align-center py-2">
                    <v-avatar size="36" color="primary-lighten-5" class="mr-3">
                      <v-icon color="primary" size="small">mdi-account</v-icon>
                    </v-avatar>
                    <div>
                      <div class="font-weight-bold text-body-2">{{ item.nama }}</div>
                      <div class="text-caption text-grey">{{ item.nip }}</div>
                    </div>
                  </div>
                </template>

                <!-- Gender -->
                <template v-slot:item.jk="{ item }">
                  <v-chip 
                    :color="item.jk?.includes('LAKI') ? 'blue-lighten-1' : 'pink-lighten-1'" 
                    variant="tonal" 
                    size="x-small"
                  >
                    {{ item.jk?.includes('LAKI') ? 'L' : 'P' }}
                  </v-chip>
                </template>

                <!-- Golongan -->
                <template v-slot:item.golru="{ item }">
                  <span class="text-body-2">{{ item.golru || '-' }}</span>
                </template>

                <!-- SKPD -->
                <template v-slot:item.skpd="{ item }">
                  <div class="text-body-2 text-truncate" style="max-width: 180px;">
                    {{ item.skpd?.nama_skpd || item.upt || '-' }}
                  </div>
                </template>

                <!-- Sumber Dana -->
                <template v-slot:item.sumber_dana="{ item }">
                  <v-chip :color="item.sumber_dana === 'BLUD' ? 'orange' : 'blue'" size="x-small" variant="tonal">
                    {{ item.sumber_dana || 'APBD' }}
                  </v-chip>
                </template>

                <!-- Gaji Pokok -->
                <template v-slot:item.gapok="{ item }">
                  <span class="font-weight-medium">{{ formatCurrency(item.gapok) }}</span>
                </template>

                <!-- Actions -->
                <template v-slot:item.actions="{ item }">
                  <v-btn icon size="small" variant="text" color="primary" @click.stop="showDetails(item)">
                    <v-icon size="18">mdi-eye</v-icon>
                  </v-btn>
                  <v-btn icon size="small" variant="text" color="warning" @click.stop="openEditDialog(item)">
                    <v-icon size="18">mdi-pencil</v-icon>
                  </v-btn>
                  <v-btn icon size="small" variant="text" color="error" @click.stop="confirmDelete(item)">
                    <v-icon size="18">mdi-delete</v-icon>
                  </v-btn>
                </template>

                <template v-slot:no-data>
                  <div class="text-center py-12">
                    <v-icon size="64" color="grey-lighten-3">mdi-database-off</v-icon>
                    <div class="text-grey mt-2">Tidak ada data pegawai.</div>
                  </div>
                </template>
                
                <template v-slot:loading>
                  <v-skeleton-loader type="table-row-divider@10"></v-skeleton-loader>
                </template>
              </v-data-table>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </v-main>

    <!-- Create/Edit Dialog -->
    <v-dialog v-model="formDialog" max-width="900px" scrollable persistent>
      <v-card class="rounded-xl">
        <v-toolbar color="primary" dark>
          <v-toolbar-title>{{ isEditing ? 'Edit Pegawai' : 'Tambah Pegawai Baru' }}</v-toolbar-title>
          <v-spacer></v-spacer>
          <v-btn icon @click="formDialog = false"><v-icon>mdi-close</v-icon></v-btn>
        </v-toolbar>
        
        <v-card-text class="pa-6">
          <v-form ref="formRef" v-model="formValid">
            <v-row dense>
              <!-- Data Pribadi -->
              <v-col cols="12"><div class="text-overline text-primary mb-2">Data Pribadi</div></v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.nip" label="NIP *" variant="outlined" density="compact" :rules="[rules.required]" :disabled="isEditing"></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.nik" label="NIK" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.nama" label="Nama Lengkap *" variant="outlined" density="compact" :rules="[rules.required]"></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-select v-model="form.jk" label="Jenis Kelamin *" :items="['LAKI - LAKI', 'PEREMPUAN']" variant="outlined" density="compact" :rules="[rules.required]"></v-select>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="form.tempat_lahir" label="Tempat Lahir" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="form.tgl_lahir" label="Tanggal Lahir" type="date" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-select v-model="form.agama" label="Agama" :items="['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu']" variant="outlined" density="compact"></v-select>
              </v-col>
              <v-col cols="12" md="4">
                <v-select v-model="form.status" label="Status" :items="['Aktif', 'Pensiun', 'Keluar', 'Diberhentikan', 'Meninggal']" variant="outlined" density="compact"></v-select>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="form.no_hp" label="No. HP" variant="outlined" density="compact"></v-text-field>
              </v-col>

              <!-- Kepegawaian -->
              <v-col cols="12" class="mt-4"><div class="text-overline text-primary mb-2">Data Kepegawaian</div></v-col>
              <v-col cols="12" md="6">
                <v-select v-model="form.idskpd" label="SKPD *" :items="skpdOptions" item-title="nama_skpd" item-value="id_skpd" variant="outlined" density="compact" :rules="[rules.required]"></v-select>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.upt" label="UPT" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="form.golru" label="Golongan/Ruang" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="form.tmt_golru" label="TMT Golongan" type="date" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="form.eselon" label="Eselon" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.jabatan" label="Jabatan" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="form.jenis_jabatan" label="Jenis Jabatan" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model="form.tmt_jabatan" label="TMT Jabatan" type="date" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model.number="form.mk_thn" label="Masa Kerja (Tahun)" type="number" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="3">
                <v-text-field v-model.number="form.mk_bln" label="Masa Kerja (Bulan)" type="number" variant="outlined" density="compact"></v-text-field>
              </v-col>

              <!-- Pendidikan -->
              <v-col cols="12" class="mt-4"><div class="text-overline text-primary mb-2">Pendidikan</div></v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="form.tk_ijazah" label="Tingkat Ijazah" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="form.nm_pendidikan" label="Nama Pendidikan" variant="outlined" density="compact"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model="form.th_lulus" label="Tahun Lulus" variant="outlined" density="compact"></v-text-field>
              </v-col>

              <!-- Gaji -->
              <v-col cols="12" class="mt-4"><div class="text-overline text-primary mb-2">Data Gaji</div></v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model.number="form.gapok" label="Gaji Pokok" type="number" variant="outlined" density="compact" prefix="Rp"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model.number="form.tunjangan" label="Tunjangan" type="number" variant="outlined" density="compact" prefix="Rp"></v-text-field>
              </v-col>
              <v-col cols="12" md="4">
                <v-text-field v-model.number="form.potongan" label="Potongan" type="number" variant="outlined" density="compact" prefix="Rp"></v-text-field>
              </v-col>

              <!-- Keterangan -->
              <v-col cols="12" class="mt-4">
                <v-textarea v-model="form.keterangan" label="Keterangan" variant="outlined" density="compact" rows="2"></v-textarea>
              </v-col>
            </v-row>
          </v-form>
        </v-card-text>

        <v-card-actions class="pa-6 pt-0">
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="formDialog = false">BATAL</v-btn>
          <v-btn color="primary" variant="flat" @click="saveEmployee" :loading="saving" :disabled="!formValid">
            {{ isEditing ? 'SIMPAN PERUBAHAN' : 'TAMBAH PEGAWAI' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Detail Dialog -->
    <v-dialog v-model="detailDialog" max-width="700px" scrollable>
      <v-card class="rounded-xl overflow-hidden glass-card">
        <v-img height="100" src="https://cdn.vuetifyjs.com/images/backgrounds/vbanner.jpg" cover class="d-flex align-end pa-4">
          <v-avatar size="70" color="white" class="elevation-4" style="border: 3px solid white; position: absolute; bottom: -35px; left: 24px;">
            <v-icon size="35" color="primary">mdi-account</v-icon>
          </v-avatar>
        </v-img>
        
        <v-card-text class="pa-6 pt-12">
          <div class="mb-4 pt-2">
            <h2 class="text-h5 font-weight-black">{{ selectedEmployee?.nama }}</h2>
            <div class="text-body-1 text-primary font-weight-bold">{{ selectedEmployee?.nip }}</div>
            <v-chip :color="selectedEmployee?.jk?.includes('LAKI') ? 'blue' : 'pink'" size="small" variant="tonal" class="mt-2">
              {{ selectedEmployee?.jk }}
            </v-chip>
          </div>

          <v-divider class="mb-4"></v-divider>

          <v-row dense>
            <v-col cols="6" class="mb-3">
              <div class="text-caption text-grey">NIK</div>
              <div class="text-body-2 font-weight-medium">{{ selectedEmployee?.nik || '-' }}</div>
            </v-col>
            <v-col cols="6" class="mb-3">
              <div class="text-caption text-grey">Tempat/Tgl Lahir</div>
              <div class="text-body-2 font-weight-medium">{{ selectedEmployee?.tempat_lahir || '-' }}, {{ formatDate(selectedEmployee?.tgl_lahir) }}</div>
            </v-col>
            <v-col cols="6" class="mb-3">
              <div class="text-caption text-grey">Agama</div>
              <div class="text-body-2 font-weight-medium">{{ selectedEmployee?.agama || '-' }}</div>
            </v-col>
            <v-col cols="6" class="mb-3">
              <div class="text-caption text-grey">No. HP</div>
              <div class="text-body-2 font-weight-medium">{{ selectedEmployee?.no_hp || '-' }}</div>
            </v-col>
            <v-col cols="12" class="mb-3">
              <div class="text-caption text-grey">SKPD / UPT</div>
              <div class="text-body-2 font-weight-medium">{{ selectedEmployee?.skpd?.nama_skpd || '-' }} / {{ selectedEmployee?.upt || '-' }}</div>
            </v-col>
            <v-col cols="6" class="mb-3">
              <div class="text-caption text-grey">Jabatan</div>
              <div class="text-body-2 font-weight-medium">{{ selectedEmployee?.jabatan || '-' }}</div>
            </v-col>
            <v-col cols="6" class="mb-3">
              <div class="text-caption text-grey">Golongan</div>
              <div class="text-body-2 font-weight-medium">{{ selectedEmployee?.golru || '-' }} ({{ selectedEmployee?.eselon || '-' }})</div>
            </v-col>
            <v-col cols="6" class="mb-3">
              <div class="text-caption text-grey">Masa Kerja</div>
              <div class="text-body-2 font-weight-medium">{{ selectedEmployee?.mk_thn || 0 }} tahun {{ selectedEmployee?.mk_bln || 0 }} bulan</div>
            </v-col>
            <v-col cols="6" class="mb-3">
              <div class="text-caption text-grey">Pendidikan</div>
              <div class="text-body-2 font-weight-medium">{{ selectedEmployee?.tk_ijazah || '-' }} - {{ selectedEmployee?.nm_pendidikan || '-' }}</div>
            </v-col>
            <v-col cols="6" class="mb-3">
              <div class="text-caption text-grey">Gaji Pokok</div>
              <div class="text-body-2 font-weight-bold text-success">{{ formatCurrency(selectedEmployee?.gapok) }}</div>
            </v-col>
            <v-col cols="6" class="mb-3">
              <div class="text-caption text-grey">Sumber Dana</div>
              <v-chip :color="selectedEmployee?.sumber_dana === 'BLUD' ? 'orange' : 'blue'" size="small" variant="tonal">
                {{ selectedEmployee?.sumber_dana || 'APBD' }}
              </v-chip>
            </v-col>
            <v-col cols="6" class="mb-3">
              <div class="text-caption text-grey">Tunjangan</div>
              <div class="text-body-2 font-weight-medium">{{ formatCurrency(selectedEmployee?.tunjangan) }}</div>
            </v-col>
          </v-row>

          <!-- Status Pegawai -->
          <v-divider class="my-4"></v-divider>
          <div class="text-overline text-primary mb-2">Status Pegawai</div>
          <v-chip :color="selectedEmployee?.status === 'Aktif' ? 'success' : 'warning'" variant="tonal" class="mb-4">
            {{ selectedEmployee?.status || 'Aktif' }}
          </v-chip>

          <!-- Dokumen SK -->
          <v-divider class="my-4"></v-divider>
          <div class="d-flex align-center justify-space-between mb-3">
            <div class="text-overline text-primary">Dokumen SK</div>
            <v-btn size="small" color="primary" variant="tonal" prepend-icon="mdi-upload" @click="showUploadForm = !showUploadForm">
              Upload SK
            </v-btn>
          </div>

          <!-- Upload Form -->
          <v-expand-transition>
            <v-card v-if="showUploadForm" variant="outlined" class="mb-4 pa-4 rounded-lg">
              <v-row dense>
                <v-col cols="12">
                  <v-file-input
                    v-model="docFile"
                    label="Pilih File (PDF/JPG/PNG, maks 5MB)"
                    accept=".pdf,.jpg,.jpeg,.png"
                    variant="outlined"
                    density="compact"
                    prepend-icon="mdi-file-document"
                  ></v-file-input>
                </v-col>
                <v-col cols="12" md="6">
                  <v-select
                    v-model="docType"
                    :items="['SK Pengangkatan', 'SK Mutasi', 'SK Pensiun', 'SK Kenaikan Pangkat', 'SK Lainnya']"
                    label="Jenis Dokumen"
                    variant="outlined"
                    density="compact"
                  ></v-select>
                </v-col>
                <v-col cols="12" md="6">
                  <v-text-field v-model="docNotes" label="Catatan (opsional)" variant="outlined" density="compact"></v-text-field>
                </v-col>
                <v-col cols="12" class="text-right">
                  <v-btn size="small" variant="text" @click="showUploadForm = false" class="mr-2">Batal</v-btn>
                  <v-btn size="small" color="primary" variant="flat" :loading="uploadingDoc" :disabled="!docFile || !docType" @click="uploadDocument">
                    Upload
                  </v-btn>
                </v-col>
              </v-row>
            </v-card>
          </v-expand-transition>

          <!-- Document List -->
          <v-progress-linear v-if="loadingDocs" indeterminate color="primary" class="mb-2"></v-progress-linear>
          <div v-else-if="employeeDocuments.length === 0" class="text-center py-4">
            <v-icon size="40" color="grey-lighten-2">mdi-file-document-outline</v-icon>
            <div class="text-grey text-body-2 mt-1">Belum ada dokumen SK.</div>
          </div>
          <v-list v-else density="compact" class="rounded-lg" variant="outlined">
            <v-list-item v-for="doc in employeeDocuments" :key="doc.id" class="px-4">
              <template v-slot:prepend>
                <v-icon :color="doc.file_name?.endsWith('.pdf') ? 'red' : 'blue'" size="24">
                  {{ doc.file_name?.endsWith('.pdf') ? 'mdi-file-pdf-box' : 'mdi-file-image' }}
                </v-icon>
              </template>
              <v-list-item-title class="text-body-2 font-weight-medium">{{ doc.type }}</v-list-item-title>
              <v-list-item-subtitle class="text-caption">
                {{ doc.file_name }} · {{ formatDate(doc.created_at) }}
                <span v-if="doc.notes"> · {{ doc.notes }}</span>
              </v-list-item-subtitle>
              <template v-slot:append>
                <v-btn icon size="x-small" variant="text" color="primary" @click="previewDoc(doc)" title="Lihat">
                  <v-icon size="18">mdi-eye</v-icon>
                </v-btn>
                <v-btn icon size="x-small" variant="text" color="error" @click="deleteDoc(doc)" title="Hapus">
                  <v-icon size="18">mdi-delete</v-icon>
                </v-btn>
              </template>
            </v-list-item>
          </v-list>
        </v-card-text>

        <v-card-actions class="pa-6 pt-0">
          <v-spacer></v-spacer>
          <v-btn variant="tonal" color="primary" rounded="xl" @click="detailDialog = false">TUTUP</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Document Preview Dialog -->
    <v-dialog v-model="previewDialog" max-width="900px" scrollable>
      <v-card class="rounded-xl overflow-hidden">
        <v-toolbar color="primary" density="compact">
          <v-toolbar-title class="text-body-1 font-weight-bold">{{ previewTitle }}</v-toolbar-title>
          <v-spacer></v-spacer>
          <v-btn icon variant="text" @click="previewDialog = false">
            <v-icon>mdi-close</v-icon>
          </v-btn>
        </v-toolbar>
        <v-card-text class="pa-0" style="min-height: 500px;">
          <!-- PDF Preview -->
          <iframe
            v-if="previewIsPdf"
            :src="previewUrl"
            style="width: 100%; height: 80vh; border: none;"
          ></iframe>
          <!-- Image Preview -->
          <div v-else class="d-flex justify-center align-center pa-4" style="min-height: 400px; background: #f5f5f5;">
            <img :src="previewUrl" style="max-width: 100%; max-height: 75vh; object-fit: contain; border-radius: 8px;" />
          </div>
        </v-card-text>
      </v-card>
    </v-dialog>

    <!-- Delete Confirmation -->
    <v-dialog v-model="deleteDialog" max-width="400px">
      <v-card class="rounded-xl">
        <v-card-title class="text-h6 pa-6">Konfirmasi Hapus</v-card-title>
        <v-card-text class="px-6">
          Apakah Anda yakin ingin menghapus pegawai <strong>{{ employeeToDelete?.nama }}</strong>?
          <br>Tindakan ini tidak dapat dibatalkan.
        </v-card-text>
        <v-card-actions class="pa-6 pt-0">
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="deleteDialog = false">BATAL</v-btn>
          <v-btn color="error" variant="flat" @click="deleteEmployee" :loading="deleting">HAPUS</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Snackbar -->
    <v-snackbar v-model="snackbar" :timeout="3000" :color="snackbarColor" rounded="lg">
      {{ snackbarMessage }}
    </v-snackbar>
  </v-app>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api'
import Navbar from '../components/Navbar.vue'
import Sidebar from '../components/Sidebar.vue'

const route = useRoute()
const loading = ref(true)
const search = ref('')
const employees = ref([])
const stats = ref({ total: 0, male: 0, female: 0 })
const genderFilter = ref('Semua')
const statusFilter = ref('Semua')
const selectedSkpd = ref(null)
const skpdOptions = ref([])
const exportLoading = ref(null)

// Dialogs
const formDialog = ref(false)
const detailDialog = ref(false)
const deleteDialog = ref(false)
const selectedEmployee = ref(null)
const employeeToDelete = ref(null)
const isEditing = ref(false)
const formRef = ref(null)
const formValid = ref(false)
const saving = ref(false)
const deleting = ref(false)

// Documents
const employeeDocuments = ref([])
const loadingDocs = ref(false)
const showUploadForm = ref(false)
const uploadingDoc = ref(false)
const docFile = ref(null)
const docType = ref(null)
const docNotes = ref('')
const previewDialog = ref(false)
const previewUrl = ref('')
const previewTitle = ref('')
const previewIsPdf = ref(false)

// Snackbar
const snackbar = ref(false)
const snackbarMessage = ref('')
const snackbarColor = ref('success')

// Form
const defaultForm = {
  nip: '', nik: '', nama: '', jk: '', tempat_lahir: '', tgl_lahir: '',
  status: '', no_hp: '', agama: '', golru: '', tmt_golru: '',
  jabatan: '', eselon: '', jenis_jabatan: '', tmt_jabatan: '',
  idskpd: null, upt: '', satker: '', mk_thn: null, mk_bln: null,
  tk_ijazah: '', nm_pendidikan: '', th_lulus: '', keterangan: '',
  gapok: null, tunjangan: null, potongan: null
}
const form = ref({ ...defaultForm })

const rules = {
  required: v => !!v || 'Field ini wajib diisi'
}

const headers = [
  { title: 'PEGAWAI', key: 'nama', sortable: true },
  { title: 'JK', key: 'jk', sortable: false, width: '60px' },
  { title: 'GOLONGAN', key: 'golru', sortable: true, width: '100px' },
  { title: 'JABATAN', key: 'jabatan', sortable: true },
  { title: 'SKPD/UPT', key: 'skpd', sortable: false },
  { title: 'SUMBER DANA', key: 'sumber_dana', sortable: true, width: '120px' },
  { title: 'GAJI POKOK', key: 'gapok', sortable: true, align: 'end' },
  { title: 'AKSI', key: 'actions', sortable: false, width: '140px', align: 'center' },
]

const filteredEmployees = computed(() => employees.value)

const formatCurrency = (value) => {
  if (!value) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value)
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
}

const fetchEmployees = async () => {
  loading.value = true
  try {
    const response = await api.get('/employees', {
      params: { 
        search: search.value,
        skpd_id: route.query.skpd_id || selectedSkpd.value || undefined,
        gender: genderFilter.value,
        status: statusFilter.value,
        per_page: 50
      }
    })
    
    if (response.data.success) {
      employees.value = response.data.data.data || []
      // Use stats from server
      if (response.data.stats) {
        stats.value = response.data.stats
      }
    }
  } catch (err) {
    console.error('Error:', err)
    showSnackbar('Gagal memuat data pegawai', 'error')
  } finally {
    loading.value = false
  }
}

const exportEmployees = async (format) => {
  exportLoading.value = format
  try {
    const params = {
      format: format,
      search: search.value,
      skpd_id: selectedSkpd.value || undefined,
      gender: genderFilter.value,
      status: statusFilter.value
    }
    const response = await api.get('/employees/export', {
      params,
      responseType: 'blob'
    })

    const ext = format === 'pdf' ? 'pdf' : 'xlsx'
    const blob = new Blob([response.data])
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `data_pegawai.${ext}`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
    showSnackbar('Export berhasil!', 'success')
  } catch (err) {
    console.error('Export failed:', err)
    showSnackbar('Export gagal', 'error')
  } finally {
    exportLoading.value = null
  }
}

const fetchSkpds = async () => {
  try {
    const response = await api.get('/skpd')
    if (response.data.success) {
      skpdOptions.value = response.data.data || []
    }
  } catch (err) {
    console.error('Error fetching SKPDs:', err)
  }
}

const showDetails = async (item) => {
  selectedEmployee.value = item
  detailDialog.value = true
  showUploadForm.value = false
  await fetchDocuments(item.id)
}

const fetchDocuments = async (employeeId) => {
  loadingDocs.value = true
  employeeDocuments.value = []
  try {
    const res = await api.get(`/employees/${employeeId}/documents`)
    employeeDocuments.value = res.data.data || []
  } catch (e) {
    console.error('Failed to load documents', e)
  } finally {
    loadingDocs.value = false
  }
}

const uploadDocument = async () => {
  if (!docFile.value || !docType.value || !selectedEmployee.value) return
  uploadingDoc.value = true
  try {
    const formData = new FormData()
    formData.append('file', docFile.value[0] || docFile.value)
    formData.append('type', docType.value)
    if (docNotes.value) formData.append('notes', docNotes.value)
    await api.post(`/employees/${selectedEmployee.value.id}/documents`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    showSnack('Dokumen berhasil diupload', 'success')
    showUploadForm.value = false
    docFile.value = null
    docType.value = null
    docNotes.value = ''
    await fetchDocuments(selectedEmployee.value.id)
  } catch (e) {
    showSnack('Gagal upload: ' + (e.response?.data?.message || e.message), 'error')
  } finally {
    uploadingDoc.value = false
  }
}

const previewDoc = async (doc) => {
  try {
    const res = await api.get(`/employees/${selectedEmployee.value.id}/documents/${doc.id}/download`, {
      responseType: 'blob'
    })
    const blob = new Blob([res.data], { type: res.headers['content-type'] })
    // Revoke previous URL to avoid memory leaks
    if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
    previewUrl.value = URL.createObjectURL(blob)
    previewTitle.value = `${doc.type} - ${doc.file_name}`
    previewIsPdf.value = doc.file_name?.toLowerCase().endsWith('.pdf')
    previewDialog.value = true
  } catch (e) {
    showSnack('Gagal memuat file: ' + (e.response?.data?.message || e.message), 'error')
  }
}

const downloadDoc = async (doc) => {
  try {
    const res = await api.get(`/employees/${selectedEmployee.value.id}/documents/${doc.id}/download`, {
      responseType: 'blob'
    })
    const blob = new Blob([res.data], { type: res.headers['content-type'] })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = doc.file_name
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
  } catch (e) {
    showSnack('Gagal download file', 'error')
  }
}

const deleteDoc = async (doc) => {
  if (!confirm('Hapus dokumen "' + doc.type + '"?')) return
  try {
    await api.delete(`/employees/${selectedEmployee.value.id}/documents/${doc.id}`)
    showSnack('Dokumen berhasil dihapus', 'success')
    await fetchDocuments(selectedEmployee.value.id)
  } catch (e) {
    showSnack('Gagal menghapus dokumen', 'error')
  }
}

const showSnack = (message, color = 'success') => {
  snackbarMessage.value = message
  snackbarColor.value = color
  snackbar.value = true
}

const openCreateDialog = () => {
  isEditing.value = false
  form.value = { ...defaultForm }
  formDialog.value = true
}

const openEditDialog = (item) => {
  isEditing.value = true
  form.value = {
    ...defaultForm,
    ...item,
    idskpd: item.idskpd,
    tgl_lahir: item.tgl_lahir ? item.tgl_lahir.split('T')[0] : '',
    tmt_golru: item.tmt_golru ? item.tmt_golru.split('T')[0] : '',
    tmt_jabatan: item.tmt_jabatan ? item.tmt_jabatan.split('T')[0] : '',
  }
  formDialog.value = true
}

const saveEmployee = async () => {
  saving.value = true
  try {
    const payload = { ...form.value }
    // Clean up empty values
    Object.keys(payload).forEach(key => {
      if (payload[key] === '' || payload[key] === null) delete payload[key]
    })

    let response
    if (isEditing.value) {
      response = await api.put(`/employees/${form.value.id}`, payload)
    } else {
      response = await api.post('/employees', payload)
    }

    if (response.data.success) {
      showSnackbar(response.data.message, 'success')
      formDialog.value = false
      fetchEmployees()
    }
  } catch (err) {
    console.error('Error saving:', err)
    const message = err.response?.data?.message || 'Gagal menyimpan data'
    showSnackbar(message, 'error')
  } finally {
    saving.value = false
  }
}

const confirmDelete = (item) => {
  employeeToDelete.value = item
  deleteDialog.value = true
}

const deleteEmployee = async () => {
  deleting.value = true
  try {
    const response = await api.delete(`/employees/${employeeToDelete.value.id}`)
    if (response.data.success) {
      showSnackbar('Pegawai berhasil dihapus', 'success')
      deleteDialog.value = false
      fetchEmployees()
    }
  } catch (err) {
    console.error('Error deleting:', err)
    showSnackbar('Gagal menghapus pegawai', 'error')
  } finally {
    deleting.value = false
  }
}

const showSnackbar = (message, color = 'success') => {
  snackbarMessage.value = message
  snackbarColor.value = color
  snackbar.value = true
}

onMounted(() => {
  fetchEmployees()
  fetchSkpds()
})

watch(() => route.query.skpd_id, () => {
  fetchEmployees()
})

watch([genderFilter, statusFilter, selectedSkpd], () => {
  fetchEmployees()
})

let searchTimeout = null
watch(search, () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    fetchEmployees()
  }, 500)
})
</script>

<style scoped>
.modern-bg {
  background-color: rgb(var(--v-theme-background)) !important;
}

.glass-card {
  background-color: rgb(var(--v-theme-surface)) !important;
  border: 1px solid rgba(var(--v-border-color), 0.08) !important;
}

.sticky-top {
  position: sticky;
  top: 96px;
}

.stat-item {
  background: rgba(var(--v-border-color), 0.04);
  padding: 12px;
  border-radius: 12px;
  transition: all 0.2s;
}

.stat-item:hover {
  background: rgb(var(--v-theme-surface));
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

:deep(.modern-table) {
  background: transparent !important;
}

:deep(.v-data-table-header) {
  background: rgba(var(--v-border-color), 0.05) !important;
}

:deep(.v-data-table-header th) {
  font-weight: 700 !important;
  color: rgb(var(--v-theme-on-surface)) !important;
  opacity: 0.7;
  text-transform: uppercase;
  font-size: 0.7rem !important;
  letter-spacing: 0.05em;
}

:deep(.v-data-table__tr:hover) {
  background-color: rgba(var(--v-theme-primary), 0.02) !important;
}
</style>
