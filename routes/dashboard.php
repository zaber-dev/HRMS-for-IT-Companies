<?php

use App\Http\Controllers\DashboardController;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureUserIsActive;

Route::middleware(['auth', 'verified', EnsureUserIsActive::class, EnsurePasswordChanged::class])
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });
