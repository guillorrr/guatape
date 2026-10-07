#!/bin/bash
# Production deploy, run on the server from the deploy clone (a fixed checkout
# with .env.prod next to docker-compose.prod.yml). Used by
# .github/workflows/deploy.yml; also safe to run by hand.
#
#   scripts/deploy.sh [git-ref]      # default: origin/main
#
# Order matters: maintenance on → code (then re-exec the new script) → images → deps → workers → BACKUP →
# migrate → maintenance off → smoke test. The backup runs right before
# migrating so a bad migration can always be rolled back to this exact point.
#
# Env: COMPOSE_PROJECT (default: directory name), KEEP_BACKUPS (default: 30).
set -euo pipefail

step() { echo; echo "▶ $*"; }

# Everything lives in main(), called on the last line: bash then parses the
# whole file before running it. Without that, `git reset` below rewrites this
# very script while bash is still reading it, and the rest runs mixed code.
main() {
  cd "$(dirname "${BASH_SOURCE[0]}")/.."

  REF="${1:-origin/main}"
  PROJECT="${COMPOSE_PROJECT:-$(basename "$PWD")}"
  KEEP_BACKUPS="${KEEP_BACKUPS:-30}"
  DC=(docker compose -f docker-compose.prod.yml --env-file .env.prod -p "$PROJECT")

  [ -f .env.prod ] || { echo "✗ .env.prod missing (copy .env.prod.example)"; exit 1; }

  # Phase 1 (old code): maintenance on + checkout, then re-run the script as it
  # exists in $REF, so changes to this file apply in the same deploy.
  if [ -z "${DEPLOY_CHECKED_OUT:-}" ]; then
    step "Maintenance on"
    "${DC[@]}" exec -T api php artisan down --retry=15 2>/dev/null || echo "  api not running (first deploy?), continuing"

    step "Checking out $REF"
    git fetch --all --prune
    git reset --hard "$REF"

    DEPLOY_CHECKED_OUT=1 exec scripts/deploy.sh "$REF"
  fi

  step "Building and starting services"
  "${DC[@]}" up -d --build --remove-orphans

  step "Waiting for db"
  for i in $(seq 1 30); do
    if "${DC[@]}" exec -T db sh -c 'mysqladmin ping -h localhost -uroot -p"$MYSQL_ROOT_PASSWORD"' >/dev/null 2>&1; then
      echo "  ✓ db up"; break
    fi
    [ "$i" -eq 30 ] && { echo "  ✗ db never came up"; exit 1; }
    sleep 2
  done

  step "Composer (no dev)"
  "${DC[@]}" exec -T api composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

  step "Backup before migrate"
  mkdir -p backups
  file="backups/pre-deploy-$(date -u +%Y%m%d-%H%M%S).sql.gz"
  "${DC[@]}" exec -T db sh -c 'mysqldump --single-transaction --quick -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' | gzip > "$file"
  # mysqldump writes this trailer only when it finished; size says nothing
  # (an empty database dumps to a few hundred bytes).
  if ! gzip -dc "$file" | tail -n 1 | grep -q -- '-- Dump completed'; then
    echo "  ✗ backup is incomplete, refusing to migrate"; rm -f "$file"; exit 1
  fi
  ls -t backups/pre-deploy-*.sql.gz | tail -n +"$((KEEP_BACKUPS + 1))" | xargs -r rm
  echo "  ✓ $file"

  step "Migrate and seed configuration"
  "${DC[@]}" exec -T api php artisan migrate --force
  "${DC[@]}" exec -T api php artisan db:seed --class=RolePermissionSeeder --force

  step "Caches"
  "${DC[@]}" exec -T api php artisan optimize
  "${DC[@]}" exec -T api php artisan storage:link 2>/dev/null || true

  step "Restart workers (pick up new code)"
  "${DC[@]}" exec -T api php artisan queue:restart
  "${DC[@]}" restart scheduler

  step "Maintenance off"
  "${DC[@]}" exec -T api php artisan up

  step "Smoke test"
  for i in 1 2 3 4 5; do
    if "${DC[@]}" exec -T nginx wget -qO- http://localhost/up >/dev/null 2>&1; then
      echo "  ✓ /up healthy"; "${DC[@]}" ps; exit 0
    fi
    sleep 3
  done
  echo "  ✗ /up never answered 200 — check: ${DC[*]} logs api nginx"
  exit 1
}

main "$@"
