<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LoginController;
use App\Http\Controllers\SignupController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\CompetitionController;

/*
|--------------------------------------------------------------------------
| HALAMAN UTAMA
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| AUTHENTICATION - LOGIN
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    Route::get('/login', [
        LoginController::class,
        'showLogin'
    ])->name('login');

    Route::post('/login', [
        LoginController::class,
        'login'
    ])->name('login.submit');

});

/*
|--------------------------------------------------------------------------
| AUTHENTICATION - SIGN UP
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    Route::get('/signup', [
        SignupController::class,
        'showSignup'
    ])->name('signup');

    Route::post('/signup/step-1', [
        SignupController::class,
        'step1'
    ])->name('signup.step1');

    Route::post('/signup/step-2', [
        SignupController::class,
        'step2'
    ])->name('signup.step2');

});

/*
|--------------------------------------------------------------------------
| VERIFIKASI OTP EMAIL
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    // Halaman verifikasi
    Route::get('/verification', [
        VerificationController::class,
        'show'
    ])->name('verification');

    // Proses verifikasi OTP
    Route::post('/verification', [
        VerificationController::class,
        'verify'
    ])->name('verification.verify');

    // Kirim ulang OTP
    Route::post('/verification/resend', [
        VerificationController::class,
        'resend'
    ])->name('verification.resend');

    // Verifikasi berhasil
    Route::get('/verification-success', function () {

        if (!session('account_verified')) {
            return redirect()->route('signup');
        }

        return view('verification-success');

    })->name('verification-success');

    // Verifikasi gagal
    Route::get('/verification-failed', function () {
        return view('verification-failed');
    })->name('verification-failed');

});

/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/

Route::post('/logout', [
    LoginController::class,
    'logout'
])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| HALAMAN UTAMA FINS
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // Home
    Route::get('/home', function () {
        return view('home');
    })->name('home');

    // Profile
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

    // Daftar kompetisi
    Route::get('/cari-lomba', [
        CompetitionController::class,
        'index'
    ])->name('cari-lomba');

    // Detail kompetisi
    Route::get('/cari-lomba/{id}', [
        CompetitionController::class,
        'show'
    ])->whereNumber('id')->name('cari_lomba.show');

    /*
    |--------------------------------------------------------------------------
    | POSTING LOMBA
    |--------------------------------------------------------------------------
    */

    // Halaman posting
    Route::get('/posting-lomba', function () {
        return view('posting_lomba');
    })->name('posting-lomba');

    // Menyimpan posting lomba
    Route::post('/tambah-lomba', [
        CompetitionController::class,
        'store'
    ])->name('lomba.store');

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

});