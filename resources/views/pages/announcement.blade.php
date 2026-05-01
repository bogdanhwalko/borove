@extends('layouts.app')

@php
  $typeLabels = [
    'urgent'   => 'Терміново',
    'event'    => 'Подія',
    'info'     => 'Інформація',
    'services' => 'Послуги',
  ];
  $typeIcons  = [
    'urgent'   => '⚠️',
    'event'    => '📅',
    'info'     => 'ℹ️',
    'services' => '🛠️',
  ];
  $typeLabel  = $typeLabels[$announcement->type] ?? 'Оголошення';
  $typeIcon   = $typeIcons[$announcement->type]  ?? '📋';

  $title       = mb_substr($announcement->title ?? 'Оголошення', 0, 70);
  $description = mb_substr(strip_tags($announcement->body ?? $announcement->title), 0, 160);
  $imageUrl    = $announcement->image_path
    ? url('/storage/' . $announcement->image_path)
    : url('/img/header-village.jpg');
@endphp

@section('title', $title . ' — Оголошення | Борове')
@section('description', $description)
@section('og_type', 'article')
@section('og_title', $title)
@section('og_description', $description)
@section('og_image', $imageUrl)

@section('jsonld')
{!! json_encode([
  '@context'      => 'https://schema.org',
  '@type'         => 'Article',
  'headline'      => $announcement->title,
  'description'   => $description,
  'image'         => $imageUrl,
  'datePublished' => optional($announcement->created_at)->toIso8601String(),
  'dateModified'  => optional($announcement->updated_at)->toIso8601String(),
  'articleSection' => $typeLabel,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
@endsection

@section('content')

<main>
  <div class="container">
    <div style="max-width:760px;margin:0 auto;padding:32px 0">

      <div style="margin-bottom:20px">
        <a href="/announcements" class="btn-read">&#8592; Усі оголошення</a>
      </div>

      <article class="announcement-detail">
        <div class="announcement-detail-meta">
          <span class="ann-type ann-type--{{ $announcement->type }}">{{ $typeIcon }} {{ $typeLabel }}</span>
          <time datetime="{{ optional($announcement->created_at)->toIso8601String() }}">
            {{ optional($announcement->created_at)->format('d.m.Y') }}
          </time>
        </div>

        <h1>{{ $announcement->title }}</h1>

        @if($announcement->image_path)
          <img class="announcement-detail-img"
               src="{{ '/storage/' . $announcement->image_path }}"
               alt="{{ $announcement->title }}"
               loading="eager">
        @endif

        <div class="announcement-detail-body">{!! nl2br(e($announcement->body)) !!}</div>

        @if($announcement->contact)
          <div class="announcement-detail-contact">
            <strong>&#128222; Контакт:</strong> {{ $announcement->contact }}
          </div>
        @endif
      </article>

    </div>
  </div>
</main>

@endsection
