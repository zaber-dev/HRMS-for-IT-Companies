<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

require __DIR__.'/dashboard.php';
require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
require __DIR__.'/leave.php';
require __DIR__.'/skills.php';
require __DIR__.'/projects.php';
