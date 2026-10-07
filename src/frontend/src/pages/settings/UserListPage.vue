<script setup lang="ts">
/**
 * Reference implementation of the CRUD pattern (docs/datatable-pattern.md):
 * AppCrudTable + useDataTable for the list, useForm for create/edit with
 * server-side validation, permission-gated actions, confirm before delete.
 */
import { computed, onMounted, ref } from 'vue';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import MultiSelect from 'primevue/multiselect';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import AppCrudTable, { type CrudColumn } from '@/molecules/AppCrudTable.vue';
import AppModal from '@/molecules/AppModal.vue';
import { useDataTable } from '@/composables/useDataTable';
import { useForm } from '@/composables/useForm';
import { useAppToast } from '@/composables/useAppToast';
import { usePermissions } from '@/composables/usePermissions';
import { useFormatters } from '@/composables/useFormatters';
import { useAuthStore } from '@/core/stores/auth.store';
import { ApiError } from '@/core/services/api.service';
import { userService } from '@/core/services/user.service';
import type { Role, User } from '@/core/models';
import { useI18n } from 'vue-i18n';
import { LOCALE_NAMES, SUPPORTED_LOCALES } from '@/i18n';

const toast = useAppToast();
const { t } = useI18n();
const confirm = useConfirm();
const auth = useAuthStore();
const { can } = usePermissions();
const { formatDate } = useFormatters();
const canManage = computed(() => can('users.manage'));

const columns = computed<CrudColumn[]>(() => [
  { key: 'name', label: t('users.columns.name'), sortable: true, toggleable: false },
  { key: 'email', label: t('users.columns.email'), sortable: true },
  { key: 'roles', label: t('users.columns.roles') },
  { key: 'created_at', label: t('users.columns.createdAt'), sortable: true, defaultVisible: false },
]);

const languageOptions = computed(() => [
  { value: null, label: t('users.browserLanguage') },
  ...SUPPORTED_LOCALES.map((code) => ({ value: code, label: LOCALE_NAMES[code] })),
]);

const table = useDataTable<User>({
  fetchFn: (params) => userService.list(params),
  defaultSortBy: 'name',
  defaultSortDir: 'asc',
  persistKey: 'settings.users',
});

const roles = ref<Role[]>([]);
const roleOptions = computed(() => roles.value.map((r) => ({ label: r.name, value: r.name })));
const roleFilter = computed({
  get: () => (table.filters.role as string | undefined) ?? null,
  set: (value: string | null) =>
    value ? table.setFilter('role', value) : table.clearFilter('role'),
});

onMounted(async () => {
  table.fetch();
  try {
    roles.value = (await userService.roles()).data.data;
  } catch {
    /* the shell already toasts API failures */
  }
});

// --- Create / edit ---
const showForm = ref(false);
const editing = ref<User | null>(null);
const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  roles: [] as string[],
  locale: null as string | null,
});

function openCreate() {
  editing.value = null;
  form.reset();
  showForm.value = true;
}

function openEdit(user: User) {
  editing.value = user;
  form.reset({ name: user.name, email: user.email, roles: [...user.roles], locale: user.locale });
  showForm.value = true;
}

async function save() {
  try {
    await form.submit(async () => {
      if (editing.value) {
        await userService.update(editing.value.id, {
          name: form.data.name,
          email: form.data.email,
          locale: form.data.locale,
        });
        await userService.syncRoles(editing.value.id, form.data.roles);
      } else {
        await userService.create({ ...form.data });
      }
    });
    toast.success(editing.value ? t('users.updated') : t('users.created'));
    showForm.value = false;
    table.fetch();
  } catch (e) {
    if (e instanceof ApiError && !e.isValidation) toast.error(e.message);
  }
}

// --- Admin password reset ---
const passwordTarget = ref<User | null>(null);
const passwordForm = useForm({ password: '', password_confirmation: '' });

function openPassword(user: User) {
  passwordTarget.value = user;
  passwordForm.reset();
}

async function savePassword() {
  const target = passwordTarget.value;
  if (!target) return;
  try {
    const { data } = await passwordForm.submit(() =>
      userService.setPassword(
        target.id,
        passwordForm.data.password,
        passwordForm.data.password_confirmation,
      ),
    );
    toast.success(data.message);
    passwordTarget.value = null;
  } catch (e) {
    if (e instanceof ApiError && !e.isValidation) toast.error(e.message);
  }
}

// --- Delete ---
function confirmDelete(user: User) {
  confirm.require({
    header: t('users.deleteTitle'),
    message: t('users.deleteMessage', { name: user.name }),
    icon: 'pi pi-exclamation-triangle',
    rejectProps: { label: t('common.cancel'), severity: 'secondary', outlined: true },
    acceptProps: { label: t('common.delete'), severity: 'danger' },
    // Destructive: focus Cancel so a stray Enter doesn't delete.
    defaultFocus: 'reject',
    accept: async () => {
      try {
        await userService.destroy(user.id);
        toast.success(t('users.deleted'));
        table.fetch();
      } catch (e) {
        if (e instanceof ApiError)
          toast.error(e.fieldError('user') ?? e.fieldError('roles') ?? e.message);
      }
    },
  });
}
</script>

<template>
  <div class="page">
    <header class="page__header">
      <h1>{{ t('nav.users') }}</h1>
      <Button v-if="canManage" :label="t('users.new')" icon="pi pi-plus" @click="openCreate" />
    </header>

    <AppCrudTable
      v-model:search="table.search.value"
      :columns="columns"
      :data="table.data.value"
      :loading="table.loading.value"
      :current-page="table.currentPage.value"
      :last-page="table.lastPage.value"
      :total="table.total.value"
      :per-page="table.perPage.value"
      :sort-by="table.sortBy.value"
      :sort-dir="table.sortDir.value"
      :has-actions="canManage"
      actions-width="140px"
      persist-key="settings.users"
      :search-placeholder="t('users.searchPlaceholder')"
      :empty-text="t('users.empty')"
      @page-change="table.goToPage"
      @per-page-change="table.setPerPage"
      @sort="table.setSort"
    >
      <template #filters>
        <Select
          v-model="roleFilter"
          :options="roleOptions"
          option-label="label"
          option-value="value"
          :placeholder="t('users.allRoles')"
          show-clear
          size="small"
        />
      </template>

      <template #cell-name="{ item }">
        {{ item.name }}
        <Tag
          v-if="item.id === auth.user?.id"
          :value="t('common.you')"
          severity="secondary"
          class="page__me"
        />
      </template>
      <template #cell-roles="{ item }">
        <div class="page__tags">
          <Tag v-for="role in item.roles" :key="role" :value="role" severity="info" />
          <span v-if="!item.roles.length" class="page__muted">{{ t('users.noRole') }}</span>
        </div>
      </template>
      <template #cell-created_at="{ item }">{{ formatDate(item.created_at) }}</template>

      <template #actions="{ item }">
        <Button
          v-tooltip.top="t('common.edit')"
          icon="pi pi-pencil"
          text
          rounded
          size="small"
          :aria-label="t('common.edit')"
          @click="openEdit(item)"
        />
        <Button
          v-tooltip.top="t('users.changePassword')"
          icon="pi pi-key"
          text
          rounded
          size="small"
          :aria-label="t('users.changePassword')"
          @click="openPassword(item)"
        />
        <Button
          v-tooltip.top="t('common.delete')"
          icon="pi pi-trash"
          text
          rounded
          size="small"
          severity="danger"
          :aria-label="t('common.delete')"
          :disabled="item.id === auth.user?.id"
          @click="confirmDelete(item)"
        />
      </template>
    </AppCrudTable>

    <AppModal
      :show="showForm"
      :title="editing ? t('users.editTitle') : t('users.new')"
      @close="showForm = false"
    >
      <form id="user-form" class="form" @submit.prevent="save">
        <div class="form__field">
          <label for="user-name">{{ t('users.columns.name') }}</label>
          <InputText
            id="user-name"
            v-model="form.data.name"
            :invalid="form.hasError('name')"
            autocomplete="off"
          />
          <small v-if="form.error('name')" class="form__error">{{ form.error('name') }}</small>
        </div>
        <div class="form__field">
          <label for="user-email">{{ t('auth.email') }}</label>
          <InputText
            id="user-email"
            v-model="form.data.email"
            type="email"
            :invalid="form.hasError('email')"
            autocomplete="off"
          />
          <small v-if="form.error('email')" class="form__error">{{ form.error('email') }}</small>
        </div>
        <template v-if="!editing">
          <div class="form__field">
            <label for="user-password">{{ t('auth.password') }}</label>
            <Password
              v-model="form.data.password"
              input-id="user-password"
              :feedback="false"
              toggle-mask
              fluid
              :invalid="form.hasError('password')"
            />
            <small v-if="form.error('password')" class="form__error">{{
              form.error('password')
            }}</small>
          </div>
          <div class="form__field">
            <label for="user-password-confirmation">{{ t('auth.passwordConfirm') }}</label>
            <Password
              v-model="form.data.password_confirmation"
              input-id="user-password-confirmation"
              :feedback="false"
              toggle-mask
              fluid
            />
          </div>
        </template>
        <div class="form__field">
          <label for="user-locale">{{ t('common.language') }}</label>
          <Select
            v-model="form.data.locale"
            input-id="user-locale"
            :options="languageOptions"
            option-label="label"
            option-value="value"
          />
        </div>
        <div class="form__field">
          <label for="user-roles">{{ t('users.columns.roles') }}</label>
          <MultiSelect
            v-model="form.data.roles"
            input-id="user-roles"
            :options="roleOptions"
            option-label="label"
            option-value="value"
            :placeholder="t('users.noRole')"
            display="chip"
            :invalid="form.hasError('roles')"
          />
          <small v-if="form.error('roles')" class="form__error">{{ form.error('roles') }}</small>
        </div>
      </form>
      <template #footer>
        <Button :label="t('common.cancel')" severity="secondary" text @click="showForm = false" />
        <Button
          :label="t('common.save')"
          type="submit"
          form="user-form"
          :loading="form.processing.value"
        />
      </template>
    </AppModal>

    <AppModal
      :show="passwordTarget !== null"
      :title="t('users.passwordTitle', { name: passwordTarget?.name ?? '' })"
      size="sm"
      @close="passwordTarget = null"
    >
      <form id="password-form" class="form" @submit.prevent="savePassword">
        <div class="form__field">
          <label for="new-password">{{ t('users.newPassword') }}</label>
          <Password
            v-model="passwordForm.data.password"
            input-id="new-password"
            :feedback="false"
            toggle-mask
            fluid
            :invalid="passwordForm.hasError('password')"
          />
          <small v-if="passwordForm.error('password')" class="form__error">{{
            passwordForm.error('password')
          }}</small>
        </div>
        <div class="form__field">
          <label for="new-password-confirmation">{{ t('auth.passwordConfirm') }}</label>
          <Password
            v-model="passwordForm.data.password_confirmation"
            input-id="new-password-confirmation"
            :feedback="false"
            toggle-mask
            fluid
          />
        </div>
        <small class="page__muted">{{ t('users.passwordHint') }}</small>
      </form>
      <template #footer>
        <Button
          :label="t('common.cancel')"
          severity="secondary"
          text
          @click="passwordTarget = null"
        />
        <Button
          :label="t('common.save')"
          type="submit"
          form="password-form"
          :loading="passwordForm.processing.value"
        />
      </template>
    </AppModal>
  </div>
</template>

<style scoped>
.page__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.page__header h1 {
  font-size: 1.5rem;
}

.page__tags {
  display: flex;
  gap: 4px;
  flex-wrap: wrap;
}

.page__me {
  margin-left: 6px;
}

.page__muted {
  color: var(--p-text-muted-color);
  font-size: 0.85rem;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.form__field {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form__field label {
  font-size: 0.85rem;
  font-weight: 600;
}

.form__error {
  color: var(--p-red-500);
}
</style>
