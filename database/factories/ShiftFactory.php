<?php

namespace Database\Factories;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'team_id' => fn (array $attributes) => User::find($attributes['user_id'])->team_id,
            'date' => now()->addDays(2)->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '15:00',
            'department' => 'checkout',
        ];
    }
}
