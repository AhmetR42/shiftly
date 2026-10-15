<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TimeOffRequestForm;
use App\Http\Resources\TimeOffResource;
use App\Models\Activity;
use App\Models\TimeOffRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TimeOffController extends Controller
{
    /**
     * Managers see the whole team, employees only their own requests.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $requests = TimeOffRequest::query()
            ->with('user:id,name')
            ->when(
                $user->isManager(),
                fn ($q) => $q->whereHas('user', fn ($q) => $q->where('team_id', $user->team_id)),
                fn ($q) => $q->where('user_id', $user->id),
            )
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->orderBy('start_date')
            ->get();

        return TimeOffResource::collection($requests);
    }

    public function store(TimeOffRequestForm $request): JsonResponse
    {
        $timeOff = $request->user()->timeOffRequests()->create($request->validated());
        $timeOff->load('user:id,name');

        Activity::record($request->user(), 'time_off_requested', ['days' => (int) $timeOff->start_date->diffInDays($timeOff->end_date) + 1]);

        return (new TimeOffResource($timeOff))->response()->setStatusCode(201);
    }

    public function approve(Request $request, TimeOffRequest $timeOff): TimeOffResource
    {
        return $this->review($request, $timeOff, 'approved');
    }

    public function decline(Request $request, TimeOffRequest $timeOff): TimeOffResource
    {
        return $this->review($request, $timeOff, 'declined');
    }

    public function destroy(Request $request, TimeOffRequest $timeOff): JsonResponse
    {
        $this->authorize('delete', $timeOff);
        $timeOff->delete();

        return response()->json(status: 204);
    }

    private function review(Request $request, TimeOffRequest $timeOff, string $status): TimeOffResource
    {
        $this->authorize('review', $timeOff);

        $timeOff->update(['status' => $status, 'reviewed_by' => $request->user()->id]);
        Activity::record($request->user(), "time_off_{$status}", ['name' => $timeOff->user->name]);

        return new TimeOffResource($timeOff->load('user:id,name'));
    }
}
