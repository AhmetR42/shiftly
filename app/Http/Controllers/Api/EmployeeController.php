<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RotaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * The team with their planned hours for the given week.
     */
    public function index(Request $request, RotaService $rota): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isManager(), 403);
        $monday = RotaService::weekStart($request->query('week'));
        $hours = $rota->hoursPerUser($rota->shiftsForWeek($user->team_id, $monday));

        $employees = User::where('team_id', $user->team_id)
            ->where('role', User::ROLE_EMPLOYEE)
            ->with('availabilities')
            ->orderBy('name')
            ->get()
            ->map(fn (User $e) => [
                'id' => $e->id,
                'name' => $e->name,
                'department' => $e->department,
                'contract_hours' => $e->contract_hours,
                'planned_hours' => $hours[$e->id] ?? 0,
                'availability' => $e->availabilities->sortBy('weekday')->map->only(['weekday', 'available', 'from_time', 'until_time'])->values(),
            ]);

        return response()->json(['data' => $employees, 'meta' => ['week_start' => $monday->format('Y-m-d')]]);
    }
}
