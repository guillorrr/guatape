#!/bin/bash
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"

echo "========================================="
echo "  Guatapé — Project Setup"
echo "========================================="

# --- 1. Environment ---
echo ""
echo "[1/6] Setting up environment..."
if [ ! -f "$PROJECT_DIR/.env" ]; then
  cp "$PROJECT_DIR/.env.example" "$PROJECT_DIR/.env"
  echo "  ✓ .env created from .env.example"
else
  echo "  ✓ .env already exists"
fi

source "$PROJECT_DIR/.env"

# --- 2. SSL Certificates ---
echo ""
echo "[2/6] Generating SSL certificates..."
if [ ! -f "$PROJECT_DIR/certs/cert.pem" ]; then
  docker compose --profile setup run --rm mkcert
  echo "  ✓ SSL certificates generated"
else
  echo "  ✓ SSL certificates already exist"
fi

# --- 3. Laravel dependencies ---
echo ""
echo "[3/6] Setting up Laravel..."
docker compose run --rm --no-deps api composer install
if [ ! -f "$PROJECT_DIR/src/api/.env" ]; then
  cp "$PROJECT_DIR/src/api/.env.example" "$PROJECT_DIR/src/api/.env"
  echo "  ✓ src/api/.env created from src/api/.env.example"
fi
echo "  ✓ Laravel configured"

# --- 4. Frontend Setup ---
echo ""
echo "[4/6] Setting up Vue 3 frontend..."
if [ ! -f "$PROJECT_DIR/src/frontend/node_modules/.package-lock.json" ]; then
  docker compose run --rm --no-deps frontend npm install
  echo "  ✓ Frontend dependencies installed"
else
  echo "  ✓ Frontend dependencies already installed"
fi

# --- 5. Database ---
echo ""
echo "[5/6] Setting up database..."
echo "  Waiting for MySQL to be healthy..."
docker compose up -d --wait db redis
docker compose up -d api
docker compose exec api sh -c "grep -q '^APP_KEY=base64' .env || php artisan key:generate"
docker compose exec api php artisan migrate --force --seed
echo "  ✓ Database migrated"

# --- 6. Storage link ---
echo ""
echo "[6/6] Final setup..."
docker compose exec api php artisan storage:link
echo "  ✓ Storage linked"

echo ""
echo "========================================="
echo "  Setup complete!"
echo "========================================="
echo ""
echo "  Start dev:      npm start"
echo "  Start all:      npm run start:all"
echo "  API:            https://${DOMAIN:-guatape.local}/api"
echo "  Frontend:       https://${DOMAIN:-guatape.local}"
echo "  phpMyAdmin:     http://localhost:${PHPMYADMIN_PORT:-8082}"
echo "  Mailhog:        http://localhost:${MAILHOG_UI_PORT:-8025}"
echo "  Storybook:      http://localhost:${STORYBOOK_PORT:-6006}"
echo ""
