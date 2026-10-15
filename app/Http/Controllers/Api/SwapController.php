<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\OfferShiftRequest;
use App\Http\Resources\SwapRequestResource;
use App\Models\Activity;
use App\Models\Shift;
use App\Models\SwapRequest;
use App\Services\RotaService;
use App\Support\Rota\Warning;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Swap flow:
 *   employee offers a shift      -> open
 *   a colleague picks it up      -> pending
 *   the manager approves/declines -> approved (shift changes owner) / declined
 *   the requester can cancel while open or pending.
 */
class SwapController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $swaps = SwapRequest::query()
            ->with(['shift', 'requester:id,name,team_id', 'taker:id,name'])
            ->whereHas('requester', fn ($q) => $q->where('team_id', $user->team_id))
            ->when(! $user->isManager(), fn ($q) => $q->where(fn ($q) => $q
                ->where('requester_id', $user->id)
                ->orWhere('taker_id', $user->id)
                ->orWhere('status', SwapRequest::OPEN)))
            ->orderByRaw("case status when 'pending' then 0 when 'open' then 1 else 2 end")
            ->latest()
            ->get();

        return SwapRequestResource::collection($swaps);
    }

    public function store(OfferShiftRequest $request): JsonResponse
    {
        $shift = Shift::findOrFail($request->validated('shift_id'));
        $this->authorize('offer', $shift);

        if ($shift->date->isPast() && ! $shift->date->isToday()) {
            throw ValidationException::withMessages(['shift_id' => 'You can only offer upcoming shifts.']);
        }
        if ($shift->swapRequests()->whereIn('status', [SwapRequest::OPEN, SwapRequest::PENDING])->exists()) {
            throw ValidationException::withMessages(['shift_id' => 'This shift is already offered.']);
        }

        $swap = $shift->swapRequests()->create([
            'requester_id' => $request->user()->id,
            'note' => $request->validated('note'),
        ]);
        Activity::record($request->user(), 'swap_offered', ['date' => $shift->date->format('Y-m-d')]);

        return $this->show($swap, 201);
    }

    public function pickUp(Request $request, SwapRequest $swap): JsonResponse
    {
        $this->authorize('pickUp', $swap);

        $swap->update(['taker_id' => $request->user()->id, 'status' => SwapRequest::PENDING]);
        Activity::record($request->user(), 'swap_picked_up', ['name' => $swap->requester->name]);

        return $this->show($swap);
    }

    /**
     * Approving moves the shift to the colleague who picked it up. We send
     * back rule warnings for the new owner so the manager sees them right away.
     */
    public function approve(Request $request, SwapRequest $swap, RotaService $rota): JsonResponse
    {
        $this->authorize('review', $swap);

        DB::transaction(function () use ($swap) {
            $swap->update(['status' => SwapRequest::APPROVED]);
            $swap->shift->update(['user_id' => $swap->taker_id]);
        });
        Activity::record($request->user(), 'swap_approved', ['name' => $swap->taker->name]);

        $warnings = $rota->warningsForShift($swap->shift->refresh()->load('user'));

        return $this->show($swap, 200, array_map(fn (Warning $w) => $w->toArray(), $warnings));
    }

    public function decline(Request $request, SwapRequest $swap): JsonResponse
    {
        $this->authorize('review', $swap);

        $swap->update(['status' => SwapRequest::DECLINED]);
        Activity::record($request->user(), 'swap_declined', ['name' => $swap->taker?->name]);

        return $this->show($swap);
    }

    public function destroy(Request $request, SwapRequest $swap): JsonResponse
    {
        $this->authorize('cancel', $swap);

        $swap->update(['status' => SwapRequest::CANCELLED]);

        return response()->json(status: 204);
    }

    private function show(SwapRequest $swap, int $status = 200, array $warnings = []): JsonResponse
    {
        $swap->load(['shift', 'requester:id,name', 'taker:id,name']);

        return (new SwapRequestResource($swap))
            ->additional(['meta' => ['warnings' => $warnings]])
            ->response()
            ->setStatusCode($status);
    }
}
