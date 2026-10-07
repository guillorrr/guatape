<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import AppLocaleSwitcher from '@/molecules/AppLocaleSwitcher.vue';
import { useAuthStore } from '@/core/stores/auth.store';

const auth = useAuthStore();
const { t } = useI18n();
const appName = import.meta.env.VITE_APP_NAME || 'Guatape';
</script>

<template>
  <header class="app-header">
    <div class="app-header__container">
      <RouterLink to="/" class="app-header__logo">
        {{ appName }}
      </RouterLink>
      <nav class="app-header__nav">
        <AppLocaleSwitcher />
        <RouterLink v-if="auth.isAuthenticated" to="/app">{{ t('app.goToApp') }}</RouterLink>
        <RouterLink v-else :to="{ name: 'login' }">{{ t('app.login') }}</RouterLink>
      </nav>
    </div>
  </header>
</template>

<style scoped lang="scss">
.app-header {
  background: var(--app-surface-card);
  border-bottom: 1px solid var(--app-surface-border);
  padding: $spacing-sm $spacing-lg;

  &__container {
    max-width: $breakpoint-xl;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  &__logo {
    font-size: $font-size-xl;
    font-weight: 700;
    color: var(--p-text-color);

    &:hover {
      text-decoration: none;
    }
  }

  &__nav {
    display: flex;
    align-items: center;
    gap: $spacing-md;
  }
}
</style>
