import { createApp } from 'vue'
import { createVuetify } from 'vuetify'
import { createPinia } from 'pinia'
import router from './router'
import App from './App.vue'
import VueApexCharts from "vue3-apexcharts";

import 'vuetify/styles'
import '@mdi/font/css/materialdesignicons.css'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

const vuetify = createVuetify({
  components,
  directives,
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        colors: {
          primary: '#4338ca', // Indigo 700
          secondary: '#0ea5e9', // Sky 500
          accent: '#6366f1', // Indigo 500
          surface: '#ffffff',
          background: '#f1f5f9', // Slate 100
          error: '#ef4444',
          info: '#3b82f6',
          success: '#22c55e',
          warning: '#f59e0b',
        },
      },
      dark: {
        dark: true,
        colors: {
          primary: '#818cf8', // Indigo 400
          secondary: '#38bdf8', // Sky 400
          surface: '#1e293b', // Slate 800
          background: '#0f172a', // Slate 900
          'primary-darken-1': '#3730a3', // Indigo 800
        },
      },
    },
  },
})

const pinia = createPinia()
const app = createApp(App)

app.use(vuetify)
app.use(pinia)
app.use(router)
app.use(VueApexCharts)

app.mount('#app')
