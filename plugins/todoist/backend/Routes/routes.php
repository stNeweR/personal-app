<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Plugins\Todoist\Http\Controllers\TodoistController;

Route::get('status', [TodoistController::class, 'status']);
Route::post('connect', [TodoistController::class, 'connect']);
Route::post('disconnect', [TodoistController::class, 'disconnect']);
Route::get('tasks', [TodoistController::class, 'index']);
Route::post('tasks', [TodoistController::class, 'store']);
Route::post('tasks/{id}/complete', [TodoistController::class, 'complete']);
Route::post('tasks/{id}/reopen', [TodoistController::class, 'reopen']);
Route::delete('tasks/{id}', [TodoistController::class, 'destroy']);
