@extends('layouts.app')

@section('title', 'Запити на покупку — Борове')
@section('description', 'Запити на покупку — моя палатка')

@section('content')

<div class="page-hero">
  <h1>&#128276; Запити на покупку</h1>
  <p id="requestsCount">Завантаження...</p>
</div>

<main>
  <div class="container">
    <div style="max-width:680px;margin:0 auto;padding:32px 0">

      <div style="margin-bottom:20px">
        <a href="/shop" class="btn-read">&#8592; Базар</a>
      </div>

      <div id="requestsWrap">
        <div class="empty-state"><div class="empty-icon">&#128276;</div><p>Завантаження...</p></div>
      </div>

    </div>
  </div>
</main>

@endsection
