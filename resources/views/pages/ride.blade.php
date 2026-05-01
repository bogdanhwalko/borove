@extends('layouts.app')

@php
  $rideDate = $ride->ride_date instanceof \Carbon\Carbon
    ? $ride->ride_date
    : \Carbon\Carbon::parse($ride->ride_date);
  $rideTime    = substr($ride->ride_time ?? '', 0, 5);
  $dateText    = $rideDate->format('d.m.Y');
  $title       = mb_substr(($ride->from_place ?? '?') . ' → ' . ($ride->to_place ?? '?'), 0, 70);
  $description = 'Попутка ' . $title . ' — ' . $dateText . ' о ' . $rideTime . '. Місць: ' . (int) $ride->seats . '.';
  $imageUrl    = url('/img/header-village.jpg');
  $isoStart    = $rideDate->copy()->setTimeFromTimeString($rideTime ?: '00:00')->toIso8601String();
@endphp

@section('title', $title . ' — Попутка ' . $dateText . ' | Борове')
@section('description', $description)
@section('og_type', 'article')
@section('og_title', $title)
@section('og_description', $description)
@section('og_image', $imageUrl)

@section('jsonld')
{!! json_encode([
  '@context'    => 'https://schema.org',
  '@type'       => 'Event',
  'name'        => $title,
  'description' => $description,
  'startDate'   => $isoStart,
  'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
  'eventStatus' => 'https://schema.org/EventScheduled',
  'location'    => [
    '@type'   => 'Place',
    'name'    => $ride->from_place,
    'address' => $ride->from_place,
  ],
  'organizer'   => [
    '@type' => 'Person',
    'name'  => $ride->name,
  ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
@endsection

@section('content')

<main>
  <div class="container">
    <div style="max-width:680px;margin:0 auto;padding:32px 0">

      <div style="margin-bottom:20px">
        <a href="/rides" class="btn-read">&#8592; Усі попутки</a>
      </div>

      <article class="ride-detail">
        <h1>&#128664; {{ $ride->from_place }} → {{ $ride->to_place }}</h1>

        <div class="ride-detail-meta">
          <div><strong>&#128197; Дата:</strong> {{ $dateText }}</div>
          <div><strong>&#128340; Час:</strong> {{ $rideTime }}</div>
          <div><strong>&#128100; Водій:</strong> {{ $ride->name }}</div>
          <div><strong>&#128666; Вільних місць:</strong> <span id="rideSeats">{{ $ride->seats }}</span></div>
          @if($ride->contact)
            <div><strong>&#128222; Контакт:</strong> <a href="tel:{{ preg_replace('/\s+/', '', $ride->contact) }}">{{ $ride->contact }}</a></div>
          @endif
        </div>

        @if($ride->comment)
          <div class="ride-detail-comment">
            <h3>Коментар</h3>
            <p>{!! nl2br(e($ride->comment)) !!}</p>
          </div>
        @endif
      </article>

    </div>
  </div>
</main>

@endsection
