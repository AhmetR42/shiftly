import { useEffect, useState } from 'react';
import { useI18n } from '../i18n/I18nContext';
import { Icon } from './Icon';

type Theme = 'light' | 'dark';
const KEY = 'shiftly.theme';

function initialTheme(): Theme {
  try {
    const saved = localStorage.getItem(KEY);
    if (saved === 'light' || saved === 'dark') return saved;
  } catch {
    /* ignore */
  }
  return 'light';
}

export function ThemeToggle() {
  const { t } = useI18n();
  const [theme, setTheme] = useState<Theme>(initialTheme);

  useEffect(() => {
    document.documentElement.dataset.theme = theme;
    try {
      localStorage.setItem(KEY, theme);
    } catch {
      /* ignore */
    }
  }, [theme]);

  const next = theme === 'light' ? 'dark' : 'light';

  return (
    <button
      type="button"
      className="icon-button"
      onClick={() => setTheme(next)}
      aria-label={next === 'dark' ? t('topbar.darkMode') : t('topbar.lightMode')}
      title={next === 'dark' ? t('topbar.darkMode') : t('topbar.lightMode')}
    >
      <Icon name={theme === 'light' ? 'moon' : 'sun'} />
    </button>
  );
}
