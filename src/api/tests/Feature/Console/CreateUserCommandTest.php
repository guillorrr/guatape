<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateUserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_an_admin_with_a_hidden_password(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->artisan('users:create', ['email' => 'ops@example.com'])
            ->expectsQuestion('Password', 'long-enough-pass')
            ->assertSuccessful();

        $user = User::where('email', 'ops@example.com')->firstOrFail();
        $this->assertSame('ops', $user->name);
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue(password_verify('long-enough-pass', $user->password));
    }

    public function test_fails_on_unknown_role_or_duplicate_email(): void
    {
        $this->seed(RolePermissionSeeder::class);
        User::factory()->create(['email' => 'taken@example.com']);

        $this->artisan('users:create', ['email' => 'x@example.com', '--role' => 'nope'])->assertFailed();

        $this->artisan('users:create', ['email' => 'taken@example.com'])
            ->expectsQuestion('Password', 'long-enough-pass')
            ->assertFailed();
    }
}
