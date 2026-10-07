<script setup lang="ts">
defineProps<{
  value: number
  max?: number
  variant?: 'primary' | 'success' | 'warning' | 'danger'
  showLabel?: boolean
  height?: string
}>()
</script>

<template>
  <div class="progress" :style="{ height: height ?? '8px' }">
    <div
      class="progress__bar"
      :class="`progress__bar--${variant ?? 'primary'}`"
      :style="{ width: `${Math.min((value / (max ?? 100)) * 100, 100)}%` }"
    />
    <span v-if="showLabel" class="progress__label">{{ Math.round((value / (max ?? 100)) * 100) }}%</span>
  </div>
</template>

<style scoped lang="scss">
.progress {
  width: 100%;
  background: #e5e7eb;
  border-radius: 9999px;
  overflow: hidden;
  position: relative;

  &__bar {
    height: 100%;
    border-radius: 9999px;
    transition: width 0.3s ease;

    &--primary { background: #1a73e8; }
    &--success { background: #10b981; }
    &--warning { background: #f59e0b; }
    &--danger { background: #ef4444; }
  }

  &__label {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.65rem;
    font-weight: 600;
    color: #fff;
  }
}
</style>
