<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Configuration seeder: safe to run in every environment, idempotent.
 *
 * Permissions follow `recurso.accion` (users.view, users.manage…). Add the
 * fork's permissions to PERMISSIONS and map them to roles below; Admin always
 * gets everything.
 */
class RolePermissionSeeder extends Seeder
{
    /** @var list<string> */
    public const PERMISSIONS = [
        'users.view',
        'users.manage',
        'settings.manage',
        'system.view',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate(UserRole::Admin->value, 'web')->syncPermissions(Permission::all());

        Role::findOrCreate(UserRole::Member->value, 'web')->syncPermissions([]);
    }
}
