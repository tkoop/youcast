<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FeedController;

Route::get('/', [FeedController::class, 'index']);

Route::post('/feeds/create', [FeedController::class, 'create'])->name('feed.create');
Route::get('/feeds/{id}/edit', [FeedController::class, 'edit'])->name('feed.edit');
Route::post('/feeds/{id}/update', [FeedController::class, 'update'])->name('feed.update');
Route::get('/feeds/{id}.rss', [FeedController::class, 'rss'])->name('feed.rss');
Route::get('/feeds/{id}/episodes/{episodeId}/audio', [FeedController::class, 'audio'])->name('feed.audio');

Route::post('/feeds/{id}/settings/youtube-cookies', [FeedController::class, 'saveCookies'])->name('settings.youtube-cookies');
Route::get('/feeds/{id}/settings/youtube-oauth', [FeedController::class, 'startOAuth'])->name('settings.youtube-oauth');
Route::get('/feeds/{id}/settings/youtube-oauth/check', [FeedController::class, 'checkOAuth'])->name('settings.youtube-oauth-check');
