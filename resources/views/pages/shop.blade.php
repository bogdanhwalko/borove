@extends('layouts.app')

@section('title', 'Базар — Борове')
@section('description', 'Базар села Борове — купити та продати товари')

@section('content')

<div class="page-hero">
  <h1>&#128717; Базар</h1>
  <p>Купуйте та продавайте товари серед жителів громади</p>
</div>

<main>
  <div class="container">
    <div class="main-layout">

      <section class="content-main" aria-label="Товари">
        <div id="shopHeader" style="display:none"></div>
        <div id="productList" aria-live="polite">
          <div class="empty-state"><div class="empty-icon">&#128717;</div><p>Завантаження...</p></div>
        </div>
        <div id="productPagination" class="pagination"></div>
      </section>

      <aside class="content-sidebar" aria-label="Моя палатка">

        <button class="form-mobile-toggle" id="shopSidebarToggle" aria-expanded="false" aria-controls="shopSidebarSection">
          &#128717; Моя палатка
        </button>

        <div class="add-form-section" id="shopSidebarSection">
          <div id="shopSidebarWrap"></div>
        </div>

        <div class="widget fade-in" style="margin-top:20px">
          <div class="widget-header">&#128161; Як це працює</div>
          <div class="widget-body" style="font-size:.85rem;line-height:1.6">
            <p>&#128717; Додайте свій товар у палатку</p>
            <p>&#128222; Покупець надсилає запит</p>
            <p>&#128100; Ви отримуєте контакт покупця</p>
            <p>&#129309; Домовляйтесь напряму</p>
          </div>
        </div>

      </aside>
    </div>
  </div>
</main>

@endsection

@push('modals')
<div id="buyModal" class="modal-overlay" style="display:none" role="dialog" aria-modal="true" aria-labelledby="buyModalTitle">
  <div class="modal-box">
    <h3 id="buyModalTitle">&#128217; Запит на покупку</h3>
    <p id="buyModalProduct" class="modal-product-name"></p>
    <div class="form-group">
      <label for="buyMessage">Повідомлення продавцю (необов'язково)</label>
      <textarea id="buyMessage" placeholder="Запитання, деталі, зручний час..." maxlength="300" rows="3"></textarea>
    </div>
    <div class="modal-actions">
      <button class="btn-submit" id="buyModalConfirm">&#128222; Надіслати запит</button>
      <button class="btn-cancel" id="buyModalCancel">Скасувати</button>
    </div>
  </div>
</div>
@endpush
