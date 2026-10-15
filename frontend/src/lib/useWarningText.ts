import type { RuleWarning } from '../api/types';
import { useI18n } from '../i18n/I18nContext';
import { formatHours } from './dates';

/** Human sentence for a rule warning, e.g. "Only 8 hours rest between two shifts". */
export function useWarningText() {
  const { t } = useI18n();
  return (w: RuleWarning, contract?: number) => {
    switch (w.type) {
      case 'overlap':
        return t('warning.overlap');
      case 'short_rest':
        return t('warning.shortRest', { hours: formatHours(w.value ?? 0) });
      case 'over_contract':
        return t('warning.overContract', { hours: formatHours(w.value ?? 0), contract: contract ?? '' });
    }
  };
}
