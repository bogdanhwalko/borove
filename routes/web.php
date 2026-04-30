<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/',               fn() => view('pages.index'));
Route::get('/shop',           fn() => view('pages.shop'));
Route::get('/rides',          fn() => view('pages.rides'));
Route::get('/announcements',  fn() => view('pages.announcements'));
Route::get('/gallery',        fn() => view('pages.gallery'));
Route::get('/gallery/{slug}', fn() => view('pages.album'));
Route::get('/articles',       fn() => view('pages.articles'));
Route::get('/articles/{slug}',fn() => view('pages.article'));
Route::get('/auth',           fn() => view('pages.auth'));
Route::get('/admin',          fn() => view('pages.admin'));
Route::get('/profile',        fn() => view('pages.profile'));
Route::get('/requests',       fn() => view('pages.requests'));

Route::get('/media/{path}', function (string $path) {
    abort_if(str_contains($path, '..'), 404);
    abort_unless(Storage::disk('public')->exists($path), 404);

    return response()->file(Storage::disk('public')->path($path), [
        'Cache-Control' => 'public, max-age=31536000',
    ]);
})->where('path', '.*');
