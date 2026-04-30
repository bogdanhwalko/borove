<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\RideController;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserAlbumController;
use App\Http\Controllers\MarketController;
use App\Http\Controllers\UserShopController;
use Illuminate\Support\Facades\Route;

// Auth
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Profile
    Route::get('/profile',                                      [ProfileController::class, 'show']);
    Route::patch('/profile',                                    [ProfileController::class, 'update']);
    Route::middleware('throttle:5,1')->post('/profile/avatar',  [ProfileController::class, 'uploadAvatar']);
    Route::post('/profile/password',                            [ProfileController::class, 'changePassword']);
    Route::get('/profile/logs',                                 [ProfileController::class, 'logs']);
});

// Articles (public)
Route::get('/articles',        [ArticleController::class, 'index']);
Route::get('/articles/{slug}', [ArticleController::class, 'show']);

// Announcements (read public, write auth)
Route::get('/announcements', [AnnouncementController::class, 'index']);
Route::middleware(['auth:sanctum', 'throttle:20,60'])->post('/announcements', [AnnouncementController::class, 'store']);

// Rides (read public, write auth)
Route::get('/rides', [RideController::class, 'index']);
Route::middleware(['auth:sanctum', 'throttle:20,60'])->post('/rides', [RideController::class, 'store']);
Route::middleware('auth:sanctum')->patch('/rides/{id}/seats', [RideController::class, 'updateSeats']);

// Albums (public)
Route::get('/albums',        [AlbumController::class, 'index']);
Route::get('/albums/{slug}', [AlbumController::class, 'show']);

// Feedback
Route::middleware(['auth:sanctum', 'throttle:5,1'])->post('/feedback', [FeedbackController::class, 'store']);

// Marketplace (public)
Route::get('/products',    [MarketController::class, 'index']);
Route::get('/shops/{id}',  [MarketController::class, 'showShop']);

// Marketplace (auth)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/products/{id}/buy-request', [MarketController::class, 'buyRequest']);
    Route::get('/my/shop',                    [UserShopController::class, 'show']);
    Route::post('/my/shop',                   [UserShopController::class, 'createOrUpdate']);
    Route::post('/my/shop/products',          [UserShopController::class, 'storeProduct']);
    Route::delete('/my/shop/products/{id}',   [UserShopController::class, 'destroyProduct']);
    Route::get('/my/shop/requests',           [UserShopController::class, 'requests']);
    Route::post('/my/shop/requests/view-all', [UserShopController::class, 'markAllRequestsViewed']);
    Route::post('/my/shop/requests/{id}/view',[UserShopController::class, 'markRequestViewed']);
});

// User gallery submission (auth)
Route::middleware('auth:sanctum')->post('/my/albums', [UserAlbumController::class, 'store']);

// Admin (auth + is_admin checked inside controller)
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('/articles',             [AdminController::class, 'indexArticles']);
    Route::post('/articles',            [AdminController::class, 'storeArticle']);
    Route::put('/articles/{id}',        [AdminController::class, 'updateArticle']);
    Route::delete('/articles/{id}',     [AdminController::class, 'destroyArticle']);

    Route::delete('/announcements/{id}', [AdminController::class, 'destroyAnnouncement']);
    Route::delete('/rides/{id}',         [AdminController::class, 'destroyRide']);

    Route::get('/albums/pending',        [AdminController::class, 'indexPendingAlbums']);
    Route::post('/albums/{id}/publish',  [AdminController::class, 'publishAlbum']);
    Route::post('/albums/{id}/cover',    [AdminController::class, 'setCover']);
    Route::post('/albums',               [AdminController::class, 'storeAlbum']);
    Route::delete('/albums/{id}',        [AdminController::class, 'destroyAlbum']);

    Route::post('/albums/{id}/photos',  [AdminController::class, 'storePhoto']);
    Route::delete('/photos/{id}',       [AdminController::class, 'destroyPhoto']);

    Route::get('/profile-requests',                   [AdminController::class, 'indexProfileRequests']);
    Route::post('/profile-requests/{id}/approve',     [AdminController::class, 'approveProfileRequest']);
    Route::post('/profile-requests/{id}/reject',      [AdminController::class, 'rejectProfileRequest']);

    Route::get('/feedback',                 [AdminController::class, 'indexFeedbackMessages']);
    Route::post('/feedback/{id}/close',     [AdminController::class, 'closeFeedbackMessage']);
    Route::delete('/feedback/{id}',         [AdminController::class, 'destroyFeedbackMessage']);
});
