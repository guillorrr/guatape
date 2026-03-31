<script setup lang="ts">
import { useAuthStore } from '@/core/stores/auth.store';
import AppButton from '@/atoms/AppButton.vue';

const auth = useAuthStore();
</script>

<template>
  <header class="app-header">
    <div class="app-header__container">
      <RouterLink to="/" class="app-header__logo">
        Guatape
      </RouterLink>
      <nav class="app-header__nav">
        <template v-if="auth.isAuthenticated">
          <RouterLink to="/dashboard">Dashboard</RouterLink>
          <AppButton variant="ghost" size="sm" @click="auth.logout()">
            Cerrar sesion
          </AppButton>
        </template>
        <template v-else>
          <RouterLink to="/login">Iniciar sesion</RouterLink>
        </template>
      </nav>
    </div>
  </header>
</template>

<style scoped lang="scss">
.app-header {
  background: $white;
  border-bottom: 1px solid $gray-200;
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
    color: $gray-900;

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
