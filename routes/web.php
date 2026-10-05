<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\MailboxController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Google redirects here after consent; auth happens via the state-linked mailbox.
Route::get('/oauth/callback', [MailboxController::class, 'callback'])->name('oauth.callback');

// Gmail real-time push notifications (Pub/Sub). Token in path; no session.
Route::post('/gmail/webhook/{token}', [\App\Http\Controllers\GmailWebhookController::class, 'handle'])
    ->name('gmail.webhook');

Route::middleware('auth')->group(function () {
    Route::get('/', [InboxController::class, 'index'])->name('inbox');
    Route::get('/thread/{thread}', [InboxController::class, 'show'])->name('thread');
    Route::post('/thread/{thread}/reply', [InboxController::class, 'reply'])->name('thread.reply');
    Route::post('/sync', [InboxController::class, 'sync'])->name('sync');
    Route::get('/password', [AuthController::class, 'showPassword'])->name('password');
    Route::post('/password', [AuthController::class, 'updatePassword'])->name('password.update');
    Route::get('/attachment/{attachment}', [AttachmentController::class, 'download'])->name('attachment');

    Route::middleware('admin')->group(function () {
        Route::get('/mailboxes', [MailboxController::class, 'index'])->name('mailboxes');
        Route::post('/mailboxes', [MailboxController::class, 'store'])->name('mailboxes.store');
        Route::post('/mailboxes/{mailbox}', [MailboxController::class, 'update'])->name('mailboxes.update');
        Route::post('/mailboxes/{mailbox}/delete', [MailboxController::class, 'destroy'])->name('mailboxes.delete');
        Route::get('/mailboxes/connect-new', [MailboxController::class, 'connectNew'])->name('mailboxes.connect-new');
        Route::get('/mailboxes/{mailbox}/connect', [MailboxController::class, 'connect'])->name('mailboxes.connect');

        Route::get('/activity', [\App\Http\Controllers\ActivityController::class, 'index'])->name('activity');
        Route::get('/users', [UserController::class, 'index'])->name('users');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::post('/users/{user}/delete', [UserController::class, 'destroy'])->name('users.delete');
    });
});
