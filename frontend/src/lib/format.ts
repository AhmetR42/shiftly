import { parseISODate } from './dates';

export function formatDay(value: string, locale: string, options: Intl.DateTimeFormatOptions = {}): string {
  return new Intl.DateTimeFormat(locale, { weekday: 'short', day: 'numeric', month: 'short', ...options }).format(
    parseISODate(value),
  );
}

export function weekdayName(value: string, locale: string, style: 'short' | 'long' = 'short'): string {
  return new Intl.DateTimeFormat(locale, { weekday: style }).format(parseISODate(value));
}

export function timeAgo(iso: string, locale: string, now = Date.now()): string {
  const seconds = Math.round((new Date(iso).getTime() - now) / 1000);
  const rtf = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' });
  const units: [Intl.RelativeTimeFormatUnit, number][] = [
    ['day', 86400],
    ['hour', 3600],
    ['minute', 60],
  ];
  for (const [unit, size] of units) {
    if (Math.abs(seconds) >= size) return rtf.format(Math.round(seconds / size), unit);
  }
  return rtf.format(0, 'minute');
}
