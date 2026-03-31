<script setup lang="ts">
import { useAuthStore } from '@/core/stores/auth.store';

const auth = useAuthStore();
</script>

<template>
  <div class="dashboard-page">
    <h1>Dashboard</h1>
    <p v-if="auth.user">Bienvenido, {{ auth.user.name }}</p>
  </div>
</template>

<style scoped lang="scss">
.dashboard-page {
  h1 {
    font-size: $font-size-2xl;
    margin-bottom: $spacing-md;
  }
}
</style>
