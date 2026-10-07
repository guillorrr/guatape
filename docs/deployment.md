# Deployment

```
push to main ──▶ CI (ci.yml) ──▶ deploy job on a self-hosted runner ──▶ scripts/deploy.sh
                 Pint, PHPUnit,       (label `deploy`, in vars.DEPLOY_DIR)
                 type-check, build
```

## Production stack (`docker-compose.prod.yml`)

| Service | What |
|---|---|
| `api` | PHP-FPM with the deploy clone's `src/api` mounted |
| `queue-worker` | `queue:work redis` (restarts itself every hour, `--max-time`) |
| `scheduler` | `schedule:work` |
| `nginx` | image with the built SPA baked in; serves `/` (SPA), proxies `/api`, `/sanctum`, `/up` to PHP; plain HTTP on `HTTP_PORT` |
| `db`, `redis` | MySQL 8.4, Redis 7 with volumes |

TLS terminates **in front** of nginx (Caddy, Traefik, a Cloudflare tunnel, a
load balancer). Set `TRUSTED_PROXIES` accordingly.

Every Laravel container gets `.env.prod` as its environment (`env_file`), so a
new variable is added in one place — and documented in `.env.prod.example`.

## Server setup (once)

1. Docker + compose plugin; a user that can run them.
2. Clone the repo into the deploy directory (a checkout used only for
   deploying, never for development).
3. `cp .env.prod.example .env.prod` and fill it: `APP_KEY`, `APP_URL`,
   `DB_*`, `SANCTUM_STATEFUL_DOMAINS`, mail. Every variable says what happens
   when it is empty.
4. Register a GitHub self-hosted runner with the label `deploy` on that host.
5. Repo → Settings → Variables → Actions: `DEPLOY_DIR` = path of the clone.
   Without it, the deploy job is skipped and CI still runs.
6. First deploy: push to main (or run the workflow by hand), then create the
   first admin:

   ```bash
   docker compose -f docker-compose.prod.yml --env-file .env.prod -p <project> \
     exec api php artisan users:create admin@example.com
   ```

## What `scripts/deploy.sh` does

1. `artisan down` (skipped on the first deploy).
2. `git reset --hard <sha>`, then **re-executes itself** from the new tree so
   changes to the script apply in the same deploy.
3. `up -d --build`, reload nginx (a recreated api has a new IP).
4. Wait for the db, `composer install --no-dev`.
5. **Backup right before migrating** into `backups/pre-deploy-*.sql.gz`; if
   the dump is incomplete it refuses to migrate. Keeps the last 30
   (`KEEP_BACKUPS`).
6. `migrate --force`, configuration seeders, `optimize`, `storage:link`.
7. `queue:restart`, restart the scheduler, `artisan up`.
8. Smoke test on `/up`; non-zero exit if it never answers 200.

Run it by hand from the clone: `scripts/deploy.sh [git-ref]`
(`COMPOSE_PROJECT` to choose the compose project name).

## Rolling back

- Code: `scripts/deploy.sh <previous-sha>`.
- Data (a migration went wrong): stop the app, restore the dump taken right
  before it:

  ```bash
  gunzip -c backups/pre-deploy-<ts>.sql.gz | docker compose -f docker-compose.prod.yml \
    --env-file .env.prod -p <project> exec -T db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'
  ```

  then deploy the matching code.
