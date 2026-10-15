<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Availability;
use App\Models\Shift;
use App\Models\SwapRequest;
use App\Models\Team;
use App\Models\TimeOffRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Builds a believable supermarket team: one manager, eight colleagues
 * and three weeks of shifts (last week, this week and next week).
 *
 * Used by the database seeder and by the "Try as ..." demo buttons.
 */
class DemoTeamBuilder
{
    /** name, department, contract hours */
    private const PEOPLE = [
        ['Sanne de Vries', 'checkout', 24],
        ['Daan Jansen', 'stock', 32],
        ['Lotte Bakker', 'bakery', 28],
        ['Milan Visser', 'fresh', 20],
        ['Emma Smit', 'checkout', 12],
        ['Yusuf Demir', 'stock', 16],
        ['Fleur de Boer', 'fresh', 24],
        ['Noah Meijer', 'checkout', 12],
    ];

    /** Typical opening hours of a neighbourhood supermarket, per department. */
    private const TEMPLATES = [
        'checkout' => [['08:00', '14:00'], ['12:00', '18:00'], ['16:00', '22:00'], ['17:00', '21:00']],
        'fresh' => [['07:00', '13:00'], ['10:00', '16:00'], ['14:00', '20:00']],
        'stock' => [['06:00', '12:00'], ['06:00', '14:00'], ['15:00', '21:00']],
        'bakery' => [['05:30', '12:30'], ['06:00', '13:00']],
    ];

    private string $passwordHash;

    /**
     * @param  array{manager_email?: string, employee_email?: string, demo?: bool, name?: string}  $options
     * @return array{team: Team, manager: User, employee: User}
     */
    public function build(array $options = []): array
    {
        $this->passwordHash = Hash::make('password');
        $isDemo = $options['demo'] ?? true;
        $suffix = Str::lower(Str::random(6));
        mt_srand($isDemo ? crc32($suffix) : 1042);

        return DB::transaction(function () use ($options, $isDemo, $suffix) {
            $team = Team::create([
                'name' => $options['name'] ?? 'Supermarket Utrecht Centrum',
                'is_demo' => $isDemo,
            ]);

            $manager = $this->person($team, 'Ingrid Mulder', 'manager', 'checkout', 38,
                $options['manager_email'] ?? "manager-{$suffix}@demo.shiftly.test");

            $employees = collect(self::PEOPLE)->map(function (array $p, int $i) use ($team, $options, $suffix) {
                $email = $i === 0
                    ? ($options['employee_email'] ?? "employee-{$suffix}@demo.shiftly.test")
                    : Str::slug($p[0], '.')."-{$suffix}@demo.shiftly.test";

                return $this->person($team, $p[0], 'employee', $p[1], $p[2], $email);
            });

            $employees->each(fn (User $e) => $this->availability($e));

            $thisMonday = RotaService::weekStart();
            foreach ([-1, 0, 1] as $offset) {
                $this->planWeek($team, $employees->all(), $thisMonday->addWeeks($offset));
            }

            $this->addRequests($team, $manager, $employees->all(), $thisMonday);

            return ['team' => $team, 'manager' => $manager, 'employee' => $employees->first()];
        });
    }

    private function person(Team $team, string $name, string $role, string $department, int $hours, string $email): User
    {
        $user = new User([
            'team_id' => $team->id,
            'name' => $name,
            'email' => $email,
            'role' => $role,
            'department' => $department,
            'contract_hours' => $hours,
        ]);
        // Skip the hashed cast so we don't hash "password" nine times.
        $user->setRawAttributes(array_merge($user->getAttributes(), ['password' => $this->passwordHash]));
        $user->save();

        return $user;
    }

    private function availability(User $employee): void
    {
        // Students (small contracts) are not available on weekday mornings.
        $student = $employee->contract_hours <= 16;

        foreach (range(1, 7) as $weekday) {
            Availability::create([
                'user_id' => $employee->id,
                'weekday' => $weekday,
                'available' => ! ($weekday === 7 && $employee->contract_hours > 28),
                'from_time' => $student && $weekday <= 5 ? '15:00' : null,
                'until_time' => null,
            ]);
        }
    }

    /**
     * @param  list<User>  $employees
     */
    private function planWeek(Team $team, array $employees, CarbonImmutable $monday): void
    {
        foreach ($employees as $employee) {
            $templates = self::TEMPLATES[$employee->department];
            $student = $employee->contract_hours <= 16;
            $days = range(0, 6);
            shuffle($days);

            $planned = 0;
            foreach ($days as $day) {
                $options = $student && $day < 5
                    ? array_values(array_filter($templates, fn ($t) => $t[0] >= '15:00'))
                    : $templates;
                if ($options === []) {
                    $options = [end($templates)];
                }
                [$start, $end] = $options[mt_rand(0, count($options) - 1)];
                $length = (strtotime($end) - strtotime($start)) / 3600;

                if ($planned + $length > $employee->contract_hours) {
                    continue;
                }

                Shift::create([
                    'team_id' => $team->id,
                    'user_id' => $employee->id,
                    'date' => $monday->addDays($day)->format('Y-m-d'),
                    'start_time' => $start,
                    'end_time' => $end,
                    'department' => $employee->department,
                ]);
                $planned += $length;
            }
        }
    }

    /**
     * Sprinkle in some swaps, time-off and one planning mistake so every
     * screen has something to show.
     *
     * @param  list<User>  $employees
     */
    private function addRequests(Team $team, User $manager, array $employees, CarbonImmutable $monday): void
    {
        [$sanne, $daan, $lotte, $milan, $emma, $yusuf, $fleur] = $employees;

        // A late close followed by an early stock shift: less than 11 hours of rest.
        $friday = $monday->addDays(4)->format('Y-m-d');
        $saturday = $monday->addDays(5)->format('Y-m-d');
        Shift::where('user_id', $daan->id)->whereIn('date', [$friday, $saturday])->delete();
        Shift::create(['team_id' => $team->id, 'user_id' => $daan->id, 'date' => $friday,
            'start_time' => '15:00', 'end_time' => '22:00', 'department' => 'stock', 'note' => 'Delivery evening']);
        Shift::create(['team_id' => $team->id, 'user_id' => $daan->id, 'date' => $saturday,
            'start_time' => '06:00', 'end_time' => '12:00', 'department' => 'stock']);

        // Sanne offers a shift next week, Milan already picked one up from Fleur.
        $nextWeek = [$monday->addWeek()->format('Y-m-d'), $monday->addWeek()->addDays(6)->format('Y-m-d')];
        $sanneShift = Shift::where('user_id', $sanne->id)->whereBetween('date', $nextWeek)->orderBy('date')->first();
        if ($sanneShift) {
            SwapRequest::create(['shift_id' => $sanneShift->id, 'requester_id' => $sanne->id,
                'status' => SwapRequest::OPEN, 'note' => 'Family birthday, anyone?']);
        }

        $fleurShift = Shift::where('user_id', $fleur->id)->whereBetween('date', $nextWeek)->orderBy('date')->first();
        if ($fleurShift) {
            SwapRequest::create(['shift_id' => $fleurShift->id, 'requester_id' => $fleur->id,
                'taker_id' => $milan->id, 'status' => SwapRequest::PENDING, 'note' => 'Exam week']);
        }

        $emmaShift = Shift::where('user_id', $emma->id)->whereBetween('date', $nextWeek)->orderBy('date', 'desc')->first();
        if ($emmaShift) {
            SwapRequest::create(['shift_id' => $emmaShift->id, 'requester_id' => $emma->id, 'status' => SwapRequest::OPEN]);
        }

        TimeOffRequest::create(['user_id' => $lotte->id, 'start_date' => $monday->addWeeks(2)->format('Y-m-d'),
            'end_date' => $monday->addWeeks(2)->addDays(4)->format('Y-m-d'), 'reason' => 'Holiday in Portugal']);
        TimeOffRequest::create(['user_id' => $yusuf->id, 'start_date' => $monday->addWeek()->addDays(2)->format('Y-m-d'),
            'end_date' => $monday->addWeek()->addDays(2)->format('Y-m-d'), 'reason' => 'Driving test']);
        TimeOffRequest::create(['user_id' => $sanne->id, 'start_date' => $monday->addWeeks(3)->format('Y-m-d'),
            'end_date' => $monday->addWeeks(3)->addDay()->format('Y-m-d'), 'reason' => 'Weekend away',
            'status' => 'approved', 'reviewed_by' => $manager->id]);

        $log = [
            [$manager, 'week_copied', ['week' => $monday->format('Y-m-d')], 50],
            [$lotte, 'time_off_requested', ['days' => 5], 30],
            [$fleur, 'swap_offered', ['date' => $fleurShift?->date->format('Y-m-d')], 26],
            [$milan, 'swap_picked_up', ['name' => $fleur->name], 20],
            [$sanne, 'swap_offered', ['date' => $sanneShift?->date->format('Y-m-d')], 8],
            [$yusuf, 'time_off_requested', ['days' => 1], 3],
        ];
        foreach ($log as [$actor, $type, $data, $hoursAgo]) {
            $activity = Activity::record($actor, $type, $data);
            $activity->forceFill(['created_at' => now()->subHours($hoursAgo)])->save();
        }
    }
}
