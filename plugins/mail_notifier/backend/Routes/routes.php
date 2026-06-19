<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Plugins\MailNotifier\Http\Controllers\MailNotifierController;

Route::get('status', [MailNotifierController::class, 'status']);
Route::post('connect', [MailNotifierController::class, 'connect']);
Route::post('disconnect', [MailNotifierController::class, 'disconnect']);
Route::post('send-verification', [MailNotifierController::class, 'sendVerification']);
Route::post('verify-email', [MailNotifierController::class, 'verifyEmail']);
