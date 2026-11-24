
<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LoginController;
use App\Http\Controllers\SignupController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\CompetitionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PostingLombaController;
use App\Http\Controllers\LombaSayaController;
use App\Http\Controllers\ChatFileController;
use App\Http\Controllers\ChatAttachmentController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\PartnerInvitationController;
use App\Http\Controllers\CariPartnerController;
use App\Http\Controllers\RealtimeController;
use App\Http\Controllers\NotificationController;

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
| LOGIN
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
| SIGN UP
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
| VERIFIKASI EMAIL
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    Route::get('/verification', [
        VerificationController::class,
        'show'
    ])->name('verification');

    Route::post('/verification', [
        VerificationController::class,
        'verify'
    ])->name('verification.verify');

    Route::post('/verification/resend', [
        VerificationController::class,
        'resend'
    ])->name('verification.resend');

    Route::get('/verification-success', function () {

        if (!session('account_verified')) {
            return redirect()->route('signup');
        }

        return view('verification-success');

    })->name('verification-success');

    Route::get('/verification-failed', function () {
        return view('verification-failed');
    })->name('verification-failed');

    Route::get('/verification/success', function () {
        return redirect()->route('verification-success');
    })->name('verification.success');

    Route::get('/verification/failed', function () {
        return redirect()->route('verification-failed');
    })->name('verification.failed');

});

/*
|--------------------------------------------------------------------------
| LUPA PASSWORD DAN RESET PASSWORD
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    Route::get('/forgot-password', [
        PasswordResetController::class,
        'requestForm'
    ])->name('password.request');

    Route::post('/forgot-password', [
        PasswordResetController::class,
        'sendLink'
    ])
    ->middleware('throttle:3,1')
    ->name('password.email');

    Route::get('/reset-password', [
        PasswordResetController::class,
        'resetForm'
    ])->name('password.reset');

    Route::post('/reset-password', [
        PasswordResetController::class,
        'resetPassword'
    ])
    ->middleware('throttle:5,1')
    ->name('password.update');

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
| HALAMAN FINS - WAJIB LOGIN
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
    | NOTIFIKASI
    |--------------------------------------------------------------------------
    */

    Route::get('/notifikasi', function () {
        return view('notifikasi');
    })->name('notifikasi');

    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */

    Route::get('/profile', [
        ProfileController::class,
        'index'
    ])->name('profile');

    Route::put('/profile', [
        ProfileController::class,
        'update'
    ])->name('profile.update');

    Route::post('/profile/photo', [
        ProfileController::class,
        'uploadPhoto'
    ])->name('profile.photo.upload');

    Route::delete('/profile/photo', [
        ProfileController::class,
        'removePhoto'
    ])->name('profile.photo.remove');

    /*
    |--------------------------------------------------------------------------
    | CARI PARTNER
    |--------------------------------------------------------------------------
    */

    Route::get('/cari-partner', [
        CariPartnerController::class,
        'index'
    ])->name('cari-partner');

    /*
    |--------------------------------------------------------------------------
    | CARI LOMBA
    |--------------------------------------------------------------------------
    */

    Route::get('/cari-lomba', [
        CompetitionController::class,
        'index'
    ])->name('cari-lomba');

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

    Route::get('/posting-lomba', [
        PostingLombaController::class,
        'create'
    ])->name('posting-lomba');

    Route::get('/posting-lomba/create', [
        PostingLombaController::class,
        'create'
    ])->name('posting-lomba.create');

    Route::post('/posting-lomba', [
        PostingLombaController::class,
        'store'
    ])->name('posting-lomba.store');

    Route::post('/tambah-lomba', [
        CompetitionController::class,
        'store'
    ])->name('lomba.store');

    /*
    |--------------------------------------------------------------------------
    | LOMBA SAYA
    |--------------------------------------------------------------------------
    */

    Route::get('/lomba-saya', [
        LombaSayaController::class,
        'index'
    ])->name('lomba-saya');

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
    | CHAT FILE
    |--------------------------------------------------------------------------
    */

    Route::post('/chat/upload', [
        ChatFileController::class,
        'upload'
    ])->name('chat.file.upload');

    Route::get('/chat/files/{filename}', [
        ChatFileController::class,
        'download'
    ])
    ->where('filename', '[A-Za-z0-9._-]+')
    ->name('chat.file.download');

    /*
    |--------------------------------------------------------------------------
    | CHAT ATTACHMENTS - SUPABASE STORAGE
    |--------------------------------------------------------------------------
    */

    Route::post('/chat/attachments/upload', [
        ChatAttachmentController::class,
        'upload'
    ])->name('chat.attachments.upload');

    Route::get('/chat/attachments/{attachmentId}/download', [
        ChatAttachmentController::class,
        'download'
    ])
    ->whereNumber('attachmentId')
    ->name('chat.attachments.download');

    /*
    |--------------------------------------------------------------------------
    | CHAT CONVERSATIONS
    |--------------------------------------------------------------------------
    */

    Route::get('/chat/conversations', [
        ChatController::class,
        'index'
    ])->name('chat.conversations.index');

    Route::get('/chat/conversations/{conversationId}/messages', [
        ChatController::class,
        'messages'
    ])
    ->whereNumber('conversationId')
    ->name('chat.conversations.messages');

    Route::post('/chat/conversations/{conversationId}/messages', [
        ChatController::class,
        'sendMessage'
    ])
    ->whereNumber('conversationId')
    ->name('chat.conversations.messages.send');

    /*
    |--------------------------------------------------------------------------
    | PARTNER INVITATIONS - CARI PARTNER
    |--------------------------------------------------------------------------
    */

    // User A mengirim undangan
    Route::post('/partner-invitations', [
        PartnerInvitationController::class,
        'store'
    ])->name('partner-invitations.store');

    // User B melihat undangan masuk
    Route::get('/partner-invitations/received', [
        PartnerInvitationController::class,
        'received'
    ])->name('partner-invitations.received');

    // User A melihat undangan terkirim
    Route::get('/partner-invitations/sent', [
        PartnerInvitationController::class,
        'sent'
    ])->name('partner-invitations.sent');

    // User B menerima atau menolak undangan
    Route::patch('/partner-invitations/{id}/respond', [
        PartnerInvitationController::class,
        'respond'
    ])
    ->whereNumber('id')
    ->name('partner-invitations.respond');

});

// ================= SUPABASE REALTIME =================

Route::get('/realtime/token', [RealtimeController::class, 'token'])
    ->middleware(['auth', 'throttle:60,1'])
    ->block(30, 30)
    ->name('realtime.token');

// ================= NOTIFICATIONS =================

Route::middleware('auth')->prefix('notifications')->group(function () {

    Route::get('/', [NotificationController::class, 'index'])
        ->name('notifications.index');

    Route::get('/unread-count', [NotificationController::class, 'unreadCount'])
        ->name('notifications.unread-count');

    Route::patch('/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.read-all');

    Route::patch('/{id}/read', [NotificationController::class, 'markAsRead'])
        ->whereNumber('id')
        ->name('notifications.read');

});