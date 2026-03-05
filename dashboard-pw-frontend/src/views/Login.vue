<template>
  <div class="login-wrapper">
    <!-- Animated Background -->
    <div class="animated-bg">
      <div class="bg-gradient"></div>
      <div class="floating-shapes">
        <div v-for="n in 15" :key="n" class="shape" :class="'shape-' + n"></div>
      </div>
      <div class="grid-overlay"></div>
    </div>

    <!-- Left Panel - Branding & Animation -->
    <div class="left-panel">
      <div class="brand-content">
        <div class="brand-icon-wrapper">
          <div class="brand-icon">
            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="6" y="10" width="36" height="28" rx="4" stroke="white" stroke-width="2.5" fill="none"/>
              <path d="M6 18H42" stroke="white" stroke-width="2"/>
              <circle cx="12" cy="14" r="1.5" fill="white"/>
              <circle cx="17" cy="14" r="1.5" fill="white"/>
              <circle cx="22" cy="14" r="1.5" fill="white"/>
              <rect x="11" y="23" width="10" height="3" rx="1.5" fill="white" opacity="0.7"/>
              <rect x="11" y="29" width="16" height="3" rx="1.5" fill="white" opacity="0.5"/>
              <rect x="27" y="22" width="10" height="11" rx="2" fill="white" opacity="0.3"/>
              <path d="M29 30L31 27L33 29L35 24" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="brand-pulse"></div>
        </div>
        
        <h1 class="brand-title">SIP-Gaji</h1>
        <p class="brand-subtitle">Sistem Informasi Penggajian</p>
        
        <div class="stats-showcase">
          <div class="stat-card" v-for="(stat, i) in showcaseStats" :key="i" :style="{ animationDelay: (i * 0.2) + 's' }">
            <v-icon :icon="stat.icon" size="20" color="white"></v-icon>
            <div class="stat-info">
              <span class="stat-value">{{ stat.value }}</span>
              <span class="stat-label">{{ stat.label }}</span>
            </div>
          </div>
        </div>

        <div class="feature-tags">
          <span class="feature-tag" v-for="(tag, i) in features" :key="i" :style="{ animationDelay: (i * 0.15 + 0.5) + 's' }">
            <v-icon :icon="tag.icon" size="14" class="mr-1"></v-icon>
            {{ tag.text }}
          </span>
        </div>
      </div>
      
      <div class="left-footer">
        <span>Dashboard Penggajian &mdash; Provinsi Kalimantan Selatan</span>
      </div>
    </div>

    <!-- Right Panel - Login Form -->
    <div class="right-panel">
      <div class="form-container">
        <div class="theme-toggle-pos">
          <ThemeToggle />
        </div>

        <div class="form-header">
          <div class="welcome-badge">
            <v-icon size="16" class="mr-1">mdi-hand-wave</v-icon>
            Selamat Datang
          </div>
          <h2 class="form-title">Masuk ke Sistem</h2>
          <p class="form-subtitle">Akses dashboard penggajian Anda</p>
        </div>

        <v-form @submit.prevent="handleLogin" ref="form" class="login-form">
          <div class="input-group">
            <label class="input-label">Username</label>
            <v-text-field
              v-model="username"
              placeholder="Masukkan username"
              prepend-inner-icon="mdi-account-outline"
              :rules="[rules.required]"
              variant="outlined"
              rounded="lg"
              autocomplete="username"
              density="comfortable"
              hide-details="auto"
            ></v-text-field>
          </div>

          <div class="input-group">
            <label class="input-label">Password</label>
            <v-text-field
              v-model="password"
              placeholder="Masukkan password"
              prepend-inner-icon="mdi-lock-outline"
              :type="showPassword ? 'text' : 'password'"
              :append-inner-icon="showPassword ? 'mdi-eye-outline' : 'mdi-eye-off-outline'"
              @click:append-inner="showPassword = !showPassword"
              :rules="[rules.required]"
              variant="outlined"
              rounded="lg"
              autocomplete="current-password"
              density="comfortable"
              hide-details="auto"
            ></v-text-field>
          </div>

          <div class="input-group">
            <label class="input-label">
              Verifikasi
              <span class="captcha-badge">{{ captchaQuestion }}</span>
            </label>
            <div class="d-flex align-center ga-2">
              <v-text-field
                v-model="captchaAnswer"
                placeholder="Jawaban"
                prepend-inner-icon="mdi-calculator-variant-outline"
                :rules="[rules.required]"
                variant="outlined"
                rounded="lg"
                density="comfortable"
                hide-details="auto"
              ></v-text-field>
              <v-btn
                icon="mdi-refresh"
                variant="tonal"
                color="primary"
                rounded="lg"
                size="44"
                @click="fetchCaptcha"
              ></v-btn>
            </div>
          </div>

          <v-alert
            v-if="error"
            type="error"
            variant="tonal"
            class="mb-4 rounded-lg"
            density="compact"
            closable
            @click:close="error = ''"
          >
            {{ error }}
          </v-alert>

          <v-btn
            type="submit"
            color="primary"
            size="x-large"
            block
            rounded="xl"
            :loading="loading"
            elevation="0"
            class="login-btn mt-2"
          >
            <v-icon start>mdi-login</v-icon>
            MASUK
          </v-btn>
        </v-form>

        <div class="form-footer">
          &copy; {{ new Date().getFullYear() }} Pemprov Kalimantan Selatan
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api'
import ThemeToggle from '../components/ThemeToggle.vue'

const router = useRouter()
const username = ref('')
const password = ref('')
const showPassword = ref(false)
const loading = ref(false)
const error = ref('')
const captchaQuestion = ref('')
const captchaKey = ref('')
const captchaAnswer = ref('')

const showcaseStats = [
  { icon: 'mdi-account-group', value: 'PNS & PPPK', label: 'Data Pegawai' },
  { icon: 'mdi-cash-multiple', value: 'Real-time', label: 'Proses Gaji' },
  { icon: 'mdi-chart-timeline-variant', value: 'Analitik', label: 'Laporan' },
]

const features = [
  { icon: 'mdi-shield-check', text: 'Aman' },
  { icon: 'mdi-lightning-bolt', text: 'Cepat' },
  { icon: 'mdi-cloud-sync', text: 'Terintegrasi' },
  { icon: 'mdi-chart-bar', text: 'Dashboard' },
]

const fetchCaptcha = async () => {
  try {
    const response = await api.get('/captcha')
    if (response.data.success) {
      captchaQuestion.value = response.data.data.captcha_question
      captchaKey.value = response.data.data.captcha_key
      captchaAnswer.value = ''
    }
  } catch (err) {
    console.error('Failed to fetch captcha:', err)
  }
}

fetchCaptcha()

const rules = {
  required: value => !!value || 'Wajib diisi',
}

const handleLogin = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const response = await api.post('/login', {
      username: username.value,
      password: password.value,
      captcha_key: captchaKey.value,
      captcha_answer: captchaAnswer.value
    })
    
    if (response.data.success) {
      localStorage.setItem('token', response.data.data.token)
      localStorage.setItem('user', JSON.stringify(response.data.data.user))
      router.push('/')
    }
  } catch (err) {
    if (err.response) {
      error.value = err.response.data.message || 'Login gagal'
      fetchCaptcha()
    } else {
      error.value = 'Koneksi jaringan terputus'
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

.login-wrapper {
  display: flex;
  min-height: 100vh;
  font-family: 'Inter', sans-serif;
  position: relative;
}

/* ====== Animated Background ====== */
.animated-bg {
  position: fixed;
  inset: 0;
  z-index: 0;
  overflow: hidden;
}

.bg-gradient {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #0c1426 0%, #1a1a3e 30%, #0e1b3d 60%, #0a0f1f 100%);
}

.grid-overlay {
  position: absolute;
  inset: 0;
  background-image: 
    linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
  background-size: 60px 60px;
  animation: gridMove 20s linear infinite;
}

@keyframes gridMove {
  0% { transform: translate(0, 0); }
  100% { transform: translate(60px, 60px); }
}

.floating-shapes .shape {
  position: absolute;
  border-radius: 50%;
  opacity: 0;
  animation: floatUp 12s infinite;
}

.shape-1  { width: 6px; height: 6px; background: #3b82f6; left: 10%; animation-delay: 0s; }
.shape-2  { width: 4px; height: 4px; background: #8b5cf6; left: 20%; animation-delay: 1s; }
.shape-3  { width: 8px; height: 8px; background: #06b6d4; left: 30%; animation-delay: 2s; }
.shape-4  { width: 5px; height: 5px; background: #10b981; left: 40%; animation-delay: 3s; }
.shape-5  { width: 3px; height: 3px; background: #f59e0b; left: 50%; animation-delay: 0.5s; }
.shape-6  { width: 7px; height: 7px; background: #3b82f6; left: 60%; animation-delay: 1.5s; }
.shape-7  { width: 4px; height: 4px; background: #ec4899; left: 70%; animation-delay: 2.5s; }
.shape-8  { width: 6px; height: 6px; background: #8b5cf6; left: 80%; animation-delay: 3.5s; }
.shape-9  { width: 5px; height: 5px; background: #06b6d4; left: 15%; animation-delay: 4s; }
.shape-10 { width: 3px; height: 3px; background: #10b981; left: 25%; animation-delay: 5s; }
.shape-11 { width: 7px; height: 7px; background: #f59e0b; left: 35%; animation-delay: 6s; }
.shape-12 { width: 4px; height: 4px; background: #3b82f6; left: 55%; animation-delay: 7s; }
.shape-13 { width: 6px; height: 6px; background: #ec4899; left: 65%; animation-delay: 8s; }
.shape-14 { width: 5px; height: 5px; background: #8b5cf6; left: 75%; animation-delay: 9s; }
.shape-15 { width: 3px; height: 3px; background: #06b6d4; left: 85%; animation-delay: 10s; }

@keyframes floatUp {
  0% { bottom: -20px; opacity: 0; transform: translateX(0) scale(0); }
  10% { opacity: 0.6; transform: scale(1); }
  90% { opacity: 0.3; }
  100% { bottom: 110vh; opacity: 0; transform: translateX(80px) scale(0.5); }
}

/* ====== Left Panel ====== */
.left-panel {
  flex: 1.1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  padding: 60px;
  position: relative;
  z-index: 2;
}

.brand-content {
  text-align: center;
  animation: fadeInUp 0.8s ease-out;
}

.brand-icon-wrapper {
  position: relative;
  display: inline-flex;
  margin-bottom: 32px;
}

.brand-icon {
  width: 90px;
  height: 90px;
  background: linear-gradient(135deg, #3b82f6, #8b5cf6);
  border-radius: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 18px;
  position: relative;
  z-index: 1;
  box-shadow: 0 20px 60px rgba(59, 130, 246, 0.3);
  animation: iconFloat 4s ease-in-out infinite;
}

.brand-icon svg {
  width: 100%;
  height: 100%;
}

.brand-pulse {
  position: absolute;
  inset: -8px;
  border-radius: 30px;
  border: 2px solid rgba(99, 102, 241, 0.3);
  animation: pulse 2.5s ease-in-out infinite;
}

@keyframes iconFloat {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-8px); }
}

@keyframes pulse {
  0%, 100% { transform: scale(1); opacity: 0.5; }
  50% { transform: scale(1.1); opacity: 0; }
}

.brand-title {
  font-size: 3rem;
  font-weight: 900;
  color: white;
  letter-spacing: -1px;
  margin-bottom: 8px;
  background: linear-gradient(135deg, #ffffff, #94a3b8);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.brand-subtitle {
  font-size: 1.1rem;
  color: rgba(148, 163, 184, 0.8);
  font-weight: 400;
  margin-bottom: 48px;
}

/* Stat Cards */
.stats-showcase {
  display: flex;
  gap: 12px;
  margin-bottom: 36px;
  justify-content: center;
  flex-wrap: wrap;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 18px;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 14px;
  backdrop-filter: blur(10px);
  animation: fadeInUp 0.6s ease-out both;
}

.stat-info {
  display: flex;
  flex-direction: column;
  text-align: left;
}

.stat-value {
  color: white;
  font-size: 0.85rem;
  font-weight: 700;
}

.stat-label {
  color: rgba(148, 163, 184, 0.7);
  font-size: 0.7rem;
}

/* Feature Tags */
.feature-tags {
  display: flex;
  gap: 8px;
  justify-content: center;
  flex-wrap: wrap;
}

.feature-tag {
  display: inline-flex;
  align-items: center;
  padding: 6px 14px;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 20px;
  color: rgba(148, 163, 184, 0.8);
  font-size: 0.75rem;
  font-weight: 500;
  animation: fadeInUp 0.5s ease-out both;
}

.left-footer {
  position: absolute;
  bottom: 30px;
  color: rgba(100, 116, 139, 0.5);
  font-size: 0.75rem;
}

/* ====== Right Panel ====== */
.right-panel {
  flex: 0.9;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  z-index: 2;
  padding: 40px;
}

.form-container {
  width: 100%;
  max-width: 420px;
  background: rgba(var(--v-theme-surface), 0.95);
  border: 1px solid rgba(var(--v-border-color), 0.1);
  border-radius: 28px;
  padding: 48px 40px;
  backdrop-filter: blur(20px);
  box-shadow: 0 32px 64px rgba(0, 0, 0, 0.3);
  animation: fadeInRight 0.6s ease-out;
  position: relative;
}

.theme-toggle-pos {
  position: absolute;
  top: 16px;
  right: 16px;
}

.form-header {
  margin-bottom: 32px;
}

.welcome-badge {
  display: inline-flex;
  align-items: center;
  padding: 6px 14px;
  background: rgba(var(--v-theme-primary), 0.1);
  color: rgb(var(--v-theme-primary));
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  margin-bottom: 16px;
}

.form-title {
  font-size: 1.75rem;
  font-weight: 800;
  margin-bottom: 4px;
  letter-spacing: -0.5px;
}

.form-subtitle {
  font-size: 0.9rem;
  opacity: 0.6;
}

/* Input Groups */
.input-group {
  margin-bottom: 20px;
}

.input-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.8rem;
  font-weight: 600;
  margin-bottom: 6px;
  margin-left: 4px;
  opacity: 0.7;
}

.captcha-badge {
  background: rgba(var(--v-theme-primary), 0.1);
  color: rgb(var(--v-theme-primary));
  padding: 2px 10px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 0.8rem;
}

/* Login Button */
.login-btn {
  letter-spacing: 1.5px;
  font-weight: 700;
  font-size: 0.95rem;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.login-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 32px rgba(var(--v-theme-primary), 0.4) !important;
}

.form-footer {
  text-align: center;
  margin-top: 28px;
  font-size: 0.75rem;
  opacity: 0.4;
}

/* ====== Animations ====== */
@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(24px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeInRight {
  from { opacity: 0; transform: translateX(32px); }
  to { opacity: 1; transform: translateX(0); }
}

/* ====== Responsive ====== */
@media (max-width: 960px) {
  .login-wrapper {
    flex-direction: column;
  }
  
  .left-panel {
    padding: 40px 24px 24px;
    flex: none;
  }
  
  .brand-title { font-size: 2rem; }
  .brand-subtitle { margin-bottom: 24px; font-size: 0.95rem; }
  .stats-showcase { margin-bottom: 20px; }
  .left-footer { display: none; }
  
  .right-panel {
    flex: none;
    padding: 0 16px 32px;
  }
  
  .form-container {
    padding: 32px 24px;
    border-radius: 20px;
  }
}
</style>
