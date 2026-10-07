<script setup lang="ts">
import { useI18n } from 'vue-i18n';
/**
 * Date picker bound to the API format "YYYY-MM-DD" (or null), so forms never
 * deal with Date objects or time-zone shifts. See core/utils/dates.ts.
 */
import DatePicker from 'primevue/datepicker';
import { computed } from 'vue';
import { fromApiDate, toApiDate } from '@/core/utils/dates';

const props = withDefaults(
  defineProps<{
    modelValue: string | null;
    /** "YYYY-MM-DD" bounds. */
    min?: string | null;
    max?: string | null;
    inputId?: string;
    placeholder?: string;
    invalid?: boolean;
    disabled?: boolean;
  }>(),
  {
    min: null,
    max: null,
    inputId: undefined,
    placeholder: undefined,
    invalid: false,
    disabled: false,
  },
);

const { t } = useI18n();

const emit = defineEmits<{ 'update:modelValue': [value: string | null] }>();

const date = computed({
  get: () => fromApiDate(props.modelValue),
  set: (value: Date | Date[] | (Date | null)[] | null | undefined) =>
    emit('update:modelValue', value instanceof Date ? toApiDate(value) : null),
});
</script>

<template>
  <DatePicker
    v-model="date"
    :min-date="fromApiDate(min) ?? undefined"
    :max-date="fromApiDate(max) ?? undefined"
    :input-id="inputId"
    :placeholder="placeholder ?? t('forms.datePlaceholder')"
    :invalid="invalid"
    :disabled="disabled"
    show-icon
    icon-display="input"
    show-button-bar
    fluid
  />
</template>
