import type { Meta, StoryObj } from '@storybook/vue3';
import InputText from 'primevue/inputtext';
import AppField from './AppField.vue';

const meta: Meta<typeof AppField> = {
  title: 'Forms/AppField',
  component: AppField,
  tags: ['autodocs'],
  args: { label: 'Email', for: 'email', required: true, hint: 'Lo usás para iniciar sesión.' },
  render: (args) => ({
    components: { AppField, InputText },
    setup: () => ({ args }),
    template:
      '<div style="max-width: 360px"><AppField v-bind="args"><InputText id="email" :invalid="!!args.error" /></AppField></div>',
  }),
};

export default meta;
type Story = StoryObj<typeof AppField>;

export const WithHint: Story = {};

export const WithError: Story = { args: { error: 'El campo email es obligatorio.' } };
