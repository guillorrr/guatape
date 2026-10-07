import type { Meta, StoryObj } from '@storybook/vue3';
import { ref } from 'vue';
import AppFileInput from './AppFileInput.vue';

const meta: Meta<typeof AppFileInput> = {
  title: 'Forms/AppFileInput',
  component: AppFileInput,
  tags: ['autodocs'],
};

export default meta;

export const Multiple: StoryObj<typeof AppFileInput> = {
  render: () => ({
    components: { AppFileInput },
    setup: () => ({ files: ref<File[]>([]) }),
    template:
      '<div style="max-width: 420px"><AppFileInput v-model="files" multiple accept=".pdf,image/*" :max-kb="2048" /></div>',
  }),
};
