<script setup lang="ts">
import Button from 'primevue/button';
import { useI18n } from 'vue-i18n';
import { useRouter } from 'vue-router';

const router = useRouter();
const { t } = useI18n();
</script>

<template>
  <div class="not-found">
    <h1>404</h1>
    <p>{{ t('notFound.message') }}</p>
    <Button :label="t('common.backHome')" icon="pi pi-home" @click="router.push('/')" />
  </div>
</template>

<style scoped>
.not-found {
  min-height: 60vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  text-align: center;
  padding: 16px;
}

.not-found h1 {
  font-size: 3rem;
}
</style>
