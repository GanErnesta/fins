
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\SignupController;
use App\Http\Controllers\VerificationController;

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

    // Halaman Login
    Route::get('/login', [
        LoginController::class,
        'showLogin'
    ])->name('login');

    // Proses Login menggunakan NIM dan Password
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

    // Menampilkan Halaman Sign Up
    Route::get('/signup', [
        SignupController::class,
        'showSignup'
    ])->name('signup');

    // Step 1 - Nama, NIM, Fakultas, Program Studi
    Route::post('/signup/step-1', [
        SignupController::class,
        'step1'
    ])->name('signup.step1');

    // Step 2 - Email, Password, Konfirmasi Password
    // SignupController mengirim OTP ke email asli
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

    // Menampilkan halaman input OTP
    Route::get('/verification', [
        VerificationController::class,
        'show'
    ])->name('verification');

    // Memeriksa OTP
    // Jika berhasil, VerificationController
    // menyimpan akun ke database Supabase
    Route::post('/verification', [
        VerificationController::class,
        'verify'
    ])->name('verification.verify');

    // Mengirim ulang OTP ke email mahasiswa
    Route::post('/verification/resend', [
        VerificationController::class,
        'resend'
    ])->name('verification.resend');

    // Halaman Verifikasi Berhasil
    Route::get('/verification-success', function () {

        if (!session('account_verified')) {
            return redirect()->route('signup');
        }

        return view('verification-success');

    })->name('verification-success');

    // Halaman Verifikasi Gagal
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
    | FITUR CARI PARTNER
    |--------------------------------------------------------------------------
    */

    Route::get('/cari-partner', function () {
        return view('cari_partner');
    })->name('cari-partner');

    /*
    |--------------------------------------------------------------------------
    | FITUR PERLOMBAAN
    |--------------------------------------------------------------------------
    */

    // Cari Lomba
    Route::get('/cari-lomba', function () {
        return view('cari_lomba');
    })->name('cari-lomba');

    // Posting Lomba
    Route::get('/posting-lomba', function () {
        return view('posting_lomba');
    })->name('posting-lomba');

    // Lomba Saya
    Route::get('/lomba-saya', function () {
        return view('lomba_saya');
    })->name('lomba-saya');

    /*
    |--------------------------------------------------------------------------
    | FITUR CHAT
    |--------------------------------------------------------------------------
    */

    Route::get('/chat', function () {
        return view('chat');
    })->name('chat');

});
