<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use App\Tenancy\Tenancy;
use Illuminate\Support\Facades\DB;

class TenantService
{
    public function __construct(private readonly Tenancy $tenancy) {}

    /**
     * Creates the organization with its first admin, atomically.
     *
     * @param  array{name: string, slug: string, domain?: ?string, status?: string}  $data
     * @param  array{name: string, email: string, password: string}  $admin
     */
    public function create(array $data, array $admin): Tenant
    {
        return DB::transaction(function () use ($data, $admin) {
            $tenant = Tenant::create($data);

            $this->tenancy->run($tenant, function () use ($admin) {
                User::create($admin + ['email_verified_at' => now()])->assignRole(UserRole::Admin->value);
            });

            return $tenant;
        });
    }
}
