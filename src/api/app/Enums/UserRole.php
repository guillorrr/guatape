<?php

namespace App\Enums;

/**
 * Roles shipped with the scaffold. Forks add their own cases and map them to
 * permissions in RolePermissionSeeder.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Member => 'Member',
        };
    }
}
