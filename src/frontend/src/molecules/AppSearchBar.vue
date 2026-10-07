<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import { ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';

const { t } = useI18n();

const props = defineProps<{
  modelValue: string;
  placeholder?: string;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: string];
}>();

const localValue = ref(props.modelValue);

const debouncedEmit = useDebounceFn((val: string) => {
  emit('update:modelValue', val);
}, 300);

watch(localValue, (val) => debouncedEmit(val));
watch(
  () => props.modelValue,
  (val) => {
    localValue.value = val;
  },
);

function clear() {
  localValue.value = '';
  emit('update:modelValue', '');
}
</script>

<template>
  <div class="app-search-bar">
    <IconField>
      <InputIcon class="pi pi-search" />
      <InputText
        v-model="localValue"
        :placeholder="placeholder ?? t('common.search')"
        fluid
        class="search-input"
      />
    </IconField>
    <button
      v-if="localValue"
      type="button"
      class="clear-btn"
      aria-label="Limpiar busqueda"
      @click="clear"
    >
      <i class="pi pi-times" />
    </button>
  </div>
</template>

<style scoped>
.app-search-bar {
  margin-bottom: 16px;
  max-width: 400px;
  position: relative;
}

.search-input {
  padding-right: 2.25rem;
}

.clear-btn {
  position: absolute;
  top: 50%;
  right: 0.5rem;
  transform: translateY(-50%);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.75rem;
  height: 1.75rem;
  border: none;
  border-radius: 50%;
  background: transparent;
  color: var(--p-text-muted-color);
  cursor: pointer;
  transition:
    background-color 0.15s,
    color 0.15s;
}

.clear-btn:hover {
  background: var(--p-surface-100);
  color: var(--p-text-color);
}

.clear-btn i {
  font-size: 0.75rem;
}
</style>
