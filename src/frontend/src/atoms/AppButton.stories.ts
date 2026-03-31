import type { Meta, StoryObj } from '@storybook/vue3';
import AppButton from './AppButton.vue';

const meta: Meta<typeof AppButton> = {
  title: 'Atoms/AppButton',
  component: AppButton,
  tags: ['autodocs'],
  argTypes: {
    variant: {
      control: 'select',
      options: ['primary', 'secondary', 'danger', 'ghost'],
    },
    size: {
      control: 'select',
      options: ['sm', 'md', 'lg'],
    },
  },
};

export default meta;
type Story = StoryObj<typeof AppButton>;

export const Primary: Story = {
  args: { variant: 'primary' },
  render: (args) => ({
    components: { AppButton },
    setup: () => ({ args }),
    template: '<AppButton v-bind="args">Click me</AppButton>',
  }),
};

export const Secondary: Story = {
  args: { variant: 'secondary' },
  render: (args) => ({
    components: { AppButton },
    setup: () => ({ args }),
    template: '<AppButton v-bind="args">Secondary</AppButton>',
  }),
};

export const Danger: Story = {
  args: { variant: 'danger' },
  render: (args) => ({
    components: { AppButton },
    setup: () => ({ args }),
    template: '<AppButton v-bind="args">Delete</AppButton>',
  }),
};

export const Loading: Story = {
  args: { loading: true },
  render: (args) => ({
    components: { AppButton },
    setup: () => ({ args }),
    template: '<AppButton v-bind="args">Loading...</AppButton>',
  }),
};
