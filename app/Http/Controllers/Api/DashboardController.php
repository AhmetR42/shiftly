<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\ShiftResource;
use App\Models\Shift;
use App\Models\SwapRequest;
use App\Models\TimeOffRequest;
use App\Services\RotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, RotaService $rota): JsonResponse
    {
        $user = $request->user();
        $monday = RotaService::weekStart($request->query('week'));
        $teamShifts = $rota->shiftsForWeek($user->team_id, $monday);
        $myShifts = $teamShifts->where('user_id', $user->id);

        $coverage = collect(range(0, 6))->map(function (int $offset) use ($monday, $teamShifts) {
            $date = $monday->addDays($offset)->format('Y-m-d');
            $day = $teamShifts->filter(fn (Shift $s) => $s->date->format('Y-m-d') === $date);

            return ['date' => $date, 'shifts' => $day->count(), 'hours' => round($day->sum(fn (Shift $s) => $s->hours()), 1)];
        });

        $nextShift = Shift::where('user_id', $user->id)
            ->where(fn ($q) => $q->where('date', '>', now()->format('Y-m-d'))
                ->orWhere(fn ($q) => $q->where('date', now()->format('Y-m-d'))->where('end_time', '>', now()->format('H:i'))))
            ->orderBy('date')->orderBy('start_time')
            ->first();

        $swaps = SwapRequest::whereHas('requester', fn ($q) => $q->where('team_id', $user->team_id));
        $today = now()->format('Y-m-d');

        return response()->json([
            'week_start' => $monday->format('Y-m-d'),
            'my_hours' => $rota->hoursPerUser($myShifts)[$user->id] ?? 0,
            'contract_hours' => $user->contract_hours,
            'next_shift' => $nextShift ? new ShiftResource($nextShift) : null,
            'open_swaps' => (clone $swaps)->where('status', SwapRequest::OPEN)->where('requester_id', '!=', $user->id)->count(),
            'pending_swaps' => (clone $swaps)->where('status', SwapRequest::PENDING)->count(),
            'pending_time_off' => TimeOffRequest::where('status', 'pending')
                ->whereHas('user', fn ($q) => $q->where('team_id', $user->team_id))->count(),
            'warnings' => count($rota->warningsFor($teamShifts, $user->team_id)),
            'team_hours' => round($teamShifts->sum(fn (Shift $s) => $s->hours()), 1),
            'coverage' => $coverage,
            'on_shift_today' => ShiftResource::collection(
                $teamShifts->filter(fn (Shift $s) => $s->date->format('Y-m-d') === $today)->values()
            ),
            'activity' => ActivityResource::collection(
                $user->team->activities()->with('user:id,name')->latest()->latest('id')->limit(6)->get()
            ),
        ]);
    }
}
