<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private function week(array $overrides = []): array
    {
        return collect(range(1, 7))->map(fn ($d) => array_merge(
            ['weekday' => $d, 'available' => true, 'from_time' => null, 'until_time' => null],
            $overrides[$d] ?? [],
        ))->all();
    }

    public function test_new_users_get_seven_available_days(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/availability')
            ->assertOk()
            ->assertJsonCount(7, 'data')
            ->assertJsonPath('data.0.weekday', 1)
            ->assertJsonPath('data.6.available', true);
    }

    public function test_availability_can_be_saved(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/availability', ['days' => $this->week([
            3 => ['from_time' => '15:00', 'until_time' => '22:00'],
            7 => ['available' => false, 'from_time' => '10:00'],
        ])])
            ->assertOk()
            ->assertJsonPath('data.2.from_time', '15:00')
            ->assertJsonPath('data.6.available', false)
            ->assertJsonPath('data.6.from_time', null);

        $this->assertDatabaseCount('availabilities', 7);
    }

    public function test_availability_is_validated(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/v1/availability', ['days' => []])->assertJsonValidationErrors('days');
        $this->putJson('/api/v1/availability', ['days' => $this->week([
            2 => ['from_time' => '18:00', 'until_time' => '09:00'],
        ])])->assertJsonValidationErrors('days.1.until_time');
    }

    public function test_saving_only_changes_your_own_availability(): void
    {
        $other = User::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/v1/availability', ['days' => $this->week()])->assertOk();

        $this->assertDatabaseMissing('availabilities', ['user_id' => $other->id]);
    }
}
