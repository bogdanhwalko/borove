@extends('layouts.app')

@section('title', 'Мій профіль — Борове')
@section('description', 'Мій профіль — Борове')
@section('noindex', 'true')

@section('content')

<div class="page-hero">
  <h1>&#128100; Мій профіль</h1>
  <p>Налаштування облікового запису</p>
</div>

<main>
  <div class="container">

    <div id="profileGate">
      <div class="empty-state">
        <div class="empty-icon">&#128274;</div>
        <p>Щоб переглянути профіль, потрібно <a href="/auth">увійти</a></p>
      </div>
    </div>

    <div id="profilePanel" style="display:none">
      <div class="profile-layout">

        <div class="profile-sidebar">
          <div class="profile-avatar-wrap">
            <div class="profile-avatar" id="profileAvatarCircle">
              <span id="profileAvatarInitials"></span>
              <img id="profileAvatarImg" src="" alt="Фото профілю" style="display:none">
            </div>
            <label class="profile-avatar-btn" title="Змінити фото">
              &#128247;
              <input type="file" id="avatarFileInput" accept="image/*" style="display:none">
            </label>
          </div>
          <div class="profile-name" id="profileDisplayName"></div>
          <div class="profile-nickname" id="profileDisplayNick"></div>
        </div>

        <div class="profile-content">
          <div class="admin-tabs profile-tabs">
            <button class="admin-tab active" data-ptab="info">&#128100; Дані профілю</button>
            <button class="admin-tab" data-ptab="password">&#128272; Пароль</button>
            <button class="admin-tab" data-ptab="logs">&#128196; Журнал змін</button>
          </div>

          <div class="profile-tab-section" id="ptabInfo">
            <div class="add-form">
              <h3>&#9998; Редагувати профіль</h3>
              <form id="profileForm" novalidate>
                <div class="form-row">
                  <div class="form-group">
                    <label for="pfLastName">Прізвище</label>
                    <input type="text" id="pfLastName" maxlength="100" placeholder="Прізвище">
                  </div>
                  <div class="form-group">
                    <label for="pfFirstName">Ім'я</label>
                    <input type="text" id="pfFirstName" maxlength="100" placeholder="Ім'я">
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label for="pfPatronymic">По батькові</label>
                    <input type="text" id="pfPatronymic" maxlength="100" placeholder="По батькові">
                  </div>
                  <div class="form-group">
                    <label for="pfNickname">Нікнейм</label>
                    <input type="text" id="pfNickname" maxlength="50" placeholder="Нікнейм">
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label for="pfStreet">Вулиця</label>
                    <input type="text" id="pfStreet" maxlength="200" placeholder="Назва вулиці">
                  </div>
                  <div class="form-group">
                    <label for="pfPhone">Телефон</label>
                    <input type="tel" id="pfPhone" maxlength="10" placeholder="0XXXXXXXXX">
                  </div>
                </div>
                <div class="admin-form-actions">
                  <button type="submit" class="btn-submit">&#128190; Зберегти зміни</button>
                </div>
              </form>
            </div>
          </div>

          <div class="profile-tab-section" id="ptabPassword" style="display:none">
            <div class="add-form">
              <h3>&#128272; Змінити пароль</h3>
              <form id="passwordForm" novalidate>
                <div class="form-group">
                  <label for="pfCurrentPwd">Поточний пароль</label>
                  <input type="password" id="pfCurrentPwd" placeholder="Введіть поточний пароль" required>
                </div>
                <div class="form-group">
                  <label for="pfNewPwd">Новий пароль</label>
                  <input type="password" id="pfNewPwd" placeholder="Мінімум 8 символів" required minlength="8">
                </div>
                <div class="form-group">
                  <label for="pfNewPwdConfirm">Повторіть новий пароль</label>
                  <input type="password" id="pfNewPwdConfirm" placeholder="Повторіть новий пароль" required>
                </div>
                <div class="admin-form-actions">
                  <button type="submit" class="btn-submit">&#128272; Змінити пароль</button>
                </div>
              </form>
            </div>
          </div>

          <div class="profile-tab-section" id="ptabLogs" style="display:none">
            <div class="add-form">
              <h3>&#128196; Журнал змін</h3>
              <div id="profileLogList">
                <p class="admin-loading">Завантаження...</p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

  </div>
</main>

@endsection
