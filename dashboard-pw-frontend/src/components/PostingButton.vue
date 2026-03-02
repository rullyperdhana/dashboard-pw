<template>
  <div class="d-flex flex-column align-center">
    <v-btn
      :color="status.is_posted ? 'red-lighten-4' : 'green-lighten-4'"
      :variant="status.is_posted ? 'flat' : 'flat'"
      size="x-small"
      class="rounded-lg mb-1"
      @click="$emit('toggle', { type, month, year, isPosted: status.is_posted })"
    >
      <v-icon :color="status.is_posted ? 'red' : 'green'" size="20">
        {{ status.is_posted ? 'mdi-lock' : 'mdi-lock-open-variant' }}
      </v-icon>
      <span :class="status.is_posted ? 'text-red ml-1' : 'text-green ml-1'" class="font-weight-bold">
        {{ status.is_posted ? 'POSTED' : 'OPEN' }}
      </span>
    </v-btn>
    <div v-if="status.is_posted" class="text-caption text-grey">
      {{ formatDate(status.posted_at) }}
    </div>
    <div v-if="status.is_posted && status.user" class="text-caption text-grey-darken-1 font-weight-bold">
      by {{ status.user.name }}
    </div>
  </div>
</template>

<script setup>
defineProps({
  status: Object,
  type: String,
  month: Number,
  year: Number
})

defineEmits(['toggle'])

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: '2-digit' })
}
</script>
