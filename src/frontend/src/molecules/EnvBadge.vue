<script setup lang="ts">
const mode = import.meta.env.MODE;
const isDev = mode === 'development';
</script>

<template>
  <span
    class="env-badge"
    :class="isDev ? 'env-badge--dev' : 'env-badge--prod'"
    :title="
      isDev
        ? 'Entorno de desarrollo — sandbox, sin consecuencias reales'
        : 'Entorno productivo — cuidado con cambios destructivos'
    "
  >
    {{ isDev ? 'DEV' : 'PROD' }}
  </span>
</template>

<style scoped>
.env-badge {
  display: inline-flex;
  align-items: center;
  padding: 3px 10px;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  border-radius: 999px;
  font-family: monospace;
  user-select: none;
}

/* DEV = sandbox, señal tranquila en azul info */
.env-badge--dev {
  background: var(--p-blue-50);
  color: var(--p-blue-700);
  border: 1px solid var(--p-blue-200);
}

/* PROD = operás sobre datos reales, color danger loud */
.env-badge--prod {
  background: var(--p-red-500);
  color: white;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.25);
}
</style>
