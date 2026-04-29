# Інструкція з розгортання на cPanel

## Стек

- **Laravel 12** — обслуговує і фронтенд (Blade), і API
- **PHP 8.2** з Sanctum
- **MySQL 8.0**

Фронтенд рендериться через Blade-шаблони — єдиний додаток, одне розгортання.

---

## Вимоги до хостингу

| Вимога | Як перевірити |
|---|---|
| PHP 8.2+ | cPanel → «Вибір версії PHP» |
| MySQL 8.0+ | cPanel → «Бази даних MySQL» |
| SSH / Термінал | cPanel → «Термінал» або SSH-доступ |
| mod_rewrite | зазвичай є за замовчуванням |

---

## Структура файлів на сервері

```
/home/CPANEL_USER/
│
├── borove.com.ua/          ← document root (тільки public/ від Laravel)
│   ├── index.php           ← єдина точка входу
│   ├── .htaccess
│   └── storage/            ← символічне посилання (крок 6)
│
└── borove_app/             ← весь Laravel (НЕ в borove.com.ua — безпечно)
    ├── app/
    ├── resources/views/    ← Blade-шаблони
    ├── public/             ← css/, js/, img/, images/
    ├── routes/
    ├── storage/
    ├── vendor/
    ├── .env
    └── artisan
```

---

## Крок 1 — Підготовка файлів локально

Переконайтесь що Docker запущений і `backend/` зібрано:

```powershell
cd "c:\Users\hwalk\OneDrive\Робочий стіл\borove"
docker compose up -d
# зачекайте поки app-контейнер стане healthy
```

Папка `backend/` — це повний Laravel з `vendor/`, views, assets.

---

## Крок 2 — Завантаження Laravel

Підключіться через **FTP/SFTP** (FileZilla) або cPanel → «Файловий менеджер».

Створіть папку `borove_app/` поряд з `borove.com.ua/`.

Завантажте вміст `backend/` у `borove_app/`:
```
app/
bootstrap/
config/
database/
resources/
routes/
storage/
vendor/
artisan
composer.json
```

> Папку `backend/public/` не завантажуйте сюди — її вміст піде в `borove.com.ua/` окремо.

---

## Крок 3 — Завантаження public

Завантажте вміст `backend/public/` у `borove.com.ua/`:
```
index.php
.htaccess
css/
js/
img/
images/
```

---

## Крок 4 — Налаштування `index.php`

Відредагуйте `borove.com.ua/index.php` — змініть шляхи до Laravel:

```php
<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../borove_app/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../borove_app/vendor/autoload.php';

$app = require_once __DIR__.'/../borove_app/bootstrap/app.php';

$app->handleRequest(Request::capture());
```

---

## Крок 5 — База даних

cPanel → **«Бази даних MySQL»**:

1. Створіть базу (наприклад `cpanelusername_borove`)
2. Створіть користувача з паролем
3. Надайте **всі привілеї**

---

## Крок 6 — Файл `.env`

Створіть `borove_app/.env`:

```env
APP_NAME="Борове"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://borove.com.ua

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cpanelusername_borove
DB_USERNAME=cpanelusername_borove
DB_PASSWORD=YOUR_DB_PASSWORD

CACHE_STORE=file
SESSION_DRIVER=file
SESSION_LIFETIME=120

FILESYSTEM_DISK=public

SANCTUM_STATEFUL_DOMAINS=borove.com.ua
SESSION_DOMAIN=.borove.com.ua
```

---

## Крок 7 — Термінал (SSH)

cPanel → **«Термінал»**:

```bash
cd ~/borove_app

# Генерація ключа
php artisan key:generate

# Таблиці БД
php artisan migrate --force

# Початкові дані
php artisan db:seed --force

# Права
chmod -R 775 storage bootstrap/cache

# Символічне посилання для завантажених файлів
ln -s ~/borove_app/storage/app/public ~/borove.com.ua/storage
```

---

## Крок 8 — Призначити адміністратора

```bash
cd ~/borove_app
php artisan tinker --execute="App\Models\User::where('email', 'YOUR@EMAIL.COM')->update(['is_admin' => 1]);"
```

---

## Крок 9 — Перевірка

- `https://borove.com.ua` — головна сторінка
- `https://borove.com.ua/shop` — базар
- `https://borove.com.ua/api/articles` — JSON

---

## Часті проблеми

| Симптом | Рішення |
|---|---|
| 500 Internal Server Error | `borove_app/storage/logs/laravel.log` |
| «No application encryption key» | `php artisan key:generate` |
| Фото не відображаються | Перевірте символічне посилання `borove.com.ua/storage` |
| «Access denied» до БД | Перевірте `DB_*` у `.env` |
| Сторінка 404 | Перевірте `.htaccess`, чи увімкнено `mod_rewrite` |

---

## Оновлення

**Views (шаблони сторінок):**
Завантажте змінені файли в `borove_app/resources/views/` — зміни активні одразу.

**CSS/JS/img:**
Завантажте в `borove.com.ua/css/`, `borove.com.ua/js/` тощо.

**PHP (контролери, моделі):**
```bash
cd ~/borove_app && php artisan config:clear && php artisan cache:clear
```

**Нові міграції:**
```bash
cd ~/borove_app && php artisan migrate --force
```
