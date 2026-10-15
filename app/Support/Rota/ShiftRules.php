<?php

namespace App\Support\Rota;

/**
 * The planning rules a manager should not have to keep in their head:
 *  - one person cannot work two shifts at the same time,
 *  - there should be at least 11 hours of rest between two shifts
 *    (the Dutch Arbeidstijdenwet minimum),
 *  - a week should not go over the hours in someone's contract.
 *
 * These are warnings, not hard blocks. Sometimes a manager has no choice,
 * but they should at least see it.
 */
final class ShiftRules
{
    public const MIN_REST_HOURS = 11;

    public function overlaps(ShiftSlot $a, ShiftSlot $b): bool
    {
        return $a->userId === $b->userId
            && $a->start < $b->end
            && $b->start < $a->end;
    }

    /**
     * Hours between the end of the earlier shift and the start of the later one.
     * Returns null when the shifts overlap (rest is meaningless then).
     */
    public function restBetween(ShiftSlot $a, ShiftSlot $b): ?float
    {
        [$first, $second] = $a->start <= $b->start ? [$a, $b] : [$b, $a];
        $gap = ($second->start->getTimestamp() - $first->end->getTimestamp()) / 3600;

        return $gap < 0 ? null : $gap;
    }

    /**
     * Two shifts on the same day with a short break in between (a split shift)
     * are not a rest problem. Only shifts on different days count.
     */
    public function hasShortRest(ShiftSlot $a, ShiftSlot $b): bool
    {
        if ($a->userId !== $b->userId || $a->start->format('Y-m-d') === $b->start->format('Y-m-d')) {
            return false;
        }

        $rest = $this->restBetween($a, $b);

        return $rest !== null && $rest < self::MIN_REST_HOURS;
    }

    /**
     * @param  iterable<ShiftSlot>  $slots
     */
    public function totalHours(iterable $slots): float
    {
        $total = 0.0;
        foreach ($slots as $slot) {
            $total += $slot->hours();
        }

        return round($total, 2);
    }

    /**
     * Check one (new or edited) shift against the other shifts of that person.
     *
     * @param  iterable<ShiftSlot>  $others
     * @return list<Warning>
     */
    public function checkCandidate(ShiftSlot $candidate, iterable $others, int $contractHours): array
    {
        $warnings = [];
        $week = [$candidate];

        foreach ($others as $other) {
            if ($other->userId !== $candidate->userId || ($candidate->id !== null && $other->id === $candidate->id)) {
                continue;
            }

            $week[] = $other;
            $ids = array_values(array_filter([$candidate->id, $other->id]));

            if ($this->overlaps($candidate, $other)) {
                $warnings[] = new Warning(Warning::OVERLAP, $candidate->userId, $ids);
            } elseif ($this->hasShortRest($candidate, $other)) {
                $warnings[] = new Warning(Warning::SHORT_REST, $candidate->userId, $ids, round($this->restBetween($candidate, $other), 1));
            }
        }

        $total = $this->totalHours($week);
        if ($total > $contractHours) {
            $warnings[] = new Warning(Warning::OVER_CONTRACT, $candidate->userId, [], $total);
        }

        return $warnings;
    }

    /**
     * All warnings for a week of shifts.
     *
     * @param  list<ShiftSlot>  $slots
     * @param  array<int, int>  $contractHours  user id => contract hours
     * @return list<Warning>
     */
    public function checkWeek(array $slots, array $contractHours): array
    {
        $warnings = [];
        $byUser = [];
        foreach ($slots as $slot) {
            $byUser[$slot->userId][] = $slot;
        }

        foreach ($byUser as $userId => $userSlots) {
            usort($userSlots, fn (ShiftSlot $a, ShiftSlot $b) => $a->start <=> $b->start);
            $count = count($userSlots);

            for ($i = 0; $i < $count; $i++) {
                for ($j = $i + 1; $j < $count; $j++) {
                    $a = $userSlots[$i];
                    $b = $userSlots[$j];
                    $ids = array_values(array_filter([$a->id, $b->id]));

                    if ($this->overlaps($a, $b)) {
                        $warnings[] = new Warning(Warning::OVERLAP, $userId, $ids);
                    } elseif ($j === $i + 1 && $this->hasShortRest($a, $b)) {
                        $warnings[] = new Warning(Warning::SHORT_REST, $userId, $ids, round($this->restBetween($a, $b), 1));
                    }
                }
            }

            $total = $this->totalHours($userSlots);
            if (isset($contractHours[$userId]) && $total > $contractHours[$userId]) {
                $warnings[] = new Warning(Warning::OVER_CONTRACT, $userId, [], $total);
            }
        }

        return $warnings;
    }
}
