<script setup lang="ts">
import AppModal from './AppModal.vue'
import AppButton from '@/atoms/AppButton.vue'

defineProps<{
  show: boolean
  title: string
  message: string
  confirmText?: string
  cancelText?: string
  variant?: 'danger' | 'primary'
}>()

const emit = defineEmits<{
  confirm: []
  cancel: []
}>()
</script>

<template>
  <AppModal :show="show" :title="title" size="sm" @close="emit('cancel')">
    <p>{{ message }}</p>
    <template #footer>
      <AppButton variant="ghost" @click="emit('cancel')">{{ cancelText ?? 'Cancelar' }}</AppButton>
      <AppButton :variant="variant ?? 'danger'" @click="emit('confirm')">{{ confirmText ?? 'Confirmar' }}</AppButton>
    </template>
  </AppModal>
</template>
