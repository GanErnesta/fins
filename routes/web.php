
<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CompetitionController;
use App\Http\Controllers\PostingLombaController;

/*
|--------------------------------------------------------------------------
| HALAMAN UTAMA
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('login');
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

Route::get('/login', function () {
    return view('login');
})->name('login');

Route::get('/signup', function () {
    return view('signup');
})->name('signup');

/*
|--------------------------------------------------------------------------
| HALAMAN UTAMA SETELAH LOGIN
|--------------------------------------------------------------------------
*/

Route::get('/home', function () {
    return view('home');
})->name('home');

Route::get('/profile', function () {
    return view('profile');
})->name('profile');

/*
|--------------------------------------------------------------------------
| CARI PARTNER
|--------------------------------------------------------------------------
*/

Route::get('/cari-partner', function () {
    return view('cari_partner');
})->name('cari_partner');

/*
|--------------------------------------------------------------------------
| CARI LOMBA
|--------------------------------------------------------------------------
*/

Route::get('/cari-lomba', [
    CompetitionController::class,
    'index'
])->name('cari_lomba');

/*
|--------------------------------------------------------------------------
| POSTING LOMBA
|--------------------------------------------------------------------------
*/

Route::get('/posting-lomba', [
    PostingLombaController::class,
    'create'
])->name('posting_lomba.create');

Route::post('/posting-lomba', [
    PostingLombaController::class,
    'store'
])->name('posting_lomba.store');

/*
|--------------------------------------------------------------------------
| LOMBA SAYA
|--------------------------------------------------------------------------
*/

Route::get('/lomba-saya', function () {
    return view('lomba_saya');
})->name('lomba_saya');

/*
|--------------------------------------------------------------------------
| CHAT
|--------------------------------------------------------------------------
*/

Route::get('/chat', function () {
    return view('chat');
})->name('chat');

/*
|--------------------------------------------------------------------------
| VERIFIKASI
|--------------------------------------------------------------------------
*/

Route::get('/verification', function () {
    return view('verification');
})->name('verification');

Route::get('/verification-success', function () {
    return view('verification-success');
})->name('verification.success');

Route::get('/verification-failed', function () {
    return view('verification-failed');
})->name('verification.failed');
