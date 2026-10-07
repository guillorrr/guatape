<script setup lang="ts">
import Select from 'primevue/select';

export interface SelectOption {
  value: string | number;
  label: string;
}

export interface AppSelectProps {
  label?: string;
  options: SelectOption[];
  placeholder?: string;
  required?: boolean;
  disabled?: boolean;
  error?: string;
  filter?: boolean;
}

withDefaults(defineProps<AppSelectProps>(), {
  label: undefined,
  placeholder: 'Seleccionar...',
  required: false,
  disabled: false,
  error: undefined,
  filter: false,
});

const model = defineModel<string | number | null>();
</script>

<template>
  <div class="field">
    <label v-if="label" class="field__label">
      {{ label }}
      <span v-if="required" class="field__required">*</span>
    </label>
    <Select
      v-model="model"
      :options="options"
      option-label="label"
      option-value="value"
      :placeholder="placeholder"
      :disabled="disabled"
      :invalid="!!error"
      :filter="filter"
      fluid
    />
    <small v-if="error" class="field__error">{{ error }}</small>
  </div>
</template>

<style scoped>
.field {
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.field__label {
  font-size: 0.85rem;
  font-weight: 500;
  color: var(--p-text-color);
}
.field__required {
  color: var(--p-red-500);
}
.field__error {
  color: var(--p-red-500);
  font-size: 0.75rem;
}
</style>
