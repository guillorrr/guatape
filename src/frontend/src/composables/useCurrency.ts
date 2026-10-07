import { CURRENCY, intlLocale } from '@/core/constants/locale';

export function useCurrency() {
  function formatMoney(value: number | string | null | undefined, currency = CURRENCY): string {
    const num = value == null ? 0 : typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat(intlLocale(), {
      style: 'currency',
      currency,
      minimumFractionDigits: 2,
    }).format(Number.isNaN(num) ? 0 : num);
  }

  function formatNumber(value: number | null | undefined, decimals = 2): string {
    if (value == null) return '0';
    return new Intl.NumberFormat(intlLocale(), {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals,
    }).format(value);
  }

  return { formatMoney, formatNumber };
}
