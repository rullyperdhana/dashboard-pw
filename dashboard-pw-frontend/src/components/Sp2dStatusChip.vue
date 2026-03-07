<template>
  <v-tooltip bottom v-if="status.is_realized">
    <template v-slot:activator="{ props }">
      <v-chip
        v-bind="props"
        color="success"
        size="small"
        variant="flat"
        prepend-icon="mdi-check-circle"
        class="font-weight-bold"
      >
        Sudah Cair
      </v-chip>
    </template>
    <div class="pa-1">
      <div class="text-caption font-weight-bold">No. SP2D:</div>
      <div class="text-body-2 mb-1">{{ status.nomor_sp2d }}</div>
      <div class="text-caption font-weight-bold">Tanggal:</div>
      <div class="text-body-2 mb-1">{{ formatDate(status.tanggal_sp2d) }}</div>
      <v-divider class="my-1"></v-divider>
      <div class="text-caption font-weight-bold">Nilai Netto:</div>
      <div class="text-body-2">{{ formatCurrency(status.netto) }}</div>
    </div>
  </v-tooltip>

  <v-chip
    v-else
    color="error"
    size="small"
    variant="tonal"
    prepend-icon="mdi-clock-outline"
    class="font-weight-medium"
  >
    Belum
  </v-chip>
</template>

<script setup>
defineProps({
  status: {
    type: Object,
    required: true
  }
})

const formatDate = (dateStr) => {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
}

const formatCurrency = (val) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(val || 0)
}
</script>
