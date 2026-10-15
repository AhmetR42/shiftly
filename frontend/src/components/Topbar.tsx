import { useEffect, useRef, useState } from 'react';
import { useAuth } from '../auth/AuthContext';
import { useDashboard } from '../api/queries';
import { useI18n } from '../i18n/I18nContext';
import { ActivityList } from './ActivityList';
import { Icon } from './Icon';
import { LanguageToggle } from './LanguageToggle';
import { LogoMark } from './Logo';
import { ThemeToggle } from './ThemeToggle';

interface Props {
  search: string;
  onSearch: (value: string) => void;
}

export function Topbar({ search, onSearch }: Props) {
  const { user, logout } = useAuth();
  const { t, intlLocale } = useI18n();
  const { data } = useDashboard();
  const [open, setOpen] = useState(false);
  const panel = useRef<HTMLDivElement>(null);

  const firstName = user?.name.split(' ')[0] ?? '';
  const today = new Intl.DateTimeFormat(intlLocale, { day: 'numeric', month: 'long', year: 'numeric' }).format(new Date());
  const pending =
    user?.role === 'manager' ? (data?.pending_swaps ?? 0) + (data?.pending_time_off ?? 0) : (data?.open_swaps ?? 0);

  useEffect(() => {
    if (!open) return;
    const close = (e: MouseEvent) => {
      if (!panel.current?.contains(e.target as Node)) setOpen(false);
    };
    document.addEventListener('mousedown', close);
    return () => document.removeEventListener('mousedown', close);
  }, [open]);

  return (
    <header className="topbar">
      <div className="topbar__mobile-logo">
        <LogoMark size={30} />
      </div>
      <div className="topbar__greeting">
        <h1>{t('topbar.hello', { name: firstName })}</h1>
        <p>
          <span className="topbar__chevrons" aria-hidden="true">»</span> {today}
        </p>
      </div>

      <div className="topbar__actions">
        <label className="search">
          <Icon name="search" size={18} />
          <input
            type="search"
            value={search}
            onChange={(e) => onSearch(e.target.value)}
            placeholder={t('topbar.search')}
            aria-label={t('topbar.search')}
          />
        </label>

        <LanguageToggle />
        <ThemeToggle />

        <div className="bell" ref={panel}>
          <button
            type="button"
            className="icon-button"
            onClick={() => setOpen((v) => !v)}
            aria-label={t('topbar.notifications')}
            aria-expanded={open}
          >
            <Icon name="bell" />
            {pending > 0 && <span className="bell__dot">{pending}</span>}
          </button>
          {open && (
            <div className="bell__panel card">
              <h3>{t('dashboard.activity')}</h3>
              <ActivityList items={data?.activity ?? []} compact />
            </div>
          )}
        </div>

        <button type="button" className="icon-button topbar__logout" onClick={() => logout()} aria-label={t('nav.logout')}>
          <Icon name="logout" />
        </button>
      </div>
    </header>
  );
}
