<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_log_in_and_gets_a_token(): void
    {
        $user = User::factory()->create(['email' => 'sanne@example.com']);

        $this->postJson('/api/v1/auth/login', ['email' => 'sanne@example.com', 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'role', 'team']]);
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->create(['email' => 'sanne@example.com']);

        $this->postJson('/api/v1/auth/login', ['email' => 'sanne@example.com', 'password' => 'nope'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_login_validates_input(): void
    {
        $this->postJson('/api/v1/auth/login', [])->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_me_returns_the_current_user(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $this->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('data.email', $user->email);
    }

    public function test_guests_cannot_reach_protected_endpoints(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();
    }

    public function test_logout_revokes_the_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('web')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();
        $this->assertCount(0, $user->tokens()->get());
    }

    public function test_forgot_password_gives_the_same_answer_for_unknown_emails(): void
    {
        User::factory()->create(['email' => 'known@example.com']);

        $known = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'known@example.com'])->assertOk()->json();
        $unknown = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nobody@example.com'])->assertOk()->json();

        $this->assertSame($known, $unknown);
    }
}
