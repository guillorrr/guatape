# Guatapé — Laravel 13 + Vue 3 Scaffold

## Fork Detection
Check `APP_NAME` in `.env` to determine if this is the base scaffold or a fork. Never reference "Guatapé" in forked projects — use the actual `APP_NAME`.

## Tech Stack
- **Backend**: Laravel 13 (PHP 8.4), Eloquent, spatie/laravel-permission
- **Auth**: Laravel Sanctum, **cookie-based SPA session** (no tokens in the browser) — see `docs/auth.md`
- **Frontend**: Vue 3 + Vite + TypeScript, Pinia, Vue Router, **PrimeVue 4** (Aura preset)
- **Database**: MySQL 8.4 — **Cache/Queue**: Redis 7 (sessions in the database by default)
- **Design System**: Storybook 8, SCSS, Atomic Design
- **Monorepo**: npm workspaces (`src/frontend`); `src/api` is a regular Laravel app

## Project Structure

```
├── src/
│   ├── api/                          # Laravel 13
│   │   ├── app/
│   │   │   ├── Models/               # Eloquent models (+ Concerns/ traits)
│   │   │   ├── Http/Controllers/Api/ # API controllers, one resource each
│   │   │   ├── Http/Requests/        # FormRequests, grouped by resource
│   │   │   ├── Http/Resources/       # JsonResources
│   │   │   ├── Services/             # Business logic
│   │   │   ├── Support/              # Cross-cutting helpers (ListQuery, JobRunRecorder…)
│   │   │   ├── Console/Commands/     # Artisan commands (listed on Sistema → Comandos)
│   │   │   ├── Enums/  Attributes/
│   │   │   └── (Actions/ DTOs/ Integrations/ Jobs/ Events/ Listeners/ Observers/ Policies/ as needed)
│   │   ├── config/activity.php       # Background-activity recording
│   │   ├── config/attachments.php    # Which models accept attachments
│   │   ├── database/seeders/         # RolePermissionSeeder = config; demo data in DatabaseSeeder
│   │   ├── routes/api.php            # /api/v1
│   │   ├── routes/console.php        # Scheduled tasks
│   │   └── tests/                    # PHPUnit (Feature/, Unit/)
│   └── frontend/                     # Vue 3 SPA
│       └── src/
│           ├── atoms/ molecules/ organisms/ templates/ pages/
│           ├── composables/          # useDataTable, useForm, usePermissions, useAppToast…
│           ├── core/
│           │   ├── services/         # api.service (ApiError, CSRF) + one service per resource
│           │   ├── stores/           # auth, layout
│           │   ├── models/           # TypeScript interfaces (ItemResponse, PaginatedResponse…)
│           │   ├── constants/        # pagination, locale
│           │   ├── styles/           # SCSS + PrimeVue preset
│           │   └── navigation.ts     # Sidebar menu (filtered by permission)
│           └── router/               # Routes + auth/permission guard
├── docker/                           # Dockerfiles, nginx (dev + prod), db init, mkcert
├── scripts/                          # setup.sh, deploy.sh, backup-db.sh
├── docs/                             # auth, backend conventions, datatable pattern, deployment
└── .github/workflows/                # ci.yml, deploy.yml
```

## Docs — read before touching the area

| Topic | File |
|---|---|
| Login, sessions, CSRF, roles and permissions | `docs/auth.md` |
| API shape, errors, `ListQuery`, background jobs, attachments, settings, seeders, tests | `docs/backend-conventions.md` |
| CRUD list pages (`AppCrudTable` + `useDataTable` + `ListQuery`) | `docs/datatable-pattern.md` |
| CI, production stack, deploy script, rollback | `docs/deployment.md` |

## Architecture Conventions

### Backend (Laravel)
- **Controllers**: Thin, delegate to Services. One resource per controller.
- **Validation**: FormRequests, never in controllers.
- **Output**: JsonResources; lists via `ListQuery` + `Resource::collection()` (`{data, links, meta}`).
- **Authorization**: `permission:<resource.action>` middleware on routes; permissions live in `RolePermissionSeeder`.
- **Errors**: 422 `{message, errors}`, 409 `duplicate_unique_key` (`DuplicateKeyResponder`), 401/403/404 `{message}`.
- **Background work**: jobs on Redis (`queue-worker`), schedule in `routes/console.php` with `->description()`; every run is recorded in `job_runs`.
- **Integrations**: External API wrappers in `app/Integrations/<Name>`. Isolated, testable.
- **Env vars**: a new variable goes, in the same change, into `src/api/.env.example` and `.env.prod.example` with a comment saying what happens when it's empty.

### Frontend (Vue 3)
- **Atomic Design**: atoms → molecules → organisms → templates → pages.
- **Composition API only**: `<script setup lang="ts">`.
- **API calls**: through `core/services/*.service.ts`; failures are `ApiError` (`status`, `message`, `errors`, `fieldError()`).
- **Forms**: `useForm` (`form.submit(() => …)`, `form.error('field')`).
- **Lists**: `AppCrudTable` + `useDataTable` (see `docs/datatable-pattern.md`; `UserListPage.vue` is the reference).
- **Permissions**: `meta.permission` on routes, `permission` in `core/navigation.ts`, `usePermissions().can()` in templates. UX only; the API enforces them.
- **Layouts**: `AppLayout` (public), `AuthLayout` (login/reset), `DashboardLayout` (`/app/**`) are parent routes; pages don't wrap themselves.
- **Destructive confirms**: `useConfirm()` with `defaultFocus: 'reject'`.
- **Mobile**: every page must fit 390px wide; wide tables scroll inside their box.
- **Storybook**: every atom and molecule should have a `.stories.ts` file.

## Naming Conventions

### Backend
- **Models**: `PascalCase` singular (`Invoice`, `Customer`)
- **Controllers**: `PascalCase` + `Controller` (`InvoiceController`)
- **Services**: `PascalCase` + `Service` (`InvoiceService`)
- **Permissions**: `resource.action` (`invoices.view`, `invoices.manage`)
- **Routes**: `kebab-case` plural, in English (`/api/v1/invoice-items`)

### Frontend
- **Components**: `PascalCase` with prefix (`AppButton.vue`, `InvoiceCard.vue`)
- **Stores**: `camelCase` + `.store.ts`; **Services**: `camelCase` + `.service.ts`; **Composables**: `use` prefix
- **URLs**: English, kebab-case (`/app/settings/users`); UI labels can be in any language

## Key Commands

```bash
# Development
npm start                   # api, queue-worker, scheduler, frontend, nginx, db, redis
npm run start:all           # + tools (phpMyAdmin, Mailhog) and Storybook
npm run dev                 # Same as start, attached logs
npm run logs:worker         # queue-worker + scheduler logs

# Laravel (inside the api container)
npm run artisan -- <cmd>    # e.g. npm run artisan -- users:create me@example.com
npm run tinker
npm run migrate             # / migrate:fresh (with seed) / migrate:status
npm run test:api            # PHPUnit on <db>_testing
npm run queue:restart       # After changing job code

# Frontend
npm run storybook
npm run test:frontend
# src/frontend: npm run type-check && npm run build   (what CI runs)

# Infrastructure
npm run setup               # Full first-time setup (idempotent)
npm run docker:rebuild
npm run backup:db
scripts/deploy.sh [ref]     # Production deploy, on the server (docs/deployment.md)
```

Local login after setup: `admin@example.com` / `password`.

## Docker Services

| Service      | Image          | Port(s)    | Profile   |
|--------------|----------------|------------|-----------|
| db           | MySQL 8.4      | 3306       | default   |
| api          | PHP 8.4-FPM    | 9000       | default   |
| queue-worker | (api image)    | —          | default   |
| scheduler    | (api image)    | —          | default   |
| frontend     | Node 22        | 5173       | default   |
| nginx        | Nginx Alpine   | 80, 443    | default   |
| redis        | Redis 7        | 6379       | default   |
| storybook    | Node 22        | 6006       | storybook |
| phpmyadmin   | phpMyAdmin     | 8082       | tools     |
| mailhog      | Mailhog        | 1025, 8025 | tools     |
| mkcert       | docker-mkcert  | —          | setup     |

All host ports are configurable in the root `.env`.

## Adding a New Module (Backend)

1. Model + migration: `php artisan make:model Invoice -m`
2. Permissions: add `invoices.view` / `invoices.manage` to `RolePermissionSeeder` and map them to roles
3. FormRequests in `app/Http/Requests/Invoice/`, `InvoiceResource`, service if there's logic
4. Controller in `app/Http/Controllers/Api/`, `index` with `ListQuery`
5. Routes in `routes/api.php` behind `permission:` middleware
6. Feature tests in `tests/Feature/<Module>/` (including a 403 for a role without the permission)

## Adding a New Page (Frontend)

1. Service in `core/services/`, types in `core/models/`
2. Page in `pages/<area>/`; for lists start from `pages/settings/UserListPage.vue`
3. Route under `/app` in `router/index.ts` with `meta.title` and `meta.permission`
4. Menu entry in `core/navigation.ts` with the same permission
5. New atoms/molecules get stories

## Database Seeders
- **Configuration** (all environments, idempotent, run by every deploy): `RolePermissionSeeder`.
- **Demo data** (never in production): `DatabaseSeeder`, after its `isProduction()` return.
- First production admin: `php artisan users:create <email>`.

## Code Style
- PHP: Laravel Pint (`vendor/bin/pint`, CI runs `--test`)
- TypeScript/Vue: ESLint + Prettier (see `.prettierrc`); `npm run type-check` must pass
- Commits: Conventional Commits (`feat:`, `fix:`, `docs:`…), Husky + commitlint

## Git discipline (parallel agents)
- **Never use `git add -A`, `git add .` or `git add <directory>`.** Several sessions may work on the same tree; a blanket add sweeps someone else's files into your commit. Stage the specific files this task touched, by path, after `git status`.
- If a file you edited was also changed by another session, surface it instead of committing blindly.
- Don't stage build artifacts (`dist/`, `.js` sidecars next to `.ts`/`.vue`, dumps).
