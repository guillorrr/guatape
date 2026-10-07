import { LOCALE, TIME_ZONE } from '@/core/constants/locale';

export function useFormatters() {
  /** 5400 → "1h 30m"; null/0 → fallback. */
  function formatDuration(seconds: number | null | undefined, fallback = '-'): string {
    if (!seconds || seconds <= 0) return fallback;
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    if (h > 0) return `${h}h ${m}m`;
    return `${m}m`;
  }

  function formatDate(value: string | null | undefined): string {
    if (!value) return '-';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '-';
    return d.toLocaleDateString(LOCALE, {
      timeZone: TIME_ZONE,
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    });
  }

  function formatDateTime(value: string | null | undefined): string {
    if (!value) return '-';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '-';
    return d.toLocaleString(LOCALE, {
      timeZone: TIME_ZONE,
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      hourCycle: 'h23',
    });
  }

  /** Short relative age for chips: "recién", "hace 5m", "hace 3h", "hace 2d". */
  function formatRelativeAge(value: string | null | undefined): string {
    if (!value) return '-';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '-';
    const mins = Math.floor((Date.now() - d.getTime()) / 60000);
    if (mins < 1) return 'recién';
    if (mins < 60) return `hace ${mins}m`;
    const hours = Math.floor(mins / 60);
    if (hours < 24) return `hace ${hours}h`;
    return `hace ${Math.floor(hours / 24)}d`;
  }

  return { formatDuration, formatDate, formatDateTime, formatRelativeAge };
}
