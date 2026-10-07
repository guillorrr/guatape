<script setup lang="ts">
/**
 * Platform administration of organizations (super admins, central domain).
 * Creating one also creates its first admin; turning one off is "suspend"
 * (there's no delete: removing an organization's data is an offline task).
 */
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import AppCrudTable, { type CrudColumn } from '@/molecules/AppCrudTable.vue';
import AppField from '@/molecules/AppField.vue';
import AppModal from '@/molecules/AppModal.vue';
import { useAppToast } from '@/composables/useAppToast';
import { useDataTable } from '@/composables/useDataTable';
import { useForm } from '@/composables/useForm';
import { useFormatters } from '@/composables/useFormatters';
import { ApiError } from '@/core/services/api.service';
import { tenancyService, type Tenant } from '@/core/services/tenancy.service';

const { t } = useI18n();
const toast = useAppToast();
const confirm = useConfirm();
const { formatDate } = useFormatters();

const columns = computed<CrudColumn[]>(() => [
  { key: 'name', label: t('tenants.columns.name'), sortable: true, toggleable: false },
  { key: 'slug', label: t('tenants.columns.slug'), sortable: true },
  { key: 'url', label: t('tenants.columns.url') },
  { key: 'users_count', label: t('tenants.columns.users'), align: 'right', width: '110px' },
  { key: 'status', label: t('tenants.columns.status'), width: '120px' },
  {
    key: 'created_at',
    label: t('tenants.columns.createdAt'),
    sortable: true,
    defaultVisible: false,
  },
]);

const table = useDataTable<Tenant>({
  fetchFn: (params) => tenancyService.list(params),
  defaultSortBy: 'name',
  defaultSortDir: 'asc',
  persistKey: 'platform.tenants',
});

const statusOptions = computed(() =>
  (['active', 'suspended'] as const).map((value) => ({
    value,
    label: t(`tenants.status.${value}`),
  })),
);
const statusFilter = computed({
  get: () => (table.filters.status as string | undefined) ?? null,
  set: (value: string | null) =>
    value ? table.setFilter('status', value) : table.clearFilter('status'),
});

onMounted(() => table.fetch());

// --- Create (with its first admin) ---
const showCreate = ref(false);
const createForm = useForm({
  name: '',
  slug: '',
  domain: null as string | null,
  admin: { name: '', email: '', password: '', password_confirmation: '' },
});
let slugTouched = false;

/** "Acme Inc." → "acme-inc", as long as the slug wasn't edited by hand. */
watch(
  () => createForm.data.name,
  (name) => {
    if (slugTouched) return;
    createForm.data.slug = name
      .normalize('NFD')
      .replace(/[̀-ͯ]/g, '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '')
      .slice(0, 63);
  },
);

function openCreate() {
  slugTouched = false;
  createForm.reset();
  showCreate.value = true;
}

async function create() {
  try {
    await createForm.submit(() => tenancyService.create({ ...createForm.data }));
    toast.success(t('tenants.created'));
    showCreate.value = false;
    table.fetch();
  } catch (e) {
    if (e instanceof ApiError && !e.isValidation) toast.error(e.message);
  }
}

// --- Edit ---
const editing = ref<Tenant | null>(null);
const editForm = useForm({ name: '', domain: null as string | null });

function openEdit(tenant: Tenant) {
  editing.value = tenant;
  editForm.reset({ name: tenant.name, domain: tenant.domain });
}

async function saveEdit() {
  const tenant = editing.value;
  if (!tenant) return;
  try {
    await editForm.submit(() => tenancyService.update(tenant.id, { ...editForm.data }));
    toast.success(t('tenants.updated'));
    editing.value = null;
    table.fetch();
  } catch (e) {
    if (e instanceof ApiError && !e.isValidation) toast.error(e.message);
  }
}

// --- Suspend / reactivate ---
function toggleStatus(tenant: Tenant) {
  const suspending = tenant.status === 'active';
  confirm.require({
    header: suspending ? t('tenants.suspendTitle') : t('tenants.activateTitle'),
    message: suspending
      ? t('tenants.suspendMessage', { name: tenant.name })
      : t('tenants.activateMessage', { name: tenant.name }),
    icon: 'pi pi-exclamation-triangle',
    rejectProps: { label: t('common.cancel'), severity: 'secondary', outlined: true },
    acceptProps: {
      label: suspending ? t('tenants.suspend') : t('tenants.activate'),
      severity: suspending ? 'danger' : 'primary',
    },
    defaultFocus: 'reject',
    accept: async () => {
      try {
        await tenancyService.update(tenant.id, { status: suspending ? 'suspended' : 'active' });
        table.fetch();
      } catch (e) {
        if (e instanceof ApiError) toast.error(e.message);
      }
    },
  });
}
</script>

<template>
  <div class="page">
    <header class="page__header">
      <h1>{{ t('nav.tenants') }}</h1>
      <Button :label="t('tenants.new')" icon="pi pi-plus" @click="openCreate" />
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
      actions-width="110px"
      persist-key="platform.tenants"
      :search-placeholder="t('tenants.searchPlaceholder')"
      :empty-text="t('tenants.empty')"
      @page-change="table.goToPage"
      @per-page-change="table.setPerPage"
      @sort="table.setSort"
    >
      <template #filters>
        <Select
          v-model="statusFilter"
          :options="statusOptions"
          option-label="label"
          option-value="value"
          :placeholder="t('tenants.columns.status')"
          show-clear
          size="small"
        />
      </template>
      <template #cell-url="{ item }">
        <a :href="item.url" target="_blank" rel="noopener">{{ item.url }}</a>
      </template>
      <template #cell-status="{ item }">
        <Tag
          :value="t(`tenants.status.${item.status}`)"
          :severity="item.status === 'active' ? 'success' : 'danger'"
        />
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
          v-tooltip.top="item.status === 'active' ? t('tenants.suspend') : t('tenants.activate')"
          :icon="item.status === 'active' ? 'pi pi-ban' : 'pi pi-check-circle'"
          text
          rounded
          size="small"
          :severity="item.status === 'active' ? 'danger' : 'success'"
          :aria-label="item.status === 'active' ? t('tenants.suspend') : t('tenants.activate')"
          @click="toggleStatus(item)"
        />
      </template>
    </AppCrudTable>

    <AppModal :show="showCreate" :title="t('tenants.new')" @close="showCreate = false">
      <form id="tenant-create" class="form" @submit.prevent="create">
        <AppField
          :label="t('tenants.columns.name')"
          for="tenant-name"
          :error="createForm.error('name')"
          required
        >
          <InputText
            id="tenant-name"
            v-model="createForm.data.name"
            :invalid="createForm.hasError('name')"
          />
        </AppField>
        <AppField
          :label="t('tenants.columns.slug')"
          for="tenant-slug"
          :error="createForm.error('slug')"
          :hint="t('tenants.slugHint')"
          required
        >
          <InputText
            id="tenant-slug"
            v-model="createForm.data.slug"
            :invalid="createForm.hasError('slug')"
            @input="slugTouched = true"
          />
        </AppField>
        <AppField
          :label="t('tenants.domain')"
          for="tenant-domain"
          :error="createForm.error('domain')"
          :hint="t('tenants.domainHint')"
        >
          <InputText
            id="tenant-domain"
            v-model="createForm.data.domain"
            :invalid="createForm.hasError('domain')"
          />
        </AppField>

        <h3 class="form__section">{{ t('tenants.firstAdmin') }}</h3>
        <AppField
          :label="t('users.columns.name')"
          for="tenant-admin-name"
          :error="createForm.error('admin.name')"
          required
        >
          <InputText
            id="tenant-admin-name"
            v-model="createForm.data.admin.name"
            :invalid="createForm.hasError('admin.name')"
          />
        </AppField>
        <AppField
          :label="t('auth.email')"
          for="tenant-admin-email"
          :error="createForm.error('admin.email')"
          required
        >
          <InputText
            id="tenant-admin-email"
            v-model="createForm.data.admin.email"
            type="email"
            :invalid="createForm.hasError('admin.email')"
          />
        </AppField>
        <AppField
          :label="t('auth.password')"
          for="tenant-admin-password"
          :error="createForm.error('admin.password')"
          required
        >
          <Password
            v-model="createForm.data.admin.password"
            input-id="tenant-admin-password"
            :feedback="false"
            toggle-mask
            fluid
            :invalid="createForm.hasError('admin.password')"
          />
        </AppField>
        <AppField :label="t('auth.passwordConfirm')" for="tenant-admin-password-confirmation">
          <Password
            v-model="createForm.data.admin.password_confirmation"
            input-id="tenant-admin-password-confirmation"
            :feedback="false"
            toggle-mask
            fluid
          />
        </AppField>
      </form>
      <template #footer>
        <Button :label="t('common.cancel')" severity="secondary" text @click="showCreate = false" />
        <Button
          :label="t('common.create')"
          type="submit"
          form="tenant-create"
          :loading="createForm.processing.value"
        />
      </template>
    </AppModal>

    <AppModal
      :show="editing !== null"
      :title="t('tenants.editTitle')"
      size="sm"
      @close="editing = null"
    >
      <form id="tenant-edit" class="form" @submit.prevent="saveEdit">
        <AppField
          :label="t('tenants.columns.name')"
          for="tenant-edit-name"
          :error="editForm.error('name')"
          required
        >
          <InputText
            id="tenant-edit-name"
            v-model="editForm.data.name"
            :invalid="editForm.hasError('name')"
          />
        </AppField>
        <AppField
          :label="t('tenants.domain')"
          for="tenant-edit-domain"
          :error="editForm.error('domain')"
          :hint="t('tenants.domainHint')"
        >
          <InputText
            id="tenant-edit-domain"
            v-model="editForm.data.domain"
            :invalid="editForm.hasError('domain')"
          />
        </AppField>
      </form>
      <template #footer>
        <Button :label="t('common.cancel')" severity="secondary" text @click="editing = null" />
        <Button
          :label="t('common.save')"
          type="submit"
          form="tenant-edit"
          :loading="editForm.processing.value"
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

.form {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.form__section {
  font-size: 0.95rem;
  margin-top: 6px;
  padding-top: 12px;
  border-top: 1px solid var(--app-surface-border);
}
</style>
