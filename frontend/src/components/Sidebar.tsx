import { NavLink } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';
import { useI18n } from '../i18n/I18nContext';
import type { TranslationKey } from '../i18n/en';
import { Icon, type IconName } from './Icon';
import { Logo } from './Logo';

interface NavItem {
  to: string;
  label: TranslationKey;
  icon: IconName;
}

const managerNav: NavItem[] = [
  { to: '/', label: 'nav.dashboard', icon: 'dashboard' },
  { to: '/rota', label: 'nav.rota', icon: 'rota' },
  { to: '/swaps', label: 'nav.swaps', icon: 'swap' },
  { to: '/time-off', label: 'nav.timeOff', icon: 'timeOff' },
];

const employeeNav: NavItem[] = [
  { to: '/', label: 'nav.dashboard', icon: 'dashboard' },
  { to: '/my-week', label: 'nav.myWeek', icon: 'week' },
  { to: '/swaps', label: 'nav.swaps', icon: 'swap' },
  { to: '/time-off', label: 'nav.timeOff', icon: 'timeOff' },
  { to: '/availability', label: 'nav.availability', icon: 'availability' },
];

export function Sidebar() {
  const { user, logout } = useAuth();
  const { t } = useI18n();
  const items = user?.role === 'manager' ? managerNav : employeeNav;

  return (
    <aside className="sidebar">
      <div className="sidebar__logo">
        <Logo light />
      </div>

      <nav className="sidebar__nav" aria-label={t('nav.main')}>
        {items.map((item) => (
          <NavLink key={item.to} to={item.to} end={item.to === '/'} className="sidebar__link">
            <Icon name={item.icon} />
            <span>{t(item.label)}</span>
          </NavLink>
        ))}
      </nav>

      <div className="sidebar__footer">
        <button type="button" className="sidebar__link sidebar__logout" onClick={() => logout()}>
          <Icon name="logout" />
          <span>{t('nav.logout')}</span>
        </button>
        {user && (
          <div className="sidebar__user">
            <span className="avatar">{initials(user.name)}</span>
            <span className="sidebar__user-text">
              <b>{user.name}</b>
              <small>{t(user.role === 'manager' ? 'role.manager' : 'role.employee')}</small>
            </span>
          </div>
        )}
      </div>
    </aside>
  );
}

// eslint-disable-next-line react-refresh/only-export-components
export function initials(name: string): string {
  const parts = name.split(' ').filter(Boolean);
  return ((parts[0]?.[0] ?? '') + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase();
}
