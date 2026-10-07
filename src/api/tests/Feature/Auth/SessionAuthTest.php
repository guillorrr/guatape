<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_login_starts_a_session_and_returns_the_user_with_permissions(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);
        $user->assignRole(UserRole::Admin->value);

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'secret-pass'])
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.roles', ['admin'])
            ->assertJsonFragment(['users.manage']);

        $this->assertAuthenticatedAs($user, 'web');
        $this->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('data.id', $user->id);
    }

    public function test_wrong_password_is_a_validation_error(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'nope'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest('web');
    }

    public function test_login_is_throttled_after_five_failures(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);

        foreach (range(1, 5) as $_) {
            $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'nope']);
        }

        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'secret-pass'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_logout_ends_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')->postJson('/api/v1/auth/logout')->assertOk();

        $this->assertGuest('web');
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_api_answers_json_401_even_without_accept_header(): void
    {
        $this->get('/api/v1/auth/me')->assertUnauthorized()->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_registration_is_closed_by_default(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Ana', 'email' => 'ana@example.com',
            'password' => 'secret-pass', 'password_confirmation' => 'secret-pass',
        ])->assertNotFound();
    }

    public function test_registration_when_enabled_logs_the_user_in(): void
    {
        config(['app.registration_enabled' => true]);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Ana', 'email' => 'ana@example.com',
            'password' => 'secret-pass', 'password_confirmation' => 'secret-pass',
        ])->assertCreated()->assertJsonPath('data.email', 'ana@example.com');

        $this->assertAuthenticated('web');
    }

    public function test_change_password_requires_the_current_one(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass']);

        $this->actingAs($user, 'web')->putJson('/api/v1/auth/password', [
            'current_password' => 'wrong',
            'password' => 'new-secret-pass', 'password_confirmation' => 'new-secret-pass',
        ])->assertJsonValidationErrors('current_password');

        $this->actingAs($user, 'web')->putJson('/api/v1/auth/password', [
            'current_password' => 'secret-pass',
            'password' => 'new-secret-pass', 'password_confirmation' => 'new-secret-pass',
        ])->assertOk();

        $this->assertTrue(password_verify('new-secret-pass', $user->fresh()->password));
    }

    public function test_update_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')->patchJson('/api/v1/auth/profile', ['name' => 'Nuevo'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Nuevo');
    }
}
