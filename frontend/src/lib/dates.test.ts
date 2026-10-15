import { addDays, durationHours, formatHours, isoWeekNumber, weekDays, weekStart } from './dates';

describe('date helpers', () => {
  it('finds the monday of a week', () => {
    expect(weekStart('2026-10-15')).toBe('2026-10-12'); // Thursday
    expect(weekStart('2026-10-18')).toBe('2026-10-12'); // Sunday belongs to the same week
    expect(weekStart('2026-10-12')).toBe('2026-10-12');
  });

  it('adds days across month boundaries', () => {
    expect(addDays('2026-10-30', 3)).toBe('2026-11-02');
    expect(addDays('2026-10-12', -7)).toBe('2026-10-05');
  });

  it('lists seven days', () => {
    expect(weekDays('2026-10-12')).toHaveLength(7);
    expect(weekDays('2026-10-12')[6]).toBe('2026-10-18');
  });

  it('calculates ISO week numbers', () => {
    expect(isoWeekNumber('2026-10-15')).toBe(42);
    expect(isoWeekNumber('2026-01-01')).toBe(1);
    expect(isoWeekNumber('2027-01-01')).toBe(53);
  });

  it('formats durations', () => {
    expect(durationHours('08:00', '14:30')).toBe(6.5);
    expect(formatHours(6)).toBe('6');
    expect(formatHours(6.5)).toBe('6.5');
  });
});
