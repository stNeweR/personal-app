<?php

use App\Core\Telegram\Infrastructure\Http\V1\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('telegram-webhook', [TelegramWebhookController::class, 'handleWebhook']);
});
