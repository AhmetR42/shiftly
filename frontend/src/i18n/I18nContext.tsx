import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { en, type TranslationKey } from './en';
import { nl } from './nl';

export type Locale = 'en' | 'nl';

const dictionaries: Record<Locale, Record<TranslationKey, string>> = { en, nl };
const STORAGE_KEY = 'shiftly.locale';

type Params = Record<string, string | number | null | undefined>;

interface I18n {
  locale: Locale;
  setLocale: (locale: Locale) => void;
  t: (key: TranslationKey, params?: Params) => string;
  /** BCP 47 tag for Intl formatting */
  intlLocale: string;
}

const I18nContext = createContext<I18n | null>(null);

function initialLocale(): Locale {
  try {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved === 'en' || saved === 'nl') return saved;
  } catch {
    /* ignore */
  }
  return navigator.language?.toLowerCase().startsWith('nl') ? 'nl' : 'en';
}

// eslint-disable-next-line react-refresh/only-export-components
export function translate(locale: Locale, key: TranslationKey, params: Params = {}): string {
  const template = dictionaries[locale][key] ?? en[key] ?? key;
  return template.replace(/\{(\w+)\}/g, (_, name: string) => String(params[name] ?? ''));
}

export function I18nProvider({ children, locale: forced }: { children: ReactNode; locale?: Locale }) {
  const [locale, setLocaleState] = useState<Locale>(forced ?? initialLocale);

  const setLocale = useCallback((next: Locale) => {
    setLocaleState(next);
    try {
      localStorage.setItem(STORAGE_KEY, next);
    } catch {
      /* ignore */
    }
  }, []);

  useEffect(() => {
    document.documentElement.lang = locale;
  }, [locale]);

  const value = useMemo<I18n>(
    () => ({
      locale,
      setLocale,
      t: (key, params) => translate(locale, key, params),
      intlLocale: locale === 'nl' ? 'nl-NL' : 'en-GB',
    }),
    [locale, setLocale],
  );

  return <I18nContext.Provider value={value}>{children}</I18nContext.Provider>;
}

// eslint-disable-next-line react-refresh/only-export-components
export function useI18n(): I18n {
  const ctx = useContext(I18nContext);
  if (!ctx) throw new Error('useI18n must be used inside <I18nProvider>');
  return ctx;
}
