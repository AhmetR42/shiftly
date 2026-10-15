import { useState, type FormEvent } from 'react';
import { api, ApiError } from '../api/client';
import { useRotaMutation, useTimeOff } from '../api/queries';
import { useAuth } from '../auth/AuthContext';
import { StatusBadge } from '../components/StatusBadge';
import { useI18n } from '../i18n/I18nContext';
import { toISODate } from '../lib/dates';
import { formatDay } from '../lib/format';

export function TimeOffPage() {
  const { t, intlLocale } = useI18n();
  const { user } = useAuth();
  const { data: requests = [] } = useTimeOff();
  const today = toISODate(new Date());
  const [form, setForm] = useState({ start_date: today, end_date: today, reason: '' });
  const [error, setError] = useState<string | null>(null);

  const create = useRotaMutation(api.requestTimeOff);
  const review = useRotaMutation(({ id, decision }: { id: number; decision: 'approve' | 'decline' }) => api.reviewTimeOff(id, decision));
  const withdraw = useRotaMutation(api.withdrawTimeOff);
  const manager = user?.role === 'manager';

  const submit = (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    create.mutate(form, {
      onSuccess: () => setForm({ start_date: today, end_date: today, reason: '' }),
      onError: (err) => setError(err instanceof ApiError ? err.firstError() : t('common.error')),
    });
  };

  return (
    <div className={manager ? undefined : 'two-col'}>
      {!manager && (
        <form className="card form" onSubmit={submit}>
          <h2>{t('timeOff.request')}</h2>
          <div className="form__row">
            <label className="field">
              <span>{t('timeOff.from')}</span>
              <input type="date" min={today} required value={form.start_date} onChange={(e) => setForm({ ...form, start_date: e.target.value, end_date: e.target.value > form.end_date ? e.target.value : form.end_date })} />
            </label>
            <label className="field">
              <span>{t('timeOff.until')}</span>
              <input type="date" min={form.start_date} required value={form.end_date} onChange={(e) => setForm({ ...form, end_date: e.target.value })} />
            </label>
          </div>
          <label className="field">
            <span>{t('timeOff.reason')}</span>
            <input type="text" maxLength={200} value={form.reason} onChange={(e) => setForm({ ...form, reason: e.target.value })} />
          </label>
          {error && <p className="form__error">{error}</p>}
          <button type="submit" className="button" disabled={create.isPending}>
            {t('timeOff.submit')}
          </button>
        </form>
      )}

      <section>
        <div className="page-head">
          <h1>{t('timeOff.title')}</h1>
        </div>
        {requests.length === 0 ? (
          <p className="card muted empty">{t('timeOff.empty')}</p>
        ) : (
          <ul className="list">
            {requests.map((r) => (
              <li key={r.id} className="card list-item">
                <div className="list-item__date">
                  <b>{formatDay(r.start_date, intlLocale)}</b>
                  <span>{r.days > 1 ? `– ${formatDay(r.end_date, intlLocale)}` : ''}</span>
                </div>
                <div className="list-item__main">
                  <b>{manager ? r.user.name : t('timeOff.days', { days: r.days })}</b>
                  <span className="muted">
                    {manager && `${t('timeOff.days', { days: r.days })} · `}
                    {r.reason}
                  </span>
                </div>
                <StatusBadge status={r.status} />
                <div className="list-item__actions">
                  {manager && r.status === 'pending' && (
                    <>
                      <button type="button" className="button button--small" onClick={() => review.mutate({ id: r.id, decision: 'approve' })}>
                        {t('common.approve')}
                      </button>
                      <button type="button" className="button button--ghost button--small" onClick={() => review.mutate({ id: r.id, decision: 'decline' })}>
                        {t('common.decline')}
                      </button>
                    </>
                  )}
                  {!manager && r.status === 'pending' && (
                    <button type="button" className="button button--ghost button--small" onClick={() => withdraw.mutate(r.id)}>
                      {t('timeOff.withdraw')}
                    </button>
                  )}
                </div>
              </li>
            ))}
          </ul>
        )}
      </section>
    </div>
  );
}
