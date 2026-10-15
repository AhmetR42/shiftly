import { useMemo, useState, type FormEvent } from 'react';
import { api, ApiError } from '../api/client';
import { useRotaMutation } from '../api/queries';
import { DEPARTMENTS, type Department, type Employee, type Shift, type ShiftInput } from '../api/types';
import { useI18n } from '../i18n/I18nContext';
import type { TranslationKey } from '../i18n/en';
import { durationHours, formatHours } from '../lib/dates';
import { checkCandidate } from '../lib/rules';
import { useWarningText } from '../lib/useWarningText';
import { Icon } from './Icon';
import { Modal } from './Modal';

interface Props {
  shift?: Shift;
  defaults?: { user_id?: number; date: string };
  employees: Employee[];
  weekShifts: Shift[];
  onClose: () => void;
}

const PRESETS: [string, string][] = [
  ['06:00', '12:00'],
  ['08:00', '14:00'],
  ['12:00', '18:00'],
  ['16:00', '22:00'],
];

export function ShiftModal({ shift, defaults, employees, weekShifts, onClose }: Props) {
  const { t } = useI18n();
  const warningText = useWarningText();
  const firstEmployee = employees.find((e) => e.id === defaults?.user_id) ?? employees[0];

  const [form, setForm] = useState<ShiftInput>(() => ({
    user_id: shift?.user_id ?? firstEmployee?.id ?? 0,
    date: shift?.date ?? defaults?.date ?? '',
    start_time: shift?.start_time ?? '08:00',
    end_time: shift?.end_time ?? '14:00',
    department: shift?.department ?? firstEmployee?.department ?? 'checkout',
    note: shift?.note ?? '',
  }));
  const [error, setError] = useState<string | null>(null);

  const save = useRotaMutation((input: ShiftInput) => (shift ? api.updateShift(shift.id, input) : api.createShift(input)));
  const remove = useRotaMutation((id: number) => api.deleteShift(id));

  const set = <K extends keyof ShiftInput>(key: K, value: ShiftInput[K]) => setForm((f) => ({ ...f, [key]: value }));

  const employee = employees.find((e) => e.id === form.user_id);
  const valid = form.end_time > form.start_time;

  // Live rule check while typing, before anything is saved.
  const warnings = useMemo(() => {
    if (!employee || !valid || !form.date) return [];
    return checkCandidate({ ...form, id: shift?.id ?? 0 }, weekShifts, employee.contract_hours);
  }, [form, employee, valid, shift?.id, weekShifts]);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    setError(null);
    save.mutate(form, {
      onSuccess: onClose,
      onError: (err) => setError(err instanceof ApiError ? err.firstError() : t('common.error')),
    });
  };

  return (
    <Modal title={shift ? t('shift.edit') : t('shift.new')} onClose={onClose}>
      <form className="form" onSubmit={submit}>
        <label className="field">
          <span>{t('shift.employee')}</span>
          <select
            value={form.user_id}
            onChange={(e) => {
              const next = employees.find((x) => x.id === Number(e.target.value));
              setForm((f) => ({ ...f, user_id: Number(e.target.value), department: shift ? f.department : (next?.department ?? f.department) }));
            }}
          >
            {employees.map((e) => (
              <option key={e.id} value={e.id}>
                {e.name}
              </option>
            ))}
          </select>
        </label>

        <div className="form__row">
          <label className="field">
            <span>{t('shift.date')}</span>
            <input type="date" required value={form.date} onChange={(e) => set('date', e.target.value)} />
          </label>
          <label className="field">
            <span>{t('shift.department')}</span>
            <select value={form.department} onChange={(e) => set('department', e.target.value as Department)}>
              {DEPARTMENTS.map((d) => (
                <option key={d} value={d}>
                  {t(`dept.${d}` as TranslationKey)}
                </option>
              ))}
            </select>
          </label>
        </div>

        <div className="form__row">
          <label className="field">
            <span>{t('shift.start')}</span>
            <input type="time" required step={900} value={form.start_time} onChange={(e) => set('start_time', e.target.value)} />
          </label>
          <label className="field">
            <span>{t('shift.end')}</span>
            <input type="time" required step={900} value={form.end_time} onChange={(e) => set('end_time', e.target.value)} />
          </label>
        </div>

        <div className="presets" aria-label={t('shift.presets')}>
          {PRESETS.map(([start, end]) => (
            <button
              key={start + end}
              type="button"
              className={`chip${form.start_time === start && form.end_time === end ? ' is-active' : ''}`}
              onClick={() => setForm((f) => ({ ...f, start_time: start, end_time: end }))}
            >
              {start}–{end}
            </button>
          ))}
        </div>

        <label className="field">
          <span>{t('shift.note')}</span>
          <input type="text" maxLength={140} value={form.note ?? ''} onChange={(e) => set('note', e.target.value)} placeholder={t('shift.notePlaceholder')} />
        </label>

        <p className="form__summary">
          {valid ? t('shift.length', { hours: formatHours(durationHours(form.start_time, form.end_time)) }) : t('shift.endAfterStart')}
        </p>

        {warnings.length > 0 && (
          <ul className="warnings" aria-live="polite">
            {warnings.map((w, i) => (
              <li key={i}>
                <Icon name="alert" size={16} />
                {warningText(w, employee?.contract_hours)}
              </li>
            ))}
          </ul>
        )}

        {error && <p className="form__error">{error}</p>}

        <footer className="modal__footer">
          {shift && (
            <button
              type="button"
              className="button button--danger-ghost"
              disabled={remove.isPending}
              onClick={() => remove.mutate(shift.id, { onSuccess: onClose })}
            >
              <Icon name="trash" size={18} />
              {t('common.delete')}
            </button>
          )}
          <span className="spacer" />
          <button type="button" className="button button--ghost" onClick={onClose}>
            {t('common.cancel')}
          </button>
          <button type="submit" className="button" disabled={!valid || save.isPending}>
            {save.isPending ? t('common.saving') : t('common.save')}
          </button>
        </footer>
      </form>
    </Modal>
  );
}
