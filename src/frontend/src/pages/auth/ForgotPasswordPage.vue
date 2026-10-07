<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Message from 'primevue/message';
import { ref } from 'vue';
import { useAuthStore } from '@/core/stores/auth.store';
import { ApiError } from '@/core/services/api.service';
import { useForm } from '@/composables/useForm';

const auth = useAuthStore();
const { t } = useI18n();
const form = useForm({ email: '' });
const sent = ref<string | null>(null);
const failure = ref<string | null>(null);

async function submit() {
  failure.value = null;
  try {
    sent.value = await form.submit(() => auth.forgotPassword(form.data.email));
  } catch (e) {
    if (e instanceof ApiError && !e.isValidation) failure.value = e.message;
  }
}
</script>

<template>
  <form class="auth-form" @submit.prevent="submit">
    <h1>{{ t('auth.forgot.title') }}</h1>

    <Message v-if="sent" severity="success" size="small">{{ t('auth.forgot.sent') }}</Message>
    <Message v-if="failure" severity="error" size="small">{{ failure }}</Message>

    <template v-if="!sent">
      <div class="auth-form__field">
        <label for="forgot-email">{{ t('auth.email') }}</label>
        <InputText
          id="forgot-email"
          v-model="form.data.email"
          type="email"
          autocomplete="username"
          :invalid="form.hasError('email')"
          autofocus
        />
        <small v-if="form.error('email')" class="auth-form__error">{{ form.error('email') }}</small>
      </div>
      <Button type="submit" :label="t('auth.forgot.submit')" :loading="form.processing.value" />
    </template>

    <div class="auth-form__links">
      <RouterLink :to="{ name: 'login' }">{{ t('auth.forgot.back') }}</RouterLink>
    </div>
  </form>
</template>
