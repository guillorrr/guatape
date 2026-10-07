<script setup lang="ts">
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import { computed } from 'vue';

export interface AppInputProps {
  label?: string;
  type?: string;
  placeholder?: string;
  error?: string;
  required?: boolean;
}

const props = withDefaults(defineProps<AppInputProps>(), {
  type: 'text',
  label: undefined,
  placeholder: undefined,
  error: undefined,
  required: false,
});

const model = defineModel<string | number>();

const isNumber = computed(() => props.type === 'number');

// InputNumber binds numbers and InputText strings; narrow the shared model.
const numberModel = computed({
  get: () =>
    typeof model.value === 'number' ? model.value : model.value ? Number(model.value) : null,
  set: (v: number | null) => {
    model.value = v ?? undefined;
  },
});
const textModel = computed({
  get: () => (model.value == null ? '' : String(model.value)),
  set: (v: string | undefined) => {
    model.value = v;
  },
});
</script>

<template>
  <div class="field">
    <label v-if="label" class="field__label">
      {{ label }}
      <span v-if="required" class="field__required">*</span>
    </label>
    <InputNumber
      v-if="isNumber"
      v-model="numberModel"
      :placeholder="placeholder"
      :disabled="false"
      :invalid="!!error"
      :min-fraction-digits="0"
      :max-fraction-digits="2"
      fluid
    />
    <InputText
      v-else
      v-model="textModel"
      :type="type"
      :placeholder="placeholder"
      :required="required"
      :invalid="!!error"
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
