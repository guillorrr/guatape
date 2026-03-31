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

# --- 3. Laravel Installation ---
echo ""
echo "[3/6] Setting up Laravel..."
if [ ! -f "$PROJECT_DIR/src/api/composer.json" ]; then
  echo "  Installing Laravel 12..."
  docker compose run --rm --no-deps -u root api bash -c \
    "composer create-project laravel/laravel /tmp/laravel && \
     cp -a /tmp/laravel/. /var/www/api/ && \
     rm -rf /tmp/laravel && \
     chown -R www:www /var/www/api"
  echo "  ✓ Laravel installed"
else
  echo "  ✓ Laravel already installed"
  echo "  Installing composer dependencies..."
  docker compose run --rm --no-deps api composer install
fi

# Configure Laravel .env
echo "  Configuring Laravel environment..."
docker compose run --rm --no-deps api bash -c "
  sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=mysql/' .env
  sed -i 's/DB_HOST=.*/DB_HOST=db/' .env
  sed -i 's/DB_PORT=.*/DB_PORT=3306/' .env
  sed -i 's/DB_DATABASE=.*/DB_DATABASE=${DB_DATABASE:-guatape}/' .env
  sed -i 's/DB_USERNAME=.*/DB_USERNAME=${DB_USERNAME:-guatape}/' .env
  sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD:-secret}/' .env
  sed -i 's/REDIS_HOST=.*/REDIS_HOST=redis/' .env
  sed -i 's/MAIL_MAILER=.*/MAIL_MAILER=smtp/' .env
  sed -i 's/MAIL_HOST=.*/MAIL_HOST=mailhog/' .env
  sed -i 's/MAIL_PORT=.*/MAIL_PORT=1025/' .env
  sed -i 's/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/' .env
"

# Install Sanctum
echo "  Installing Laravel Sanctum..."
docker compose run --rm --no-deps api composer require laravel/sanctum

# Create custom directories
echo "  Creating project structure..."
docker compose run --rm --no-deps api bash -c "
  mkdir -p app/Services
  mkdir -p app/Integrations/MercadoLibre
  mkdir -p app/Integrations/Afip
  mkdir -p app/DTOs
  mkdir -p app/Enums
  mkdir -p app/Events
  mkdir -p app/Listeners
  mkdir -p app/Jobs
  mkdir -p app/Observers
  mkdir -p app/Policies
  mkdir -p app/Actions
"
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
docker compose up -d db redis
echo "  Waiting for MySQL to be ready..."
sleep 10
docker compose exec api php artisan migrate --force
docker compose exec api php artisan key:generate
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
