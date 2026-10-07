import { mount, type ComponentMountingOptions } from '@vue/test-utils';
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';
import Tooltip from 'primevue/tooltip';
import type { Component } from 'vue';
import { i18n } from '@/i18n';

/** mount() with the same plugins as main.ts: i18n and PrimeVue (unstyled theme, no CSS). */
export function mountWithPrime<C extends Component>(
  component: C,
  options: ComponentMountingOptions<C> = {},
) {
  return mount(component, {
    ...options,
    global: {
      ...options.global,
      plugins: [
        i18n,
        [PrimeVue, { theme: 'none' }],
        ConfirmationService,
        ToastService,
        ...(options.global?.plugins ?? []),
      ],
      directives: { tooltip: Tooltip, ...(options.global?.directives ?? {}) },
    },
  } as ComponentMountingOptions<C>);
}
