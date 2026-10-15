import { useMemo, useState, type DragEvent } from 'react';
import { useOutletContext } from 'react-router-dom';
import { api } from '../api/client';
import { useEmployees, useRotaMutation, useShifts } from '../api/queries';
import type { Employee, Shift } from '../api/types';
import type { LayoutContext } from '../components/AppLayout';
import { Icon } from '../components/Icon';
import { ShiftModal } from '../components/ShiftModal';
import { initials } from '../components/Sidebar';
import { WeekNav } from '../components/WeekNav';
import { useI18n } from '../i18n/I18nContext';
import { formatHours, isToday, parseISODate, weekDays, weekStart } from '../lib/dates';
import { formatDay } from '../lib/format';
import { flaggedShiftIds } from '../lib/rules';
import { useWarningText } from '../lib/useWarningText';

type Editing = { shift?: Shift; defaults?: { user_id?: number; date: string } } | null;

export function RotaPage() {
  const { t, intlLocale } = useI18n();
  const { search } = useOutletContext<LayoutContext>();
  const warningText = useWarningText();
  const [monday, setMonday] = useState(() => weekStart());
  const [editing, setEditing] = useState<Editing>(null);
  const [toast, setToast] = useState<string | null>(null);
  const [dropTarget, setDropTarget] = useState<string | null>(null);

  const { data: week } = useShifts(monday);
  const { data: employees = [] } = useEmployees(monday);
  const move = useRotaMutation(({ id, ...input }: { id: number; date: string; user_id: number }) => api.updateShift(id, input));
  const copy = useRotaMutation((target: string) => api.copyWeek(target));

  const days = weekDays(monday);
  const shifts = useMemo(() => week?.data ?? [], [week]);
  const warnings = week?.meta.warnings ?? [];
  const flagged = flaggedShiftIds(warnings);
  const visible = employees.filter((e) => e.name.toLowerCase().includes(search.trim().toLowerCase()));
  const byName = new Map(employees.map((e) => [e.id, e]));

  const cellShifts = (userId: number, date: string) => shifts.filter((s) => s.user_id === userId && s.date === date);

  const onDrop = (e: DragEvent, employee: Employee, date: string) => {
    e.preventDefault();
    setDropTarget(null);
    const id = Number(e.dataTransfer.getData('text/plain'));
    const shift = shifts.find((s) => s.id === id);
    if (shift && (shift.date !== date || shift.user_id !== employee.id)) {
      move.mutate({ id, date, user_id: employee.id });
    }
  };

  const copyWeek = () =>
    copy.mutate(monday, {
      onSuccess: (r) => {
        setToast(t('rota.copied', { count: r.created }));
        setTimeout(() => setToast(null), 3000);
      },
    });

  return (
    <div className="rota">
      <div className="page-head">
        <h1>{t('rota.title')}</h1>
        <WeekNav monday={monday} onChange={setMonday} />
        <span className="spacer" />
        <button type="button" className="button button--ghost" onClick={copyWeek} disabled={copy.isPending}>
          <Icon name="copy" size={18} />
          {t('rota.copyWeek')}
        </button>
        <button type="button" className="button" onClick={() => setEditing({ defaults: { date: days[0] } })}>
          <Icon name="plus" size={18} />
          {t('rota.addShift')}
        </button>
      </div>

      {toast && <p className="notice" role="status">{toast}</p>}

      {warnings.length > 0 && (
        <details className="card warnings-panel">
          <summary>
            <Icon name="alert" size={18} /> {t('rota.warningsTitle', { count: warnings.length })}
          </summary>
          <ul className="warnings">
            {warnings.map((w, i) => (
              <li key={i}>
                <b>{byName.get(w.user_id)?.name}</b>: {warningText(w, byName.get(w.user_id)?.contract_hours)}
              </li>
            ))}
          </ul>
        </details>
      )}

      <p className="muted small">{t('rota.dragHint')}</p>

      <div className="card rota-grid-wrap">
        <table className="rota-grid">
          <thead>
            <tr>
              <th scope="col">{t('rota.employee')}</th>
              {days.map((d) => (
                <th key={d} scope="col" className={isToday(d) ? 'is-today' : undefined}>
                  {formatDay(d, intlLocale)}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {visible.map((employee) => {
              const hours = week?.meta.hours[employee.id] ?? 0;
              return (
                <tr key={employee.id}>
                  <th scope="row">
                    <div className="rota-grid__row-head">
                    <span className="avatar avatar--small">{initials(employee.name)}</span>
                    <span className="rota-grid__person">
                      <b>{employee.name}</b>
                      <small className={hours > employee.contract_hours ? 'is-over' : undefined}>
                        {formatHours(hours)} / {employee.contract_hours} {t('common.hours')}
                      </small>
                    </span>
                    </div>
                  </th>
                  {days.map((date) => {
                    const key = `${employee.id}|${date}`;
                    const weekday = ((parseISODate(date).getDay() + 6) % 7) + 1;
                    const unavailable = employee.availability.find((a) => a.weekday === weekday)?.available === false;
                    return (
                      <td
                        key={date}
                        className={[dropTarget === key && 'is-drop', unavailable && 'is-unavailable'].filter(Boolean).join(' ') || undefined}
                        title={unavailable ? t('rota.unavailable') : undefined}
                        onDragOver={(e) => {
                          e.preventDefault();
                          setDropTarget(key);
                        }}
                        onDragLeave={() => setDropTarget((k) => (k === key ? null : k))}
                        onDrop={(e) => onDrop(e, employee, date)}
                        onClick={(e) => {
                          if (e.target === e.currentTarget) setEditing({ defaults: { user_id: employee.id, date } });
                        }}
                      >
                        {cellShifts(employee.id, date).map((s) => (
                          <button
                            key={s.id}
                            type="button"
                            draggable
                            onDragStart={(e) => e.dataTransfer.setData('text/plain', String(s.id))}
                            onClick={() => setEditing({ shift: s })}
                            className={`shift-chip dept--${s.department}${flagged.has(s.id) ? ' is-flagged' : ''}`}
                          >
                            {flagged.has(s.id) && <Icon name="alert" size={14} />}
                            {s.start_time}–{s.end_time}
                            {s.swap_status && <span className="shift-chip__swap" aria-label={t(`status.${s.swap_status}`)}>⇄</span>}
                          </button>
                        ))}
                      </td>
                    );
                  })}
                </tr>
              );
            })}
          </tbody>
        </table>
        {visible.length === 0 && <p className="muted center">{t('rota.noMatch')}</p>}
      </div>

      {editing && (
        <ShiftModal
          shift={editing.shift}
          defaults={editing.defaults}
          employees={employees}
          weekShifts={shifts}
          onClose={() => setEditing(null)}
        />
      )}
    </div>
  );
}
