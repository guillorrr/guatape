<script setup lang="ts">
import { reactive, ref, watch } from 'vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import { useAuthStore } from '@/core/stores/auth.store';
import { useAppToast } from '@/composables/useAppToast';
import { ApiError } from '@/core/services/api.service';

const auth = useAuthStore();
const toast = useAppToast();

const profile = reactive({
  name: auth.user?.name ?? '',
  email: auth.user?.email ?? '',
});
const profileSaving = ref(false);
const profileErrors = ref<Record<string, string[]>>({});

watch(
  () => auth.user,
  (u) => {
    if (u) {
      profile.name = u.name;
      profile.email = u.email;
    }
  },
  { immediate: true },
);

async function saveProfile() {
  profileSaving.value = true;
  profileErrors.value = {};
  try {
    await auth.updateProfile({ name: profile.name, email: profile.email });
    toast.success('Perfil actualizado');
  } catch (e) {
    const err = e instanceof ApiError ? e : null;
    profileErrors.value = err?.errors ?? {};
    toast.error(err?.message ?? 'Error al guardar el perfil');
  } finally {
    profileSaving.value = false;
  }
}

const passwordForm = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
});
const passwordSaving = ref(false);
const passwordErrors = ref<Record<string, string[]>>({});

async function changePassword() {
  passwordSaving.value = true;
  passwordErrors.value = {};
  try {
    await auth.changePassword({ ...passwordForm });
    toast.success('Contraseña actualizada. Las otras sesiones fueron cerradas.');
    passwordForm.current_password = '';
    passwordForm.password = '';
    passwordForm.password_confirmation = '';
  } catch (e) {
    const err = e instanceof ApiError ? e : null;
    passwordErrors.value = err?.errors ?? {};
    toast.error(err?.message ?? 'Error al cambiar la contraseña');
  } finally {
    passwordSaving.value = false;
  }
}

function firstError(field: string, errors: Record<string, string[]>): string | undefined {
  return errors[field]?.[0];
}
</script>

<template>
  <div class="account">
    <h1>Mi cuenta</h1>

    <section class="account__card">
      <header>
        <h2>Perfil</h2>
        <div v-if="auth.roles.length" class="account__roles">
          <Tag v-for="r in auth.roles" :key="r" :value="r" severity="info" />
        </div>
      </header>

      <form class="account__form" @submit.prevent="saveProfile">
        <div class="account__field">
          <label for="profile-name">Nombre</label>
          <InputText
            id="profile-name"
            v-model="profile.name"
            :invalid="!!firstError('name', profileErrors)"
            autocomplete="name"
          />
          <small v-if="firstError('name', profileErrors)" class="account__error">
            {{ firstError('name', profileErrors) }}
          </small>
        </div>

        <div class="account__field">
          <label for="profile-email">Email</label>
          <InputText
            id="profile-email"
            v-model="profile.email"
            type="email"
            :invalid="!!firstError('email', profileErrors)"
            autocomplete="email"
          />
          <small v-if="firstError('email', profileErrors)" class="account__error">
            {{ firstError('email', profileErrors) }}
          </small>
        </div>

        <Button type="submit" label="Guardar perfil" icon="pi pi-save" :loading="profileSaving" />
      </form>
    </section>

    <section class="account__card">
      <header>
        <h2>Cambiar contraseña</h2>
      </header>

      <Message severity="info" :closable="false" class="account__hint">
        Al cambiar la contraseña se cerrarán todas tus otras sesiones (esta se mantiene).
      </Message>

      <form class="account__form" @submit.prevent="changePassword">
        <div class="account__field">
          <label for="current-password">Contraseña actual</label>
          <Password
            v-model="passwordForm.current_password"
            input-id="current-password"
            :feedback="false"
            toggle-mask
            :invalid="!!firstError('current_password', passwordErrors)"
            autocomplete="current-password"
            fluid
          />
          <small v-if="firstError('current_password', passwordErrors)" class="account__error">
            {{ firstError('current_password', passwordErrors) }}
          </small>
        </div>

        <div class="account__field">
          <label for="new-password">Nueva contraseña</label>
          <Password
            v-model="passwordForm.password"
            input-id="new-password"
            toggle-mask
            :invalid="!!firstError('password', passwordErrors)"
            autocomplete="new-password"
            fluid
          />
          <small v-if="firstError('password', passwordErrors)" class="account__error">
            {{ firstError('password', passwordErrors) }}
          </small>
        </div>

        <div class="account__field">
          <label for="new-password-confirm">Repetir nueva contraseña</label>
          <Password
            v-model="passwordForm.password_confirmation"
            input-id="new-password-confirm"
            :feedback="false"
            toggle-mask
            autocomplete="new-password"
            fluid
          />
        </div>

        <Button
          type="submit"
          label="Cambiar contraseña"
          icon="pi pi-key"
          :loading="passwordSaving"
        />
      </form>
    </section>
  </div>
</template>

<style scoped lang="scss">
.account {
  max-width: 640px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 24px;

  h1 {
    margin: 0;
  }

  &__card {
    background: var(--app-surface-card);
    border: 1px solid var(--app-surface-border);
    border-radius: var(--p-border-radius);
    padding: 24px;

    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      margin-bottom: 16px;

      h2 {
        margin: 0;
        font-size: 1.125rem;
      }
    }
  }

  &__roles {
    display: flex;
    gap: 6px;
  }

  &__form {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  &__field {
    display: flex;
    flex-direction: column;
    gap: 6px;

    label {
      font-size: 0.875rem;
      font-weight: 500;
    }
  }

  &__error {
    color: var(--p-red-500);
    font-size: 0.8rem;
  }

  &__hint {
    margin-bottom: 8px;
  }
}
</style>
