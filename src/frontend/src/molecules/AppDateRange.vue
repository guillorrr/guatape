<script setup lang="ts">
/**
 * From/to range as two "YYYY-MM-DD" strings ([from, to], either may be null).
 * Typical use: a list filter (`?from=&to=`) or a period on a form.
 */
import DatePicker from 'primevue/datepicker';
import { computed } from 'vue';
import { fromApiDate, toApiDate } from '@/core/utils/dates';

type Range = [string | null, string | null];

const props = withDefaults(
  defineProps<{
    modelValue: Range;
    inputId?: string;
    placeholder?: string;
    invalid?: boolean;
    disabled?: boolean;
  }>(),
  { inputId: undefined, placeholder: 'Desde – hasta', invalid: false, disabled: false },
);

const emit = defineEmits<{ 'update:modelValue': [value: Range] }>();

const range = computed({
  get: () => {
    const [from, to] = props.modelValue;
    const dates = [fromApiDate(from), fromApiDate(to)].filter((d): d is Date => d !== null);
    return dates.length ? dates : null;
  },
  set: (value: Date | Date[] | (Date | null)[] | null | undefined) => {
    const list = Array.isArray(value) ? value : [];
    emit('update:modelValue', [toApiDate(list[0] ?? null), toApiDate(list[1] ?? null)]);
  },
});
</script>

<template>
  <DatePicker
    v-model="range"
    selection-mode="range"
    date-format="dd/mm/yy"
    :manual-input="false"
    :input-id="inputId"
    :placeholder="placeholder"
    :invalid="invalid"
    :disabled="disabled"
    show-icon
    icon-display="input"
    show-button-bar
    fluid
  />
</template>
