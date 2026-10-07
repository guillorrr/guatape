import { createApp } from 'vue';
import { createPinia } from 'pinia';
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';
import Tooltip from 'primevue/tooltip';
import AppPreset from '@/core/styles/primevue-preset';
import { primeVueLocaleEs } from '@/core/constants/primevue-locale-es';
import router from '@/router';
import App from '@/App.vue';
import 'primeicons/primeicons.css';
import '@/core/styles/main.scss';

const app = createApp(App);

app.use(createPinia());
app.use(router);
app.use(PrimeVue, {
  locale: primeVueLocaleEs,
  theme: {
    preset: AppPreset,
    options: {
      cssLayer: false,
      // Dark mode is opt-in: add the `app-dark` class to <html>.
      darkModeSelector: '.app-dark',
    },
  },
});
app.use(ConfirmationService);
app.use(ToastService);
app.directive('tooltip', Tooltip);

app.mount('#app');
