@extends('layouts.app')

@section('title', 'Фотогалерея | Борове')
@section('description', 'Фотогалерея села Борове — всі альбоми')

@section('content')

<div class="page-hero">
  <h1>&#128247; Фотогалерея</h1>
  <p>Фотолітопис нашої громади — свята, події та повсякдення</p>
</div>

<div id="gallerySubmitWrap" style="display:none">
  <div class="container">
    <div id="gallerySubmitForm" class="gallery-submit-form">
      <div class="add-form">
        <h3>&#128247; Новий фотоальбом</h3>
        <p class="gallery-submit-note">Після публікації альбом буде відправлено на модерацію. Адмін перевірить і опублікує його.</p>
        <form id="userAlbumForm" novalidate>
          <div class="form-group">
            <label for="uaTitle">Назва альбому *</label>
            <input type="text" id="uaTitle" placeholder="напр. Великдень 2026" required maxlength="200">
          </div>
          <div class="form-group">
            <label for="uaDate">Дата події *</label>
            <input type="date" id="uaDate" required>
          </div>
          <div class="form-group">
            <label for="uaDesc">Опис (необов'язково)</label>
            <textarea id="uaDesc" placeholder="Короткий опис події або місця..." maxlength="500"></textarea>
          </div>
          <div class="form-group">
            <label>Фотографії * (до 30 фото, кожне до 20 МБ)</label>
            <label class="file-upload-label file-upload-label--multi" id="uaPhotosLabel" for="uaPhotos">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
              <span id="uaPhotosName">Вибрати фото (можна кілька)</span>
            </label>
            <input type="file" id="uaPhotos" name="photos" accept="image/*" multiple class="file-upload-input">
          </div>
          <div id="uaPreview" class="ua-preview" style="display:none"></div>
          <button type="submit" class="btn-submit">&#128228; Відправити на модерацію</button>
        </form>
      </div>
    </div>
  </div>
</div>

<main>
  <div class="container">
    <div style="display:flex;justify-content:flex-end;padding:24px 0 0">
      <button id="gallerySubmitToggle" class="gallery-submit-toggle-btn" style="display:none" aria-expanded="false" aria-controls="gallerySubmitWrap">
        &#43; Додати фотоальбом
      </button>
    </div>
    <div class="album-grid" id="galleryGrid" style="padding:16px 0 24px">
      <div class="empty-state"><div class="empty-icon">&#128247;</div><p>Завантаження...</p></div>
    </div>
    <div id="galleryPagination" style="padding-bottom:48px"></div>
  </div>
</main>

@endsection
