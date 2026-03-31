<script setup lang="ts">
export interface AppInputProps {
  label?: string;
  type?: string;
  placeholder?: string;
  error?: string;
  required?: boolean;
}

withDefaults(defineProps<AppInputProps>(), {
  type: 'text',
  label: undefined,
  placeholder: undefined,
  error: undefined,
  required: false,
});

const model = defineModel<string>();
</script>

<template>
  <div class="input-field" :class="{ 'input-field--error': error }">
    <label v-if="label" class="input-field__label">
      {{ label }}
      <span v-if="required" class="input-field__required">*</span>
    </label>
    <input
      v-model="model"
      :type="type"
      :placeholder="placeholder"
      :required="required"
      class="input-field__input"
    />
    <span v-if="error" class="input-field__error">{{ error }}</span>
  </div>
</template>

<style scoped lang="scss">
.input-field {
  display: flex;
  flex-direction: column;
  gap: $spacing-xs;

  &__label {
    font-size: $font-size-sm;
    font-weight: 500;
    color: $gray-700;
  }

  &__required {
    color: $danger;
  }

  &__input {
    padding: $spacing-sm $spacing-md;
    border: 1px solid $gray-300;
    border-radius: $border-radius-sm;
    font-size: $font-size-base;
    font-family: $font-family;
    transition: border-color 0.15s ease;

    &:focus {
      outline: none;
      border-color: $primary;
      box-shadow: 0 0 0 2px rgba($primary, 0.2);
    }
  }

  &--error &__input {
    border-color: $danger;
  }

  &__error {
    font-size: $font-size-sm;
    color: $danger;
  }
}
</style>
