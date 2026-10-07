<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Password from 'primevue/password';
import { ref } from 'vue';
import { useAuthStore } from '@/core/stores/auth.store';
import { ApiError } from '@/core/services/api.service';
import { useForm } from '@/composables/useForm';
import { useAppToast } from '@/composables/useAppToast';

const auth = useAuthStore();
const route = useRoute();
const router = useRouter();
const toast = useAppToast();

const token = typeof route.query.token === 'string' ? route.query.token : '';
const email = typeof route.query.email === 'string' ? route.query.email : '';
const form = useForm({ password: '', password_confirmation: '' });
const failure = ref<string | null>(
  token && email ? null : 'El enlace está incompleto. Pedí uno nuevo.',
);

async function submit() {
  failure.value = null;
  try {
    const message = await form.submit(() => auth.resetPassword({ token, email, ...form.data }));
    toast.success(message);
    await router.push({ name: 'login' });
  } catch (e) {
    // An expired or reused token comes back as a 422 on `email`.
    if (e instanceof ApiError)
      failure.value = e.fieldError('email') ?? (e.isValidation ? null : e.message);
  }
}
</script>

<template>
  <form class="auth-form" @submit.prevent="submit">
    <h1>Nueva contraseña</h1>
    <p v-if="email" class="auth-form__email">{{ email }}</p>

    <Message v-if="failure" severity="error" size="small">
      {{ failure }}
      <RouterLink :to="{ name: 'forgot-password' }">Pedir otro enlace</RouterLink>
    </Message>

    <div class="auth-form__field">
      <label for="reset-password">Contraseña</label>
      <Password
        v-model="form.data.password"
        input-id="reset-password"
        toggle-mask
        fluid
        autocomplete="new-password"
        :invalid="form.hasError('password')"
      />
      <small v-if="form.error('password')" class="auth-form__error">{{
        form.error('password')
      }}</small>
    </div>
    <div class="auth-form__field">
      <label for="reset-password-confirmation">Repetir contraseña</label>
      <Password
        v-model="form.data.password_confirmation"
        input-id="reset-password-confirmation"
        :feedback="false"
        toggle-mask
        fluid
        autocomplete="new-password"
      />
    </div>

    <Button
      type="submit"
      label="Guardar contraseña"
      :loading="form.processing.value"
      :disabled="!token || !email"
    />
  </form>
</template>

<style scoped>
.auth-form__email {
  color: var(--p-text-muted-color);
  font-size: 0.875rem;
}
</style>
