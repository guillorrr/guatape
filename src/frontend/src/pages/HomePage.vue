<script setup lang="ts">
import Button from 'primevue/button'
import { useAuthStore } from '@/core/stores/auth.store'

const auth = useAuthStore()
const appName = import.meta.env.VITE_APP_NAME || 'Guatape'
</script>

<template>
  <div class="home-page">
    <h1>{{ appName }}</h1>
    <p>Laravel 13 + Vue 3</p>
    <RouterLink v-if="auth.isAuthenticated" to="/app">
      <Button label="Ir a la aplicación" icon="pi pi-arrow-right" icon-pos="right" />
    </RouterLink>
    <RouterLink v-else :to="{ name: 'login' }">
      <Button label="Iniciar sesión" />
    </RouterLink>
  </div>
</template>

<style scoped lang="scss">
.home-page {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 60vh;
  gap: $spacing-md;
  text-align: center;

  h1 {
    font-size: $font-size-2xl;
  }

  p {
    color: var(--p-text-muted-color);
  }
}
</style>
