<?php

use App\Http\Controllers\LogtimeController;
use Illuminate\Support\Facades\Route;

Route::match(['get', 'post'], '/', [LogtimeController::class, 'index'])
    ->middleware(['app_basic_auth', 'throttle:60,1'])
    ->name('logtime.index');
