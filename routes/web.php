<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Root redirect: authenticated → dashboard, guest → login
Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

require __DIR__.'/dashboard.php';
require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/leave.php';
require __DIR__.'/skills.php';
require __DIR__.'/projects.php';
