<?php

namespace Tests\Feature;

use App\Models\Shift;
use App\Models\SwapRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SwapTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $sanne;

    private User $milan;

    private Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->manager()->create();
        $this->sanne = User::factory()->create(['team_id' => $this->manager->team_id]);
        $this->milan = User::factory()->create(['team_id' => $this->manager->team_id]);
        $this->shift = Shift::factory()->for($this->sanne)->create(['date' => now()->addDays(3)->format('Y-m-d')]);
    }

    private function offer(string $status = SwapRequest::OPEN, ?User $taker = null): SwapRequest
    {
        return SwapRequest::create([
            'shift_id' => $this->shift->id,
            'requester_id' => $this->sanne->id,
            'taker_id' => $taker?->id,
            'status' => $status,
        ]);
    }

    public function test_an_employee_can_offer_their_own_shift(): void
    {
        Sanctum::actingAs($this->sanne);

        $this->postJson('/api/v1/swaps', ['shift_id' => $this->shift->id, 'note' => 'Dentist'])
            ->assertCreated()
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.shift.id', $this->shift->id);
    }

    public function test_an_employee_cannot_offer_a_colleagues_shift(): void
    {
        Sanctum::actingAs($this->milan);

        $this->postJson('/api/v1/swaps', ['shift_id' => $this->shift->id])->assertForbidden();
    }

    public function test_a_shift_cannot_be_offered_twice_or_in_the_past(): void
    {
        Sanctum::actingAs($this->sanne);
        $this->offer();

        $this->postJson('/api/v1/swaps', ['shift_id' => $this->shift->id])->assertJsonValidationErrors('shift_id');

        $old = Shift::factory()->for($this->sanne)->create(['date' => now()->subDays(3)->format('Y-m-d')]);
        $this->postJson('/api/v1/swaps', ['shift_id' => $old->id])->assertJsonValidationErrors('shift_id');
    }

    public function test_a_colleague_can_pick_up_an_open_shift(): void
    {
        $swap = $this->offer();
        Sanctum::actingAs($this->milan);

        $this->postJson("/api/v1/swaps/{$swap->id}/pick-up")
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.taker.id', $this->milan->id);

        // Picking up does not move the shift yet; the manager decides.
        $this->assertSame($this->sanne->id, $this->shift->refresh()->user_id);
    }

    public function test_you_cannot_pick_up_your_own_or_another_teams_shift(): void
    {
        $swap = $this->offer();

        Sanctum::actingAs($this->sanne);
        $this->postJson("/api/v1/swaps/{$swap->id}/pick-up")->assertForbidden();

        Sanctum::actingAs(User::factory()->create());
        $this->postJson("/api/v1/swaps/{$swap->id}/pick-up")->assertForbidden();
    }

    public function test_the_manager_approves_and_the_shift_changes_owner(): void
    {
        $swap = $this->offer(SwapRequest::PENDING, $this->milan);
        Sanctum::actingAs($this->manager);

        $this->postJson("/api/v1/swaps/{$swap->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonStructure(['meta' => ['warnings']]);

        $this->assertSame($this->milan->id, $this->shift->refresh()->user_id);
    }

    public function test_the_manager_can_decline(): void
    {
        $swap = $this->offer(SwapRequest::PENDING, $this->milan);
        Sanctum::actingAs($this->manager);

        $this->postJson("/api/v1/swaps/{$swap->id}/decline")->assertOk()->assertJsonPath('data.status', 'declined');
        $this->assertSame($this->sanne->id, $this->shift->refresh()->user_id);
    }

    public function test_employees_cannot_approve_and_open_swaps_cannot_be_approved(): void
    {
        $pending = $this->offer(SwapRequest::PENDING, $this->milan);
        Sanctum::actingAs($this->milan);
        $this->postJson("/api/v1/swaps/{$pending->id}/approve")->assertForbidden();

        $pending->update(['status' => SwapRequest::OPEN, 'taker_id' => null]);
        Sanctum::actingAs($this->manager);
        $this->postJson("/api/v1/swaps/{$pending->id}/approve")->assertForbidden();
    }

    public function test_the_requester_can_cancel(): void
    {
        $swap = $this->offer();

        Sanctum::actingAs($this->milan);
        $this->deleteJson("/api/v1/swaps/{$swap->id}")->assertForbidden();

        Sanctum::actingAs($this->sanne);
        $this->deleteJson("/api/v1/swaps/{$swap->id}")->assertNoContent();
        $this->assertSame('cancelled', $swap->refresh()->status);
    }

    public function test_employees_see_their_own_and_open_swaps_managers_see_all(): void
    {
        $emma = User::factory()->create(['team_id' => $this->manager->team_id]);
        $this->offer(); // open, visible to everyone
        $emmaShift = Shift::factory()->for($emma)->create();
        SwapRequest::create(['shift_id' => $emmaShift->id, 'requester_id' => $emma->id,
            'taker_id' => $this->sanne->id, 'status' => SwapRequest::DECLINED]);

        Sanctum::actingAs($this->milan);
        $this->getJson('/api/v1/swaps')->assertJsonCount(1, 'data');

        Sanctum::actingAs($this->manager);
        $this->getJson('/api/v1/swaps')->assertJsonCount(2, 'data');
    }
}
