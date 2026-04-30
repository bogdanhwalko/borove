# Розгортання на cPanel

Покрокова інструкція для деплою сайту «Борове» на типовий shared-хостинг із cPanel.

## Стек

- **Laravel 11/12** (PHP 8.2+) — Blade-шаблони + REST API
- **MySQL 8.0** (підійде MySQL 5.7+ або MariaDB 10.4+)
- **Apache + .htaccess** (cPanel зазвичай Apache; nginx-конфіг з `docker/` не використовується)
- **Sanctum** для токен-автентифікації

---

## 0. Що знадобиться

- **Доступ до cPanel**: File Manager / FTP, MySQL Databases, PHP Version Selector.
- **SSH-доступ** *(бажано)* — щоб зручно запускати `composer` і `php artisan`. Якщо немає — є альтернатива (див. п.4).
- **PHP ≥ 8.2** з розширеннями: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd` (або `imagick`), `zip`.
- **Composer** на сервері або локально.
- **Домен / піддомен**, налаштований у cPanel.

---

## 1. База даних

1. У cPanel → **MySQL Databases** (або **MySQL Database Wizard**).
2. Створіть БД, наприклад `loginuser_borove`.
3. Створіть користувача з надійним паролем, наприклад `loginuser_borove`.
4. Додайте користувача до бази з **усіма правами**.
5. Запам'ятайте: `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`. Хост — зазвичай `localhost`.

---

## 2. Версія PHP та ліміти

1. **PHP Version Selector** → виберіть **PHP 8.2** (або новіше).
2. Увімкніть розширення зі списку п.0.
3. **Options** (PHP INI Editor):
   - `upload_max_filesize = 25M`
   - `post_max_size = 200M`
   - `max_file_uploads = 50`
   - `memory_limit = 256M`
   - `max_execution_time = 120`

> Якщо немає UI для php.ini — створіть у `public/.user.ini` (див. крок 8) ті самі значення.

---

## 3. Структура завантаження

Не кладіть **усю** Laravel-папку в `public_html`! Це відкриє `.env`, `vendor/`, `storage/` назовні. Дві стратегії:

### Стратегія A — рекомендована: домен на `public/`

У cPanel → **Domains** (або **Addon Domains** / **Subdomains**) → відредагуйте Document Root і вкажіть `borove.com.ua/public`.

```
/home/USER/
├── borove/                   ← Laravel-додаток (поза публічним)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/               ← створиться composer-ом
│   ├── .env
│   ├── artisan
│   └── public/               ← це Document Root домену
│       ├── index.php
│       ├── .htaccess
│       ├── css/
│       └── js/
```

### Стратегія B — якщо хост не дозволяє змінити Document Root

Розділяємо файли: `public/` → `public_html/`, решта → у братський каталог.

```
/home/USER/
├── borove-app/               ← Laravel-додаток
│   ├── app/, bootstrap/, ...
│   └── (без public/)
└── public_html/              ← був public/
    ├── index.php             ← треба підправити (див. нижче)
    ├── .htaccess
    ├── css/
    └── js/
```

У такому варіанті потрібно відредагувати `public_html/index.php`:

```php
require __DIR__.'/../borove-app/vendor/autoload.php';
$app = require_once __DIR__.'/../borove-app/bootstrap/app.php';
```

---

## 4. Завантаження коду

**Варіант 1 (через Git):** в SSH:
```bash
cd ~
git clone <ваш репозиторій> borove
cd borove
```

**Варіант 2 (через ZIP):** локально запакуйте папку `backend/` (або скопіюйте `backend-src/`) у zip, через File Manager **Upload** → **Extract**.

**Що копіювати:** весь репозиторій — він і є Laravel-додатком. Достатньо клонувати в потрібну папку. На сервері не потрібні: `docker/`, `docker-compose.yml`, `.git/`, `node_modules/`, `tests/`, `phpunit.xml`.

---

## 5. Залежності (Composer)

З SSH у корені проєкту:

```bash
composer install --no-dev --optimize-autoloader
```

**Без SSH:** виконайте `composer install` локально на своєму ПК і завантажте папку `vendor/` разом із кодом.

---

## 6. Налаштування `.env`

Скопіюйте `.env.example` → `.env` і відредагуйте:

```dotenv
APP_NAME="Борове"
APP_ENV=production
APP_KEY=                       # згенерується далі
APP_DEBUG=false
APP_URL=https://yourdomain.com

LOG_CHANNEL=stack
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=loginuser_borove
DB_USERNAME=loginuser_borove
DB_PASSWORD=ваш_пароль
DB_ENGINE=InnoDB

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

SANCTUM_STATEFUL_DOMAINS=yourdomain.com
SESSION_DOMAIN=yourdomain.com
```

Згенеруйте ключ:
```bash
php artisan key:generate
```

**Без SSH:** згенеруйте локально, скопіюйте значення `APP_KEY` у `.env`.

---

## 7. Міграції та кеш

```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Без SSH:** деякі cPanel-хости мають **«Setup PHP Worker»** або кнопку **«Run command»**. Альтернатива — створіть тимчасовий PHP-скрипт у `public/migrate.php`:

```php
<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->call('migrate', ['--force' => true]);
$kernel->call('storage:link');
echo "OK";
```

Відкрийте `https://yourdomain.com/migrate.php` один раз → побачите `OK` → **видаліть файл!**

---

## 8. `.user.ini` (якщо немає UI для php.ini)

Створіть `public/.user.ini`:

```ini
upload_max_filesize = 25M
post_max_size = 200M
max_file_uploads = 50
memory_limit = 256M
```

---

## 9. Security headers (заміна nginx-конфігу)

У `public/.htaccess` додайте на початок (поруч з існуючим `<IfModule mod_rewrite.c>`):

```apache
<IfModule mod_headers.c>
  Header always set X-Frame-Options "SAMEORIGIN"
  Header always set X-Content-Type-Options "nosniff"
  Header always set Referrer-Policy "strict-origin-when-cross-origin"
  Header always set Content-Security-Policy "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: blob: https://picsum.photos https://*.picsum.photos; connect-src 'self' https://api.open-meteo.com; frame-ancestors 'self';"
</IfModule>
```

---

## 10. SSL / HTTPS

У cPanel → **SSL/TLS Status** → **Run AutoSSL** (Let's Encrypt). Або встановіть свій сертифікат вручну.

Після увімкнення SSL — додайте у `.htaccess` редірект:

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteCond %{HTTPS} off
  RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
```

---

## 11. Права доступу

Через File Manager або SSH:
```bash
chmod -R 755 storage bootstrap/cache
chown -R USER:USER storage bootstrap/cache
```

(заміни `USER` на ім'я cPanel-юзера)

---

## 12. Створення першого адміна

В phpMyAdmin → таблиця `users` → відредагувати ваш запис → `is_admin = 1`.

Або через SSH:
```bash
php artisan tinker
>>> \App\Models\User::where('phone','0671234567')->update(['is_admin' => 1]);
```

---

## 13. Перевірка

Відкрийте `https://yourdomain.com`:
- ✅ Головна завантажилась з шапкою, погодою, списками
- ✅ `/announcements`, `/rides`, `/gallery`, `/shop` працюють
- ✅ Реєстрація + логін працюють
- ✅ Завантаження фото в оголошення/альбоми працює (>2МБ перевірка)
- ✅ В DevTools немає 404, помилок CSP, PHP-warnings

Якщо щось не так:
- `storage/logs/laravel.log` — логи Laravel
- cPanel → **Errors** — Apache error_log
- `php artisan optimize:clear` — скинути кеш конфігу/виглядів

---

## 14. Оновлення (deploy v2)

```bash
cd ~/borove
git pull                                     # або заливаємо нові файли
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Часті проблеми

| Симптом | Причина / фікс |
|---|---|
| **500 Internal Server Error**, на сторінці нічого | Перевір `storage/logs/laravel.log`. Часто — права на `storage/`, чи відсутня `APP_KEY`. |
| **«No application encryption key has been specified»** | Запусти `php artisan key:generate` і скопіюй у `.env`. |
| **«The image failed to upload»** | PHP-ліміти: `upload_max_filesize` / `post_max_size` замалі. Див. п.2. |
| **404 на всіх сторінках, крім головної** | mod_rewrite вимкнений або Document Root не на `public/`. Див. п.3. |
| **CSS/JS повертають 404** | Document Root неправильний. Перевір, що бачиш `public/index.php`, а не корінь Laravel. |
| **«Database connection refused»** | Перевір `.env`: `DB_HOST=localhost`, ім'я БД має префікс кешерського юзера. |
| **Бачу `.env` у браузері** | Document Root показує корінь Laravel замість `public/` — терміново виправляйте! Це уразливість. |
| **Помилки CSP у консолі** | Заголовок CSP неправильно скопійований у `.htaccess` (з `;` замість одного рядка). Або браузер закешував стару версію — `Ctrl+Shift+R`. |
| **«storage/photos/...» 404** | Не запущено `php artisan storage:link`. |

---

## Безпека

Перед публікацією перевір:
- [ ] `APP_DEBUG=false` у проді
- [ ] `APP_ENV=production`
- [ ] `.env` **не у public_html** (Стратегія A) або заборонено `<Files .env>`
- [ ] `composer install --no-dev` (без розробницьких пакетів)
- [ ] HTTPS увімкнено + redirect
- [ ] Security headers у `.htaccess`
- [ ] `is_admin` поставлено лише на доменних адмінів
- [ ] Резервна копія БД налаштована (cPanel → **Backup**)

---

## Що НЕ потрібно деплоїти

- `docker/`, `docker-compose.yml` — лише для локальної розробки
- `.git/`, `node_modules/`, `tests/`, `phpunit.xml`
- `.bootstrap-complete` — маркер Docker-init
