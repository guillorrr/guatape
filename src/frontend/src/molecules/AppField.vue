<script setup lang="ts">
/**
 * Label + control + hint/error, the same way on every form:
 *
 *   <AppField label="Email" for="user-email" :error="form.error('email')" required>
 *     <InputText id="user-email" v-model="form.data.email" :invalid="form.hasError('email')" />
 *   </AppField>
 */
withDefaults(
  defineProps<{
    label?: string;
    /** id of the control, so clicking the label focuses it. */
    for?: string;
    error?: string;
    hint?: string;
    required?: boolean;
  }>(),
  { label: undefined, for: undefined, error: undefined, hint: undefined, required: false },
);
</script>

<template>
  <div class="app-field" :class="{ 'app-field--invalid': !!error }">
    <label v-if="label" class="app-field__label" :for="$props.for">
      {{ label }}<span v-if="required" class="app-field__required" aria-hidden="true">*</span>
    </label>
    <slot />
    <small v-if="error" class="app-field__error" role="alert">{{ error }}</small>
    <small v-else-if="hint" class="app-field__hint">{{ hint }}</small>
  </div>
</template>

<style scoped>
.app-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 0;
}

.app-field__label {
  font-size: 0.85rem;
  font-weight: 600;
}

.app-field__required {
  color: var(--p-red-500);
  margin-left: 2px;
}

.app-field__error {
  color: var(--p-red-500);
}

.app-field__hint {
  color: var(--p-text-muted-color);
}

/* Make PrimeVue inputs fill the field. */
.app-field :deep(.p-inputtext),
.app-field :deep(.p-select),
.app-field :deep(.p-multiselect),
.app-field :deep(.p-autocomplete),
.app-field :deep(.p-datepicker),
.app-field :deep(.p-password),
.app-field :deep(.p-inputnumber),
.app-field :deep(.p-textarea) {
  width: 100%;
}
</style>
