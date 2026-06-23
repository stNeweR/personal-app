<?php

declare(strict_types=1);

use Plugins\Playlist\Http\Controllers\PlaylistController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PlaylistController::class, 'show']);
Route::post('/', [PlaylistController::class, 'save']);
Route::delete('/', [PlaylistController::class, 'destroy']);
