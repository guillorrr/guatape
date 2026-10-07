import type { Meta, StoryObj } from '@storybook/vue3';
import { ref } from 'vue';
import AppDatePicker from '../atoms/AppDatePicker.vue';
import AppTimePicker from '../atoms/AppTimePicker.vue';
import AppDateRange from './AppDateRange.vue';

const meta: Meta = {
  title: 'Forms/Dates',
  tags: ['autodocs'],
};

export default meta;

export const AllPickers: StoryObj = {
  render: () => ({
    components: { AppDatePicker, AppTimePicker, AppDateRange },
    setup: () => ({
      date: ref<string | null>('2026-10-15'),
      time: ref<string | null>('09:30'),
      range: ref<[string | null, string | null]>([null, null]),
    }),
    template: `<div style="max-width: 320px; display: grid; gap: 12px">
      <AppDatePicker v-model="date" />
      <AppTimePicker v-model="time" />
      <AppDateRange v-model="range" />
      <pre>{{ { date, time, range } }}</pre>
    </div>`,
  }),
};
