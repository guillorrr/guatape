<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Tenancy\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the tenant from the request host before anything loads the user:
 *
 *   <central domain>           → central context (no tenant)
 *   <slug>.<central domain>    → tenant by slug
 *   any other host             → tenant by its custom `domain`
 *
 * It runs first in the api group, so the session user is looked up already
 * scoped: a session cookie shared across subdomains never authenticates a
 * user of another tenant there (the lookup finds nobody → 401).
 */
class IdentifyTenant
{
    public function __construct(private readonly Tenancy $tenancy) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->tenancy->enabled()) {
            return $next($request);
        }

        $tenant = $this->resolve(strtolower($request->getHost()));

        if ($tenant === false) {
            return response()->json(['message' => __('Organization not found.'), 'code' => 'tenant_not_found'], 404);
        }
        if ($tenant !== null && ! $tenant->isActive()) {
            return response()->json(['message' => __('This organization is suspended.'), 'code' => 'tenant_suspended'], 403);
        }

        $this->tenancy->set($tenant);

        try {
            return $next($request);
        } finally {
            // Long-lived workers (Octane, queue) must not inherit it.
            $this->tenancy->set(null);
        }
    }

    /** null = central context; false = host names a tenant that doesn't exist. */
    private function resolve(string $host): Tenant|false|null
    {
        foreach (config('tenancy.central_domains', []) as $central) {
            $central = strtolower($central);
            if ($host === $central) {
                return null;
            }
            if (str_ends_with($host, '.'.$central)) {
                $slug = substr($host, 0, -strlen('.'.$central));
                if (in_array($slug, config('tenancy.reserved_slugs', []), true)) {
                    return null;
                }

                return Tenant::where('slug', $slug)->first() ?? false;
            }
        }

        return Tenant::where('domain', $host)->first() ?? false;
    }
}
