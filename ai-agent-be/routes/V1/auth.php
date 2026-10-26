<?php

use App\Http\Controllers\API\V1\LoginController;
use Illuminate\Support\Facades\Route;

Route::name('auth.')
    ->prefix('auth')
    ->group(function (): void {
        Route::post('/login', [LoginController::class, 'login'])
            ->middleware('throttle:login')
            ->name('login');
    });
