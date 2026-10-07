# Backend conventions

## API shape

- Everything under `/api/v1` (`bootstrap/app.php`), JSON always — even when the
  client forgets `Accept: application/json`.
- One resource per controller, validation in FormRequests, output through
  `JsonResource`s.
- Errors the SPA relies on:

| Status | Body | Meaning |
|---|---|---|
| 401 | `{message}` | no session |
| 403 | `{message}` | missing permission |
| 404 | `{message}` | not found |
| 409 | `{message, code: "duplicate_unique_key", details}` | a unique index rejected the write (`DuplicateKeyResponder`) |
| 419 | `{message}` | CSRF token expired (the SPA retries once) |
| 422 | `{message, errors: {field: [..]}}` | validation; `useForm` shows them under each input |

## Lists: `ListQuery`

Every index endpoint uses `App\Support\ListQuery` + a resource collection:

```php
$users = ListQuery::for(User::query()->with('roles'), $request)
    ->search(['name', 'email'])                                  // ?search=
    ->sortable(['name', 'email', 'created_at'], default: 'name') // ?sort_by=&sort_dir=
    ->filter('role', fn ($q, string $role) => $q->role($role))   // ?role=
    ->paginate();                                                // ?page=&per_page= (max 100)

return UserResource::collection($users);                         // {data, links, meta}
```

Only whitelisted columns can be sorted; unknown filters are ignored. That
envelope is what `useDataTable` on the SPA reads (see `datatable-pattern.md`).

## Duplicate keys → 409

Unique indexes are the last line of defence (two concurrent requests pass
validation). `DuplicateKeyResponder` turns the MySQL 1062 into a 409 naming
the table, column and value. Register a resolver to also name the record that
holds the value:

```php
DuplicateKeyResponder::resolveOwnerUsing('products', fn (string $column, string $value) =>
    Product::where($column, $value)->first(['id', 'name']));
```

## Background work

- Queue: Redis, `queue-worker` service. Add one worker service per queue that
  must not wait behind another.
- Scheduler: `routes/console.php`, `scheduler` service. Give every entry a
  `->description()`.
- Every job and scheduled command lands in `job_runs` (`JobRunRecorder`),
  shown on **Sistema → Actividad** with its schedule. Jobs can report what
  they did with `JobRunRecorder::note('45 users imported')`. Tuning (ignored
  and quiet tasks, retention, domain labels): `config/activity.php`.
- **Sistema → Comandos** lists the app's own artisan commands by reflection.
  Mark one-off commands with `#[AppCommand(CommandLifecycle::OneShot, note: '…')]`.

## Files: attachments

`use HasAttachments` on the model, register it in `config/attachments.php`
`parents` with its manage/view permissions, and the generic endpoints work:
`GET|POST /attachments/{type}/{id}`, `GET /attachments/{id}/download`,
`DELETE /attachments/{id}`. Files go to `ATTACHMENTS_DISK` (private; downloads
go through the API).

## Rich text

Columns edited with the SPA's `AppRichText` get the `SanitizedHtml` cast:
every write goes through `HtmlSanitizer`, which keeps the editor's formats
(paragraphs, h2/h3, bold, italic, underline, strike, lists, quotes, http(s)
links) and drops everything else.

```php
protected function casts(): array { return ['description' => SanitizedHtml::class]; }
```

## Translations

`APP_LOCALE=es` by default. Framework messages live in `lang/es/*.php`; the
app's own sentences use `__('English sentence.')` with the Spanish in
`lang/es.json`. `tests/Unit/TranslationsTest.php` fails when a framework key
or a literal `__()` string has no Spanish translation. Field names for
validation messages: `lang/es/validation.php` → `attributes`.

## Runtime settings

`AppSetting::get('key', $default)` / `AppSetting::set('key', $value)`: values
are JSON (they keep their type) and cached. Use it for what an admin changes
at runtime; keep secrets and infrastructure in `.env`.

## Seeders

- **Configuration** (every environment, idempotent, run by each deploy):
  `RolePermissionSeeder`.
- **Demo data** (never in production): inside `DatabaseSeeder` after the
  `isProduction()` return. Locally `admin@example.com` / `password`.

## Tests

- Run on `<DB_DATABASE>_testing` (`mysql_testing` connection), created by
  `docker/db/init` on the db container's first start. They never touch the dev
  database.
- `phpunit.xml` forces both `<env>` and `<server>`: the api container exports
  `APP_ENV`/`DB_*` as OS variables, which Laravel reads first.
- Authorization is real in tests (no blanket `Gate::before`). `actingAs()`
  resets the guards, so switching users mid-test works.
- `npm run test:api` (inside the container) or the CI workflow.
