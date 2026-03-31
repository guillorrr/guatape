<script setup lang="ts">
import { ref } from 'vue';
import { useAuthStore } from '@/core/stores/auth.store';
import AppInput from '@/atoms/AppInput.vue';
import AppButton from '@/atoms/AppButton.vue';

const auth = useAuthStore();
const email = ref('');
const password = ref('');
const error = ref('');

async function handleLogin() {
  error.value = '';
  try {
    await auth.login({ email: email.value, password: password.value });
  } catch (e: unknown) {
    error.value = 'Credenciales invalidas';
  }
}
</script>

<template>
  <div class="login-page">
    <form class="login-form" @submit.prevent="handleLogin">
      <h2>Iniciar sesion</h2>

      <p v-if="error" class="login-form__error">{{ error }}</p>

      <AppInput
        v-model="email"
        label="Email"
        type="email"
        placeholder="tu@email.com"
        required
      />

      <AppInput
        v-model="password"
        label="Contrasena"
        type="password"
        placeholder="********"
        required
      />

      <AppButton type="submit" :loading="auth.loading" size="lg">
        Ingresar
      </AppButton>
    </form>
  </div>
</template>

<style scoped lang="scss">
.login-page {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 60vh;
}

.login-form {
  background: $white;
  padding: $spacing-xl;
  border-radius: $border-radius;
  box-shadow: $shadow-md;
  width: 100%;
  max-width: 400px;
  display: flex;
  flex-direction: column;
  gap: $spacing-md;

  h2 {
    text-align: center;
  }

  &__error {
    color: $danger;
    text-align: center;
    font-size: $font-size-sm;
  }
}
</style>
