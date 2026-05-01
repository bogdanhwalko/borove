@extends('layouts.app')

@section('title', 'Борове — Сільська газета')
@section('description', 'Офіційний сайт села Борове — новини, оголошення, попутки')

@section('content')

<div class="news-ticker" aria-label="Останні оголошення">
  <span class="ticker-inner" id="announcementTicker">
    <span class="ticker-label">Оголошення</span>
    Завантаження останніх оголошень...
  </span>
</div>

<main>
  <div class="container">
    <div class="main-layout">

      <section class="content-main" aria-label="Новини та статті">

        <div id="featuredArticle"></div>

        <hr class="divider">
        <h2 class="section-title">
          <span class="icon">&#128664;</span> Попутки
          <a href="/rides" class="btn-read" style="margin-left:auto;font-size:.8rem">Всі попутки &#8594;</a>
        </h2>
        <div id="indexRideList">
          <div class="empty-state"><div class="empty-icon">&#128664;</div><p>Завантаження...</p></div>
        </div>

        <hr class="divider">
        <h2 class="section-title">
          <span class="icon">&#128717;</span> Базар
          <a href="/shop" class="btn-read" style="margin-left:auto;font-size:.8rem">Всі товари &#8594;</a>
        </h2>
        <div id="homeProductGrid">
          <div class="empty-state"><div class="empty-icon">&#128717;</div><p>Завантаження...</p></div>
        </div>

        <hr class="divider">
        <h2 class="section-title">
          <span class="icon">&#128247;</span> Фотогалерея
          <a href="/gallery" class="btn-read" style="margin-left:auto;font-size:.8rem">Всі альбоми &#8594;</a>
        </h2>
        <div class="album-grid" id="indexAlbumGrid">
          <div class="empty-state"><div class="empty-icon">&#128247;</div><p>Завантаження...</p></div>
        </div>

        <hr class="divider">
        <h2 class="section-title">
          <span class="icon">&#128240;</span> Останні новини
          <a href="/articles" class="btn-read" style="margin-left:auto;font-size:.8rem">Всі статті &#8594;</a>
        </h2>
        <div class="article-grid" id="indexArticleGrid">
          <div class="empty-state"><div class="empty-icon">&#128240;</div><p>Завантаження...</p></div>
        </div>

      </section>

      <aside class="content-sidebar" aria-label="Бічна панель">

        <div class="widget fade-in">
          <div class="widget-header">&#9925; Погода в Боровому</div>
          <div id="weatherWidgetBody" class="widget-body">
            <div class="weather-loading">&#8987; Завантаження...</div>
          </div>
        </div>

        <div class="widget fade-in">
          <div class="widget-header">&#128203; Оголошення</div>
          <div class="widget-body" id="sideAnnList">
            <div class="empty-state"><div class="empty-icon">&#128203;</div><p>Завантаження...</p></div>
          </div>
          <div style="padding:0 16px 14px;text-align:right">
            <a href="/announcements" class="btn-read">Всі оголошення &#8594;</a>
          </div>
        </div>

        <div class="widget fade-in" id="topProductWidget" style="display:none">
          <div class="widget-header">&#128293; Топ товар</div>
          <div id="topProductBody" class="top-product-wrap"></div>
        </div>

        <div class="widget fade-in widget--feedback">
          <div class="widget-header">&#9993; Зворотний зв'язок з адміном</div>
          <div class="widget-body">
            <div id="feedbackAuthNotice" class="form-auth-notice feedback-auth-notice">
              <div class="form-auth-icon">&#128274;</div>
              <p>Щоб написати адміну, потрібно <a href="/auth">увійти</a></p>
            </div>
            <form id="feedbackForm" class="feedback-form" style="display:none" novalidate>
              <div class="feedback-profile">
                <div class="feedback-profile-row">
                  <span>Відправник</span>
                  <strong id="feedbackProfileName"></strong>
                </div>
                <div class="feedback-profile-row">
                  <span>Контакт</span>
                  <strong id="feedbackProfileContact"></strong>
                </div>
              </div>
              <div class="form-group">
                <label for="feedbackMessage">Повідомлення *</label>
                <textarea id="feedbackMessage" name="message" maxlength="2000" required placeholder="Напишіть питання або пропозицію..."></textarea>
              </div>
              <button type="submit" class="btn-submit">Надіслати</button>
              <p id="feedbackStatus" class="feedback-status" role="status" aria-live="polite"></p>
            </form>
          </div>
        </div>

      </aside>
    </div>
  </div>
</main>

@endsection
