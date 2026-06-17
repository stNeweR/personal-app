<?php

use App\Core\Telegram\Infrastructure\Http\V1\Controllers\TelegramWebhookController;
use App\Modules\Plugin\Infrastructure\Http\V1\Controllers\PluginController;
use App\Modules\Pomodoro\Infrastructure\Http\V1\Controllers\PomodoroController;
use App\Modules\User\Infrastructure\Http\V1\Controllers\AuthController;
use App\Modules\User\Infrastructure\Http\V1\Controllers\TodoistController;
use App\Modules\User\Infrastructure\Http\V1\Controllers\YandexCalendarController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('telegram-webhook', [TelegramWebhookController::class, 'handleWebhook']);

    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::post('telegram-link-token', [AuthController::class, 'telegramLinkToken']);
        });
    });

    Route::middleware('auth:sanctum')->prefix('yandex-calendar')->group(function () {
        Route::post('connect', [YandexCalendarController::class, 'connect']);
        Route::get('today', [YandexCalendarController::class, 'today']);
        Route::get('status', [YandexCalendarController::class, 'status']);
    });

    Route::middleware('auth:sanctum')->prefix('todoist')->group(function () {
        Route::get('status', [TodoistController::class, 'status']);
        Route::post('connect', [TodoistController::class, 'connect']);
        Route::post('disconnect', [TodoistController::class, 'disconnect']);
        Route::get('tasks', [TodoistController::class, 'index']);
        Route::post('tasks', [TodoistController::class, 'store']);
        Route::post('tasks/{id}/complete', [TodoistController::class, 'complete']);
        Route::post('tasks/{id}/reopen', [TodoistController::class, 'reopen']);
        Route::delete('tasks/{id}', [TodoistController::class, 'destroy']);
    });

    Route::middleware('auth:sanctum')->prefix('pomodoro')->group(function () {
        Route::get('sessions', [PomodoroController::class, 'today']);
        Route::get('sessions/active', [PomodoroController::class, 'activeSession']);
        Route::get('settings', [PomodoroController::class, 'getSettings']);
        Route::post('settings', [PomodoroController::class, 'saveSettings']);
        Route::post('sessions', [PomodoroController::class, 'createSession']);
        Route::patch('sessions/{id}', [PomodoroController::class, 'updateSession']);
        Route::delete('sessions/{id}', [PomodoroController::class, 'deleteSession']);
    });

    Route::middleware('auth:sanctum')->prefix('plugins')->group(function () {
        Route::post('{name}/{action}', [PluginController::class, 'execute']);
    });
});
