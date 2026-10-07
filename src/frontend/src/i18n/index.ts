import { createI18n } from 'vue-i18n';
import en from '@/locales/en.json';
import es from '@/locales/es.json';

/**
 * UI languages. Add one by creating src/locales/<code>.json (same keys as
 * es.json — a spec enforces it), registering it here and in the API's
 * config app.supported_locales + lang/<code>.
 */
export const SUPPORTED_LOCALES = ['es', 'en'] as const;
export type AppLocale = (typeof SUPPORTED_LOCALES)[number];

export const DEFAULT_LOCALE: AppLocale = 'es';

/** Native name of each language, for pickers. */
export const LOCALE_NAMES: Record<AppLocale, string> = { es: 'Español', en: 'English' };

const STORAGE_KEY = 'locale';

export function isSupportedLocale(value: unknown): value is AppLocale {
  return typeof value === 'string' && (SUPPORTED_LOCALES as readonly string[]).includes(value);
}

/** Stored choice → browser languages (primary subtag) → default. */
function initialLocale(): AppLocale {
  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (isSupportedLocale(stored)) return stored;
  } catch {
    /* storage disabled */
  }
  const browser = (typeof navigator !== 'undefined' ? navigator.languages : []) ?? [];
  for (const language of browser) {
    const primary = language.toLowerCase().split('-')[0];
    if (isSupportedLocale(primary)) return primary;
  }
  return DEFAULT_LOCALE;
}

export const i18n = createI18n({
  legacy: false,
  locale: initialLocale(),
  fallbackLocale: DEFAULT_LOCALE,
  messages: { es, en },
  // Missing keys are bugs: shout in development, stay quiet in production.
  missingWarn: import.meta.env.DEV,
  fallbackWarn: false,
});

/** Current UI language (reactive). */
export function currentLocale(): AppLocale {
  return i18n.global.locale.value as AppLocale;
}

/**
 * Switch the UI language. The API follows through the Accept-Language header
 * (api.service) or, once saved, the user's profile; PrimeVue and the date and
 * number formatters follow the reactive locale.
 */
export function setLocale(locale: AppLocale): void {
  i18n.global.locale.value = locale;
  document.documentElement.lang = locale;
  try {
    localStorage.setItem(STORAGE_KEY, locale);
  } catch {
    /* storage disabled */
  }
}

document.documentElement.lang = currentLocale();
