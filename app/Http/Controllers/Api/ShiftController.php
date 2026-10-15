<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CopyWeekRequest;
use App\Http\Requests\ShiftRequest;
use App\Http\Resources\ShiftResource;
use App\Models\Activity;
use App\Models\Shift;
use App\Services\RotaService;
use App\Support\Rota\Warning;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class ShiftController extends Controller
{
    public function __construct(private readonly RotaService $rota) {}

    /**
     * Everyone can see the team rota. Pass ?mine=1 for only your own shifts.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $monday = RotaService::weekStart($request->query('week'));
        $shifts = $this->rota->shiftsForWeek($user->team_id, $monday, $request->boolean('mine') ? $user->id : null);

        $warnings = $user->isManager() ? $this->rota->warningsFor($shifts, $user->team_id) : [];

        return ShiftResource::collection($shifts)->additional(['meta' => [
            'week_start' => $monday->format('Y-m-d'),
            'hours' => (object) $this->rota->hoursPerUser($shifts),
            'warnings' => array_map(fn (Warning $w) => $w->toArray(), $warnings),
        ]]);
    }

    public function store(ShiftRequest $request): JsonResponse
    {
        $this->authorize('create', Shift::class);

        $shift = Shift::create([...$request->validated(), 'team_id' => $request->user()->team_id]);
        Activity::record($request->user(), 'shift_created', ['name' => $shift->user->name, 'date' => $shift->date->format('Y-m-d')]);

        return $this->respondWithWarnings($shift, 201);
    }

    public function update(ShiftRequest $request, Shift $shift): JsonResponse
    {
        $this->authorize('update', $shift);

        $shift->update($request->validated());

        return $this->respondWithWarnings($shift->refresh());
    }

    public function destroy(Request $request, Shift $shift): JsonResponse
    {
        $this->authorize('delete', $shift);

        Activity::record($request->user(), 'shift_deleted', ['name' => $shift->user->name, 'date' => $shift->date->format('Y-m-d')]);
        $shift->delete();

        return response()->json(status: 204);
    }

    /**
     * Copy every shift of the previous week into the given week.
     * Shifts that already exist (same person, day and times) are skipped,
     * so pressing the button twice doesn't double the rota.
     */
    public function copyWeek(CopyWeekRequest $request): JsonResponse
    {
        $this->authorize('create', Shift::class);

        $user = $request->user();
        $target = RotaService::weekStart($request->input('week'));
        $source = $this->rota->shiftsForWeek($user->team_id, $target->subWeek());
        $existing = $this->rota->shiftsForWeek($user->team_id, $target)
            ->map(fn (Shift $s) => $this->fingerprint($s->user_id, $s->date->format('Y-m-d'), $s->start_time, $s->end_time))
            ->flip();

        $created = DB::transaction(function () use ($source, $existing) {
            $count = 0;
            foreach ($source as $shift) {
                $date = $shift->date->addWeek()->format('Y-m-d');
                if ($existing->has($this->fingerprint($shift->user_id, $date, $shift->start_time, $shift->end_time))) {
                    continue;
                }

                Shift::create([
                    ...$shift->only(['team_id', 'user_id', 'start_time', 'end_time', 'department', 'note']),
                    'date' => $date,
                ]);
                $count++;
            }

            return $count;
        });

        Activity::record($user, 'week_copied', ['week' => $target->format('Y-m-d'), 'count' => $created]);

        return response()->json(['created' => $created, 'week_start' => $target->format('Y-m-d')], 201);
    }

    private function respondWithWarnings(Shift $shift, int $status = 200): JsonResponse
    {
        $shift->load(['user:id,name,contract_hours', 'swapRequests']);

        return (new ShiftResource($shift))
            ->additional(['meta' => [
                'warnings' => array_map(fn (Warning $w) => $w->toArray(), $this->rota->warningsForShift($shift)),
            ]])
            ->response()
            ->setStatusCode($status);
    }

    private function fingerprint(int $userId, string $date, string $start, string $end): string
    {
        return "{$userId}|{$date}|{$start}|{$end}";
    }
}
