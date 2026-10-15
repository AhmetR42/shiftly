<?php

namespace Tests\Feature;

use App\Models\TimeOffRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TimeOffTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->manager()->create();
        $this->employee = User::factory()->create(['team_id' => $this->manager->team_id]);
    }

    public function test_an_employee_can_request_time_off(): void
    {
        Sanctum::actingAs($this->employee);

        $this->postJson('/api/v1/time-off', [
            'start_date' => now()->addDays(10)->format('Y-m-d'),
            'end_date' => now()->addDays(12)->format('Y-m-d'),
            'reason' => 'Holiday',
        ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.days', 3);
    }

    public function test_dates_are_validated(): void
    {
        Sanctum::actingAs($this->employee);

        $this->postJson('/api/v1/time-off', [
            'start_date' => now()->subDay()->format('Y-m-d'),
            'end_date' => now()->subDays(3)->format('Y-m-d'),
        ])->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    public function test_employees_only_see_their_own_requests(): void
    {
        TimeOffRequest::factory()->for($this->employee)->create();
        TimeOffRequest::factory()->for(User::factory()->create(['team_id' => $this->manager->team_id]))->create();
        TimeOffRequest::factory()->create(); // other team

        Sanctum::actingAs($this->employee);
        $this->getJson('/api/v1/time-off')->assertJsonCount(1, 'data');

        Sanctum::actingAs($this->manager);
        $this->getJson('/api/v1/time-off')->assertJsonCount(2, 'data');
    }

    public function test_a_manager_can_approve_and_decline(): void
    {
        $a = TimeOffRequest::factory()->for($this->employee)->create();
        $b = TimeOffRequest::factory()->for($this->employee)->create();
        Sanctum::actingAs($this->manager);

        $this->postJson("/api/v1/time-off/{$a->id}/approve")->assertOk()->assertJsonPath('data.status', 'approved');
        $this->postJson("/api/v1/time-off/{$b->id}/decline")->assertOk()->assertJsonPath('data.status', 'declined');

        // Already reviewed requests can't be reviewed again.
        $this->postJson("/api/v1/time-off/{$a->id}/decline")->assertForbidden();
    }

    public function test_employees_and_other_managers_cannot_review(): void
    {
        $request = TimeOffRequest::factory()->for($this->employee)->create();

        Sanctum::actingAs($this->employee);
        $this->postJson("/api/v1/time-off/{$request->id}/approve")->assertForbidden();

        Sanctum::actingAs(User::factory()->manager()->create());
        $this->postJson("/api/v1/time-off/{$request->id}/approve")->assertForbidden();
    }

    public function test_an_employee_can_withdraw_only_their_own_pending_request(): void
    {
        $mine = TimeOffRequest::factory()->for($this->employee)->create();
        $approved = TimeOffRequest::factory()->for($this->employee)->create(['status' => 'approved']);
        $other = TimeOffRequest::factory()->for(User::factory()->create(['team_id' => $this->manager->team_id]))->create();
        Sanctum::actingAs($this->employee);

        $this->deleteJson("/api/v1/time-off/{$other->id}")->assertForbidden();
        $this->deleteJson("/api/v1/time-off/{$approved->id}")->assertForbidden();
        $this->deleteJson("/api/v1/time-off/{$mine->id}")->assertNoContent();
    }
}
