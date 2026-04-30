@extends('layouts.app')

@section('title', 'Стаття — Борове')
@section('description', 'Стаття — Борове')

@section('content')

<div class="article-hero" id="articleHero">
  <img src="https://picsum.photos/seed/village/1400/500" alt="">
  <div class="article-hero-overlay"></div>
</div>

<main>
  <div class="container">
    <div class="article-full" id="articleContent">
      <div class="empty-state" style="padding:80px 0">
        <div class="empty-icon">&#128240;</div>
        <p>Завантаження...</p>
      </div>
    </div>
  </div>
</main>

@endsection
