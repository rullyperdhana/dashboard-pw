<template>
  <v-btn
    icon
    variant="text"
    @click="toggleTheme"
    :color="theme.global.name.value === 'dark' ? 'amber' : 'primary'"
    class="mr-2"
  >
    <v-icon>{{ theme.global.name.value === 'dark' ? 'mdi-weather-sunny' : 'mdi-weather-night' }}</v-icon>
    <v-tooltip activator="parent" location="bottom">
      Switch to {{ theme.global.name.value === 'dark' ? 'Light' : 'Dark' }} Mode
    </v-tooltip>
  </v-btn>
</template>

<script setup>
import { useTheme } from 'vuetify'
import { onMounted } from 'vue'

const theme = useTheme()

const toggleTheme = () => {
  theme.global.name.value = theme.global.name.value === 'dark' ? 'light' : 'dark'
  localStorage.setItem('theme', theme.global.name.value)
}

onMounted(() => {
  const savedTheme = localStorage.getItem('theme')
  if (savedTheme) {
    theme.global.name.value = savedTheme
  }
})
</script>
