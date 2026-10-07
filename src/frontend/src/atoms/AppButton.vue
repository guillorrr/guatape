<script setup lang="ts">
import Button from 'primevue/button'
import { computed } from 'vue'

export interface AppButtonProps {
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost'
  size?: 'sm' | 'md' | 'lg'
  disabled?: boolean
  loading?: boolean
  type?: 'button' | 'submit' | 'reset'
}

const props = withDefaults(defineProps<AppButtonProps>(), {
  variant: 'primary',
  size: 'md',
  disabled: false,
  loading: false,
  type: 'button',
})

const severity = computed(() => {
  const map: Record<string, string | undefined> = {
    primary: undefined,
    secondary: 'secondary',
    danger: 'danger',
    ghost: 'secondary',
  }
  return map[props.variant]
})

const isText = computed(() => props.variant === 'ghost')

const pSize = computed(() => {
  const map: Record<string, string> = { sm: 'small', md: '', lg: 'large' }
  return map[props.size] || undefined
})
</script>

<template>
  <Button
    :type="type"
    :severity="severity"
    :text="isText"
    :size="pSize"
    :disabled="disabled"
    :loading="loading"
  >
    <slot />
  </Button>
</template>
