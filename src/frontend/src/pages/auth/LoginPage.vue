<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import Password from 'primevue/password';
import { ref } from 'vue';
import { useAuthStore } from '@/core/stores/auth.store';
import { ApiError } from '@/core/services/api.service';
import { useForm } from '@/composables/useForm';

const auth = useAuthStore();
const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const form = useForm({ email: '', password: '', remember: false });
const failure = ref<string | null>(null);

async function submit() {
  failure.value = null;
  try {
    await form.submit(() => auth.login({ ...form.data }));
    const redirect =
      typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/')
        ? route.query.redirect
        : '/app';
    await router.push(redirect);
  } catch (e) {
    if (e instanceof ApiError && !e.isValidation) failure.value = e.message;
  }
}
</script>

<template>
  <form class="auth-form" @submit.prevent="submit">
    <h1>{{ t('auth.login.title') }}</h1>

    <Message v-if="failure" severity="error" size="small">{{ failure }}</Message>

    <div class="auth-form__field">
      <label for="login-email">{{ t('auth.email') }}</label>
      <InputText
        id="login-email"
        v-model="form.data.email"
        type="email"
        autocomplete="username"
        :invalid="form.hasError('email')"
        autofocus
      />
      <small v-if="form.error('email')" class="auth-form__error">{{ form.error('email') }}</small>
    </div>

    <div class="auth-form__field">
      <label for="login-password">{{ t('auth.password') }}</label>
      <Password
        v-model="form.data.password"
        input-id="login-password"
        :feedback="false"
        toggle-mask
        fluid
        autocomplete="current-password"
        :invalid="form.hasError('password')"
      />
      <small v-if="form.error('password')" class="auth-form__error">{{
        form.error('password')
      }}</small>
    </div>

    <label class="auth-form__remember">
      <Checkbox v-model="form.data.remember" binary input-id="login-remember" />
      <span>{{ t('auth.login.remember') }}</span>
    </label>

    <Button type="submit" :label="t('auth.login.submit')" :loading="form.processing.value" />

    <div class="auth-form__links">
      <RouterLink :to="{ name: 'forgot-password' }">{{ t('auth.login.forgot') }}</RouterLink>
    </div>
  </form>
</template>

<style scoped>
.auth-form__remember {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.875rem;
}
</style>
