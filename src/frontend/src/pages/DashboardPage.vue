<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { useAuthStore } from '@/core/stores/auth.store';

const auth = useAuthStore();
const { t } = useI18n();
</script>

<template>
  <div class="dashboard-page">
    <h1>{{ t('nav.home') }}</h1>
    <p v-if="auth.user">{{ t('dashboard.greeting', { name: auth.user.name }) }}</p>
  </div>
</template>

<style scoped>
.dashboard-page h1 {
  font-size: 1.5rem;
  margin-bottom: 8px;
}
</style>
