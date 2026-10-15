<?php

namespace Tests\Feature;

use App\Models\Shift;
use App\Models\User;
use App\Services\RotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShiftTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->manager()->create();
        $this->employee = User::factory()->create(['team_id' => $this->manager->team_id, 'contract_hours' => 12]);
    }

    private function monday(): string
    {
        return RotaService::weekStart()->format('Y-m-d');
    }

    private function day(int $offset): string
    {
        return RotaService::weekStart()->addDays($offset)->format('Y-m-d');
    }

    public function test_the_week_rota_lists_shifts_with_hours(): void
    {
        Shift::factory()->for($this->employee)->create(['date' => $this->day(1), 'start_time' => '08:00', 'end_time' => '14:30']);
        Shift::factory()->for($this->employee)->create(['date' => $this->day(8)]); // next week
        Sanctum::actingAs($this->manager);

        $this->getJson('/api/v1/shifts?week='.$this->day(3))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.week_start', $this->monday())
            ->assertJsonPath('meta.hours.'.$this->employee->id, 6.5)
            ->assertJsonPath('data.0.user_name', $this->employee->name);
    }

    public function test_the_rota_includes_rule_warnings_for_managers(): void
    {
        Shift::factory()->for($this->employee)->create(['date' => $this->day(4), 'start_time' => '15:00', 'end_time' => '22:00']);
        Shift::factory()->for($this->employee)->create(['date' => $this->day(5), 'start_time' => '06:00', 'end_time' => '12:00']);
        Sanctum::actingAs($this->manager);

        $this->getJson('/api/v1/shifts?week='.$this->monday())
            ->assertJsonPath('meta.warnings.0.type', 'short_rest')
            ->assertJsonPath('meta.warnings.1.type', 'over_contract');
    }

    public function test_mine_filter_only_returns_own_shifts(): void
    {
        $colleague = User::factory()->create(['team_id' => $this->manager->team_id]);
        Shift::factory()->for($this->employee)->create(['date' => $this->day(1)]);
        Shift::factory()->for($colleague)->create(['date' => $this->day(1)]);
        Sanctum::actingAs($this->employee);

        $this->getJson('/api/v1/shifts?mine=1')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/shifts')->assertJsonCount(2, 'data');
    }

    public function test_shifts_of_other_teams_are_never_visible(): void
    {
        Shift::factory()->create(['date' => $this->day(1)]);
        Sanctum::actingAs($this->manager);

        $this->getJson('/api/v1/shifts')->assertJsonCount(0, 'data');
    }

    public function test_a_manager_can_create_a_shift_and_gets_warnings_back(): void
    {
        Shift::factory()->for($this->employee)->create(['date' => $this->day(2), 'start_time' => '08:00', 'end_time' => '14:00']);
        Sanctum::actingAs($this->manager);

        $this->postJson('/api/v1/shifts', [
            'user_id' => $this->employee->id,
            'date' => $this->day(2),
            'start_time' => '12:00',
            'end_time' => '20:00',
            'department' => 'fresh',
        ])
            ->assertCreated()
            ->assertJsonPath('data.hours', 8)
            ->assertJsonPath('meta.warnings.0.type', 'overlap')
            ->assertJsonPath('meta.warnings.1.type', 'over_contract');

        $this->assertDatabaseHas('activities', ['type' => 'shift_created']);
    }

    public function test_shift_validation(): void
    {
        Sanctum::actingAs($this->manager);

        $this->postJson('/api/v1/shifts', [])->assertJsonValidationErrors(['user_id', 'date', 'start_time', 'end_time', 'department']);

        $this->postJson('/api/v1/shifts', [
            'user_id' => $this->employee->id, 'date' => $this->day(1),
            'start_time' => '14:00', 'end_time' => '09:00', 'department' => 'checkout',
        ])->assertJsonValidationErrors('end_time');

        $this->postJson('/api/v1/shifts', [
            'user_id' => $this->employee->id, 'date' => $this->day(1),
            'start_time' => '09:00', 'end_time' => '14:00', 'department' => 'pharmacy',
        ])->assertJsonValidationErrors('department');
    }

    public function test_a_manager_cannot_plan_someone_from_another_team(): void
    {
        Sanctum::actingAs($this->manager);
        $stranger = User::factory()->create();

        $this->postJson('/api/v1/shifts', [
            'user_id' => $stranger->id, 'date' => $this->day(1),
            'start_time' => '09:00', 'end_time' => '14:00', 'department' => 'checkout',
        ])->assertJsonValidationErrors('user_id');
    }

    public function test_employees_cannot_create_update_or_delete_shifts(): void
    {
        $shift = Shift::factory()->for($this->employee)->create();
        Sanctum::actingAs($this->employee);

        $this->postJson('/api/v1/shifts', [
            'user_id' => $this->employee->id, 'date' => $this->day(1),
            'start_time' => '09:00', 'end_time' => '14:00', 'department' => 'checkout',
        ])->assertForbidden();
        $this->patchJson("/api/v1/shifts/{$shift->id}", ['start_time' => '10:00'])->assertForbidden();
        $this->deleteJson("/api/v1/shifts/{$shift->id}")->assertForbidden();
    }

    public function test_a_shift_can_be_moved_by_sending_only_the_new_date(): void
    {
        $shift = Shift::factory()->for($this->employee)->create(['date' => $this->day(1)]);
        Sanctum::actingAs($this->manager);

        $this->patchJson("/api/v1/shifts/{$shift->id}", ['date' => $this->day(3)])
            ->assertOk()
            ->assertJsonPath('data.date', $this->day(3))
            ->assertJsonPath('data.start_time', '09:00');
    }

    public function test_update_checks_end_after_start_against_stored_values(): void
    {
        $shift = Shift::factory()->for($this->employee)->create(['start_time' => '09:00', 'end_time' => '15:00']);
        Sanctum::actingAs($this->manager);

        $this->patchJson("/api/v1/shifts/{$shift->id}", ['end_time' => '08:00'])->assertJsonValidationErrors('end_time');
    }

    public function test_a_manager_of_another_team_cannot_touch_the_shift(): void
    {
        $shift = Shift::factory()->for($this->employee)->create();
        Sanctum::actingAs(User::factory()->manager()->create());

        $this->patchJson("/api/v1/shifts/{$shift->id}", ['start_time' => '10:00'])->assertForbidden();
        $this->deleteJson("/api/v1/shifts/{$shift->id}")->assertForbidden();
    }

    public function test_a_manager_can_delete_a_shift(): void
    {
        $shift = Shift::factory()->for($this->employee)->create();
        Sanctum::actingAs($this->manager);

        $this->deleteJson("/api/v1/shifts/{$shift->id}")->assertNoContent();
        $this->assertModelMissing($shift);
    }

    public function test_copy_last_week_copies_shifts_once(): void
    {
        $lastWeek = RotaService::weekStart()->subWeek();
        Shift::factory()->for($this->employee)->create(['date' => $lastWeek->format('Y-m-d')]);
        Shift::factory()->for($this->employee)->create(['date' => $lastWeek->addDays(3)->format('Y-m-d'), 'department' => 'bakery']);
        Sanctum::actingAs($this->manager);

        $this->postJson('/api/v1/shifts/copy-week', ['week' => $this->day(2)])
            ->assertCreated()
            ->assertJsonPath('created', 2)
            ->assertJsonPath('week_start', $this->monday());

        $this->assertDatabaseHas('shifts', ['date' => $this->day(3), 'department' => 'bakery']);

        $this->postJson('/api/v1/shifts/copy-week', ['week' => $this->monday()])->assertJsonPath('created', 0);
    }

    public function test_employees_cannot_copy_weeks(): void
    {
        Sanctum::actingAs($this->employee);

        $this->postJson('/api/v1/shifts/copy-week', ['week' => $this->monday()])->assertForbidden();
    }
}
