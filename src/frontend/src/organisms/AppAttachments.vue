<script setup lang="ts">
import { useI18n } from 'vue-i18n';
/**
 * Attachments panel for any record registered in the API's
 * config/attachments.php: list, upload with progress, download, delete.
 *
 *   <AppAttachments type="users" :id="user.id" :can-manage="can('users.manage')" />
 */
import Button from 'primevue/button';
import ProgressBar from 'primevue/progressbar';
import { useConfirm } from 'primevue/useconfirm';
import { onMounted, ref, watch } from 'vue';
import AppFileInput from '@/molecules/AppFileInput.vue';
import { useAppToast } from '@/composables/useAppToast';
import { useFormatters } from '@/composables/useFormatters';
import { ApiError } from '@/core/services/api.service';
import { attachmentService, type Attachment } from '@/core/services/attachment.service';

const props = withDefaults(
  defineProps<{
    type: string;
    id: number;
    canManage?: boolean;
    accept?: string;
    maxKb?: number;
  }>(),
  { canManage: false, accept: undefined, maxKb: 20480 },
);

const toast = useAppToast();
const { t } = useI18n();
const confirm = useConfirm();
const { formatDateTime } = useFormatters();

const items = ref<Attachment[]>([]);
const loading = ref(false);
const pending = ref<File[]>([]);
const progress = ref<number | null>(null);

async function load() {
  loading.value = true;
  try {
    items.value = (await attachmentService.list(props.type, props.id)).data.data;
  } finally {
    loading.value = false;
  }
}

async function upload() {
  for (const file of pending.value) {
    progress.value = 0;
    try {
      const { data } = await attachmentService.upload(
        props.type,
        props.id,
        file,
        undefined,
        (p) => {
          progress.value = p;
        },
      );
      items.value = [data.data, ...items.value];
    } catch (e) {
      if (e instanceof ApiError) toast.error(`${file.name}: ${e.fieldError('file') ?? e.message}`);
    }
  }
  progress.value = null;
  pending.value = [];
}

function remove(item: Attachment) {
  confirm.require({
    header: t('forms.attachments.deleteTitle'),
    message: t('forms.attachments.deleteMessage', { name: item.original_filename }),
    icon: 'pi pi-exclamation-triangle',
    rejectProps: { label: t('common.cancel'), severity: 'secondary', outlined: true },
    acceptProps: { label: t('common.delete'), severity: 'danger' },
    defaultFocus: 'reject',
    accept: async () => {
      await attachmentService.destroy(item.id);
      items.value = items.value.filter((i) => i.id !== item.id);
    },
  });
}

function formatSize(bytes: number): string {
  if (bytes < 1024 * 1024) return `${Math.max(1, Math.round(bytes / 1024))} KB`;
  return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}

onMounted(load);
watch(() => [props.type, props.id], load);
</script>

<template>
  <section class="attachments">
    <template v-if="canManage">
      <AppFileInput v-model="pending" multiple :accept="accept" :max-kb="maxKb" />
      <div v-if="pending.length" class="attachments__upload">
        <Button
          :label="t('forms.attachments.upload', { n: pending.length }, pending.length)"
          icon="pi pi-upload"
          size="small"
          :loading="progress !== null"
          @click="upload"
        />
        <ProgressBar v-if="progress !== null" :value="progress" class="attachments__progress" />
      </div>
    </template>

    <p v-if="loading" class="attachments__muted">{{ t('common.loading') }}</p>
    <p v-else-if="!items.length" class="attachments__muted">{{ t('forms.attachments.empty') }}</p>
    <ul v-else class="attachments__list">
      <li v-for="item in items" :key="item.id">
        <i class="pi pi-paperclip" aria-hidden="true" />
        <div class="attachments__info">
          <a :href="item.download_url" class="attachments__name">{{ item.original_filename }}</a>
          <small class="attachments__muted">
            {{ formatSize(item.size_bytes) }} · {{ formatDateTime(item.created_at) }}
            <template v-if="item.uploaded_by"> · {{ item.uploaded_by.name }}</template>
          </small>
        </div>
        <Button
          v-if="canManage"
          icon="pi pi-trash"
          text
          rounded
          size="small"
          severity="danger"
          :aria-label="t('forms.attachments.delete', { name: item.original_filename })"
          @click="remove(item)"
        />
      </li>
    </ul>
  </section>
</template>

<style scoped>
.attachments {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.attachments__upload {
  display: flex;
  align-items: center;
  gap: 12px;
}

.attachments__progress {
  flex: 1;
  height: 8px;
}

.attachments__list {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.attachments__list li {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.attachments__info {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
}

.attachments__name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.attachments__muted {
  color: var(--p-text-muted-color);
  font-size: 0.85rem;
}
</style>
