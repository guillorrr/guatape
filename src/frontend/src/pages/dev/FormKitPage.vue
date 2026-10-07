<script setup lang="ts">
/**
 * Living reference of the form kit (registered only in development). Every
 * control is wired to useForm the way a real page would be.
 */
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import { computed, onMounted, ref } from 'vue';
import AppDatePicker from '@/atoms/AppDatePicker.vue';
import AppTimePicker from '@/atoms/AppTimePicker.vue';
import AppDateRange from '@/molecules/AppDateRange.vue';
import AppField from '@/molecules/AppField.vue';
import AppFileInput from '@/molecules/AppFileInput.vue';
import AppModal from '@/molecules/AppModal.vue';
import AppRemoteSelect, { type RemoteOption } from '@/molecules/AppRemoteSelect.vue';
import AppRepeater from '@/molecules/AppRepeater.vue';
import AppRichText from '@/molecules/AppRichText.vue';
import AppAttachments from '@/organisms/AppAttachments.vue';
import { useAppToast } from '@/composables/useAppToast';
import { useForm } from '@/composables/useForm';
import { usePermissions } from '@/composables/usePermissions';
import { useAuthStore } from '@/core/stores/auth.store';
import { ApiError } from '@/core/services/api.service';
import { userService } from '@/core/services/user.service';
import type { Role } from '@/core/models';

const auth = useAuthStore();
const toast = useAppToast();
const { can } = usePermissions();

const form = useForm({
  role: null as string | null,
  user_id: null as number | null,
  watchers: [] as number[],
  start_date: null as string | null,
  start_time: '09:00' as string | null,
  period: [null, null] as [string | null, string | null],
  description: null as string | null,
  phones: [{ type: 'mobile', number: '' }] as { type: string; number: string }[],
  files: [] as File[],
});

const roles = ref<Role[]>([]);
const roleOptions = computed(() => roles.value.map((r) => ({ label: r.name, value: r.name })));
const createdOptions = ref<RemoteOption[]>([]);
onMounted(async () => {
  roles.value = (await userService.roles()).data.data;
});

const phoneTypes = [
  { label: 'Celular', value: 'mobile' },
  { label: 'Trabajo', value: 'work' },
  { label: 'Casa', value: 'home' },
];

// Inline create from the remote select.
const quickCreate = useForm({ name: '', email: '', password: '', password_confirmation: '' });
const showQuickCreate = ref(false);
function openQuickCreate(query: string) {
  quickCreate.reset({
    name: query,
    email: '',
    password: 'secret-pass',
    password_confirmation: 'secret-pass',
  });
  showQuickCreate.value = true;
}
async function saveQuickCreate() {
  try {
    const { data } = await quickCreate.submit(() =>
      userService.create({ ...quickCreate.data, roles: form.data.role ? [form.data.role] : [] }),
    );
    createdOptions.value = [
      { value: data.data.id, label: data.data.name, description: data.data.email },
    ];
    form.data.user_id = data.data.id;
    showQuickCreate.value = false;
  } catch (e) {
    if (e instanceof ApiError && !e.isValidation) toast.error(e.message);
  }
}

const payload = computed(() =>
  JSON.stringify({ ...form.data, files: form.data.files.map((f) => f.name) }, null, 2),
);
</script>

<template>
  <div class="kit">
    <h1>Kit de formularios</h1>
    <p class="kit__muted">Referencia viva de los componentes (solo en desarrollo).</p>

    <div class="kit__grid">
      <section class="kit__card">
        <h2>Selects remotos</h2>
        <AppField label="Rol (filtra el siguiente)" for="kit-role">
          <Select
            v-model="form.data.role"
            input-id="kit-role"
            :options="roleOptions"
            option-label="label"
            option-value="value"
            placeholder="Todos"
            show-clear
          />
        </AppField>
        <AppField
          label="Responsable"
          for="kit-user"
          hint="Dependiente del rol; permite crear uno nuevo."
        >
          <AppRemoteSelect
            v-model="form.data.user_id"
            input-id="kit-user"
            :fetch-options="userService.options"
            :params="form.data.role ? { role: form.data.role } : {}"
            :initial-options="createdOptions"
            creatable
            @create="openQuickCreate"
          />
        </AppField>
        <AppField label="Observadores" for="kit-watchers">
          <AppRemoteSelect
            v-model="form.data.watchers"
            input-id="kit-watchers"
            :fetch-options="userService.options"
            multiple
          />
        </AppField>
      </section>

      <section class="kit__card">
        <h2>Fechas</h2>
        <AppField label="Fecha de inicio" for="kit-date">
          <AppDatePicker v-model="form.data.start_date" input-id="kit-date" />
        </AppField>
        <AppField label="Hora" for="kit-time">
          <AppTimePicker v-model="form.data.start_time" input-id="kit-time" />
        </AppField>
        <AppField label="Período" for="kit-period">
          <AppDateRange v-model="form.data.period" input-id="kit-period" />
        </AppField>
      </section>

      <section class="kit__card">
        <h2>Filas repetibles</h2>
        <AppRepeater
          v-model="form.data.phones"
          :new-item="() => ({ type: 'mobile', number: '' })"
          :max="4"
          add-label="Agregar teléfono"
          sortable
        >
          <template #default="{ item, index }">
            <div class="kit__row">
              <Select
                v-model="item.type"
                :options="phoneTypes"
                option-label="label"
                option-value="value"
                :aria-label="`Tipo de teléfono ${index + 1}`"
              />
              <InputText
                v-model="item.number"
                placeholder="Número"
                :aria-label="`Teléfono ${index + 1}`"
              />
            </div>
          </template>
        </AppRepeater>
      </section>

      <section class="kit__card">
        <h2>Texto enriquecido</h2>
        <AppRichText v-model="form.data.description" placeholder="Descripción…" />
      </section>

      <section class="kit__card">
        <h2>Archivos</h2>
        <AppField label="Selección local" for="kit-files">
          <AppFileInput
            v-model="form.data.files"
            input-id="kit-files"
            multiple
            accept=".pdf,image/*"
            :max-kb="2048"
          />
        </AppField>
        <h3>Adjuntos de mi usuario</h3>
        <AppAttachments
          v-if="auth.user"
          :id="auth.user.id"
          type="users"
          :can-manage="can('users.manage')"
        />
      </section>

      <section class="kit__card">
        <h2>Payload</h2>
        <pre class="kit__payload">{{ payload }}</pre>
        <Button label="Vaciar" severity="secondary" outlined size="small" @click="form.reset()" />
      </section>
    </div>

    <AppModal
      :show="showQuickCreate"
      title="Nuevo usuario"
      size="sm"
      @close="showQuickCreate = false"
    >
      <form id="kit-quick-create" class="kit__form" @submit.prevent="saveQuickCreate">
        <AppField label="Nombre" for="qc-name" :error="quickCreate.error('name')" required>
          <InputText
            id="qc-name"
            v-model="quickCreate.data.name"
            :invalid="quickCreate.hasError('name')"
          />
        </AppField>
        <AppField label="Email" for="qc-email" :error="quickCreate.error('email')" required>
          <InputText
            id="qc-email"
            v-model="quickCreate.data.email"
            type="email"
            :invalid="quickCreate.hasError('email')"
          />
        </AppField>
      </form>
      <template #footer>
        <Button label="Cancelar" text severity="secondary" @click="showQuickCreate = false" />
        <Button
          label="Crear"
          type="submit"
          form="kit-quick-create"
          :loading="quickCreate.processing.value"
        />
      </template>
    </AppModal>
  </div>
</template>

<style scoped>
.kit h1 {
  font-size: 1.5rem;
}

.kit__muted {
  color: var(--p-text-muted-color);
  margin-bottom: 16px;
}

.kit__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 380px), 1fr));
  gap: 16px;
}

.kit__card {
  background: var(--app-surface-card);
  border: 1px solid var(--app-surface-border);
  border-radius: 10px;
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  min-width: 0;
}

.kit__card h2 {
  font-size: 1.05rem;
}

.kit__card h3 {
  font-size: 0.95rem;
}

.kit__row {
  display: grid;
  grid-template-columns: minmax(0, 130px) minmax(0, 1fr);
  gap: 8px;
}

.kit__row :deep(.p-select) {
  min-width: 0;
}

.kit__form {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.kit__payload {
  background: var(--p-surface-100);
  padding: 12px;
  border-radius: 8px;
  font-size: 0.75rem;
  overflow: auto;
  max-height: 320px;
}
</style>
