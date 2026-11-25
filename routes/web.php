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

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {

    Route::get('/login', [
        LoginController::class,
        'showLogin'
    ])->name('login');

    Route::post('/login', [
        LoginController::class,
        'login'
    ])->name('login.submit');

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

Route::post('/logout', [
    LoginController::class,
    'logout'
])
->middleware('auth')
->name('logout');

Route::middleware('auth')->group(function () {

    Route::get('/home', function () {
        return view('home');
    })->name('home');

    Route::get('/notifikasi', function () {
        return view('notifikasi');
    })->name('notifikasi');

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

    Route::get('/cari-partner', [
        CariPartnerController::class,
        'index'
    ])->name('cari-partner');

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

    Route::get('/lomba-saya', [
        LombaSayaController::class,
        'index'
    ])->name('lomba-saya');

    Route::get('/chat', function () {
        return view('chat');
    })->name('chat');

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

    Route::patch('/chat/conversations/{conversationId}/messages/{messageId}', [
        ChatController::class,
        'updateMessage'
    ])
    ->whereNumber('conversationId')
    ->whereNumber('messageId')
    ->name('chat.conversations.messages.update');

    Route::delete('/chat/conversations/{conversationId}/messages/{messageId}/me', [
        ChatController::class,
        'deleteMessageForMe'
    ])
    ->whereNumber('conversationId')
    ->whereNumber('messageId')
    ->name('chat.messages.delete.me');

    Route::delete('/chat/conversations/{conversationId}/messages/{messageId}/everyone', [
        ChatController::class,
        'deleteMessageForEveryone'
    ])
    ->whereNumber('conversationId')
    ->whereNumber('messageId')
    ->name('chat.messages.delete.everyone');

    Route::post('/partner-invitations', [
        PartnerInvitationController::class,
        'store'
    ])->name('partner-invitations.store');

    Route::get('/partner-invitations/received', [
        PartnerInvitationController::class,
        'received'
    ])->name('partner-invitations.received');

    Route::get('/partner-invitations/sent', [
        PartnerInvitationController::class,
        'sent'
    ])->name('partner-invitations.sent');

    Route::patch('/partner-invitations/{id}/respond', [
        PartnerInvitationController::class,
        'respond'
    ])
    ->whereNumber('id')
    ->name('partner-invitations.respond');

});

Route::get('/realtime/token', [
    RealtimeController::class,
    'token'
])
->middleware(['auth', 'throttle:60,1'])
->block(30, 30)
->name('realtime.token');

Route::middleware('auth')->prefix('notifications')->group(function () {

    Route::get('/', [
        NotificationController::class,
        'index'
    ])->name('notifications.index');

    Route::get('/unread-count', [
        NotificationController::class,
        'unreadCount'
    ])->name('notifications.unread-count');

    Route::patch('/read-all', [
        NotificationController::class,
        'markAllAsRead'
    ])->name('notifications.read-all');

    Route::patch('/{id}/read', [
        NotificationController::class,
        'markAsRead'
    ])
    ->whereNumber('id')
    ->name('notifications.read');

});