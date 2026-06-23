<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Plugins\Playlist\Http\Controllers\PlaylistController;

Route::get('/', [PlaylistController::class, 'show']);
Route::post('/', [PlaylistController::class, 'save']);
Route::delete('/', [PlaylistController::class, 'destroy']);
