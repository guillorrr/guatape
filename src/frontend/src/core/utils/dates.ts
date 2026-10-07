/**
 * The API exchanges plain dates as "YYYY-MM-DD" and times as "HH:mm" — no time
 * zone. Date pickers work with Date objects in the browser's zone, so convert
 * with LOCAL getters/setters: toISOString() would shift the day for anyone
 * west of UTC (a date picked as the 5th would be sent as the 4th).
 */

const pad = (n: number) => String(n).padStart(2, '0');

/** Date → "YYYY-MM-DD" (local calendar day). */
export function toApiDate(date: Date | null | undefined): string | null {
  if (!date || Number.isNaN(date.getTime())) return null;
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

/** "YYYY-MM-DD" (or an ISO datetime, whose date part is used) → local midnight. */
export function fromApiDate(value: string | null | undefined): Date | null {
  const m = value?.match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (!m) return null;
  const date = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
  return Number.isNaN(date.getTime()) ? null : date;
}

/** Date → "HH:mm". */
export function toApiTime(date: Date | null | undefined): string | null {
  if (!date || Number.isNaN(date.getTime())) return null;
  return `${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

/** "HH:mm" or "HH:mm:ss" → a Date today at that time (what time pickers bind to). */
export function fromApiTime(value: string | null | undefined): Date | null {
  const m = value?.match(/^(\d{2}):(\d{2})/);
  if (!m) return null;
  const date = new Date();
  date.setHours(Number(m[1]), Number(m[2]), 0, 0);
  return date;
}
