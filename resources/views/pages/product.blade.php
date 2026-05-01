@extends('layouts.app')

@php
  $title       = mb_substr($product->title ?? 'Товар', 0, 70);
  $descSource  = trim($product->description ?? '') !== '' ? $product->description : ($product->title . ' на базарі села Борове');
  $description = mb_substr(strip_tags($descSource), 0, 160);
  $imageUrl    = $product->photo_path ? url('/storage/' . $product->photo_path) : url('/img/header-village.jpg');
  $sellerName  = $product->shop->name
                  ?? optional($product->shop->user)->nickname
                  ?? 'Палатка';
  $priceText   = $product->price !== null ? number_format((float) $product->price, 0, ',', ' ') . ' грн' : 'Ціна за домовленістю';
@endphp

@section('title', $title . ' — ' . $sellerName . ' | Базар Борове')
@section('description', $description)
@section('og_type', 'product')
@section('og_title', $title)
@section('og_description', $description)
@section('og_image', $imageUrl)

@section('jsonld')
{!! json_encode([
  '@context'    => 'https://schema.org',
  '@type'       => 'Product',
  'name'        => $product->title,
  'description' => $product->description,
  'image'       => $imageUrl,
  'offers'      => [
    '@type'         => 'Offer',
    'priceCurrency' => 'UAH',
    'price'         => $product->price !== null ? (string) $product->price : null,
    'availability'  => 'https://schema.org/InStock',
    'seller'        => [
      '@type' => 'Organization',
      'name'  => $sellerName,
    ],
  ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
@endsection

@section('content')

<main>
  <div class="container">
    <div style="max-width:880px;margin:0 auto;padding:32px 0">

      <div style="margin-bottom:20px">
        <a href="/shop" class="btn-read">&#8592; Базар</a>
      </div>

      <article class="product-detail" itemscope itemtype="https://schema.org/Product">
        @if($product->photo_path)
          <img class="product-detail-img"
               src="{{ '/storage/' . $product->photo_path }}"
               alt="{{ $product->title }}"
               itemprop="image"
               loading="eager">
        @else
          <div class="product-detail-img product-detail-img--placeholder">&#128717;</div>
        @endif

        <div class="product-detail-body">
          <h1 itemprop="name">{{ $product->title }}</h1>

          <div class="product-detail-price"
               itemprop="offers" itemscope itemtype="https://schema.org/Offer">
            @if($product->price !== null)
              <meta itemprop="priceCurrency" content="UAH">
              <span itemprop="price" content="{{ $product->price }}">{{ $priceText }}</span>
            @else
              {{ $priceText }}
            @endif
          </div>

          @if($product->description)
            <div class="product-detail-desc" itemprop="description">{!! nl2br(e($product->description)) !!}</div>
          @endif

          <div class="product-detail-seller">
            <strong>Продавець:</strong>
            @if($product->shop)
              <a href="/shop?shop={{ $product->shop->id }}">{{ $sellerName }}</a>
            @else
              {{ $sellerName }}
            @endif
          </div>

          <div class="product-detail-actions">
            <button id="productBuyBtn"
                    class="btn-submit"
                    data-id="{{ $product->id }}"
                    data-title="{{ $product->title }}"
                    data-seller-id="{{ optional($product->shop)->user_id }}">
              &#128222; Бажаю купити
            </button>
          </div>
        </div>
      </article>

    </div>
  </div>
</main>

@endsection

@push('modals')
<div id="buyModal" class="modal-overlay" style="display:none" role="dialog" aria-modal="true" aria-labelledby="buyModalTitle">
  <div class="modal-box">
    <h3 id="buyModalTitle">&#128217; Запит на покупку</h3>
    <p id="buyModalProduct" class="modal-product-name">{{ $product->title }}</p>
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
