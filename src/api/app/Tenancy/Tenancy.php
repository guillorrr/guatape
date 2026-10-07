<?php

namespace App\Tenancy;

use App\Models\Tenant;
use Closure;

/**
 * Holds the tenant of the current request, job or command (a container
 * singleton; inject it or use app(Tenancy::class)).
 *
 *   $tenancy->current()       // ?Tenant — null in the central context
 *   $tenancy->run($t, fn …)   // run code as $t (scheduler, seeders, tests)
 *   $tenancy->forEach(fn …)   // run code once per active tenant
 *
 * With tenancy disabled everything here is inert: current() is always null
 * and BelongsToTenant doesn't scope anything.
 */
class Tenancy
{
    private ?Tenant $tenant = null;

    public function enabled(): bool
    {
        return (bool) config('tenancy.enabled');
    }

    public function current(): ?Tenant
    {
        return $this->tenant;
    }

    public function id(): ?int
    {
        return $this->tenant?->getKey();
    }

    /** True when tenancy is on and no tenant is set (central domain, scheduler…). */
    public function isCentral(): bool
    {
        return $this->enabled() && $this->tenant === null;
    }

    public function set(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    /**
     * Runs $callback as $tenant and restores the previous context.
     *
     * Dispatching jobs inside: use a block body. `fn () => Job::dispatch()`
     * returns the PendingDispatch, which queues the job only when destroyed —
     * after run() has already restored the previous context.
     *
     * @template T
     *
     * @param  Closure(Tenant): T  $callback
     * @return T
     */
    public function run(Tenant $tenant, Closure $callback): mixed
    {
        $previous = $this->tenant;
        $this->tenant = $tenant;

        try {
            return $callback($tenant);
        } finally {
            $this->tenant = $previous;
        }
    }

    /**
     * @param  Closure(Tenant): mixed  $callback
     */
    public function forEach(Closure $callback): void
    {
        Tenant::where('status', Tenant::STATUS_ACTIVE)->orderBy('id')
            ->each(fn (Tenant $tenant) => $this->run($tenant, $callback));
    }
}
