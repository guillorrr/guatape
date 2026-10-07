import type { App } from 'vue';
import { watch } from 'vue';
import { usePrimeVue } from 'primevue/config';
import type { PrimeVueLocaleOptions } from '@primevue/core/config';
import { primeVueLocaleEs } from '@/core/constants/primevue-locale-es';
import { i18n, type AppLocale } from '@/i18n';

/**
 * Keeps PrimeVue's built-in texts (calendars, paginator, aria labels) in the
 * UI language. PrimeVue ships English; that default is captured as `en`.
 */
export function syncPrimeVueLocale(app: App): void {
  app.runWithContext(() => {
    const primevue = usePrimeVue();
    const locales: Record<AppLocale, PrimeVueLocaleOptions> = {
      // config is reactive (structuredClone throws on proxies); the locale is plain data.
      en: JSON.parse(JSON.stringify(primevue.config.locale ?? {})) as PrimeVueLocaleOptions,
      es: primeVueLocaleEs,
    };
    watch(
      i18n.global.locale,
      (locale) => {
        primevue.config.locale = locales[locale as AppLocale] ?? locales.en;
      },
      { immediate: true },
    );
  });
}
