<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompetitionController;


Route::get('/', function () {
    return view('login');
});

Route::get('/login', function () {
    return view('login');
});

Route::get('/signup', function () {
    return view('signup');
});

Route::get('/home', function () {
    return view('home');
});

Route::get('/profile', function () {
    return view('profile');
});

Route::get('/cari-partner', function () {
    return view('cari_partner');
});

Route::get('/cari-lomba', [CompetitionController::class, 'index'])
    ->name('cari_lomba');

Route::get('/cari-lomba/{id}', [CompetitionController::class, 'show'])
    ->whereNumber('id')
    ->name('cari_lomba.show');
Route::post('/tambah-lomba', [LombaController::class, 'store'])
    ->middleware('auth')
    ->name('lomba.store');
Route::get('/posting-lomba', function () {
    return view('posting_lomba');
})->name('posting-lomba');

Route::get('/posting-lomba', function () {
    return view('posting_lomba');
});

Route::get('/lomba-saya', function () {
    return view('lomba_saya');
});

Route::get('/chat', function () {
    return view('chat');
});

Route::get('/verification', function () {
    return view('verification');
});

Route::get('/verification-success', function () {
    return view('verification-success');
});

Route::get('/verification-failed', function () {
    return view('verification-failed');
});
