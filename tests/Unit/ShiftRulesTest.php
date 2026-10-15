<?php

namespace Tests\Unit;

use App\Support\Rota\ShiftRules;
use App\Support\Rota\ShiftSlot;
use App\Support\Rota\Warning;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ShiftRulesTest extends TestCase
{
    private ShiftRules $rules;

    protected function setUp(): void
    {
        $this->rules = new ShiftRules;
    }

    private function slot(?int $id, string $start, string $end, int $userId = 1): ShiftSlot
    {
        return new ShiftSlot($id, $userId, new DateTimeImmutable($start), new DateTimeImmutable($end));
    }

    #[Test]
    public function it_detects_overlapping_shifts(): void
    {
        $a = $this->slot(1, '2026-10-12 08:00', '2026-10-12 14:00');
        $b = $this->slot(2, '2026-10-12 13:00', '2026-10-12 18:00');

        $this->assertTrue($this->rules->overlaps($a, $b));
    }

    #[Test]
    public function shifts_that_touch_do_not_overlap(): void
    {
        $a = $this->slot(1, '2026-10-12 08:00', '2026-10-12 12:00');
        $b = $this->slot(2, '2026-10-12 12:00', '2026-10-12 16:00');

        $this->assertFalse($this->rules->overlaps($a, $b));
    }

    #[Test]
    public function shifts_of_different_people_never_overlap(): void
    {
        $a = $this->slot(1, '2026-10-12 08:00', '2026-10-12 14:00', userId: 1);
        $b = $this->slot(2, '2026-10-12 08:00', '2026-10-12 14:00', userId: 2);

        $this->assertFalse($this->rules->overlaps($a, $b));
    }

    #[Test]
    public function it_calculates_rest_between_shifts_in_any_order(): void
    {
        $evening = $this->slot(1, '2026-10-16 15:00', '2026-10-16 22:00');
        $morning = $this->slot(2, '2026-10-17 06:00', '2026-10-17 12:00');

        $this->assertSame(8.0, $this->rules->restBetween($evening, $morning));
        $this->assertSame(8.0, $this->rules->restBetween($morning, $evening));
    }

    #[Test]
    public function less_than_eleven_hours_rest_is_flagged(): void
    {
        $evening = $this->slot(1, '2026-10-16 15:00', '2026-10-16 22:00');
        $morning = $this->slot(2, '2026-10-17 06:00', '2026-10-17 12:00');
        $later = $this->slot(3, '2026-10-17 09:00', '2026-10-17 15:00');

        $this->assertTrue($this->rules->hasShortRest($evening, $morning));
        $this->assertFalse($this->rules->hasShortRest($evening, $later), 'exactly 11 hours is fine');
    }

    #[Test]
    public function a_split_shift_on_the_same_day_is_not_a_rest_problem(): void
    {
        $a = $this->slot(1, '2026-10-12 08:00', '2026-10-12 11:00');
        $b = $this->slot(2, '2026-10-12 17:00', '2026-10-12 21:00');

        $this->assertFalse($this->rules->hasShortRest($a, $b));
    }

    #[Test]
    public function it_sums_hours(): void
    {
        $total = $this->rules->totalHours([
            $this->slot(1, '2026-10-12 08:00', '2026-10-12 14:00'),
            $this->slot(2, '2026-10-13 17:00', '2026-10-13 21:30'),
        ]);

        $this->assertSame(10.5, $total);
    }

    #[Test]
    public function a_candidate_shift_gets_all_relevant_warnings(): void
    {
        $existing = [
            $this->slot(1, '2026-10-12 08:00', '2026-10-12 14:00'),
            $this->slot(2, '2026-10-13 15:00', '2026-10-13 22:00'),
            $this->slot(3, '2026-10-12 08:00', '2026-10-12 20:00', userId: 2),
        ];
        $candidate = $this->slot(null, '2026-10-12 12:00', '2026-10-12 18:00');

        $types = array_map(fn (Warning $w) => $w->type, $this->rules->checkCandidate($candidate, $existing, 12));

        $this->assertSame([Warning::OVERLAP, Warning::OVER_CONTRACT], $types);
    }

    #[Test]
    public function editing_a_shift_does_not_compare_it_with_itself(): void
    {
        $shift = $this->slot(7, '2026-10-12 08:00', '2026-10-12 14:00');

        $this->assertSame([], $this->rules->checkCandidate($shift, [$shift], 24));
    }

    #[Test]
    public function a_whole_week_is_checked_per_person(): void
    {
        $week = [
            $this->slot(1, '2026-10-16 15:00', '2026-10-16 22:00', userId: 1),
            $this->slot(2, '2026-10-17 06:00', '2026-10-17 12:00', userId: 1),
            $this->slot(3, '2026-10-12 08:00', '2026-10-12 16:00', userId: 2),
            $this->slot(4, '2026-10-13 08:00', '2026-10-13 16:00', userId: 2),
        ];

        $warnings = $this->rules->checkWeek($week, [1 => 24, 2 => 12]);

        $this->assertCount(2, $warnings);
        $this->assertSame(Warning::SHORT_REST, $warnings[0]->type);
        $this->assertSame([1, 2], $warnings[0]->shiftIds);
        $this->assertSame(8.0, $warnings[0]->value);
        $this->assertSame(Warning::OVER_CONTRACT, $warnings[1]->type);
        $this->assertSame(16.0, $warnings[1]->value);
    }

    #[Test]
    public function a_clean_week_has_no_warnings(): void
    {
        $week = [
            $this->slot(1, '2026-10-12 08:00', '2026-10-12 14:00'),
            $this->slot(2, '2026-10-14 08:00', '2026-10-14 14:00'),
        ];

        $this->assertSame([], $this->rules->checkWeek($week, [1 => 24]));
    }
}
