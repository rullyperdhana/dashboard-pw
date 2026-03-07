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
  const newTheme = theme.global.name.value === 'dark' ? 'light' : 'dark'
  if (typeof theme.change === 'function') {
    theme.change(newTheme)
  } else {
    theme.global.name.value = newTheme
  }
  localStorage.setItem('theme', newTheme)
}

onMounted(() => {
  const savedTheme = localStorage.getItem('theme')
  if (savedTheme) {
    if (typeof theme.change === 'function') {
      theme.change(savedTheme)
    } else {
      theme.global.name.value = savedTheme
    }
  }
})
</script>
