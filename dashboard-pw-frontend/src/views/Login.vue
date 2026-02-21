<template>
  <v-container fluid class="fill-height login-wrapper">
    <!-- Animated background circles -->
    <div class="bg-circle circle-1"></div>
    <div class="bg-circle circle-2"></div>
    
    <div style="position: absolute; top: 20px; right: 20px; z-index: 100;">
      <ThemeToggle />
    </div>
    
    <v-row align="center" justify="center" class="z-index-high">
      <v-col cols="12" sm="8" md="5" lg="4">
        <v-card class="glass-login rounded-xl pa-2" elevation="24">
          <v-card-text class="pa-10">
            <div class="text-center mb-10">
              <v-avatar color="primary" size="72" class="mb-4 elevation-8">
                <v-icon size="40" color="white">mdi-account-lock-outline</v-icon>
              </v-avatar>
              <h1 class="text-h4 font-weight-black primary--text mb-1">
                 Payroll<span class="text-primary-gradient">PPPK</span>
              </h1>
              <p class="text-subtitle-1 text-grey-darken-1">Administrative Dashboard Access</p>
            </div>

            <v-form @submit.prevent="handleLogin" ref="form">
              <div class="mb-5">
                <div class="text-caption font-weight-bold text-grey-darken-1 ml-1 mb-2">IDENTIFICATION</div>
                <v-text-field
                  v-model="username"
                  label="Username or Email"
                  prepend-inner-icon="mdi-account-circle-outline"
                  :rules="[rules.required]"
                  variant="solo-filled"
                  flat
                  rounded="lg"
                  autocomplete="username"
                  bg-color="grey-lighten-4"
                ></v-text-field>
              </div>
              
              <div class="mb-8">
                <div class="text-caption font-weight-bold text-grey-darken-1 ml-1 mb-2">SECURITY KEY</div>
                <v-text-field
                  v-model="password"
                  label="Enter your password"
                  prepend-inner-icon="mdi-shield-lock-outline"
                  :type="showPassword ? 'text' : 'password'"
                  :append-inner-icon="showPassword ? 'mdi-eye-outline' : 'mdi-eye-off-outline'"
                  @click:append-inner="showPassword = !showPassword"
                  :rules="[rules.required]"
                  variant="solo-filled"
                  flat
                  rounded="lg"
                  autocomplete="current-password"
                  bg-color="grey-lighten-4"
                ></v-text-field>
              </div>

              <v-alert
                v-if="error"
                type="error"
                variant="tonal"
                class="mb-6 rounded-lg"
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
                elevation="8"
                class="login-btn font-weight-bold"
              >
                AUTHENTICATE
                <v-icon right class="ml-2">mdi-arrow-right</v-icon>
              </v-btn>
            </v-form>
          </v-card-text>
          
          <v-divider class="mx-10"></v-divider>
          
          <v-card-text class="text-center text-caption text-grey-darken-1 py-8">
            <span class="font-weight-bold text-primary">DASHBOARD PENGGAJIAN PPPK</span><br>
            Provinsi Kalimantan Selatan &copy; 2026
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </v-container>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useTheme } from 'vuetify'
import api from '../api'
import ThemeToggle from '../components/ThemeToggle.vue'

const theme = useTheme()
const router = useRouter()
const username = ref('')
const password = ref('')
const showPassword = ref(false)
const loading = ref(false)
const error = ref('')

const rules = {
  required: value => !!value || 'Identification is required',
}

const handleLogin = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const response = await api.post('/login', {
      username: username.value,
      password: password.value,
    })
    
    if (response.data.success) {
      localStorage.setItem('token', response.data.data.token)
      localStorage.setItem('user', JSON.stringify(response.data.data.user))
      router.push('/')
    }
  } catch (err) {
    if (err.response) {
      error.value = err.response.data.message || 'Authentication failed'
    } else {
      error.value = 'Network disruption detected'
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.login-wrapper {
  background: #0f172a;
  min-height: 100vh;
  position: relative;
  overflow: hidden;
  font-family: 'Inter', sans-serif;
}

.bg-circle {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.15;
  z-index: 0;
}

.circle-1 {
  width: 400px;
  height: 400px;
  background: #3b82f6;
  top: -100px;
  left: -100px;
}

.circle-2 {
  width: 500px;
  height: 500px;
  background: #8b5cf6;
  bottom: -150px;
  right: -150px;
}

.z-index-high {
  position: relative;
  z-index: 10;
}

.glass-login {
  background: rgba(var(--v-theme-surface), 0.9) !important;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(var(--v-border-color), 0.05) !important;
}

.text-primary-gradient {
  background: linear-gradient(45deg, #1867C0, #5CBBF6);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.login-btn {
  letter-spacing: 1px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.login-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px -5px rgba(24, 103, 192, 0.5) !important;
}
</style>
