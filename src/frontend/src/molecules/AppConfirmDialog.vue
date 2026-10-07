<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import AppModal from './AppModal.vue';
import AppButton from '@/atoms/AppButton.vue';

const { t } = useI18n();

defineProps<{
  show: boolean;
  title: string;
  message: string;
  confirmText?: string;
  cancelText?: string;
  variant?: 'danger' | 'primary';
}>();

const emit = defineEmits<{
  confirm: [];
  cancel: [];
}>();
</script>

<template>
  <AppModal :show="show" :title="title" size="sm" @close="emit('cancel')">
    <p>{{ message }}</p>
    <template #footer>
      <AppButton variant="ghost" @click="emit('cancel')">{{
        cancelText ?? t('common.cancel')
      }}</AppButton>
      <AppButton :variant="variant ?? 'danger'" @click="emit('confirm')">{{
        confirmText ?? t('common.confirm')
      }}</AppButton>
    </template>
  </AppModal>
</template>
