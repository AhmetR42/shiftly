import type { Activity } from '../api/types';
import { useI18n } from '../i18n/I18nContext';
import type { TranslationKey } from '../i18n/en';
import { formatDay, timeAgo } from '../lib/format';
import { initials } from './Sidebar';

export function ActivityList({ items, compact = false }: { items: Activity[]; compact?: boolean }) {
  const { t, intlLocale } = useI18n();

  if (items.length === 0) {
    return <p className="muted">{t('dashboard.noActivity')}</p>;
  }

  return (
    <ul className={`activity${compact ? ' activity--compact' : ''}`}>
      {items.map((item) => {
        const key = `activity.${item.type}` as TranslationKey;
        const data = { ...item.data, date: item.data.date ? formatDay(String(item.data.date), intlLocale) : '' };
        return (
          <li key={item.id}>
            <span className="avatar avatar--small">{initials(item.actor ?? '?')}</span>
            <div>
              <p>
                <b>{item.actor}</b> {t(key, data)}
              </p>
              <time dateTime={item.created_at}>{timeAgo(item.created_at, intlLocale)}</time>
            </div>
          </li>
        );
      })}
    </ul>
  );
}
