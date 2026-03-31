<script setup lang="ts">
export interface AppButtonProps {
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost';
  size?: 'sm' | 'md' | 'lg';
  disabled?: boolean;
  loading?: boolean;
  type?: 'button' | 'submit' | 'reset';
}

withDefaults(defineProps<AppButtonProps>(), {
  variant: 'primary',
  size: 'md',
  disabled: false,
  loading: false,
  type: 'button',
});
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="['btn', `btn--${variant}`, `btn--${size}`]"
  >
    <span v-if="loading" class="btn__spinner" />
    <slot />
  </button>
</template>

<style scoped lang="scss">
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: $spacing-xs;
  border: none;
  border-radius: $border-radius-sm;
  font-family: $font-family;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s ease;

  &:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  // Sizes
  &--sm { padding: $spacing-xs $spacing-sm; font-size: $font-size-sm; }
  &--md { padding: $spacing-sm $spacing-md; font-size: $font-size-base; }
  &--lg { padding: $spacing-sm $spacing-lg; font-size: $font-size-lg; }

  // Variants
  &--primary {
    background: $primary;
    color: $white;
    &:hover:not(:disabled) { background: $primary-dark; }
  }
  &--secondary {
    background: $gray-200;
    color: $gray-900;
    &:hover:not(:disabled) { background: $gray-300; }
  }
  &--danger {
    background: $danger;
    color: $white;
    &:hover:not(:disabled) { background: darken($danger, 10%); }
  }
  &--ghost {
    background: transparent;
    color: $gray-700;
    &:hover:not(:disabled) { background: $gray-100; }
  }

  &__spinner {
    width: 14px;
    height: 14px;
    border: 2px solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
  }
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
