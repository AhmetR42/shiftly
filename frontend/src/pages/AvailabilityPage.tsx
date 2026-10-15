import { useEffect, useState, type FormEvent } from 'react';
import { api } from '../api/client';
import { useAvailability, useRotaMutation } from '../api/queries';
import type { AvailabilityDay } from '../api/types';
import { useI18n } from '../i18n/I18nContext';
import { addDays, weekStart } from '../lib/dates';
import { weekdayName } from '../lib/format';

export function AvailabilityPage() {
  const { t, intlLocale } = useI18n();
  const { data } = useAvailability();
  const [days, setDays] = useState<AvailabilityDay[]>([]);
  const [saved, setSaved] = useState(false);
  const save = useRotaMutation(api.saveAvailability);
  const monday = weekStart();

  useEffect(() => {
    if (data) setDays(data);
  }, [data]);

  const update = (weekday: number, patch: Partial<AvailabilityDay>) => {
    setSaved(false);
    setDays((all) => all.map((d) => (d.weekday === weekday ? { ...d, ...patch } : d)));
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const clean = days.map((d) => ({ ...d, from_time: d.from_time || null, until_time: d.until_time || null }));
    save.mutate(clean, { onSuccess: () => setSaved(true) });
  };

  return (
    <form className="card form availability" onSubmit={submit}>
      <h1>{t('availability.title')}</h1>
      <p className="muted">{t('availability.text')}</p>
      <ul className="availability__days">
        {days.map((d) => (
          <li key={d.weekday} className={d.available ? undefined : 'is-off'}>
            <b>{weekdayName(addDays(monday, d.weekday - 1), intlLocale, 'long')}</b>
            <label className="switch">
              <input type="checkbox" checked={d.available} onChange={(e) => update(d.weekday, { available: e.target.checked })} />
              <span>{t('availability.available')}</span>
            </label>
            <label className="field field--inline">
              <span>{t('availability.from')}</span>
              <input type="time" disabled={!d.available} value={d.from_time ?? ''} onChange={(e) => update(d.weekday, { from_time: e.target.value })} />
            </label>
            <label className="field field--inline">
              <span>{t('availability.until')}</span>
              <input type="time" disabled={!d.available} value={d.until_time ?? ''} onChange={(e) => update(d.weekday, { until_time: e.target.value })} />
            </label>
          </li>
        ))}
      </ul>
      <div className="form__actions">
        {saved && <span className="notice-inline">{t('availability.saved')}</span>}
        {save.isError && <span className="form__error">{t('common.error')}</span>}
        <button type="submit" className="button" disabled={save.isPending}>
          {save.isPending ? t('common.saving') : t('common.save')}
        </button>
      </div>
    </form>
  );
}
