<template>
  <div class="split-layout">
    <div class="left-section d-none d-md-flex">
      <div class="brand-content text-left ml-16 pl-16">
        <v-icon icon="mdi-shield-account-outline" size="80" color="white" class="mb-6"></v-icon>
        <h1 class="text-h2 font-weight-black text-white mb-4 animate-slide-up">
          Employee Self-Service
        </h1>
        <p class="text-h6 text-white opacity-80 animate-fade-in" style="max-width: 600px; line-height: 1.5">
          Portal layanan mandiri bagi Aparatur Sipil Negara & PPPK. Pantau data riwayat penggajian dan unduh slip gaji Anda secara mudah.
        </p>

        <!-- Illustration -->
        <div class="mt-16 animate-float">
           <v-icon icon="mdi-file-document-multiple-outline" size="120" color="rgba(255,255,255,0.2)"></v-icon>
        </div>
      </div>
    </div>

    <!-- Right Content -->
    <div class="right-section position-relative">
      <!-- Dark mode toggle -->
      <div class="position-absolute top-0 right-0 ma-4">
        <ThemeToggle />
      </div>

      <v-card class="login-card mx-auto px-8 py-10 rounded-xl" flat width="100%" max-width="480">
        <div class="text-center mb-10">
          <div class="logo-circle bg-primary-lighten-1 mb-6 d-inline-flex mx-auto">
            <v-icon icon="mdi-shield-crown" color="primary" size="32"></v-icon>
          </div>
          <h2 class="text-h4 font-weight-bold text-high-emphasis mb-2">Login Pegawai</h2>
          <p class="text-medium-emphasis">Masukkan informasi valid untuk mengakses portal</p>
        </div>

        <v-form @submit.prevent="handleLogin" v-model="formValid">
          <label class="text-caption font-weight-bold text-medium-emphasis mb-2 d-block">Nomor Induk Kependudukan (NIK)</label>
          <v-text-field
            v-model="nik"
            placeholder="Masukkan 16 digit NIK"
            variant="outlined"
            prepend-inner-icon="mdi-badge-account-outline"
            :rules="[v => !!v || 'NIK tidak boleh kosong']"
            density="comfortable"
            class="mb-4"
            color="primary"
            bg-color="surface"
            hide-details="auto"
            rounded="lg"
            type="number"
          ></v-text-field>

          <label class="text-caption font-weight-bold text-medium-emphasis mb-2 d-block">Nomor Induk Pegawai (NIP/NIPPPK)</label>
          <v-text-field
            v-model="nip"
            placeholder="Masukkan NIP"
            variant="outlined"
            prepend-inner-icon="mdi-account-tie"
            :type="showPassword ? 'text' : 'password'"
            :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
            @click:append-inner="showPassword = !showPassword"
            :rules="[v => !!v || 'NIP tidak boleh kosong']"
            density="comfortable"
            class="mb-6"
            color="primary"
            bg-color="surface"
            hide-details="auto"
            rounded="lg"
            type="number"
          ></v-text-field>

          <!-- Google reCAPTCHA -->
          <div class="d-flex justify-center mb-6">
            <div id="recaptcha-container"></div>
          </div>

          <v-btn
            type="submit"
            color="primary"
            size="x-large"
            block
            :loading="loading"
            class="action-btn text-none font-weight-bold mb-6"
            height="56"
            elevation="0"
          >
            Masuk Portal
          </v-btn>

          <!-- Back to Admin login -->
        <div class="text-center">
            <v-btn
                variant="text"
                color="medium-emphasis"
                class="text-none"
                size="small"
                to="/login"
            >
                Masuk sebagai Admin Pengelola
            </v-btn>
        </div>
        </v-form>
      </v-card>
    </div>

    <!-- Snackbar -->
    <v-snackbar
      v-model="snackbar"
      :color="snackbarColor"
      timeout="4000"
      location="top"
      class="mt-4"
      rounded="pill"
      elevation="24"
    >
      <div class="d-flex align-center">
        <v-icon :icon="snackbarIcon" class="mr-3"></v-icon>
        <div class="text-subtitle-2 font-weight-medium">{{ snackbarText }}</div>
      </div>
    </v-snackbar>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useTheme } from 'vuetify'
import ThemeToggle from '../../components/ThemeToggle.vue'
import api from '../../api'

const router = useRouter()
const theme = useTheme()

const formValid = ref(false)
const nik = ref('')
const nip = ref('')
const showPassword = ref(false)
const loading = ref(false)

const snackbar = ref(false)
const snackbarText = ref('')
const snackbarColor = ref('success')
const snackbarIcon = ref('mdi-check-circle')

const showMessage = (text, type = 'success') => {
  snackbarText.value = text
  snackbarColor.value = type === 'error' ? 'error' : 'success'
  snackbarIcon.value = type === 'error' ? 'mdi-alert-circle' : 'mdi-check-circle'
  snackbar.value = true
}

const handleLogin = async () => {
  // Get reCAPTCHA response
  const recaptchaResponse = window.grecaptcha ? window.grecaptcha.getResponse() : ''
  
  if (!formValid.value || !nik.value || !nip.value) {
    showMessage('Mohon lengkapi NIK dan NIP', 'error')
    return
  }

  if (!recaptchaResponse) {
    showMessage('Mohon centang kotak keamanan (reCAPTCHA)', 'error')
    return
  }
  
  loading.value = true
  try {
    const response = await api.post('/ess/login', {
      nik: nik.value,
      nip: nip.value,
      recaptcha_token: recaptchaResponse
    })
    
    if (response.data.success) {
      localStorage.setItem('ess_token', response.data.data.token)
      localStorage.setItem('ess_user', JSON.stringify(response.data.data.user))
      showMessage('Login berhasil, mengalihkan...')
      setTimeout(() => {
        router.push('/ess/dashboard')
      }, 1000)
    }
  } catch (error) {
    if (error.response?.data?.message) {
      showMessage(error.response.data.message, 'error')
    } else {
      showMessage('Gagal mencoba koneksi ke server', 'error')
    }
    // Reset reCAPTCHA on failure
    if (window.grecaptcha && typeof window.grecaptcha.reset === 'function') {
        window.grecaptcha.reset()
    }
  } finally {
    loading.value = false
  }
}

const initRecaptcha = () => {
  if (window.grecaptcha && typeof window.grecaptcha.render === 'function') {
    try {
      window.grecaptcha.render('recaptcha-container', {
        'sitekey': '6LftjpssAAAAAJJSiF6jEKwdVKKHXazhv9vYVWG5'
      })
    } catch (e) {
      console.warn('reCAPTCHA already rendered or failed:', e)
    }
  } else {
    // Tunggu script Google selesai loading jika belum ada
    setTimeout(initRecaptcha, 500)
  }
}

onMounted(() => {
  initRecaptcha()
})
</script>

<style scoped>
.split-layout {
  display: flex;
  min-height: 100vh;
  width: 100%;
}

.left-section {
  flex: 1.2;
  background: linear-gradient(135deg, rgb(var(--v-theme-primary)) 0%, rgba(var(--v-theme-primary), 0.8) 100%);
  display: flex;
  align-items: center;
  position: relative;
  overflow: hidden;
}

.left-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: radial-gradient(circle at 10% 20%, rgba(255,255,255,0.1) 0%, transparent 40%),
              radial-gradient(circle at 90% 80%, rgba(255,255,255,0.08) 0%, transparent 40%);
  pointer-events: none;
}

.right-section {
  flex: 1;
  background-color: rgb(var(--v-theme-background));
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.login-card {
  background: transparent !important;
  max-width: 480px;
  width: 100%;
  z-index: 10;
}

.logo-circle {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(var(--v-theme-primary), 0.1);
}

.action-btn {
  border-radius: 12px;
  letter-spacing: 0.5px;
}

/* Animations */
.animate-slide-up {
  animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

.animate-fade-in {
  animation: fadeIn 1s ease-out 0.3s forwards;
  opacity: 0;
}

.animate-float {
  animation: float 6s ease-in-out infinite;
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(40px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes float {
  0% { transform: translateY(0px) rotate(0deg); }
  50% { transform: translateY(-20px) rotate(5deg); }
  100% { transform: translateY(0px) rotate(0deg); }
}

/* Custom Input Styles */
:deep(.v-field) {
  border-radius: 12px;
  transition: all 0.3s ease;
}

:deep(.v-field--focused) {
  box-shadow: 0 4px 12px rgba(var(--v-theme-primary), 0.1);
}

/* Responsive adjustments */
@media (max-width: 959px) {
  .right-section {
    padding: 24px;
  }
}
</style>
