<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvailabilityRequest;
use App\Http\Resources\AvailabilityResource;
use App\Models\Activity;
use App\Models\Availability;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AvailabilityController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return AvailabilityResource::collection($this->weekFor($request->user()->id));
    }

    public function update(AvailabilityRequest $request): AnonymousResourceCollection
    {
        $user = $request->user();

        foreach ($request->validated('days') as $day) {
            Availability::updateOrCreate(
                ['user_id' => $user->id, 'weekday' => $day['weekday']],
                [
                    'available' => $day['available'],
                    'from_time' => $day['available'] ? ($day['from_time'] ?? null) : null,
                    'until_time' => $day['available'] ? ($day['until_time'] ?? null) : null,
                ],
            );
        }

        Activity::record($user, 'availability_updated');

        return AvailabilityResource::collection($this->weekFor($user->id));
    }

    /**
     * Always return seven days, filling gaps with "available all day".
     */
    private function weekFor(int $userId)
    {
        $saved = Availability::where('user_id', $userId)->get()->keyBy('weekday');

        return collect(range(1, 7))->map(fn (int $weekday) => $saved->get($weekday)
            ?? new Availability(['weekday' => $weekday, 'available' => true]));
    }
}
