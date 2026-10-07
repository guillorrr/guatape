<script setup lang="ts">
import Tag from 'primevue/tag'
import { computed } from 'vue'

const props = defineProps<{
  status: string
}>()

const severity = computed(() => {
  if (!props.status) return 'secondary'
  const s = props.status.toLowerCase()
  if (['active', 'completed', 'delivered', 'approved', 'idle'].includes(s)) return 'success'
  if (['printing', 'confirmed', 'assigned', 'submitted', 'in_progress'].includes(s)) return 'info'
  if (['queued', 'pending', 'draft', 'paused', 'under_review', 'low', 'quoting', 'review'].includes(s)) return 'warn'
  if (['error', 'failed', 'cancelled', 'rejected', 'offline', 'empty', 'closed'].includes(s)) return 'danger'
  return 'secondary'
})

const label = computed(() => {
  if (!props.status) return ''
  return props.status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
})
</script>

<template>
  <Tag :value="label" :severity="severity" rounded />
</template>
