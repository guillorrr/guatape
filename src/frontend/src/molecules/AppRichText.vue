<script setup lang="ts">
/**
 * Rich-text editor (PrimeVue Editor / Quill) bound to an HTML string.
 *
 * The toolbar only offers what the API keeps: the backend runs every rich-text
 * field through HtmlSanitizer (SanitizedHtml cast), which strips scripts,
 * event handlers, styles and anything not in its allowlist. Never render the
 * stored HTML with v-html unless it went through that cast.
 */
import Editor from 'primevue/editor';
import { computed } from 'vue';
import { normalizeEditorHtml } from '@/core/utils/html';

const props = withDefaults(
  defineProps<{
    modelValue: string | null;
    placeholder?: string;
    /** Editor height (CSS). */
    height?: string;
    invalid?: boolean;
    readonly?: boolean;
  }>(),
  { placeholder: '', height: '180px', invalid: false, readonly: false },
);

const emit = defineEmits<{ 'update:modelValue': [value: string | null] }>();

/** Formats the toolbar allows; keep in sync with HtmlSanitizer on the API. */
const formats = ['bold', 'italic', 'underline', 'strike', 'header', 'list', 'link', 'blockquote'];

const html = computed({
  get: () => props.modelValue ?? '',
  // Fixes Quill 2's &nbsp; for every space and its empty "<p><br></p>".
  set: (value: string | null | undefined) => emit('update:modelValue', normalizeEditorHtml(value)),
});
</script>

<template>
  <Editor
    v-model="html"
    :formats="formats"
    :placeholder="placeholder"
    :readonly="readonly"
    :editor-style="{ height }"
    :class="{ 'rich-text--invalid': invalid }"
  >
    <template #toolbar>
      <span class="ql-formats">
        <select class="ql-header">
          <option value="2">Título</option>
          <option value="3">Subtítulo</option>
          <option value="0" selected>Normal</option>
        </select>
      </span>
      <span class="ql-formats">
        <button class="ql-bold" type="button" aria-label="Negrita"></button>
        <button class="ql-italic" type="button" aria-label="Cursiva"></button>
        <button class="ql-underline" type="button" aria-label="Subrayado"></button>
        <button class="ql-strike" type="button" aria-label="Tachado"></button>
      </span>
      <span class="ql-formats">
        <button class="ql-list" value="ordered" type="button" aria-label="Lista numerada"></button>
        <button
          class="ql-list"
          value="bullet"
          type="button"
          aria-label="Lista con viñetas"
        ></button>
        <button class="ql-blockquote" type="button" aria-label="Cita"></button>
        <button class="ql-link" type="button" aria-label="Enlace"></button>
      </span>
      <span class="ql-formats">
        <button class="ql-clean" type="button" aria-label="Quitar formato"></button>
      </span>
    </template>
  </Editor>
</template>

<style scoped>
.rich-text--invalid :deep(.p-editor-toolbar),
.rich-text--invalid :deep(.p-editor-content) {
  border-color: var(--p-red-500);
}
</style>
