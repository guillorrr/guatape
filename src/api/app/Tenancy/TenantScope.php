<?php

namespace App\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Tenant model queries see only the current tenant's rows; in the central
 * context, only central rows (tenant_id null). Opt out explicitly with
 * Model::withoutTenancy() — e.g. a super admin listing every tenant's users.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenancy = app(Tenancy::class);
        if (! $tenancy->enabled()) {
            return;
        }

        $column = $model->qualifyColumn('tenant_id');
        $tenancy->id() === null
            ? $builder->whereNull($column)
            : $builder->where($column, $tenancy->id());
    }
}
