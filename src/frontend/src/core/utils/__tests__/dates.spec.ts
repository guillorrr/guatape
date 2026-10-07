import { describe, expect, it } from 'vitest';
import { fromApiDate, fromApiTime, toApiDate, toApiTime } from '@/core/utils/dates';

describe('dates', () => {
  it('round-trips a calendar day without shifting it', () => {
    const date = fromApiDate('2026-03-01');
    expect(date?.getDate()).toBe(1);
    expect(date?.getMonth()).toBe(2);
    expect(toApiDate(date)).toBe('2026-03-01');
  });

  it('uses the local day, not the UTC one', () => {
    // 23:30 local on the 5th is still the 5th, whatever the offset.
    expect(toApiDate(new Date(2026, 9, 5, 23, 30))).toBe('2026-10-05');
  });

  it('reads the date part of ISO datetimes', () => {
    expect(toApiDate(fromApiDate('2026-10-07T13:41:23.000000Z'))).toBe('2026-10-07');
  });

  it('handles times', () => {
    expect(toApiTime(fromApiTime('09:05'))).toBe('09:05');
    expect(toApiTime(fromApiTime('18:30:00'))).toBe('18:30');
  });

  it('maps empty or invalid input to null', () => {
    expect(fromApiDate(null)).toBeNull();
    expect(fromApiDate('mañana')).toBeNull();
    expect(toApiDate(new Date('nope'))).toBeNull();
    expect(fromApiTime('')).toBeNull();
  });
});
