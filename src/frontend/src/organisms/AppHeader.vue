<script setup lang="ts">
import { useAuthStore } from '@/core/stores/auth.store';

const auth = useAuthStore();
const appName = import.meta.env.VITE_APP_NAME || 'Guatape';
</script>

<template>
  <header class="app-header">
    <div class="app-header__container">
      <RouterLink to="/" class="app-header__logo">
        {{ appName }}
      </RouterLink>
      <nav class="app-header__nav">
        <RouterLink v-if="auth.isAuthenticated" to="/app">Ir a la aplicación</RouterLink>
        <RouterLink v-else :to="{ name: 'login' }">Iniciar sesión</RouterLink>
      </nav>
    </div>
  </header>
</template>

<style scoped lang="scss">
.app-header {
  background: var(--p-surface-card);
  border-bottom: 1px solid var(--p-surface-border);
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
