<template>
  <v-tooltip bottom>
    <template v-slot:activator="{ props }">
      <v-chip
        v-bind="props"
        :color="chipColor"
        size="small"
        :variant="status.is_realized ? 'flat' : 'tonal'"
        :prepend-icon="chipIcon"
        class="font-weight-bold"
      >
        {{ chipText }}
        <span v-if="status.count > 0" class="ml-1 opacity-80" style="font-size: 10px">({{ status.count }} Peg)</span>
      </v-chip>
    </template>
    <div class="pa-2 chip-tooltip">
      <div v-if="status.count > 0" class="d-flex justify-space-between mb-2">
        <span class="text-caption font-weight-bold">Jumlah Pegawai:</span>
        <span class="text-body-2 font-weight-bold">{{ status.count }} Orang</span>
      </div>

      <div class="d-flex justify-space-between mb-1 gap-4">
        <span class="text-caption font-weight-bold">Internal (Sistem):</span>
        <span class="text-body-2 font-weight-medium text-white">{{ formatCurrency(status.internal_amount) }}</span>
      </div>
      <div class="d-flex justify-space-between mb-1 gap-4 text-success">
        <span class="text-caption font-weight-bold">Realisasi (SIPD):</span>
        <span class="text-body-2 font-weight-medium">{{ formatCurrency(status.netto) }}</span>
      </div>
      
      <v-divider class="my-2 opacity-20"></v-divider>
      
      <div class="d-flex justify-space-between mb-0 gap-4">
        <span class="text-caption font-weight-bold">Selisih:</span>
        <span :class="['text-body-2 font-weight-bold', isMismatch ? 'text-warning' : 'text-success']">
          {{ formatCurrency(diff) }}
        </span>
      </div>

      <div v-if="status.is_realized">
        <v-divider class="my-2 opacity-20"></v-divider>
        <div class="text-caption font-weight-bold mb-1">Info SP2D:</div>
        <div class="text-caption opacity-80" style="max-width: 250px">{{ status.nomor_sp2d }}</div>
        <div class="text-caption opacity-80 mt-1">Selesai: {{ formatDate(status.tanggal_sp2d) }}</div>
      </div>
    </div>
  </v-tooltip>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: {
    type: Object,
    required: true
  }
})

const diff = computed(() => {
  return (props.status.netto || 0) - (props.status.internal_amount || 0)
})

const isMismatch = computed(() => {
  // Allow for negligible rounding differences
  return Math.abs(diff.value) > 1000 
})

const chipColor = computed(() => {
  if (!props.status.is_realized) return 'error'
  return isMismatch.value ? 'warning' : 'success'
})

const chipIcon = computed(() => {
  if (!props.status.is_realized) return 'mdi-clock-outline'
  return isMismatch.value ? 'mdi-alert-circle' : 'mdi-check-circle'
})

const chipText = computed(() => {
  if (!props.status.is_realized) return 'Belum'
  return isMismatch.value ? 'Mismatch' : 'Sesuai'
})

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
</script>

<style scoped>
.chip-tooltip {
  min-width: 260px;
}
.gap-4 {
  gap: 16px;
}
.opacity-20 {
  opacity: 0.2;
}
.opacity-80 {
  opacity: 0.8;
}
</style>
