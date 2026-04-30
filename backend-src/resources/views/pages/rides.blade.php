@extends('layouts.app')

@section('title', 'Попутки — Борове')
@section('description', 'Попутки Борове — знайдіть або запропонуйте поїздку')

@section('content')

<div class="page-hero">
  <h1>&#128664; Попутки</h1>
  <p>Їдете кудись? Повідомте сусідів — можливо, комусь по дорозі!</p>
</div>

<main>
  <div class="container">
    <div class="rides-layout">

      <section class="rides-list" aria-label="Список попуток">
        <h2 class="section-title fade-in"><span class="icon">&#128205;</span> Актуальні поїздки</h2>
        <div class="rides-filters fade-in" role="group" aria-label="Фільтр поїздок">
          <button class="filter-btn active" data-rides-filter="all">&#128209; Усі</button>
          <button class="filter-btn" data-rides-filter="own" id="ridesFilterOwn" style="display:none">&#127775; Мої поїздки</button>
        </div>
        <div id="ridesList" aria-live="polite">
          <div class="empty-state"><div class="empty-icon">&#128664;</div><p>Завантаження...</p></div>
        </div>
        <div id="ridesPagination" class="pagination"></div>
      </section>

      <aside class="rides-form-wrap" aria-label="Додати попутку">

        <button class="form-mobile-toggle" id="rideFormToggle" aria-expanded="false" aria-controls="rideFormSection">
          &#128664; Пропоную попутку
        </button>

        <div class="add-form-section" id="rideFormSection">
          <div class="add-form fade-in" id="rideFormWrap">
            <h3>&#128664; Пропоную попутку</h3>
            <form id="addRideForm" novalidate>
              <div class="ride-profile-info" id="rideProfileInfo" style="display:none">
                <div class="ride-profile-info-row">
                  <span class="ride-profile-info-label">&#128100; Ім'я</span>
                  <span class="ride-profile-info-value" id="rideProfileName"></span>
                </div>
                <div class="ride-profile-info-row">
                  <span class="ride-profile-info-label">&#128222; Телефон</span>
                  <span class="ride-profile-info-value" id="rideProfilePhone"></span>
                </div>
                <div class="ride-profile-info-hint">Ці дані беруться з вашого профілю — щоб змінити, відредагуйте профіль</div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label for="rideFrom">Звідки *</label>
                  <input type="text" id="rideFrom" name="rideFrom" placeholder="Борове" required maxlength="60" value="Борове">
                </div>
                <div class="form-group">
                  <label for="rideTo">Куди *</label>
                  <input type="text" id="rideTo" name="rideTo" placeholder="Рівне" required maxlength="60">
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label for="rideDate">Дата *</label>
                  <input type="date" id="rideDate" name="rideDate" required>
                </div>
                <div class="form-group">
                  <label for="rideTime">Час *</label>
                  <input type="time" id="rideTime" name="rideTime" required>
                </div>
              </div>
              <div class="form-group">
                <label for="rideSeats">Кількість місць</label>
                <select id="rideSeats" name="rideSeats">
                  <option value="1">1 місце</option>
                  <option value="2" selected>2 місця</option>
                  <option value="3">3 місця</option>
                  <option value="4">4 місця</option>
                  <option value="0">Місць немає</option>
                </select>
              </div>
              <div class="form-group">
                <label for="rideComment">Коментар</label>
                <textarea id="rideComment" name="rideComment" placeholder="Маршрут, зупинки, умови..." maxlength="300" style="min-height:80px"></textarea>
              </div>
              <button type="submit" class="btn-submit">&#128228; Додати попутку</button>
            </form>
          </div>
        </div>

        <div class="widget fade-in" style="margin-top:20px">
          <div class="widget-header">&#128161; Поради</div>
          <div class="widget-body">
            <ul class="tips-list">
              <li>Вказуйте точний час відправлення</li>
              <li>Домовтесь про місце зустрічі заздалегідь</li>
              <li>Уточніть деталі по телефону</li>
              <li>Оновлюйте запис якщо місць стало менше</li>
            </ul>
          </div>
        </div>

      </aside>
    </div>
  </div>
</main>

@endsection
