<?php

namespace Tests\Feature\Users;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['name' => 'Admin']);
        $this->admin->assignRole(UserRole::Admin->value);
    }

    public function test_members_cannot_list_or_manage_users(): void
    {
        $member = User::factory()->create();
        $member->assignRole(UserRole::Member->value);

        $this->actingAs($member, 'web')->getJson('/api/v1/users')->assertForbidden();
        $this->actingAs($member, 'web')->postJson('/api/v1/users', [])->assertForbidden();
    }

    public function test_index_paginates_searches_sorts_and_filters(): void
    {
        User::factory()->create(['name' => 'Zoe', 'email' => 'zoe@example.com']);
        User::factory()->create(['name' => 'Bruno', 'email' => 'bruno@example.com'])->assignRole('member');

        $this->actingAs($this->admin, 'web')->getJson('/api/v1/users?per_page=2&sort_by=name&sort_dir=desc')
            ->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('data.0.name', 'Zoe');

        $this->actingAs($this->admin, 'web')->getJson('/api/v1/users?search=brun')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.name', 'Bruno');

        $this->actingAs($this->admin, 'web')->getJson('/api/v1/users?role=member')
            ->assertJsonPath('meta.total', 1);

        // Unknown sort columns fall back to the default instead of reaching SQL.
        $this->actingAs($this->admin, 'web')->getJson('/api/v1/users?sort_by=password')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Admin');
    }

    public function test_per_page_is_clamped(): void
    {
        $this->actingAs($this->admin, 'web')->getJson('/api/v1/users?per_page=5000')
            ->assertJsonPath('meta.per_page', 100);
    }

    public function test_store_update_and_delete(): void
    {
        $id = $this->actingAs($this->admin, 'web')->postJson('/api/v1/users', [
            'name' => 'Ana', 'email' => 'ana@example.com',
            'password' => 'secret-pass', 'password_confirmation' => 'secret-pass',
            'roles' => ['member'],
        ])->assertCreated()->assertJsonPath('data.roles', ['member'])->json('data.id');

        $this->actingAs($this->admin, 'web')->patchJson("/api/v1/users/{$id}", ['name' => 'Ana María'])
            ->assertOk()->assertJsonPath('data.name', 'Ana María');

        $this->actingAs($this->admin, 'web')->deleteJson("/api/v1/users/{$id}")->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $id]);
    }

    public function test_duplicate_email_answers_409_with_the_owner(): void
    {
        // Bypasses validation on purpose: the unique index is the last line of
        // defence (e.g. two concurrent requests) and must not surface as a 500.
        User::factory()->create(['name' => 'Ana', 'email' => 'ana@example.com']);

        $request = Request::create('/api/v1/users', 'POST');
        $response = $this->app->make(ExceptionHandler::class)->render($request, $this->captureDuplicate());

        $this->assertSame(409, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertSame('duplicate_unique_key', $body['code']);
        $this->assertSame('email', $body['details']['column']);
        $this->assertSame('Ana', $body['details']['owner']['name']);
    }

    public function test_cannot_delete_yourself_or_remove_the_last_admin(): void
    {
        $this->actingAs($this->admin, 'web')->deleteJson("/api/v1/users/{$this->admin->id}")
            ->assertJsonValidationErrors('user');

        $this->actingAs($this->admin, 'web')->putJson("/api/v1/users/{$this->admin->id}/roles", ['roles' => ['member']])
            ->assertJsonValidationErrors('roles');
    }

    public function test_roles_endpoint_lists_permissions(): void
    {
        $this->actingAs($this->admin, 'web')->getJson('/api/v1/roles')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'admin');
    }

    private function captureDuplicate(): \Throwable
    {
        try {
            User::factory()->create(['email' => 'ana@example.com']);
        } catch (\Throwable $e) {
            return $e;
        }

        $this->fail('Expected a unique constraint violation.');
    }
}
