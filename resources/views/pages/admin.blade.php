@extends('layouts.app')

@section('title', 'Адмін — Борове')
@section('description', 'Адміністрування сайту Борове')
@section('noindex', 'true')

@section('content')

<div class="page-hero">
  <h1>&#9881;&#65039; Адміністрування</h1>
  <p>Управління контентом сайту</p>
</div>

<main>
  <div class="container">

    <div id="adminGate">
      <div class="empty-state"><div class="empty-icon">&#128274;</div><p>Перевірка доступу...</p></div>
    </div>

    <div id="adminPanel" style="display:none">

      <div class="admin-tabs">
        <button class="admin-tab active" data-tab="articles">&#128221; Статті</button>
        <button class="admin-tab" data-tab="gallery">&#128247; Галерея</button>
        <button class="admin-tab" data-tab="moderation" id="tabModerationBtn">
          &#128276; Модерація <span id="pendingBadge" class="pending-badge"></span>
        </button>
        <button class="admin-tab" data-tab="profiles" id="tabProfilesBtn">
          &#128100; Профілі <span id="profilesBadge" class="pending-badge"></span>
        </button>
        <button class="admin-tab" data-tab="feedback" id="tabFeedbackBtn">
          &#9993; Звернення <span id="feedbackBadge" class="pending-badge"></span>
        </button>
      </div>

      <div class="admin-section" id="tabArticles">
        <div class="admin-layout">

          <div class="admin-list-wrap">
            <div class="admin-list-header">
              <h3>Статті</h3>
              <button class="btn-admin-new" id="btnNewArticle">&#43; Нова стаття</button>
            </div>
            <div id="adminArticleList"><p class="admin-loading">Завантаження...</p></div>
            <div id="adminArticlePagination"></div>
          </div>

          <div class="admin-form-wrap">
            <div class="add-form" id="articleFormCard">
              <h3 id="articleFormTitle">&#128221; Нова стаття</h3>
              <form id="articleForm" novalidate>
                <input type="hidden" id="articleId">
                <div class="form-group">
                  <label for="artTitle">Заголовок *</label>
                  <input type="text" id="artTitle" placeholder="Назва статті" required maxlength="255">
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label for="artCategory">Категорія *</label>
                    <input type="text" id="artCategory" placeholder="Новини" required maxlength="100">
                  </div>
                  <div class="form-group">
                    <label for="artAuthor">Автор *</label>
                    <input type="text" id="artAuthor" placeholder="Ім'я автора" required maxlength="100">
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label for="artDate">Дата публікації</label>
                    <input type="date" id="artDate">
                  </div>
                  <div class="form-group">
                    <label for="artImageSeed">Picsum seed (запасний варіант)</label>
                    <input type="text" id="artImageSeed" placeholder="village-autumn" maxlength="100">
                  </div>
                </div>
                <div class="form-group">
                  <label>Фото статті (необов'язково, до 10 МБ)</label>
                  <label class="file-upload-label" id="artImageLabel" for="artImage">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    <span id="artImageName">Вибрати фото</span>
                  </label>
                  <input type="file" id="artImage" accept="image/*" class="file-upload-input">
                  <div id="artImageCurrent" class="art-image-current" style="display:none">
                    <img id="artImageCurrentPreview" alt="Поточне фото">
                    <button type="button" class="btn-cancel" id="artImageRemove" style="margin-left:auto">&#10005; Видалити фото</button>
                  </div>
                </div>
                <div class="form-group">
                  <label for="artSummary">Короткий опис *</label>
                  <textarea id="artSummary" placeholder="Анотація статті (до 500 символів)..." required maxlength="500" style="min-height:70px"></textarea>
                </div>
                <div class="form-group">
                  <label for="artBody">Текст статті *</label>
                  <textarea id="artBody" placeholder="Повний текст статті (підтримується HTML)..." required style="min-height:220px"></textarea>
                </div>
                <div class="admin-form-actions">
                  <button type="submit" class="btn-submit">&#128190; Зберегти</button>
                  <button type="button" class="btn-cancel" id="btnCancelArticle">Скасувати</button>
                </div>
              </form>
            </div>
          </div>

        </div>
      </div>

      <div class="admin-section" id="tabModeration" style="display:none">
        <div class="mod-subtabs" role="tablist">
          <button class="mod-subtab active" data-modtab="albums">&#128247; Альбоми <span class="pending-badge" id="modBadgeAlbums"></span></button>
          <button class="mod-subtab" data-modtab="announcements">&#128203; Оголошення <span class="pending-badge" id="modBadgeAnn"></span></button>
          <button class="mod-subtab" data-modtab="rides">&#128664; Попутки <span class="pending-badge" id="modBadgeRides"></span></button>
          <button class="mod-subtab" data-modtab="products">&#128717; Товари <span class="pending-badge" id="modBadgeProducts"></span></button>
          <button class="mod-subtab" data-modtab="articles">&#128221; Статті <span class="pending-badge" id="modBadgeArticles"></span></button>
        </div>

        <div class="mod-subsection" id="modSecAlbums">
          <div id="adminPendingList"><p class="admin-loading">Завантаження...</p></div>
          <div id="adminPendingPagination"></div>
        </div>
        <div class="mod-subsection" id="modSecAnnouncements" style="display:none">
          <div id="modListAnnouncements"><p class="admin-loading">Завантаження...</p></div>
        </div>
        <div class="mod-subsection" id="modSecRides" style="display:none">
          <div id="modListRides"><p class="admin-loading">Завантаження...</p></div>
        </div>
        <div class="mod-subsection" id="modSecProducts" style="display:none">
          <div id="modListProducts"><p class="admin-loading">Завантаження...</p></div>
        </div>
        <div class="mod-subsection" id="modSecArticles" style="display:none">
          <div id="modListArticles"><p class="admin-loading">Завантаження...</p></div>
        </div>
      </div>

      <div class="admin-section" id="tabProfiles" style="display:none">
        <div id="adminProfilesList"><p class="admin-loading">Завантаження...</p></div>
        <div id="adminProfilesPagination"></div>
      </div>

      <div class="admin-section" id="tabFeedback" style="display:none">
        <div class="admin-list-header">
          <h3>Звернення з форми зворотного зв'язку</h3>
        </div>
        <div id="adminFeedbackList"><p class="admin-loading">Завантаження...</p></div>
        <div id="adminFeedbackPagination"></div>
      </div>

      <div class="admin-section" id="tabGallery" style="display:none">
        <div class="admin-layout">

          <div class="admin-list-wrap">
            <div class="admin-list-header">
              <h3>Опубліковані альбоми</h3>
              <button class="btn-admin-new" id="btnShowAlbumForm">&#43; Новий альбом</button>
            </div>
            <div id="adminAlbumList"><p class="admin-loading">Завантаження...</p></div>
            <div id="adminAlbumPagination"></div>
          </div>

          <div class="admin-form-wrap">

            <div class="add-form" id="albumFormCard" style="display:none">
              <h3>&#128194; Новий альбом</h3>
              <form id="albumForm" novalidate>
                <div class="form-group">
                  <label for="albumTitle">Назва *</label>
                  <input type="text" id="albumTitle" placeholder="Назва альбому" required maxlength="200">
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label for="albumDate">Дата</label>
                    <input type="date" id="albumDate">
                  </div>
                  <div class="form-group">
                    <label for="albumCoverSeed">Обкладинка (picsum seed)</label>
                    <input type="text" id="albumCoverSeed" placeholder="village" maxlength="100">
                  </div>
                </div>
                <div class="admin-form-actions">
                  <button type="submit" class="btn-submit">&#128194; Створити</button>
                  <button type="button" class="btn-cancel" id="btnCancelAlbum">Скасувати</button>
                </div>
              </form>
            </div>

            <div class="add-form" id="photoManagerCard" style="display:none">
              <h3 id="photoManagerTitle">&#128247; Фото альбому</h3>
              <div id="adminPhotoGrid" class="admin-photo-grid"></div>
              <form id="photoUploadForm" style="margin-top:20px">
                <input type="hidden" id="uploadAlbumId">
                <div class="form-group">
                  <label for="photoFile">&#128247; Вибрати фото *</label>
                  <input type="file" id="photoFile" accept="image/*" required>
                </div>
                <div class="form-group">
                  <label for="photoCaption">Підпис до фото</label>
                  <input type="text" id="photoCaption" placeholder="Короткий опис..." maxlength="200">
                </div>
                <button type="submit" class="btn-submit">&#128228; Завантажити</button>
              </form>
            </div>

            <div id="galleryPlaceholder" class="admin-placeholder">
              <div class="empty-icon">&#128247;</div>
              <p>Виберіть альбом зі списку, щоб керувати фото</p>
            </div>

          </div>
        </div>
      </div>

    </div>
  </div>
</main>

@endsection
