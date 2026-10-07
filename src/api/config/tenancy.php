<?php

/*
|--------------------------------------------------------------------------
| Multi-tenancy (optional)
|--------------------------------------------------------------------------
|
| One database, a `tenant_id` column on tenant-owned tables (BelongsToTenant).
| Off by default: with TENANCY_ENABLED=false nothing is scoped and the app
| behaves as a single-tenant one. See docs/tenancy.md.
|
*/

return [

    'enabled' => (bool) env('TENANCY_ENABLED', false),

    // Host(s) of the central app (landing, sign-up, super admin): requests to
    // them run without a tenant. A tenant is resolved from "<slug>.<central>"
    // or from its own `domain`. Comma-separated; the first one builds tenant
    // URLs.
    'central_domains' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TENANCY_CENTRAL_DOMAINS', parse_url((string) env('APP_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost')),
    ))),

    // Subdomains that never name a tenant.
    'reserved_slugs' => ['www', 'api', 'admin', 'app', 'mail'],

];
