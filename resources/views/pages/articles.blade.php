@extends('layouts.app')

@section('title', 'Всі статті — Борове')
@section('description', 'Усі новини та статті села Борове')

@section('content')

<div class="page-hero">
  <h1>&#128240; Всі статті</h1>
  <p>Новини, події та хроніка нашої громади</p>
</div>

<main>
  <div class="container">
    <div id="articlesSubmitCta" style="text-align:right;margin:16px 0;display:none">
      <a href="/my/articles/new" class="btn-submit" style="display:inline-block;text-decoration:none">&#9998; Подати статтю</a>
    </div>
    <div class="articles-newspaper" id="articlesListGrid">
      <div class="empty-state"><div class="empty-icon">&#128240;</div><p>Завантаження...</p></div>
    </div>
    <div id="articlesListPagination" class="articles-list-pagination"></div>
  </div>
</main>

@endsection
