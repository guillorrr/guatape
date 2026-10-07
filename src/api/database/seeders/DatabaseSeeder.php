<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
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

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => 'password', 'email_verified_at' => now()],
        );
        $admin->syncRoles([UserRole::Admin->value]);
    }
}
