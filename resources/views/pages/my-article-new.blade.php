@extends('layouts.app')

@section('title', 'Подати статтю — Борове')
@section('description', 'Подати статтю в Борове')
@section('noindex', 'true')

@section('content')

<div class="page-hero">
  <h1>&#128221; Подати статтю</h1>
  <p>Поділіться історією або новиною з громадою</p>
</div>

<main>
  <div class="container">
    <div style="max-width:760px;margin:0 auto;padding:32px 0">

      <div style="margin-bottom:20px">
        <a href="/profile" class="btn-read">&#8592; До профілю</a>
      </div>

      <div id="myArticleGate">
        <div class="empty-state"><div class="empty-icon">&#128274;</div><p>Перевірка доступу...</p></div>
      </div>

      <div id="myArticlePanel" style="display:none">

        <div id="myArticleNotice" class="rating-status" style="margin-bottom:16px;display:none">
          <span></span>
          <small></small>
        </div>

        <div class="add-form">
          <h3>&#9998; Нова стаття</h3>
          <form id="myArticleForm" novalidate>
            <div class="form-group">
              <label for="myArtTitle">Заголовок *</label>
              <input type="text" id="myArtTitle" placeholder="Цікава новина зі села..." required maxlength="255">
            </div>

            <div class="form-group">
              <label for="myArtCategory">Категорія *</label>
              <input type="text" id="myArtCategory" placeholder="Новини / Історія / Події / Люди..." required maxlength="100">
            </div>

            <div class="form-group">
              <label>Фото статті (необов'язково, до 10 МБ)</label>
              <label class="file-upload-label" id="myArtImageLabel" for="myArtImage">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <span id="myArtImageName">Вибрати фото</span>
              </label>
              <input type="file" id="myArtImage" accept="image/*" class="file-upload-input">
            </div>

            <div class="form-group">
              <label for="myArtSummary">Короткий опис (анотація) *</label>
              <textarea id="myArtSummary" placeholder="2-3 речення для головної сторінки..." required maxlength="500" style="min-height:70px"></textarea>
              <div class="form-hint" id="summaryCounter">0 / 500</div>
            </div>

            <div class="form-group">
              <label for="myArtBody">Текст статті *</label>
              <textarea id="myArtBody" placeholder="Повний текст (мінімум 200 символів)..." required style="min-height:280px"></textarea>
              <div class="form-hint" id="bodyCounter">0 символів (потрібно мінімум 200)</div>
            </div>

            <div class="admin-form-actions">
              <button type="submit" class="btn-submit">&#128190; Подати на публікацію</button>
            </div>
          </form>
        </div>

      </div>

    </div>
  </div>
</main>

@endsection
