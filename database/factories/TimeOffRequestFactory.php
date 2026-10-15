<?php

namespace Database\Factories;

use App\Models\TimeOffRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeOffRequest>
 */
class TimeOffRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'start_date' => now()->addWeek()->format('Y-m-d'),
            'end_date' => now()->addWeek()->addDays(2)->format('Y-m-d'),
            'reason' => 'Holiday',
            'status' => 'pending',
        ];
    }
}
