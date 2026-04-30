#!/bin/bash
# =====================================================
# Container bootstrap for the Laravel app.
# Source code is mounted from the host (repo root); this
# script only ensures the runtime artefacts (vendor/, .env,
# storage symlink, migrations) exist on first start.
# Idempotent: safe to run on every container start.
# =====================================================
set -e

cd /var/www/html

if [ ! -f "artisan" ]; then
    echo "[init] ERROR: /var/www/html does not look like a Laravel app (no artisan file)."
    exit 1
fi

# ── Wait for MySQL ────────────────────────────────────
echo "[init] waiting for MySQL..."
RETRIES=60
while [ $RETRIES -gt 0 ]; do
    if mysqladmin ping -h db -u borove -psecret --ssl=0 --silent 2>/dev/null; then
        break
    fi
    RETRIES=$((RETRIES - 1))
    sleep 3
done
if [ $RETRIES -eq 0 ]; then
    echo "[init] ERROR: MySQL did not become ready in time"
    exit 1
fi
echo "[init] MySQL ready."

# ── Install PHP dependencies if vendor/ is missing ────
if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
    echo "[init] composer install..."
    composer install --no-interaction --no-progress --prefer-dist
fi

# ── .env from .env.example if missing ─────────────────
if [ ! -f ".env" ]; then
    echo "[init] creating .env from .env.example..."
    cp .env.example .env
    php artisan key:generate --force
fi

# Ensure APP_KEY is set
if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

# ── Storage symlink ───────────────────────────────────
if [ ! -e "public/storage" ]; then
    php artisan storage:link
fi

mkdir -p storage/app/public/photos \
         storage/app/public/announcements \
         storage/app/public/avatars \
         storage/app/public/articles \
         storage/app/public/products \
         storage/app/public/avatars-pending

# ── Migrations (idempotent) ───────────────────────────
echo "[init] running migrations..."
php artisan migrate --force

# ── Permissions ───────────────────────────────────────
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# Marker for healthcheck
touch .bootstrap-complete

echo "[init] ready. Starting php-fpm..."
exec "$@"
