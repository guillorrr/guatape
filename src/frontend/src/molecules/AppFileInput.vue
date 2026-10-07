<script setup lang="ts">
import { useI18n } from 'vue-i18n';
/**
 * Pick one or more files by click or drag & drop. Validates size and type in
 * the browser for quick feedback; the API validates again.
 */
import Button from 'primevue/button';
import { computed, ref } from 'vue';

const props = withDefaults(
  defineProps<{
    modelValue: File[];
    multiple?: boolean;
    /** <input accept>: ".pdf,image/*" */
    accept?: string;
    /** Max size per file in KB (match config/attachments.php max_kb). */
    maxKb?: number;
    disabled?: boolean;
    inputId?: string;
  }>(),
  { multiple: false, accept: undefined, maxKb: 20480, disabled: false, inputId: undefined },
);

const { t } = useI18n();

const emit = defineEmits<{ 'update:modelValue': [files: File[]] }>();

const input = ref<HTMLInputElement | null>(null);
const dragging = ref(false);
const rejected = ref<string[]>([]);

const limitLabel = computed(() =>
  props.maxKb >= 1024 ? `${Math.round(props.maxKb / 1024)} MB` : `${props.maxKb} KB`,
);

function formatSize(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`;
  return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}

function accepts(file: File): boolean {
  if (!props.accept) return true;
  return props.accept.split(',').some((rule) => {
    const r = rule.trim().toLowerCase();
    if (r.startsWith('.')) return file.name.toLowerCase().endsWith(r);
    if (r.endsWith('/*')) return file.type.startsWith(r.slice(0, -1));
    return file.type === r;
  });
}

function take(list: FileList | null) {
  if (!list) return;
  rejected.value = [];
  const ok: File[] = [];
  for (const file of Array.from(list)) {
    if (file.size > props.maxKb * 1024)
      rejected.value.push(t('forms.file.tooBig', { name: file.name, size: limitLabel.value }));
    else if (!accepts(file)) rejected.value.push(t('forms.file.badType', { name: file.name }));
    else ok.push(file);
  }
  emit('update:modelValue', props.multiple ? [...props.modelValue, ...ok] : ok.slice(0, 1));
  if (input.value) input.value.value = '';
}

function remove(index: number) {
  emit(
    'update:modelValue',
    props.modelValue.filter((_, i) => i !== index),
  );
}

function onDrop(event: DragEvent) {
  dragging.value = false;
  if (!props.disabled) take(event.dataTransfer?.files ?? null);
}
</script>

<template>
  <div class="file-input">
    <div
      class="file-input__zone"
      :class="{ 'file-input__zone--drag': dragging, 'file-input__zone--disabled': disabled }"
      @dragover.prevent="dragging = !disabled"
      @dragleave.prevent="dragging = false"
      @drop.prevent="onDrop"
    >
      <i class="pi pi-cloud-upload" aria-hidden="true" />
      <span>{{ multiple ? t('forms.file.dropMany') : t('forms.file.dropOne') }}</span>
      <Button
        :label="t('common.choose')"
        size="small"
        outlined
        :disabled="disabled"
        @click="input?.click()"
      />
      <small
        >{{ t('forms.file.limit', { size: limitLabel }) }}{{ accept ? ` · ${accept}` : '' }}</small
      >
      <input
        :id="inputId"
        ref="input"
        type="file"
        class="file-input__native"
        :multiple="multiple"
        :accept="accept"
        :disabled="disabled"
        @change="take(($event.target as HTMLInputElement).files)"
      />
    </div>
    <ul v-if="modelValue.length" class="file-input__list">
      <li v-for="(file, index) in modelValue" :key="`${file.name}-${index}`">
        <i class="pi pi-file" aria-hidden="true" />
        <span class="file-input__name">{{ file.name }}</span>
        <small>{{ formatSize(file.size) }}</small>
        <Button
          icon="pi pi-times"
          text
          rounded
          size="small"
          severity="secondary"
          :aria-label="t('forms.file.remove', { name: file.name })"
          @click="remove(index)"
        />
      </li>
    </ul>
    <small v-for="message in rejected" :key="message" class="file-input__rejected">{{
      message
    }}</small>
  </div>
</template>

<style scoped>
.file-input {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.file-input__zone {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 16px;
  border: 2px dashed var(--app-surface-border);
  border-radius: 8px;
  color: var(--p-text-muted-color);
  text-align: center;
}

.file-input__zone--drag {
  border-color: var(--p-primary-color);
  background: var(--p-primary-50);
}

.file-input__zone--disabled {
  opacity: 0.6;
}

.file-input__native {
  display: none;
}

.file-input__list {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.file-input__list li {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}

.file-input__name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  flex: 1;
  min-width: 0;
}

.file-input__rejected {
  color: var(--p-red-500);
}
</style>
