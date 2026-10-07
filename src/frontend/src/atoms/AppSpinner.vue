<script setup lang="ts">
defineProps<{
  size?: 'sm' | 'md' | 'lg'
}>()
</script>

<template>
  <div class="spinner" :class="`spinner--${size ?? 'md'}`" />
</template>

<style scoped lang="scss">
.spinner {
  border-radius: 50%;
  border: 3px solid #e5e7eb;
  border-top-color: #1a73e8;
  animation: spin 0.7s linear infinite;

  &--sm { width: 16px; height: 16px; border-width: 2px; }
  &--md { width: 32px; height: 32px; }
  &--lg { width: 48px; height: 48px; border-width: 4px; }
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>
