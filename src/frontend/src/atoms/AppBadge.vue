<script setup lang="ts">
defineProps<{
  variant?: 'success' | 'warning' | 'danger' | 'info' | 'default';
  size?: 'sm' | 'md';
}>();
</script>

<template>
  <span class="badge" :class="[`badge--${variant ?? 'default'}`, `badge--${size ?? 'md'}`]">
    <slot />
  </span>
</template>

<style scoped lang="scss">
.badge {
  display: inline-flex;
  align-items: center;
  font-weight: 600;
  border-radius: 9999px;
  white-space: nowrap;

  &--sm {
    padding: 2px 8px;
    font-size: 0.7rem;
  }
  &--md {
    padding: 4px 12px;
    font-size: 0.75rem;
  }

  &--default {
    background: #e5e7eb;
    color: #374151;
  }
  &--success {
    background: #d1fae5;
    color: #065f46;
  }
  &--warning {
    background: #fef3c7;
    color: #92400e;
  }
  &--danger {
    background: #fee2e2;
    color: #991b1b;
  }
  &--info {
    background: #dbeafe;
    color: #1e40af;
  }
}
</style>
