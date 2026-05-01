<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#1A4731">
  <meta name="description" content="@yield('description', 'Офіційний сайт села Борове — новини, оголошення, попутки')">
  <title>@yield('title', 'Борове — Сільська газета')</title>

  @hasSection('noindex')
    <meta name="robots" content="noindex, nofollow">
  @else
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('og_title', View::getSection('title') ?: 'Борове — Сільська громада')">
    <meta property="og:description" content="@yield('og_description', View::getSection('description') ?: 'Офіційний сайт села Борове')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', url('/img/header-village.jpg'))">
    <meta property="og:locale" content="uk_UA">
    <meta property="og:site_name" content="Борове">
    <meta name="twitter:card" content="summary_large_image">
  @endif

  @hasSection('jsonld')
    <script type="application/ld+json">@yield('jsonld')</script>
  @endif

  <link rel="icon" href="/favicon.ico" sizes="any">
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="/apple-touch-icon.png">
  <link rel="manifest" href="/site.webmanifest">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="/css/style.css?v={{ filemtime(public_path('css/style.css')) }}">
</head>
<body>

<header class="site-header">
  <div class="header-bg">
    <img src="/img/header-village.jpg" alt="Краєвид села Борове" loading="eager">
  </div>
  <div class="header-top">
    <a class="site-logo" href="/">
      <div class="logo-icon" aria-hidden="true">&#127807;</div>
      <div>
        <span class="logo-text-main">Борове</span>
        <span class="logo-text-sub">Сільська громада</span>
      </div>
    </a>
    <div class="header-date" id="headerDate"></div>
  </div>
  <div class="header-stats-bar">
    <div class="header-tagline">&#128205; Рівненська область · Зарічненський район</div>
    <div class="header-stats">
      <div class="h-stat"><strong>~5500</strong><span>Мешканців</span></div>
      <div class="h-stat"><strong>1600</strong><span>Рік заснування</span></div>
      <div class="h-stat"><strong>94,74</strong><span>км² площа</span></div>
    </div>
  </div>
</header>

<nav class="site-nav" aria-label="Головне меню">
  <button class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="siteMenu" aria-label="Відкрити меню">&#9776;</button>
  <ul id="siteMenu" role="list">
    <li><a href="/">&#127968; Головна</a></li>
    <li><a href="/announcements">&#128203; Оголошення</a></li>
    <li><a href="/rides">&#128664; Попутки</a></li>
    <li><a href="/gallery">&#128247; Галерея</a></li>
    <li><a href="/shop">&#128717; Базар</a></li>
    <li id="navAuthLi"><a href="/auth">&#128100; Увійти</a></li>
  </ul>
</nav>

@yield('content')

<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-col">
        <h4>&#127807; Борове</h4>
        <p>Офіційний інформаційний ресурс сільської громади Борове. Тут ви знайдете новини, оголошення та інформацію про жителів.</p>
      </div>
      <div class="footer-col">
        <h4>Навігація</h4>
        <ul>
          <li><a href="/">&#127968; Головна</a></li>
          <li><a href="/announcements">&#128203; Оголошення</a></li>
          <li><a href="/rides">&#128664; Попутки</a></li>
          <li><a href="/gallery">&#128247; Галерея</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Контакти</h4>
        <ul>
          <li>&#128205; вул. Центральна, 1</li>
          <li>&#128222; (067) 123-45-67</li>
          <li>&#128338; Пн–Пт: 9:00–17:00</li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      &copy; {{ date('Y') }} Борове. Всі права захищені.
    </div>
  </div>
</footer>

<div id="toast" class="toast" role="status" aria-live="polite"></div>
@stack('modals')
<script src="/js/main.js?v={{ filemtime(public_path('js/main.js')) }}"></script>
</body>
</html>
