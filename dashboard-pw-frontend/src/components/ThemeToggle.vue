<template>
  <v-btn
    icon
    variant="text"
    @click="toggleTheme"
    :color="theme.name.value === 'dark' ? 'amber' : 'primary'"
    class="mr-2"
  >
    <v-icon>{{ theme.name.value === 'dark' ? 'mdi-weather-sunny' : 'mdi-weather-night' }}</v-icon>
    <v-tooltip activator="parent" location="bottom">
      Switch to {{ theme.name.value === 'dark' ? 'Light' : 'Dark' }} Mode
    </v-tooltip>
  </v-btn>
</template>

<script setup>
import { useTheme } from 'vuetify'
import { onMounted } from 'vue'

const theme = useTheme()

const toggleTheme = () => {
  const newTheme = theme.name.value === 'dark' ? 'light' : 'dark'
  theme.name.value = newTheme
  localStorage.setItem('theme', newTheme)
}

onMounted(() => {
  const savedTheme = localStorage.getItem('theme')
  if (savedTheme) {
    theme.name.value = savedTheme
  }
})
</script>
