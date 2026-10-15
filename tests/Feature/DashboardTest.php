<?php

namespace Tests\Feature;

use App\Models\Shift;
use App\Models\SwapRequest;
use App\Models\User;
use App\Services\RotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_summarises_the_week(): void
    {
        $manager = User::factory()->manager()->create();
        $me = User::factory()->create(['team_id' => $manager->team_id, 'contract_hours' => 20]);
        $colleague = User::factory()->create(['team_id' => $manager->team_id]);
        $monday = RotaService::weekStart();

        Shift::factory()->for($me)->create(['date' => $monday->format('Y-m-d'), 'start_time' => '08:00', 'end_time' => '16:00']);
        $future = Shift::factory()->for($colleague)->create(['date' => now()->addDays(2)->format('Y-m-d')]);
        $next = Shift::factory()->for($me)->create(['date' => now()->addDay()->format('Y-m-d')]);
        SwapRequest::create(['shift_id' => $future->id, 'requester_id' => $colleague->id, 'status' => 'open']);

        Sanctum::actingAs($me);

        $response = $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('contract_hours', 20)
            ->assertJsonPath('next_shift.id', $next->id)
            ->assertJsonPath('open_swaps', 1)
            ->assertJsonCount(7, 'coverage')
            ->assertJsonStructure(['my_hours', 'pending_swaps', 'pending_time_off', 'warnings', 'team_hours', 'on_shift_today', 'activity']);

        $this->assertSame($monday->format('Y-m-d'), $response->json('coverage.0.date'));
        $this->assertGreaterThanOrEqual(1, $response->json('coverage.0.shifts'));
    }
}
