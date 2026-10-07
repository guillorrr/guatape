<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { useTenancyStore } from '@/core/stores/tenancy.store';

const { t } = useI18n();
const tenancy = useTenancyStore();
</script>

<template>
  <div class="unavailable">
    <i class="pi pi-building" aria-hidden="true" />
    <h1>
      {{
        tenancy.unavailable === 'suspended'
          ? t('tenancy.suspendedTitle')
          : t('tenancy.notFoundTitle')
      }}
    </h1>
    <p>
      {{
        tenancy.unavailable === 'suspended'
          ? t('tenancy.suspendedMessage')
          : t('tenancy.notFoundMessage')
      }}
    </p>
  </div>
</template>

<style scoped>
.unavailable {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 16px;
  text-align: center;
  background: var(--app-surface-ground);
}

.unavailable i {
  font-size: 2.5rem;
  color: var(--p-text-muted-color);
}

.unavailable h1 {
  font-size: 1.5rem;
}

.unavailable p {
  color: var(--p-text-muted-color);
  max-width: 420px;
}
</style>
