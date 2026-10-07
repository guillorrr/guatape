# Authentication and permissions

## Cookie-based SPA auth (Sanctum stateful)

The SPA never holds a token. The browser keeps two cookies set by Laravel:
the **session cookie** (`HttpOnly`, unreadable from JavaScript) and
**`XSRF-TOKEN`**, which axios echoes back as the `X-XSRF-TOKEN` header.

```
SPA                                   API
 │ GET /sanctum/csrf-cookie  ───────▶  sets XSRF-TOKEN
 │ POST /api/v1/auth/login   ───────▶  Auth::attempt + session()->regenerate()
 │                           ◀───────  {data: user + roles + permissions}
 │ GET /api/v1/...  (cookies)───────▶  auth:sanctum resolves the session user
```

- `src/frontend/src/core/services/api.service.ts`: `withCredentials`,
  `withXSRFToken`, retries once on **419** (expired CSRF) after re-priming the
  cookie, and turns every failure into an `ApiError`.
- `src/frontend/src/core/stores/auth.store.ts`: mirrors the session.
  `ensureLoaded()` calls `/auth/me` once per page load; the router guard waits
  for it, so routes never render on a guess.
- `App.vue` subscribes to `onApiError`: a 401 (session expired) clears the
  store and redirects to `/login?redirect=…`; 403/5xx/network show a toast.

### Required configuration

| Variable | Why it matters |
|---|---|
| `SANCTUM_STATEFUL_DOMAINS` | Host(s) of the SPA **with port**. A host missing here gets no session: login answers 200 and every following request 401. |
| `SESSION_DOMAIN` | Empty for a single host. `.example.com` to share the session across subdomains (one SPA per tenant subdomain). |
| `SESSION_SECURE_COOKIE` | `true` in production (HTTPS only). |
| `FRONTEND_URL` | Origin of the SPA; password-reset links and CORS use it. Empty: `APP_URL`. |
| `TRUSTED_PROXIES` | Behind a TLS terminator, so Laravel sees `https` and the real client IP. |

The SPA and the API are served from the **same origin** by nginx (`/api`,
`/sanctum` → Laravel, everything else → SPA). Keep it that way: cross-origin
cookie auth needs extra CORS and `SameSite` work.

## Endpoints

| Method | Path | Notes |
|---|---|---|
| POST | `/auth/login` | `{email, password, remember?}`; rate limited per email+IP (5 tries) |
| POST | `/auth/register` | 404 unless `REGISTRATION_ENABLED=true` |
| POST | `/auth/logout` | invalidates the session |
| GET | `/auth/me` | user + `roles` + effective `permissions` |
| PATCH | `/auth/profile` | name, email |
| PUT | `/auth/password` | requires `current_password`; signs out the other sessions |
| POST | `/auth/forgot-password` | same answer whether the email exists or not |
| POST | `/auth/reset-password` | `{token, email, password, password_confirmation}`; signs out every session |

Password-reset emails link to `FRONTEND_URL/reset-password?token=…&email=…`
(`AppServiceProvider`), which is `ResetPasswordPage.vue`.

Signing out other sessions works with `SESSION_DRIVER=database` (default):
sessions are rows with a `user_id` (`App\Support\UserSessions`).

## Roles and permissions

spatie/laravel-permission, guard `web`. **Gate on permissions, not on roles**:
roles are bundles that change per project, permissions are stable.

- Names follow `resource.action`: `users.view`, `users.manage`,
  `settings.manage`, `system.view`.
- `database/seeders/RolePermissionSeeder.php` is the single source: add the
  permission to `PERMISSIONS`, map it to roles. Admin always gets everything.
  It is idempotent and runs on every deploy.
- API: `Route::middleware('permission:users.manage')`.
- SPA: `meta.permission` on routes (inherited by children), `permission` on
  menu items (`core/navigation.ts`), `usePermissions().can()` in templates.
  This is UX only — the API enforces the same permission.

The first admin in production: `php artisan users:create admin@example.com`
(asks for the password without echoing it).
