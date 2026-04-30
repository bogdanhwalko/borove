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
    <div class="articles-newspaper" id="articlesListGrid">
      <div class="empty-state"><div class="empty-icon">&#128240;</div><p>Завантаження...</p></div>
    </div>
    <div id="articlesListPagination" class="articles-list-pagination"></div>
  </div>
</main>

@endsection
