<?php

namespace Tests\Feature;

use App\Models\Shift;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_starting_a_manager_demo_creates_a_full_team(): void
    {
        $response = $this->postJson('/api/v1/demo', ['role' => 'manager'])
            ->assertCreated()
            ->assertJsonPath('user.role', 'manager')
            ->assertJsonPath('user.team.is_demo', true);

        $team = Team::findOrFail($response->json('user.team.id'));
        $this->assertSame(9, $team->members()->count());
        $this->assertGreaterThan(40, Shift::where('team_id', $team->id)->count());

        $this->withToken($response->json('token'))->getJson('/api/v1/dashboard')->assertOk();
    }

    public function test_starting_an_employee_demo_logs_in_as_an_employee(): void
    {
        $this->postJson('/api/v1/demo', ['role' => 'employee'])
            ->assertCreated()
            ->assertJsonPath('user.role', 'employee');
    }

    public function test_every_demo_gets_its_own_team(): void
    {
        $a = $this->postJson('/api/v1/demo', ['role' => 'manager'])->json('user.team.id');
        $b = $this->postJson('/api/v1/demo', ['role' => 'manager'])->json('user.team.id');

        $this->assertNotSame($a, $b);
    }

    public function test_role_is_validated(): void
    {
        $this->postJson('/api/v1/demo', ['role' => 'admin'])->assertJsonValidationErrors('role');
    }

    public function test_old_demo_teams_are_pruned(): void
    {
        $this->postJson('/api/v1/demo', ['role' => 'manager']);
        Team::query()->update(['created_at' => now()->subDays(2)]);
        $this->postJson('/api/v1/demo', ['role' => 'manager']);

        $this->artisan('demo:prune')->assertSuccessful();

        $this->assertSame(1, Team::count());
    }

    public function test_the_seeder_creates_the_documented_logins(): void
    {
        $this->seed();

        $this->postJson('/api/v1/auth/login', ['email' => 'manager@shiftly.test', 'password' => 'password'])
            ->assertOk()->assertJsonPath('user.role', 'manager');
        $this->postJson('/api/v1/auth/login', ['email' => 'employee@shiftly.test', 'password' => 'password'])
            ->assertOk()->assertJsonPath('user.role', 'employee');

        // Running it again (every deploy does) must not fail or duplicate.
        $this->seed();
        $this->assertSame(1, Team::count());
    }
}
