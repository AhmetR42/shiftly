/**
 * Small date helpers. Dates travel as 'YYYY-MM-DD' strings between the API
 * and the UI, so we avoid timezone surprises by only ever building local dates.
 */

export function toISODate(date: Date): string {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

export function parseISODate(value: string): Date {
  const [y, m, d] = value.split('-').map(Number);
  return new Date(y, m - 1, d);
}

export function addDays(value: string, days: number): string {
  const date = parseISODate(value);
  date.setDate(date.getDate() + days);
  return toISODate(date);
}

/** Monday of the week the given date is in. */
export function weekStart(value: string | Date = new Date()): string {
  const date = typeof value === 'string' ? parseISODate(value) : new Date(value);
  const isoDay = (date.getDay() + 6) % 7; // Monday = 0
  date.setDate(date.getDate() - isoDay);
  return toISODate(date);
}

export function weekDays(monday: string): string[] {
  return Array.from({ length: 7 }, (_, i) => addDays(monday, i));
}

/** ISO week number, the way Dutch rotas are usually labelled ("week 42"). */
export function isoWeekNumber(value: string): number {
  const date = parseISODate(value);
  const thursday = new Date(date);
  thursday.setDate(date.getDate() + 3 - ((date.getDay() + 6) % 7));
  const firstThursday = new Date(thursday.getFullYear(), 0, 4);
  return 1 + Math.round(((thursday.getTime() - firstThursday.getTime()) / 86400000 - 3 + ((firstThursday.getDay() + 6) % 7)) / 7);
}

export function minutesOf(time: string): number {
  const [h, m] = time.split(':').map(Number);
  return h * 60 + m;
}

export function durationHours(start: string, end: string): number {
  return Math.round(((minutesOf(end) - minutesOf(start)) / 60) * 100) / 100;
}

export function formatHours(hours: number): string {
  return Number.isInteger(hours) ? String(hours) : hours.toFixed(1);
}

export function isToday(value: string): boolean {
  return value === toISODate(new Date());
}
