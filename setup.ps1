# =====================================================
# Борове — Bootstrap script for Windows PowerShell
# Вимоги: Docker Desktop запущений
# Запуск: .\setup.ps1
# =====================================================
$ErrorActionPreference = "Stop"

Write-Host "▶ Building containers..." -ForegroundColor Cyan
docker compose build --no-cache

Write-Host "▶ Starting DB..." -ForegroundColor Cyan
docker compose up -d db

Write-Host "  Waiting for MySQL to be ready..." -ForegroundColor Gray
$ready = $false
for ($i = 0; $i -lt 30; $i++) {
    $result = docker compose exec db mysqladmin ping -h localhost -u root -psecret --silent 2>$null
    if ($LASTEXITCODE -eq 0) { $ready = $true; break }
    Start-Sleep -Seconds 3
}
if (-not $ready) { Write-Error "MySQL did not start in time."; exit 1 }

Write-Host "▶ Creating Laravel project in backend/..." -ForegroundColor Cyan
if (-not (Test-Path "backend")) { New-Item -ItemType Directory -Path "backend" | Out-Null }
docker compose run --rm --no-deps app `
    composer create-project laravel/laravel /var/www/html `
    --prefer-dist --no-interaction --quiet

Write-Host "▶ Installing Laravel Sanctum..." -ForegroundColor Cyan
docker compose run --rm --no-deps app bash -c `
    "cd /var/www/html && composer require laravel/sanctum --no-interaction --quiet"

Write-Host "▶ Copying custom application files..." -ForegroundColor Cyan
docker compose run --rm --no-deps app bash -c @"
cp -f /backend-src/routes/api.php              /var/www/html/routes/api.php &&
cp -f /backend-src/app/Models/User.php         /var/www/html/app/Models/User.php &&
mkdir -p /var/www/html/app/Http/Controllers &&
cp -f /backend-src/app/Http/Controllers/AuthController.php /var/www/html/app/Http/Controllers/AuthController.php &&
rm -f /var/www/html/database/migrations/*_create_users_table.php &&
cp -f /backend-src/database/migrations/2026_04_25_000000_create_users_table.php /var/www/html/database/migrations/ &&
cp -f /backend-src/.env.template /var/www/html/.env
"@

Write-Host "▶ Publishing Sanctum & generating key..." -ForegroundColor Cyan
docker compose run --rm --no-deps app bash -c @"
cd /var/www/html &&
php artisan key:generate &&
php artisan vendor:publish --provider='Laravel\Sanctum\SanctumServiceProvider' --force --quiet
"@

Write-Host "▶ Running migrations..." -ForegroundColor Cyan
docker compose run --rm app bash -c "cd /var/www/html && php artisan migrate --force"

Write-Host "▶ Fixing permissions..." -ForegroundColor Cyan
docker compose run --rm --no-deps app bash -c `
    "chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache"

Write-Host "▶ Starting all services..." -ForegroundColor Cyan
docker compose up -d

Write-Host ""
Write-Host "✅ Done!" -ForegroundColor Green
Write-Host "   Frontend + API : http://localhost:8000" -ForegroundColor White
Write-Host "   phpMyAdmin     : http://localhost:8080" -ForegroundColor White
Write-Host ""
