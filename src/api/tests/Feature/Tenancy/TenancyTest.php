<?php

namespace Tests\Feature\Tenancy;

use App\Enums\UserRole;
use App\Models\AppSetting;
use App\Models\JobRun;
use App\Models\Tenant;
use App\Models\User;
use App\Support\JobRunRecorder;
use App\Tenancy\Tenancy;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\InteractsWithQueue;
use Tests\TestCase;

/**
 * Tenancy is optional; these run with it on. The rest of the suite runs with
 * it off, which proves "off" changes nothing.
 */
class TenancyTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $acme;

    private Tenant $globex;

    private User $acmeAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->enableTenancy();

        $this->acme = Tenant::factory()->create(['slug' => 'acme', 'name' => 'Acme']);
        $this->globex = Tenant::factory()->create(['slug' => 'globex', 'name' => 'Globex', 'domain' => 'globex.test']);

        $this->acmeAdmin = $this->userIn($this->acme, ['email' => 'admin@acme.test', 'password' => 'secret-pass']);
        $this->acmeAdmin->assignRole(UserRole::Admin->value);
    }

    private function userIn(Tenant $tenant, array $attributes = []): User
    {
        return app(Tenancy::class)->run($tenant, fn () => User::factory()->create($attributes));
    }

    public function test_the_host_picks_the_context(): void
    {
        $this->getJson($this->onTenant(null, '/api/v1/tenancy'))
            ->assertJsonPath('data.central', true)->assertJsonPath('data.tenant', null);

        $this->getJson($this->onTenant('acme', '/api/v1/tenancy'))
            ->assertJsonPath('data.central', false)->assertJsonPath('data.tenant.slug', 'acme');

        $this->getJson('http://globex.test/api/v1/tenancy')->assertJsonPath('data.tenant.slug', 'globex');

        $this->getJson($this->onTenant('nope', '/api/v1/tenancy'))
            ->assertNotFound()->assertJsonPath('code', 'tenant_not_found');
    }

    public function test_a_suspended_organization_is_closed(): void
    {
        $this->acme->update(['status' => Tenant::STATUS_SUSPENDED]);

        $this->getJson($this->onTenant('acme', '/api/v1/tenancy'))
            ->assertForbidden()->assertJsonPath('code', 'tenant_suspended');
    }

    public function test_users_only_see_their_organization(): void
    {
        $this->userIn($this->acme, ['name' => 'Ana']);
        $this->userIn($this->globex, ['name' => 'Gus']);

        $names = $this->actingAs($this->acmeAdmin, 'web')
            ->getJson($this->onTenant('acme', '/api/v1/users'))
            ->assertOk()->json('data.*.name');

        $this->assertContains('Ana', $names);
        $this->assertNotContains('Gus', $names);
    }

    public function test_records_created_inside_a_tenant_belong_to_it(): void
    {
        $id = $this->actingAs($this->acmeAdmin, 'web')->postJson($this->onTenant('acme', '/api/v1/users'), [
            'name' => 'Nuevo', 'email' => 'nuevo@acme.test',
            'password' => 'secret-pass', 'password_confirmation' => 'secret-pass',
        ])->assertCreated()->json('data.id');

        $this->assertSame($this->acme->id, User::withoutTenancy()->find($id)->tenant_id);
    }

    public function test_login_and_sessions_dont_cross_organizations(): void
    {
        // Wrong host: the user doesn't exist there.
        $this->postJson($this->onTenant('globex', '/api/v1/auth/login'), ['email' => 'admin@acme.test', 'password' => 'secret-pass'])
            ->assertJsonValidationErrors('email');

        $this->postJson($this->onTenant('acme', '/api/v1/auth/login'), ['email' => 'admin@acme.test', 'password' => 'secret-pass'])
            ->assertOk();

        // Same session cookie (shared across subdomains) on another tenant: nobody.
        // forgetGuards(): a real request starts with no resolved user (here the
        // app instance, and its guard cache, survive between requests).
        $this->app['auth']->forgetGuards();
        $this->getJson($this->onTenant('globex', '/api/v1/auth/me'))->assertUnauthorized();
        $this->getJson($this->onTenant('acme', '/api/v1/auth/me'))->assertOk();
    }

    public function test_super_admins_run_the_platform_from_the_central_domain(): void
    {
        $super = User::factory()->create(['email' => 'root@example.com', 'password' => 'secret-pass']);
        $super->forceFill(['is_super_admin' => true])->save();

        $this->postJson($this->onTenant(null, '/api/v1/auth/login'), ['email' => 'root@example.com', 'password' => 'secret-pass'])
            ->assertOk()->assertJsonPath('data.is_super_admin', true);

        $this->getJson($this->onTenant(null, '/api/v1/tenants'))->assertOk()->assertJsonPath('meta.total', 2);

        $this->postJson($this->onTenant(null, '/api/v1/tenants'), [
            'name' => 'Initech', 'slug' => 'initech',
            'admin' => ['name' => 'Bill', 'email' => 'bill@initech.test', 'password' => 'secret-pass', 'password_confirmation' => 'secret-pass'],
        ])->assertCreated()->assertJsonPath('data.users_count', 1);

        $initech = Tenant::where('slug', 'initech')->firstOrFail();
        $bill = User::withoutTenancy()->where('email', 'bill@initech.test')->firstOrFail();
        $this->assertSame($initech->id, $bill->tenant_id);
        $this->assertTrue($bill->hasRole(UserRole::Admin->value));

        // A super admin isn't a user inside any tenant.
        $this->app['auth']->forgetGuards();
        $this->getJson($this->onTenant('acme', '/api/v1/auth/me'))->assertUnauthorized();
    }

    public function test_tenant_admins_cant_manage_organizations(): void
    {
        $this->actingAs($this->acmeAdmin, 'web')
            ->getJson($this->onTenant('acme', '/api/v1/tenants'))
            ->assertForbidden();
    }

    public function test_reserved_slugs_and_duplicates_are_rejected(): void
    {
        $super = User::factory()->create();
        $super->forceFill(['is_super_admin' => true])->save();

        $this->actingAs($super, 'web')->postJson($this->onTenant(null, '/api/v1/tenants'), [
            'name' => 'X', 'slug' => 'www', 'admin' => ['name' => 'A', 'email' => 'admin@acme.test'],
        ])->assertJsonValidationErrors(['slug', 'admin.email', 'admin.password']);
    }

    public function test_queued_jobs_run_in_the_tenant_that_dispatched_them(): void
    {
        // A block, not `fn () => Job::dispatch()`: dispatch() queues the job
        // when its PendingDispatch is destroyed, and returning it from run()
        // would queue it after run() restored the central context.
        app(Tenancy::class)->run($this->acme, function () {
            TenantProbeJob::dispatch();
        });

        $run = JobRun::withoutTenancy()->where('name', 'TenantProbeJob')->firstOrFail();
        $this->assertSame($this->acme->id, $run->tenant_id);
        $this->assertSame('acme', $run->summary);
        $this->assertNull(app(Tenancy::class)->current(), 'tenant leaked out of the job');
    }

    public function test_settings_are_per_organization(): void
    {
        $tenancy = app(Tenancy::class);
        $tenancy->run($this->acme, fn () => AppSetting::set('theme', 'red'));
        $tenancy->run($this->globex, fn () => AppSetting::set('theme', 'blue'));

        $this->assertSame('red', $tenancy->run($this->acme, fn () => AppSetting::get('theme')));
        $this->assertSame('blue', $tenancy->run($this->globex, fn () => AppSetting::get('theme')));
        $this->assertNull(AppSetting::get('theme'));
    }

    public function test_tenants_create_command(): void
    {
        $this->artisan('tenants:create', ['slug' => 'umbrella', 'name' => 'Umbrella', '--admin-email' => 'alice@umbrella.test'])
            ->expectsQuestion('Admin password', 'secret-pass-123')
            ->assertSuccessful();

        $tenant = Tenant::where('slug', 'umbrella')->firstOrFail();
        $this->assertSame(1, $tenant->users()->count());
    }
}

class TenantProbeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle(Tenancy $tenancy): void
    {
        JobRunRecorder::note((string) $tenancy->current()?->slug);
    }
}
