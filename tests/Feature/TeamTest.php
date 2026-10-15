<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Shift;
use App\Models\User;
use App\Services\RotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_manager_sees_the_team_with_planned_hours(): void
    {
        $manager = User::factory()->manager()->create();
        $employee = User::factory()->create(['team_id' => $manager->team_id, 'name' => 'Daan Jansen']);
        User::factory()->create(); // other team
        Shift::factory()->for($employee)->create(['date' => RotaService::weekStart()->format('Y-m-d')]);
        Sanctum::actingAs($manager);

        $this->getJson('/api/v1/employees')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Daan Jansen')
            ->assertJsonPath('data.0.planned_hours', 6);
    }

    public function test_employees_cannot_list_the_team(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/employees')->assertForbidden();
    }

    public function test_activity_feed_is_scoped_to_the_team(): void
    {
        $user = User::factory()->create();
        Activity::record($user, 'swap_offered', ['date' => '2026-10-20']);
        Activity::record(User::factory()->create(), 'swap_offered');
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/activity')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.actor', $user->name)
            ->assertJsonPath('data.0.data.date', '2026-10-20');
    }

    public function test_the_health_check_and_spa_fallback_respond(): void
    {
        $this->get('/up')->assertOk();
        $this->get('/rota')->assertStatus(file_exists(public_path('index.html')) ? 200 : 404);
    }
}
