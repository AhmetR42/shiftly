import { useI18n } from '../i18n/I18nContext';
import { isToday } from '../lib/dates';
import { weekdayName } from '../lib/format';

interface Day {
  date: string;
  shifts: number;
  hours: number;
}

const HEIGHT = 180;
const BAR_WIDTH = 26;

/**
 * Planned hours per day as a small bar chart. Plain SVG, no chart library:
 * seven bars don't need one.
 */
export function CoverageChart({ days }: { days: Day[] }) {
  const { t, intlLocale } = useI18n();
  const max = Math.max(8, ...days.map((d) => d.hours));
  const step = niceStep(max);
  const top = Math.ceil(max / step) * step;
  const ticks = Array.from({ length: Math.round(top / step) + 1 }, (_, i) => i * step);
  const colWidth = 64;
  const width = 40 + days.length * colWidth;

  return (
    <svg className="coverage" viewBox={`0 0 ${width} ${HEIGHT + 34}`} role="img" aria-label={t('dashboard.coverage')}>
      {ticks.map((tick) => {
        const y = HEIGHT - (tick / top) * HEIGHT + 6;
        return (
          <g key={tick} className="coverage__grid">
            <line x1="34" x2={width} y1={y} y2={y} />
            <text x="26" y={y + 4} textAnchor="end">
              {tick}
            </text>
          </g>
        );
      })}
      {days.map((day, i) => {
        const h = (day.hours / top) * HEIGHT;
        const x = 40 + i * colWidth + (colWidth - BAR_WIDTH) / 2;
        return (
          <g key={day.date} className={`coverage__bar${isToday(day.date) ? ' is-today' : ''}`} style={{ ['--i' as string]: i }}>
            <title>{t('dashboard.coverageTooltip', { hours: day.hours, shifts: day.shifts })}</title>
            <rect x={x} y={HEIGHT - h + 6} width={BAR_WIDTH} height={Math.max(h, 2)} rx="6" />
            <text x={x + BAR_WIDTH / 2} y={HEIGHT - h - 2} textAnchor="middle" className="coverage__value">
              {day.shifts}
            </text>
            <text x={x + BAR_WIDTH / 2} y={HEIGHT + 28} textAnchor="middle" className="coverage__day">
              {weekdayName(day.date, intlLocale)}
            </text>
          </g>
        );
      })}
    </svg>
  );
}

function niceStep(max: number): number {
  if (max <= 20) return 5;
  if (max <= 60) return 10;
  return 20;
}
