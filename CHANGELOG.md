# Changelog

All notable changes to this scaffold. Versions follow [SemVer](https://semver.org);
releases are cut from `develop` through `release/x.y.z` branches (see the git
flow section in `CLAUDE.md`).

## [0.1.0] — 2026-10-07

First release of the scaffold as a working base for admin SPAs.

### Backend (Laravel 13, PHP 8.4)
- Laravel app versioned in `src/api` (no longer created at setup time), with
  Sanctum 4, spatie/laravel-permission 7 and Pint.
- Cookie-based SPA auth (Sanctum stateful): login with rate limiting, logout,
  profile, password change, forgot/reset password, optional registration.
- Users and roles with the `resource.action` permission convention;
  `users:create` for the first admin.
- API conventions: `/api/v1`, `ListQuery` for lists, resource envelopes,
  409 on duplicate keys with the colliding record.
- Background activity: every queue job and scheduled command recorded in
  `job_runs`; schedule and command catalogs; prune and stale-run reaper.
- Attachments on any model (private disk, per-type permissions) and typed,
  cached runtime settings (`AppSetting`).
- HTML sanitizer and `SanitizedHtml` cast for rich text.
- Languages: Spanish (default) and English; per-user language, also for emails.
- Optional multi-tenancy (`TENANCY_ENABLED`): single database with
  `tenant_id`, resolution by subdomain or custom domain, isolation of data and
  sessions, tenant-aware queued jobs, super admins and organization management.

### Frontend (Vue 3, TypeScript, PrimeVue 4)
- Admin shell with a permission-filtered menu, responsive down to 390px.
- CRUD table kit (`AppCrudTable` + `useDataTable`) and form kit (`useForm`,
  `AppField`, remote and dependent selects with inline create, repeater,
  date/time/range pickers, rich text, file input, attachments).
- vue-i18n (es, en) for every UI string, with PrimeVue texts and regional
  formats following the language.
- Pages: sign-in, password recovery, account, users, activity, commands,
  organizations (super admins) and a living reference of the form kit (dev).

### Tooling and delivery
- Docker for development (api, queue worker, scheduler, frontend, nginx with
  TLS, MySQL, Redis) with an end-to-end `setup.sh`.
- CI: Pint + PHPUnit on MySQL; ESLint (with i18n rules), Prettier, type-check,
  Vitest, build and Storybook build.
- Production stack (`docker-compose.prod.yml`) and `scripts/deploy.sh`:
  backup before migrating, re-exec after checkout, smoke test.
- Docs: auth, backend conventions, datatable pattern, forms, i18n, tenancy,
  deployment.

[0.1.0]: https://github.com/guillorrr/guatape/releases/tag/v0.1.0
