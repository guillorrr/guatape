#!/bin/bash
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

source "$PROJECT_DIR/.env"

BACKUP_DIR="$PROJECT_DIR/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
FILENAME="${APP_NAME:-guatape}_${TIMESTAMP}.sql.gz"

echo "Creating database backup..."
docker compose exec -T db mysqldump \
  -u"${DB_USERNAME:-guatape}" \
  -p"${DB_PASSWORD:-secret}" \
  "${DB_DATABASE:-guatape}" | gzip > "$BACKUP_DIR/$FILENAME"

echo "Backup saved: backups/$FILENAME"
