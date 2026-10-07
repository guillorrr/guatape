<script setup lang="ts">
import { useAuthStore } from '@/core/stores/auth.store';

const auth = useAuthStore();
</script>

<template>
  <div class="dashboard-page">
    <h1>Inicio</h1>
    <p v-if="auth.user">Hola, {{ auth.user.name }}.</p>
  </div>
</template>

<style scoped>
.dashboard-page h1 {
  font-size: 1.5rem;
  margin-bottom: 8px;
}
</style>
