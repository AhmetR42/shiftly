<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\User;
use App\Support\Rota\ShiftRules;
use App\Support\Rota\Warning;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Glue between the database and the plain ShiftRules class.
 */
class RotaService
{
    public function __construct(private readonly ShiftRules $rules) {}

    public static function weekStart(?string $date = null): CarbonImmutable
    {
        $day = $date ? CarbonImmutable::parse($date) : CarbonImmutable::today();

        return $day->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
    }

    /**
     * @return Collection<int, Shift>
     */
    public function shiftsForWeek(int $teamId, CarbonImmutable $monday, ?int $userId = null): Collection
    {
        return Shift::query()
            ->where('team_id', $teamId)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->whereBetween('date', [$monday->format('Y-m-d'), $monday->addDays(6)->format('Y-m-d')])
            ->with(['user:id,name', 'swapRequests'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * @param  Collection<int, Shift>  $shifts
     * @return list<Warning>
     */
    public function warningsFor(Collection $shifts, int $teamId): array
    {
        $contracts = User::where('team_id', $teamId)->pluck('contract_hours', 'id')->all();

        return $this->rules->checkWeek($shifts->map->toSlot()->all(), $contracts);
    }

    /**
     * Warnings for one shift against the rest of that person's week
     * (plus the day before and after, so rest across weekends is caught).
     *
     * @return list<Warning>
     */
    public function warningsForShift(Shift $shift): array
    {
        $monday = self::weekStart($shift->date->format('Y-m-d'));

        $others = Shift::query()
            ->where('user_id', $shift->user_id)
            ->whereKeyNot($shift->id)
            ->whereBetween('date', [$monday->subDay()->format('Y-m-d'), $monday->addDays(7)->format('Y-m-d')])
            ->get();

        $inWeek = $others->filter(fn (Shift $s) => $s->date->betweenIncluded($monday, $monday->addDays(6)));
        $warnings = $this->rules->checkCandidate($shift->toSlot(), $inWeek->map->toSlot()->all(), $shift->user->contract_hours);

        // Rest checks against the neighbouring weeks, contract hours already handled above.
        foreach ($others->diffKeys($inWeek) as $neighbour) {
            if ($this->rules->hasShortRest($shift->toSlot(), $neighbour->toSlot())) {
                $warnings[] = new Warning(Warning::SHORT_REST, $shift->user_id, [$shift->id, $neighbour->id],
                    round($this->rules->restBetween($shift->toSlot(), $neighbour->toSlot()), 1));
            }
        }

        return $warnings;
    }

    /**
     * @param  Collection<int, Shift>  $shifts
     * @return array<int, float> user id => hours
     */
    public function hoursPerUser(Collection $shifts): array
    {
        return $shifts->groupBy('user_id')
            ->map(fn ($group) => $this->rules->totalHours($group->map->toSlot()))
            ->all();
    }
}
