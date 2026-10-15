import type { RuleWarning, Shift } from '../api/types';
import { parseISODate, minutesOf } from './dates';

/**
 * Client-side copy of App\Support\Rota\ShiftRules. The API is the source of
 * truth; this version gives instant feedback while a manager edits or drags
 * a shift, before anything is saved.
 */

export const MIN_REST_HOURS = 11;

type Slot = Pick<Shift, 'id' | 'user_id' | 'date' | 'start_time' | 'end_time'>;

function startMs(s: Slot): number {
  return parseISODate(s.date).getTime() + minutesOf(s.start_time) * 60000;
}

function endMs(s: Slot): number {
  return parseISODate(s.date).getTime() + minutesOf(s.end_time) * 60000;
}

export function overlaps(a: Slot, b: Slot): boolean {
  return a.user_id === b.user_id && startMs(a) < endMs(b) && startMs(b) < endMs(a);
}

/** Hours between the two shifts, or null when they overlap. */
export function restBetween(a: Slot, b: Slot): number | null {
  const [first, second] = startMs(a) <= startMs(b) ? [a, b] : [b, a];
  const gap = (startMs(second) - endMs(first)) / 3600000;
  return gap < 0 ? null : gap;
}

export function hasShortRest(a: Slot, b: Slot): boolean {
  if (a.user_id !== b.user_id || a.date === b.date) return false;
  const rest = restBetween(a, b);
  return rest !== null && rest < MIN_REST_HOURS;
}

export function totalHours(slots: Slot[]): number {
  const minutes = slots.reduce((sum, s) => sum + (endMs(s) - startMs(s)) / 60000, 0);
  return Math.round((minutes / 60) * 100) / 100;
}

/**
 * Warnings for a single candidate shift against the rest of the week.
 * `others` may contain everybody's shifts; only the same person counts.
 */
export function checkCandidate(candidate: Slot, others: Slot[], contractHours: number): RuleWarning[] {
  const warnings: RuleWarning[] = [];
  const mine = others.filter((o) => o.user_id === candidate.user_id && o.id !== candidate.id);

  for (const other of mine) {
    const ids = [candidate.id, other.id].filter((id) => id > 0);
    if (overlaps(candidate, other)) {
      warnings.push({ type: 'overlap', user_id: candidate.user_id, shift_ids: ids, value: null });
    } else if (hasShortRest(candidate, other)) {
      const rest = restBetween(candidate, other) ?? 0;
      warnings.push({ type: 'short_rest', user_id: candidate.user_id, shift_ids: ids, value: Math.round(rest * 10) / 10 });
    }
  }

  const total = totalHours([candidate, ...mine]);
  if (total > contractHours) {
    warnings.push({ type: 'over_contract', user_id: candidate.user_id, shift_ids: [], value: total });
  }
  return warnings;
}

/** Which shift ids have at least one warning, for highlighting in the grid. */
export function flaggedShiftIds(warnings: RuleWarning[]): Set<number> {
  return new Set(warnings.flatMap((w) => w.shift_ids));
}
