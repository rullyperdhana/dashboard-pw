<template>
  <v-dialog :model-value="modelValue" @update:model-value="$emit('update:modelValue', $event)" max-width="500px">
    <v-card class="rounded-xl glass-modal overflow-hidden">
      <v-toolbar color="primary" density="compact" flat>
        <v-toolbar-title class="text-subtitle-1 font-weight-bold ml-2">Pengaturan Akun</v-toolbar-title>
        <v-spacer></v-spacer>
        <v-btn icon="mdi-close" variant="text" @click="$emit('update:modelValue', false)"></v-btn>
      </v-toolbar>
      
      <v-tabs v-model="tab" color="primary" grow>
        <v-tab value="profile">
          <v-icon start size="small">mdi-account-outline</v-icon> Profil
        </v-tab>
        <v-tab value="security">
          <v-icon start size="small">mdi-lock-outline</v-icon> Keamanan
        </v-tab>
      </v-tabs>

      <v-window v-model="tab" class="pa-4 pt-6">
        <!-- Profile Tab -->
        <v-window-item value="profile">
          <v-form ref="profileForm" v-model="profileValid" @submit.prevent="updateProfile">
            <v-text-field
              v-model="profileData.name"
              label="Nama Lengkap"
              variant="outlined"
              density="comfortable"
              rounded="lg"
              :rules="[v => !!v || 'Nama wajib diisi']"
              prepend-inner-icon="mdi-account"
              class="mb-2"
            ></v-text-field>
            <v-text-field
              v-model="profileData.email"
              label="Alamat Email"
              type="email"
              variant="outlined"
              density="comfortable"
              rounded="lg"
              :rules="[v => !!v || 'Email wajib diisi', v => /.+@.+\..+/.test(v) || 'Email tidak valid']"
              prepend-inner-icon="mdi-email"
              class="mb-4"
            ></v-text-field>
            <v-btn
              block
              color="primary"
              variant="flat"
              rounded="lg"
              size="large"
              @click="updateProfile"
              :loading="profileLoading"
              :disabled="!profileValid"
            >
              Simpan Perubahan
            </v-btn>
          </v-form>
        </v-window-item>

        <!-- Security Tab -->
        <v-window-item value="security">
          <v-form ref="pwForm" v-model="pwValid" @submit.prevent="changePassword">
            <v-text-field
              v-model="pwData.current_password"
              label="Password Sekarang"
              type="password"
              variant="outlined"
              density="comfortable"
              rounded="lg"
              :rules="[v => !!v || 'Wajib diisi']"
              prepend-inner-icon="mdi-lock-open-outline"
              class="mb-2"
            ></v-text-field>
            <v-divider class="my-4 border-opacity-10">
              <span class="text-caption text-disabled px-2">PASSWORD BARU</span>
            </v-divider>
            <v-text-field
              v-model="pwData.new_password"
              label="Password Baru"
              type="password"
              variant="outlined"
              density="comfortable"
              rounded="lg"
              :rules="[v => !!v || 'Wajib diisi', v => (v && v.length >= 6) || 'Minimal 6 karakter']"
              prepend-inner-icon="mdi-lock-outline"
              class="mb-2"
            ></v-text-field>
            <v-text-field
              v-model="pwData.new_password_confirmation"
              label="Konfirmasi Password Baru"
              type="password"
              variant="outlined"
              density="comfortable"
              rounded="lg"
              :rules="[v => !!v || 'Wajib diisi', v => v === pwData.new_password || 'Konfirmasi password tidak cocok']"
              prepend-inner-icon="mdi-lock-check-outline"
              class="mb-6"
            ></v-text-field>
            <v-btn
              block
              color="primary"
              variant="flat"
              rounded="lg"
              size="large"
              @click="changePassword"
              :loading="pwLoading"
              :disabled="!pwValid"
            >
              Ganti Password
            </v-btn>
          </v-form>
        </v-window-item>
      </v-window>
    </v-card>

    <!-- Internal Snackbar -->
    <v-snackbar v-model="snackbar.show" :color="snackbar.color" rounded="pill" elevation="24">
      <div class="d-flex align-center">
        <v-icon start size="small" class="mr-2">{{ snackbar.icon }}</v-icon>
        <span>{{ snackbar.text }}</span>
      </div>
      <template v-slot:actions>
        <v-btn variant="text" @click="snackbar.show = false">OK</v-btn>
      </template>
    </v-snackbar>
  </v-dialog>
</template>

<script setup>
import { ref, watch } from 'vue'
import api from '../api'

const props = defineProps({
  modelValue: Boolean,
  user: Object
})

const emit = defineEmits(['update:modelValue', 'user-updated'])

const tab = ref('profile')
const profileValid = ref(false)
const profileLoading = ref(false)
const profileData = ref({
  name: '',
  email: ''
})

const pwValid = ref(false)
const pwLoading = ref(false)
const pwData = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})

const snackbar = ref({
  show: false,
  text: '',
  color: 'success',
  icon: 'mdi-check-circle'
})

const showSnackbar = (text, color = 'success') => {
  snackbar.value = {
    show: true,
    text,
    color,
    icon: color === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle'
  }
}

// Watch dialog opening to sync data
watch(() => props.modelValue, (val) => {
  if (val && props.user) {
    profileData.value = {
      name: props.user.name || '',
      email: props.user.email || ''
    }
  }
})

const updateProfile = async () => {
  profileLoading.value = true
  try {
    const response = await api.post('/update-profile', profileData.value)
    const updatedUser = response.data.data
    emit('user-updated', updatedUser)
    showSnackbar('Profil berhasil diperbarui')
  } catch (error) {
    showSnackbar(error.response?.data?.message || 'Gagal memperbarui profil', 'error')
  } finally {
    profileLoading.value = false
  }
}

const changePassword = async () => {
  pwLoading.value = true
  try {
    await api.post('/change-password', pwData.value)
    showSnackbar('Password berhasil diubah')
    pwData.value = { current_password: '', new_password: '', new_password_confirmation: '' }
    setTimeout(() => {
        emit('update:modelValue', false)
    }, 1500)
  } catch (error) {
    showSnackbar(error.response?.data?.message || 'Gagal mengubah password', 'error')
  } finally {
    pwLoading.value = false
  }
}
</script>

<style scoped>
.glass-modal {
  background: rgba(var(--v-theme-surface), 0.9) !important;
  backdrop-filter: blur(10px);
}
</style>
