import { checkCandidate, flaggedShiftIds, hasShortRest, overlaps, restBetween, totalHours } from './rules';

const shift = (id: number, date: string, start: string, end: string, user_id = 1) => ({
  id,
  user_id,
  date,
  start_time: start,
  end_time: end,
});

describe('shift rules', () => {
  it('detects overlap only for the same person', () => {
    const a = shift(1, '2026-10-12', '08:00', '14:00');
    expect(overlaps(a, shift(2, '2026-10-12', '13:00', '18:00'))).toBe(true);
    expect(overlaps(a, shift(3, '2026-10-12', '14:00', '18:00'))).toBe(false);
    expect(overlaps(a, shift(4, '2026-10-12', '08:00', '14:00', 2))).toBe(false);
  });

  it('measures rest across days in either order', () => {
    const late = shift(1, '2026-10-16', '15:00', '22:00');
    const early = shift(2, '2026-10-17', '06:00', '12:00');
    expect(restBetween(late, early)).toBe(8);
    expect(restBetween(early, late)).toBe(8);
    expect(hasShortRest(late, early)).toBe(true);
    expect(hasShortRest(late, shift(3, '2026-10-17', '09:00', '15:00'))).toBe(false);
  });

  it('ignores rest between two shifts on the same day', () => {
    expect(hasShortRest(shift(1, '2026-10-12', '08:00', '11:00'), shift(2, '2026-10-12', '17:00', '21:00'))).toBe(false);
  });

  it('adds up hours including half hours', () => {
    expect(totalHours([shift(1, '2026-10-12', '08:00', '14:00'), shift(2, '2026-10-13', '17:00', '21:30')])).toBe(10.5);
  });

  it('returns overlap and contract warnings for a new shift', () => {
    const existing = [shift(1, '2026-10-12', '08:00', '14:00'), shift(2, '2026-10-12', '08:00', '20:00', 2)];
    const warnings = checkCandidate(shift(0, '2026-10-12', '12:00', '18:00'), existing, 10);
    expect(warnings.map((w) => w.type)).toEqual(['overlap', 'over_contract']);
    expect(warnings[0].shift_ids).toEqual([1]);
    expect(warnings[1].value).toBe(12);
  });

  it('does not compare an edited shift with itself', () => {
    const s = shift(5, '2026-10-12', '08:00', '14:00');
    expect(checkCandidate(s, [s], 24)).toEqual([]);
  });

  it('collects flagged shift ids', () => {
    const ids = flaggedShiftIds([
      { type: 'overlap', user_id: 1, shift_ids: [1, 2], value: null },
      { type: 'over_contract', user_id: 1, shift_ids: [], value: 30 },
    ]);
    expect([...ids]).toEqual([1, 2]);
  });
});
