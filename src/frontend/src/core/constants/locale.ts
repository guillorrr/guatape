import { currentLocale, type AppLocale } from '@/i18n';

/**
 * Regional formats for dates and numbers, per UI language. The language comes
 * from i18n; the region/time zone/currency are deployment settings
 * (src/frontend/.env).
 *
 * The API serves timestamps in UTC, so converting to local time is the
 * frontend's job: without an explicit timeZone, toLocaleString uses the
 * runtime's zone and shows UTC hours on servers and containers.
 */
const INTL_LOCALES: Record<AppLocale, string> = {
  es: import.meta.env.VITE_LOCALE || 'es-AR',
  en: 'en-US',
};

/** BCP 47 locale for Intl, following the current UI language. */
export function intlLocale(): string {
  return INTL_LOCALES[currentLocale()] ?? INTL_LOCALES.es;
}

export const TIME_ZONE: string = import.meta.env.VITE_TIME_ZONE || 'America/Argentina/Buenos_Aires';

export const CURRENCY: string = import.meta.env.VITE_CURRENCY || 'ARS';
