import type { Meta, StoryObj } from '@storybook/vue3';
import { ref } from 'vue';
import AppRemoteSelect, { type RemoteOption } from './AppRemoteSelect.vue';

const people: RemoteOption[] = [
  { value: 1, label: 'Ana Pérez', description: 'ana@example.com' },
  { value: 2, label: 'Bruno Díaz', description: 'bruno@example.com' },
  { value: 3, label: 'Carla Gómez', description: 'carla@example.com' },
];

// Stands in for userService.options(): filters a fixed list after a short delay.
const fetchOptions = (query: string) =>
  new Promise<RemoteOption[]>((resolve) =>
    setTimeout(
      () => resolve(people.filter((p) => p.label.toLowerCase().includes(query.toLowerCase()))),
      300,
    ),
  );

const meta: Meta<typeof AppRemoteSelect> = {
  title: 'Forms/AppRemoteSelect',
  component: AppRemoteSelect,
  tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof AppRemoteSelect>;

export const Single: Story = {
  render: () => ({
    components: { AppRemoteSelect },
    setup: () => ({ value: ref<number | null>(2), fetchOptions, initial: [people[1]] }),
    template: `<div style="max-width: 360px">
      <AppRemoteSelect v-model="value" :fetch-options="fetchOptions" :initial-options="initial" />
      <p style="margin-top: 8px">v-model: {{ value }}</p>
    </div>`,
  }),
};

export const MultipleAndCreatable: Story = {
  render: () => ({
    components: { AppRemoteSelect },
    setup: () => {
      const value = ref<number[]>([]);
      const created = ref<string | null>(null);
      return { value, created, fetchOptions };
    },
    template: `<div style="max-width: 360px">
      <AppRemoteSelect v-model="value" :fetch-options="fetchOptions" multiple creatable @create="created = $event" />
      <p style="margin-top: 8px">v-model: {{ value }} · create: {{ created }}</p>
    </div>`,
  }),
};
