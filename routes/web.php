
<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LoginController;
use App\Http\Controllers\SignupController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\CompetitionController;
use App\Http\Controllers\PostingLombaController;
use App\Http\Controllers\LombaSayaController;
use App\Http\Controllers\ChatFileController;

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

    // Proses Login
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

    // Halaman Signup
    Route::get('/signup', [
        SignupController::class,
        'showSignup'
    ])->name('signup');

    // Proses Signup Step 1
    Route::post('/signup/step-1', [
        SignupController::class,
        'step1'
    ])->name('signup.step1');

    // Proses Signup Step 2
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

    // Halaman Verifikasi
    Route::get('/verification', [
        VerificationController::class,
        'show'
    ])->name('verification');

    // Proses Verifikasi OTP
    Route::post('/verification', [
        VerificationController::class,
        'verify'
    ])->name('verification.verify');

    // Kirim Ulang OTP
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

    /*
    |--------------------------------------------------------------------------
    | ALIAS ROUTE VERIFIKASI
    |--------------------------------------------------------------------------
    | Mempertahankan nama route dari branch posting_lomba.
    */

    Route::get('/verification/success', function () {
        return redirect()->route('verification-success');
    })->name('verification.success');

    Route::get('/verification/failed', function () {
        return redirect()->route('verification-failed');
    })->name('verification.failed');

});

/*
|--------------------------------------------------------------------------
| LOGOUT
|--------------------------------------------------------------------------
*/

Route::post('/logout', [
    LoginController::class,
    'logout'
])
->middleware('auth')
->name('logout');

/*
|--------------------------------------------------------------------------
| HALAMAN UTAMA FINS
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | HOME
    |--------------------------------------------------------------------------
    */

    Route::get('/home', function () {
        return view('home');
    })->name('home');

    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */

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

    // Menampilkan daftar lomba
    Route::get('/cari-lomba', [
        CompetitionController::class,
        'index'
    ])->name('cari-lomba');

    // Menampilkan detail lomba
    Route::get('/cari-lomba/{id}', [
        CompetitionController::class,
        'show'
    ])
    ->whereNumber('id')
    ->name('cari_lomba.show');

    /*
    |--------------------------------------------------------------------------
    | POSTING LOMBA
    |--------------------------------------------------------------------------
    */

    // Halaman Posting Lomba
    Route::get('/posting-lomba', [
        PostingLombaController::class,
        'create'
    ])->name('posting-lomba');

    // Alias route untuk branch posting_lomba
    Route::get('/posting-lomba/create', [
        PostingLombaController::class,
        'create'
    ])->name('posting-lomba.create');

    // Menyimpan posting lomba melalui PostingLombaController
    Route::post('/posting-lomba', [
        PostingLombaController::class,
        'store'
    ])->name('posting-lomba.store');

    // Mempertahankan route penyimpanan dari branch main
    Route::post('/tambah-lomba', [
        CompetitionController::class,
        'store'
    ])->name('lomba.store');

    /*
    |--------------------------------------------------------------------------
    | LOMBA SAYA
    |--------------------------------------------------------------------------
    */

    // Halaman Lomba Saya menggunakan Controller
    Route::get('/lomba-saya', [
        LombaSayaController::class,
        'index'
    ])->name('lomba-saya');

    /*
    |--------------------------------------------------------------------------
    | CHAT
    |--------------------------------------------------------------------------
    */

    // Halaman Chat
    Route::get('/chat', function () {
        return view('chat');
    })->name('chat');

    /*
    |--------------------------------------------------------------------------
    | CHAT FILE - UPLOAD DAN DOWNLOAD
    |--------------------------------------------------------------------------
    */

    // Upload File Chat
    Route::post('/chat/upload', [
        ChatFileController::class,
        'upload'
    ])->name('chat.file.upload');

    // Download File Chat
    Route::get('/chat/files/{filename}', [
        ChatFileController::class,
        'download'
    ])
    ->where('filename', '[A-Za-z0-9._-]+')
    ->name('chat.file.download');

});
