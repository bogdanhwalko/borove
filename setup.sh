#!/usr/bin/env bash
# =====================================================
# Борове — Bootstrap script
# Run in WSL / Git Bash / macOS Terminal
# =====================================================
set -e

COMPOSE="docker compose"
command -v docker-compose &>/dev/null && COMPOSE="docker-compose"

echo "▶ Building containers..."
$COMPOSE build --no-cache

echo "▶ Starting DB..."
$COMPOSE up -d db
echo "  Waiting for MySQL to be ready..."
until $COMPOSE exec db mysqladmin ping -h localhost -u root -psecret --silent 2>/dev/null; do
  sleep 2
done

echo "▶ Creating Laravel project in backend/..."
mkdir -p backend
$COMPOSE run --rm --no-deps app \
  composer create-project laravel/laravel /var/www/html \
    --prefer-dist --no-interaction --quiet

echo "▶ Installing Laravel Sanctum..."
$COMPOSE run --rm --no-deps app bash -c "\
  cd /var/www/html && \
  composer require laravel/sanctum --no-interaction --quiet"

echo "▶ Copying custom application files..."
$COMPOSE run --rm --no-deps app bash -c "\
  cp -f /backend-src/routes/api.php              /var/www/html/routes/api.php && \
  cp -f /backend-src/app/Models/User.php         /var/www/html/app/Models/User.php && \
  mkdir -p /var/www/html/app/Http/Controllers && \
  cp -f /backend-src/app/Http/Controllers/AuthController.php \
        /var/www/html/app/Http/Controllers/AuthController.php && \
  rm -f /var/www/html/database/migrations/*_create_users_table.php && \
  cp -f /backend-src/database/migrations/2026_04_25_000000_create_users_table.php \
        /var/www/html/database/migrations/ && \
  cp -f /backend-src/.env.template /var/www/html/.env"

echo "▶ Publishing Sanctum config..."
$COMPOSE run --rm --no-deps app bash -c "\
  cd /var/www/html && \
  php artisan key:generate && \
  php artisan vendor:publish \
    --provider='Laravel\Sanctum\SanctumServiceProvider' --force --quiet"

echo "▶ Running migrations..."
$COMPOSE run --rm app bash -c "\
  cd /var/www/html && php artisan migrate --force"

echo "▶ Fixing permissions..."
$COMPOSE run --rm --no-deps app bash -c "\
  chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
  chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache"

echo "▶ Starting all services..."
$COMPOSE up -d

echo ""
echo "✅ Done!"
echo "   Frontend + API : http://localhost:8000"
echo "   phpMyAdmin     : http://localhost:8080"
echo ""
