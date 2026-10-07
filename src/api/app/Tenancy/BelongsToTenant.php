<?php

namespace App\Tenancy;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * For tables with a nullable `tenant_id`: scopes every query to the current
 * tenant and stamps new rows with it. Inert while tenancy is disabled.
 *
 * @method static Builder<static> withoutTenancy()
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            $tenancy = app(Tenancy::class);
            if ($tenancy->enabled() && $model->tenant_id === null && $tenancy->id() !== null) {
                $model->tenant_id = $tenancy->id();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeWithoutTenancy(Builder $query): void
    {
        $query->withoutGlobalScope(TenantScope::class);
    }
}
