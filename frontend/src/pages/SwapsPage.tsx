import { useState } from 'react';
import { api } from '../api/client';
import { useRotaMutation, useSwaps } from '../api/queries';
import { useAuth } from '../auth/AuthContext';
import { DepartmentTag, StatusBadge } from '../components/StatusBadge';
import { useI18n } from '../i18n/I18nContext';
import { formatDay } from '../lib/format';
import { useWarningText } from '../lib/useWarningText';

export function SwapsPage() {
  const { t, intlLocale } = useI18n();
  const { user } = useAuth();
  const warningText = useWarningText();
  const { data: swaps = [], isLoading } = useSwaps();
  const [notice, setNotice] = useState<string | null>(null);

  const pickUp = useRotaMutation((id: number) => api.pickUpSwap(id));
  const cancel = useRotaMutation((id: number) => api.cancelSwap(id));
  const review = useRotaMutation(({ id, decision }: { id: number; decision: 'approve' | 'decline' }) => api.reviewSwap(id, decision));
  const busy = pickUp.isPending || cancel.isPending || review.isPending;
  const manager = user?.role === 'manager';

  const approve = (id: number) =>
    review.mutate(
      { id, decision: 'approve' },
      {
        onSuccess: (r) =>
          setNotice(r.meta.warnings.length ? t('swaps.approvedWarnings', { text: r.meta.warnings.map((w) => warningText(w)).join('; ') }) : null),
      },
    );

  return (
    <div>
      <div className="page-head">
        <h1>{t('swaps.title')}</h1>
      </div>
      {notice && <p className="notice notice--warn" role="status">{notice}</p>}
      {isLoading ? (
        <p className="muted">{t('common.loading')}</p>
      ) : swaps.length === 0 ? (
        <p className="card muted empty">{t('swaps.empty')}</p>
      ) : (
        <ul className="list">
          {swaps.map((s) => (
            <li key={s.id} className="card list-item">
              <div className="list-item__date">
                <b>{formatDay(s.shift.date, intlLocale)}</b>
                <span>
                  {s.shift.start_time}–{s.shift.end_time}
                </span>
              </div>
              <div className="list-item__main">
                <b>
                  {t('swaps.from', { name: s.requester.name })} {s.taker && t('swaps.to', { name: s.taker.name })}
                </b>
                {s.note && <span className="muted">“{s.note}”</span>}
              </div>
              <DepartmentTag department={s.shift.department} />
              <StatusBadge status={s.status} />
              <div className="list-item__actions">
                {manager && s.status === 'pending' && (
                  <>
                    <button type="button" className="button button--small" disabled={busy} onClick={() => approve(s.id)}>
                      {t('common.approve')}
                    </button>
                    <button type="button" className="button button--ghost button--small" disabled={busy} onClick={() => review.mutate({ id: s.id, decision: 'decline' })}>
                      {t('common.decline')}
                    </button>
                  </>
                )}
                {!manager && s.status === 'open' && s.requester.id !== user?.id && (
                  <button type="button" className="button button--small" disabled={busy} onClick={() => pickUp.mutate(s.id)}>
                    {t('swaps.pickUp')}
                  </button>
                )}
                {s.requester.id === user?.id && (s.status === 'open' || s.status === 'pending') && (
                  <button type="button" className="button button--ghost button--small" disabled={busy} onClick={() => cancel.mutate(s.id)}>
                    {t('swaps.cancel')}
                  </button>
                )}
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
