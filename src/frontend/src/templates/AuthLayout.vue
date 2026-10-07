<script setup lang="ts">
const appName = import.meta.env.VITE_APP_NAME || 'Guatape'
</script>

<template>
  <div class="auth-layout">
    <div class="auth-layout__card">
      <RouterLink to="/" class="auth-layout__brand">{{ appName }}</RouterLink>
      <RouterView />
    </div>
  </div>
</template>

<style scoped>
.auth-layout {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  background: var(--p-surface-ground);
}

.auth-layout__card {
  width: 100%;
  max-width: 400px;
  background: var(--p-surface-card);
  border: 1px solid var(--p-surface-border);
  border-radius: 12px;
  padding: 32px 24px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.auth-layout__brand {
  font-size: 1.25rem;
  font-weight: 700;
  text-align: center;
  color: var(--p-text-color);
}

.auth-layout__brand:hover {
  text-decoration: none;
}

/* Shared by the pages rendered inside the card. */
:deep(.auth-form) {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

:deep(.auth-form h1) {
  font-size: 1.25rem;
}

:deep(.auth-form__field) {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

:deep(.auth-form__field label) {
  font-size: 0.85rem;
  font-weight: 600;
}

:deep(.auth-form__error) {
  color: var(--p-red-500);
}

:deep(.auth-form__links) {
  display: flex;
  justify-content: center;
  font-size: 0.875rem;
}
</style>
