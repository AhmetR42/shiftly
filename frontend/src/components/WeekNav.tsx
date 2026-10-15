import { useI18n } from '../i18n/I18nContext';
import { addDays, isoWeekNumber, weekStart } from '../lib/dates';
import { formatDay } from '../lib/format';
import { Icon } from './Icon';

export function WeekNav({ monday, onChange }: { monday: string; onChange: (monday: string) => void }) {
  const { t, intlLocale } = useI18n();
  const sunday = addDays(monday, 6);
  const isCurrent = monday === weekStart();

  return (
    <div className="week-nav">
      <button type="button" className="icon-button" onClick={() => onChange(addDays(monday, -7))} aria-label={t('week.previous')}>
        <Icon name="chevronLeft" />
      </button>
      <div className="week-nav__label">
        <b>{t('week.number', { n: isoWeekNumber(monday) })}</b>
        <span>
          {formatDay(monday, intlLocale, { weekday: undefined })} – {formatDay(sunday, intlLocale, { weekday: undefined })}
        </span>
      </div>
      <button type="button" className="icon-button" onClick={() => onChange(addDays(monday, 7))} aria-label={t('week.next')}>
        <Icon name="chevronRight" />
      </button>
      {!isCurrent && (
        <button type="button" className="button button--ghost button--small" onClick={() => onChange(weekStart())}>
          {t('week.today')}
        </button>
      )}
    </div>
  );
}
