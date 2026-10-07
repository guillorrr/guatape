<script setup lang="ts">
import AppHeader from '@/organisms/AppHeader.vue';
</script>

<!-- Public pages (landing, legal…). The authenticated area uses DashboardLayout. -->
<template>
  <AppHeader />
  <main class="app-main">
    <RouterView />
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
