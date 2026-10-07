import { describe, expect, it } from 'vitest';
import en from '@/locales/en.json';
import es from '@/locales/es.json';
import { SUPPORTED_LOCALES } from '@/i18n';

type Messages = { [key: string]: string | Messages };

function flatten(messages: Messages, prefix = ''): Record<string, string> {
  return Object.entries(messages).reduce<Record<string, string>>((acc, [key, value]) => {
    const path = prefix ? `${prefix}.${key}` : key;
    return typeof value === 'string'
      ? { ...acc, [path]: value }
      : { ...acc, ...flatten(value, path) };
  }, {});
}

const placeholders = (message: string) =>
  [...message.matchAll(/\{(\w+)\}/g)].map((m) => m[1]).sort();

const locales: Record<string, Record<string, string>> = { es: flatten(es), en: flatten(en) };

describe('locales', () => {
  it('registers a messages file for every supported locale', () => {
    expect(Object.keys(locales).sort()).toEqual([...SUPPORTED_LOCALES].sort());
  });

  it('every locale has exactly the keys of es', () => {
    const reference = Object.keys(locales.es).sort();
    for (const [code, messages] of Object.entries(locales)) {
      expect(Object.keys(messages).sort(), `keys of ${code}.json`).toEqual(reference);
    }
  });

  it('translations keep the same placeholders', () => {
    for (const [key, message] of Object.entries(locales.es)) {
      for (const [code, messages] of Object.entries(locales)) {
        expect(placeholders(messages[key]), `${code}: ${key}`).toEqual(placeholders(message));
      }
    }
  });
});
