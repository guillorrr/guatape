import type { Meta, StoryObj } from '@storybook/vue3';
import InputText from 'primevue/inputtext';
import { ref } from 'vue';
import AppRepeater from './AppRepeater.vue';

// Generic component (T): Meta can't infer its props, so it isn't typed here.
const meta: Meta = {
  title: 'Forms/AppRepeater',
  tags: ['autodocs'],
};

export default meta;
type Story = StoryObj;

export const Phones: Story = {
  render: () => ({
    components: { AppRepeater, InputText },
    setup: () => ({
      phones: ref([{ number: '11 5555-0000' }]),
      newItem: () => ({ number: '' }),
    }),
    template: `<div style="max-width: 420px">
      <AppRepeater v-model="phones" :new-item="newItem" :max="3" sortable add-label="Agregar teléfono">
        <template #default="{ item }"><InputText v-model="item.number" placeholder="Número" fluid /></template>
      </AppRepeater>
      <pre>{{ phones }}</pre>
    </div>`,
  }),
};
