import { Link } from 'react-router-dom';
import { useDashboard } from '../api/queries';
import { useAuth } from '../auth/AuthContext';
import { ActivityList } from '../components/ActivityList';
import { CoverageChart } from '../components/CoverageChart';
import { Gauge } from '../components/Gauge';
import { Icon } from '../components/Icon';
import { DepartmentTag } from '../components/StatusBadge';
import { useI18n } from '../i18n/I18nContext';
import { formatHours } from '../lib/dates';
import { formatDay } from '../lib/format';

export function DashboardPage() {
  const { t, intlLocale } = useI18n();
  const { user } = useAuth();
  const { data, isLoading } = useDashboard();

  if (isLoading || !data) return <p className="muted">{t('common.loading')}</p>;
  const manager = user?.role === 'manager';

  return (
    <div className="dashboard">
      <section className="card stat-card stat-card--gauge">
        <h2>{manager ? t('dashboard.teamHours') : t('dashboard.myHours')}</h2>
        {manager ? (
          <p className="stat-card__big">{formatHours(data.team_hours)}</p>
        ) : (
          <Gauge value={data.my_hours} max={data.contract_hours} label={t('common.hours')} />
        )}
      </section>

      <section className="card stat-card">
        <h2>{t('dashboard.nextShift')}</h2>
        {data.next_shift ? (
          <>
            <p className="stat-card__big">
              {data.next_shift.start_time}–{data.next_shift.end_time}
            </p>
            <p className="muted">
              {formatDay(data.next_shift.date, intlLocale, { weekday: 'long' })} · <DepartmentTag department={data.next_shift.department} />
            </p>
          </>
        ) : (
          <p className="muted">{t('dashboard.noNextShift')}</p>
        )}
      </section>

      <Link to="/swaps" className="card stat-card stat-card--link">
        <h2>{manager ? t('dashboard.toReview') : t('dashboard.openSwaps')}</h2>
        <p className="stat-card__big">{manager ? data.pending_swaps + data.pending_time_off : data.open_swaps}</p>
        <Icon name="arrowRight" className="stat-card__arrow" />
      </Link>

      {manager && (
        <Link to="/rota" className={`card stat-card stat-card--link${data.warnings ? ' stat-card--warn' : ''}`}>
          <h2>{t('dashboard.warnings')}</h2>
          <p className="stat-card__big">{data.warnings}</p>
          <Icon name="alert" className="stat-card__arrow" />
        </Link>
      )}

      <section className="card dashboard__chart">
        <h2>{t('dashboard.coverage')}</h2>
        <CoverageChart days={data.coverage} />
      </section>

      <section className="card dashboard__today">
        <h2>{t('dashboard.today')}</h2>
        {data.on_shift_today.length === 0 ? (
          <p className="muted">{t('dashboard.nobodyToday')}</p>
        ) : (
          <ul className="today-list">
            {data.on_shift_today.map((s) => (
              <li key={s.id}>
                <b>{s.user_name}</b>
                <span>
                  {s.start_time}–{s.end_time}
                </span>
                <DepartmentTag department={s.department} />
              </li>
            ))}
          </ul>
        )}
      </section>

      <section className="card dashboard__activity">
        <h2>{t('dashboard.activity')}</h2>
        <ActivityList items={data.activity} />
      </section>
    </div>
  );
}
