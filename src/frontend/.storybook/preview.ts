import type { Preview } from '@storybook/vue3';
import { setup } from '@storybook/vue3';
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';
import Tooltip from 'primevue/tooltip';
import AppPreset from '../src/core/styles/primevue-preset';
import { i18n } from '../src/i18n';
import { primeVueLocaleEs } from '../src/core/constants/primevue-locale-es';
import 'primeicons/primeicons.css';
import '../src/core/styles/main.scss';

// Same i18n + PrimeVue setup as src/main.ts, so stories render like the app
// (in Spanish: the PrimeVue locale here is fixed, not synced).
setup((app) => {
  app.use(i18n);
  app.use(PrimeVue, {
    locale: primeVueLocaleEs,
    theme: { preset: AppPreset, options: { cssLayer: false, darkModeSelector: '.app-dark' } },
  });
  app.use(ConfirmationService);
  app.use(ToastService);
  app.directive('tooltip', Tooltip);
});

const preview: Preview = {
  parameters: {
    controls: {
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/i,
      },
    },
  },
};

export default preview;
