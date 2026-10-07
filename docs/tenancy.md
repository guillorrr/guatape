# Multi-tenancy (optional)

One database, one app, many organizations: tenant-owned tables have a
nullable `tenant_id` and every query is scoped to the organization of the
request. **Off by default** — with `TENANCY_ENABLED=false` nothing is scoped
and the app behaves as a single-tenant one.

## How a request finds its organization

`IdentifyTenant` runs first in the API group and looks at the host:

| Host | Context |
|---|---|
| `<central domain>` (`TENANCY_CENTRAL_DOMAINS`) | central: no tenant — platform admin, sign-up |
| `<slug>.<central domain>` | the tenant with that slug (`www`, `api`, `admin`… are reserved) |
| any other host | the tenant whose `domain` matches (custom domains) |
| unknown slug/domain | 404 `tenant_not_found` |
| suspended tenant | 403 `tenant_suspended` |

Because it runs before anything loads the user, the session user is looked up
already scoped: a session cookie shared across subdomains never authenticates
a user of another organization there.

The SPA asks `GET /api/v1/tenancy` once (`useTenancyStore`): it brands itself
with the organization's name and shows its own page for unknown/suspended
organizations.

## Making a model tenant-owned

```php
// migration
$table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();

// model
use App\Tenancy\BelongsToTenant;

class Invoice extends Model
{
    use BelongsToTenant;
}
```

- Queries see the current organization's rows; in the central context, only
  central rows (`tenant_id` null). New rows are stamped with the current
  tenant.
- `Invoice::withoutTenancy()` opts out (platform-level reports, super admin).
- Unique indexes on tenant-owned tables usually become
  `unique(['tenant_id', 'code'])`.
- Already tenant-owned in the scaffold: `users`, `attachments`, `job_runs`,
  `app_settings` (`AppSetting` values and cache are per organization).

## Running code as an organization

```php
$tenancy = app(App\Tenancy\Tenancy::class);

$tenancy->current();                      // ?Tenant (null = central)
$tenancy->run($tenant, function () {      // as $tenant, then restores
    // …
});
$tenancy->forEach(function (Tenant $t) {  // once per active tenant
    // e.g. a scheduled command that must touch every organization
});
```

- **Queued jobs** carry the tenant that dispatched them and run inside it.
- **Scheduled commands** run in the central context; use `forEach()` for
  per-organization work.
- **Gotcha**: inside `run()`, dispatch with a block body. `fn () => Job::dispatch()`
  returns the `PendingDispatch`, which queues the job when destroyed — after
  `run()` already restored the previous context.
- **Gotcha**: a model's relation to tenant-owned rows inherits the scope of
  the *current* context. `Tenant::users()` removes it on purpose; do the same
  for relations that must cross contexts.

## Super admins

`users.is_super_admin` (no `tenant_id`) work from the central domain: every
permission (`Gate::before`), and **Plataforma → Organizaciones** to list,
create (with its first admin) and suspend organizations. There is no delete:
removing an organization's data is a deliberate, offline task.

```bash
php artisan users:create root@example.com --super-admin
php artisan tenants:create acme "Acme" --admin-email=admin@acme.com
php artisan users:create ana@acme.com --tenant=acme --role=member
```

Roles and permissions are global (shared by every organization); each
organization assigns them to its own users.

## Turning it on

| Variable | Value |
|---|---|
| `TENANCY_ENABLED` | `true` |
| `TENANCY_CENTRAL_DOMAINS` | e.g. `example.com` (empty: `APP_URL`'s host) |
| `SESSION_DOMAIN` | `.example.com`, so the session works on every subdomain |
| `SANCTUM_STATEFUL_DOMAINS` | `example.com,*.example.com` |

Plus a wildcard DNS record (`*.example.com`) and a TLS certificate that
covers it. Custom domains need their own DNS and certificate.

Development: the mkcert certificate already covers `*.<DOMAIN>`. Add the
subdomains to `/etc/hosts` (`127.0.0.1 acme.guatape.local`), or use
`localhost` as the central domain — browsers resolve `*.localhost` on their
own. With tenancy on, `php artisan migrate:fresh --seed` creates
`superadmin@example.com` (central) and the `acme` organization with
`admin@example.com` (password `password`).

## Tests

`phpunit.xml` keeps tenancy off for the whole suite, which proves "off"
changes nothing. `tests/Feature/Tenancy` turns it on (`$this->enableTenancy()`)
and hits tenant hosts with `$this->onTenant('acme', '/api/v1/…')`.
