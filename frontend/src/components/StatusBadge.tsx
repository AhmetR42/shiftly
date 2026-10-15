import { useI18n } from '../i18n/I18nContext';
import type { TranslationKey } from '../i18n/en';

export function StatusBadge({ status }: { status: string }) {
  const { t } = useI18n();
  return <span className={`badge badge--${status}`}>{t(`status.${status}` as TranslationKey)}</span>;
}

export function DepartmentTag({ department }: { department: string }) {
  const { t } = useI18n();
  return <span className={`dept dept--${department}`}>{t(`dept.${department}` as TranslationKey)}</span>;
}
