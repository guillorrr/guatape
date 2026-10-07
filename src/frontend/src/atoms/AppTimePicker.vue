<script setup lang="ts">
/** Time picker bound to "HH:mm" (24 h) or null. See core/utils/dates.ts. */
import DatePicker from 'primevue/datepicker';
import { computed } from 'vue';
import { fromApiTime, toApiTime } from '@/core/utils/dates';

const props = withDefaults(
  defineProps<{
    modelValue: string | null;
    /** Minutes between options when using the spinner arrows. */
    stepMinute?: number;
    inputId?: string;
    placeholder?: string;
    invalid?: boolean;
    disabled?: boolean;
  }>(),
  { stepMinute: 5, inputId: undefined, placeholder: 'hh:mm', invalid: false, disabled: false },
);

const emit = defineEmits<{ 'update:modelValue': [value: string | null] }>();

const time = computed({
  get: () => fromApiTime(props.modelValue),
  set: (value: Date | Date[] | (Date | null)[] | null | undefined) =>
    emit('update:modelValue', value instanceof Date ? toApiTime(value) : null),
});
</script>

<template>
  <DatePicker
    v-model="time"
    time-only
    hour-format="24"
    :step-minute="stepMinute"
    :input-id="inputId"
    :placeholder="placeholder"
    :invalid="invalid"
    :disabled="disabled"
    show-icon
    icon-display="input"
    fluid
  >
    <template #inputicon="{ clickCallback }">
      <i class="pi pi-clock" @click="clickCallback" />
    </template>
  </DatePicker>
</template>
