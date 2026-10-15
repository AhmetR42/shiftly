import { useState } from 'react';
import { api } from '../api/client';
import { useRotaMutation, useShifts } from '../api/queries';
import { Icon } from '../components/Icon';
import { DepartmentTag, StatusBadge } from '../components/StatusBadge';
import { WeekNav } from '../components/WeekNav';
import { useI18n } from '../i18n/I18nContext';
import { isToday, toISODate, weekStart } from '../lib/dates';
import { formatDay } from '../lib/format';

export function MyWeekPage() {
  const { t, intlLocale } = useI18n();
  const [monday, setMonday] = useState(() => weekStart());
  const { data } = useShifts(monday, true);
  const offer = useRotaMutation((id: number) => api.offerShift(id));
  const today = toISODate(new Date());
  const shifts = data?.data ?? [];

  return (
    <div>
      <div className="page-head">
        <h1>{t('myWeek.title')}</h1>
        <WeekNav monday={monday} onChange={setMonday} />
      </div>

      {shifts.length === 0 ? (
        <p className="card muted empty">{t('myWeek.empty')}</p>
      ) : (
        <ul className="list">
          {shifts.map((s) => (
            <li key={s.id} className={`card list-item${isToday(s.date) ? ' is-today' : ''}`}>
              <div className="list-item__date">
                <b>{formatDay(s.date, intlLocale, { weekday: 'long', month: undefined, day: undefined })}</b>
                <span>{formatDay(s.date, intlLocale, { weekday: undefined })}</span>
              </div>
              <div className="list-item__main">
                <b>
                  {s.start_time}–{s.end_time}
                </b>
                <span className="muted">
                  {s.hours} {t('common.hours')}
                  {s.note ? ` · ${s.note}` : ''}
                </span>
              </div>
              <DepartmentTag department={s.department} />
              {s.swap_status ? (
                <StatusBadge status={s.swap_status} />
              ) : (
                s.date >= today && (
                  <button type="button" className="button button--ghost button--small" disabled={offer.isPending} onClick={() => offer.mutate(s.id)}>
                    <Icon name="swap" size={16} />
                    {t('myWeek.offer')}
                  </button>
                )
              )}
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
