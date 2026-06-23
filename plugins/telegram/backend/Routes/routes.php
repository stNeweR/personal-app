<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Plugins\Telegram\Http\Controllers\TelegramNotifierController;

Route::get('status', [TelegramNotifierController::class, 'status']);
Route::post('disconnect', [TelegramNotifierController::class, 'disconnect']);
Route::get('history', [TelegramNotifierController::class, 'history']);
