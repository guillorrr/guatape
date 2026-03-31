<script setup lang="ts">
import AppHeader from '@/organisms/AppHeader.vue';
</script>

<template>
  <AppHeader />
  <main class="app-main">
    <slot />
  </main>
</template>

<style scoped lang="scss">
.app-main {
  flex: 1;
  padding: $spacing-lg;
  max-width: $breakpoint-xl;
  margin: 0 auto;
  width: 100%;
}
</style>
