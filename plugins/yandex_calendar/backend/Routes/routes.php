<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Plugins\YandexCalendar\Http\Controllers\YandexCalendarController;

Route::post('connect', [YandexCalendarController::class, 'connect']);
Route::get('today', [YandexCalendarController::class, 'today']);
Route::get('status', [YandexCalendarController::class, 'status']);
