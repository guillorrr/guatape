import type { Meta, StoryObj } from '@storybook/vue3';
import { ref } from 'vue';
import AppRichText from './AppRichText.vue';

const meta: Meta<typeof AppRichText> = {
  title: 'Forms/AppRichText',
  component: AppRichText,
  tags: ['autodocs'],
};

export default meta;

export const Default: StoryObj<typeof AppRichText> = {
  render: () => ({
    components: { AppRichText },
    setup: () => ({ html: ref<string | null>('<p>Texto con <strong>negrita</strong>.</p>') }),
    template:
      '<div style="max-width: 560px"><AppRichText v-model="html" /><pre style="white-space: pre-wrap">{{ html }}</pre></div>',
  }),
};
