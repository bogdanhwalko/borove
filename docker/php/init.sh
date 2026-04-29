#!/bin/bash
# =====================================================
# Auto-bootstrap script for the Laravel container
# Runs on every container start, does heavy setup only on first launch
# =====================================================

cd /var/www/html

# Existing working install but no marker → just mark it
if [ -f "artisan" ] && [ ! -f ".bootstrap-complete" ]; then
    echo "[init] existing Laravel detected — marking as complete"
    touch .bootstrap-complete
fi

# First-run (or broken state) → full bootstrap
if [ ! -f ".bootstrap-complete" ]; then

    echo "[init] ============================================"
    echo "[init]  First-time setup — Laravel + Sanctum"
    echo "[init]  This takes 3-7 minutes (one-time only)"
    echo "[init] ============================================"

    echo "[init] cleaning target directory..."
    rm -rf /var/www/html/* /var/www/html/.[!.]* 2>/dev/null || true

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
    echo "[init] MySQL is ready."

    echo "[init] installing Laravel..."
    composer create-project laravel/laravel . \
        --prefer-dist --no-interaction --no-progress || { echo "[init] composer failed"; exit 1; }

    echo "[init] installing Sanctum..."
    composer require laravel/sanctum --no-interaction --no-progress || { echo "[init] sanctum failed"; exit 1; }

    echo "[init] copying custom application files..."
    cp -f /backend-src/.env.template .env
    cp -f /backend-src/bootstrap/app.php bootstrap/app.php

    cp -f /backend-src/routes/api.php routes/api.php
    cp -f /backend-src/routes/web.php routes/web.php

    mkdir -p resources/views/layouts resources/views/pages
    cp -rf /backend-src/resources/views/. resources/views/

    mkdir -p public/css public/js public/img public/images
    cp -rf /backend-src/public/css/.  public/css/  2>/dev/null || true
    cp -rf /backend-src/public/js/.   public/js/   2>/dev/null || true
    cp -rf /backend-src/public/img    public/      2>/dev/null || true
    cp -rf /backend-src/public/images public/      2>/dev/null || true

    cp -f /backend-src/app/Models/User.php         app/Models/User.php
    cp -f /backend-src/app/Models/Article.php      app/Models/Article.php
    cp -f /backend-src/app/Models/Announcement.php app/Models/Announcement.php
    cp -f /backend-src/app/Models/Ride.php         app/Models/Ride.php
    cp -f /backend-src/app/Models/Album.php        app/Models/Album.php
    cp -f /backend-src/app/Models/Photo.php        app/Models/Photo.php

    mkdir -p app/Http/Controllers
    cp -f /backend-src/app/Http/Controllers/AuthController.php         app/Http/Controllers/AuthController.php
    cp -f /backend-src/app/Http/Controllers/ArticleController.php      app/Http/Controllers/ArticleController.php
    cp -f /backend-src/app/Http/Controllers/AnnouncementController.php app/Http/Controllers/AnnouncementController.php
    cp -f /backend-src/app/Http/Controllers/RideController.php         app/Http/Controllers/RideController.php
    cp -f /backend-src/app/Http/Controllers/AlbumController.php          app/Http/Controllers/AlbumController.php
    cp -f /backend-src/app/Http/Controllers/AdminController.php          app/Http/Controllers/AdminController.php
    cp -f /backend-src/app/Http/Controllers/AnnouncementController.php   app/Http/Controllers/AnnouncementController.php
    cp -f /backend-src/app/Http/Controllers/UserAlbumController.php      app/Http/Controllers/UserAlbumController.php
    cp -f /backend-src/app/Http/Controllers/MarketController.php         app/Http/Controllers/MarketController.php
    cp -f /backend-src/app/Http/Controllers/UserShopController.php       app/Http/Controllers/UserShopController.php
    cp -f /backend-src/app/Models/Shop.php          app/Models/Shop.php
    cp -f /backend-src/app/Models/Product.php       app/Models/Product.php
    cp -f /backend-src/app/Models/PurchaseRequest.php app/Models/PurchaseRequest.php

    rm -f database/migrations/*_create_users_table.php
    cp -f /backend-src/database/migrations/2026_04_25_000000_create_users_table.php         database/migrations/
    cp -f /backend-src/database/migrations/2026_04_25_000001_create_articles_table.php      database/migrations/
    cp -f /backend-src/database/migrations/2026_04_25_000002_create_announcements_table.php database/migrations/
    cp -f /backend-src/database/migrations/2026_04_25_000003_create_rides_table.php          database/migrations/
    cp -f /backend-src/database/migrations/2026_04_25_000004_create_albums_table.php         database/migrations/
    cp -f /backend-src/database/migrations/2026_04_25_000005_create_photos_table.php         database/migrations/
    cp -f /backend-src/database/migrations/2026_04_25_000006_add_is_admin_to_users.php        database/migrations/
    cp -f /backend-src/database/migrations/2026_04_25_000007_update_photos_for_upload.php     database/migrations/
    cp -f /backend-src/database/migrations/2026_04_25_000008_add_image_to_announcements.php   database/migrations/
    cp -f /backend-src/database/migrations/2026_04_25_000009_add_moderation_to_albums.php    database/migrations/
    cp -f /backend-src/database/migrations/2026_04_25_000010_add_cover_path_to_albums.php   database/migrations/
    cp -f /backend-src/database/migrations/2026_04_25_000011_create_shops_table.php         database/migrations/
    cp -f /backend-src/database/migrations/2026_04_25_000012_create_products_table.php      database/migrations/
    cp -f /backend-src/database/migrations/2026_04_25_000013_create_purchase_requests_table.php database/migrations/

    mkdir -p database/seeders
    cp -f /backend-src/database/seeders/DatabaseSeeder.php     database/seeders/DatabaseSeeder.php
    cp -f /backend-src/database/seeders/ArticleSeeder.php      database/seeders/ArticleSeeder.php
    cp -f /backend-src/database/seeders/AnnouncementSeeder.php database/seeders/AnnouncementSeeder.php
    cp -f /backend-src/database/seeders/RideSeeder.php         database/seeders/RideSeeder.php
    cp -f /backend-src/database/seeders/AlbumPhotoSeeder.php   database/seeders/AlbumPhotoSeeder.php

    echo "[init] generating app key & publishing Sanctum..."
    php artisan key:generate --force
    php artisan vendor:publish \
        --provider='Laravel\Sanctum\SanctumServiceProvider' --force
    php artisan storage:link --force
    mkdir -p storage/app/public/photos
    mkdir -p storage/app/public/announcements

    echo "[init] running migrations..."
    php artisan migrate --force

    echo "[init] seeding database..."
    php artisan db:seed --force

    echo "[init] fixing permissions..."
    chmod -R 775 storage bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache

    touch .bootstrap-complete

    echo "[init] ============================================"
    echo "[init]  Setup complete! Starting php-fpm..."
    echo "[init] ============================================"
fi

# Hand over to the original CMD (php-fpm)
exec "$@"
