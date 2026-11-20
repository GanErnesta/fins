
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

// Halaman Login
Route::get('/login', function () {
    return view('login');
})->name('login');

// Halaman Signup
Route::get('/signup', function () {
    return view('signup');
})->name('signup');

/*
|--------------------------------------------------------------------------
| HALAMAN UTAMA SETELAH LOGIN
|--------------------------------------------------------------------------
*/

// Beranda
Route::get('/home', function () {
    return view('home');
})->name('home');

// Profil
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
})->name('cari-partner');

/*
|--------------------------------------------------------------------------
| CARI LOMBA
|--------------------------------------------------------------------------
*/

// Menampilkan daftar lomba dari Supabase
Route::get('/cari-lomba', [
    CompetitionController::class,
    'index'
])->name('cari-lomba');

// Menampilkan halaman detail lomba
Route::get('/cari-lomba/{cariLomba}', [
    CompetitionController::class,
    'show'
])->whereNumber('cariLomba')
  ->name('cari_lomba.show');

/*
|--------------------------------------------------------------------------
| POSTING LOMBA
|--------------------------------------------------------------------------
*/

// Menampilkan halaman Posting Lomba
Route::get('/posting-lomba', [
    PostingLombaController::class,
    'create'
])->name('posting-lomba.create');

// Menyimpan postingan ke Supabase
Route::post('/posting-lomba', [
    PostingLombaController::class,
    'store'
])->name('posting-lomba.store');

/*
|--------------------------------------------------------------------------
| LOMBA SAYA
|--------------------------------------------------------------------------
*/

Route::get('/lomba-saya', function () {
    return view('lomba_saya');
})->name('lomba-saya');

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

// Halaman verifikasi
Route::get('/verification', function () {
    return view('verification');
})->name('verification');

// Verifikasi berhasil
Route::get('/verification-success', function () {
    return view('verification-success');
})->name('verification.success');

// Verifikasi gagal
Route::get('/verification-failed', function () {
    return view('verification-failed');
})->name('verification.failed');
