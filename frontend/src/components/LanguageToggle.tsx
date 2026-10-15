import { useI18n, type Locale } from '../i18n/I18nContext';

/**
 * Two-option switch with a thumb that slides under the active language.
 */
export function LanguageToggle() {
  const { locale, setLocale, t } = useI18n();
  const options: Locale[] = ['en', 'nl'];

  return (
    <div className="lang-toggle" role="radiogroup" aria-label={t('topbar.language')} data-active={locale}>
      <span className="lang-toggle__thumb" aria-hidden="true" />
      {options.map((option) => (
        <button
          key={option}
          type="button"
          role="radio"
          aria-checked={locale === option}
          className={locale === option ? 'is-active' : undefined}
          onClick={() => setLocale(option)}
        >
          {option.toUpperCase()}
        </button>
      ))}
    </div>
  );
}
