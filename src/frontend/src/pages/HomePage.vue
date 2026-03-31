<script setup lang="ts">
import AppButton from '@/atoms/AppButton.vue';
</script>

<template>
  <div class="home-page">
    <h1>Guatape</h1>
    <p>Laravel 12 + Vue 3 scaffold</p>
    <RouterLink to="/login">
      <AppButton>Iniciar sesion</AppButton>
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
    color: $gray-500;
  }
}
</style>
