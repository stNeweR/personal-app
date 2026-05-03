<?php

use App\Core\Telegram\Infrastructure\Http\V1\Controllers\TelegramWebhookController;
use App\Modules\User\Infrastructure\Http\V1\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('telegram-webhook', [TelegramWebhookController::class, 'handleWebhook']);

    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });
});
