<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\Tenancy;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Configuration seeders run everywhere; demo data only outside production.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        if (app()->isProduction()) {
            return;
        }

        if (! config('tenancy.enabled')) {
            $this->admin('admin@example.com');

            return;
        }

        // Tenancy on: a platform super admin on the central domain, and a demo
        // organization (acme.<central domain>) with its own admin.
        User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            ['name' => 'Super Admin', 'password' => 'password', 'email_verified_at' => now()],
        )->forceFill(['is_super_admin' => true])->save();

        $acme = Tenant::firstOrCreate(['slug' => 'acme'], ['name' => 'Acme']);
        app(Tenancy::class)->run($acme, fn () => $this->admin('admin@example.com'));
    }

    private function admin(string $email): void
    {
        User::firstOrCreate(
            ['email' => $email],
            ['name' => 'Admin', 'password' => 'password', 'email_verified_at' => now()],
        )->syncRoles([UserRole::Admin->value]);
    }
}
