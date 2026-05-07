<?php

use App\Http\Controllers\Projects\MyProjectController;
use App\Http\Controllers\Projects\ProjectAssignmentController;
use App\Http\Controllers\Projects\ProjectController;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureUserIsActive;

Route::middleware(['auth', 'verified', EnsureUserIsActive::class, EnsurePasswordChanged::class])
    ->group(function () {

        // Project CRUD (privileged users)
        Route::prefix('projects')->name('projects.')->group(function () {
            Route::get('/', [ProjectController::class, 'index'])->name('index');
            Route::get('/create', [ProjectController::class, 'create'])->name('create');
            Route::post('/', [ProjectController::class, 'store'])->name('store');
            Route::get('/{project}', [ProjectController::class, 'show'])->name('show');
            Route::get('/{project}/edit', [ProjectController::class, 'edit'])->name('edit');
            Route::put('/{project}', [ProjectController::class, 'update'])->name('update');
            Route::get('/{project}/delete', [ProjectController::class, 'delete'])->name('delete');
            Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy');

            // Assignments (scoped to a project)
            Route::prefix('/{project}/assignments')->name('assignments.')->group(function () {
                Route::get('/create', [ProjectAssignmentController::class, 'create'])->name('create');
                Route::post('/', [ProjectAssignmentController::class, 'store'])->name('store');
                Route::get('/{assignment}/edit', [ProjectAssignmentController::class, 'edit'])->name('edit');
                Route::put('/{assignment}', [ProjectAssignmentController::class, 'update'])->name('update');
                Route::delete('/{assignment}', [ProjectAssignmentController::class, 'destroy'])->name('destroy');
            });
        });

        // Employee's own projects
        Route::prefix('my-projects')->name('my-projects.')->group(function () {
            Route::get('/', [MyProjectController::class, 'index'])->name('index');
            Route::patch('/{assignment}/complete', [MyProjectController::class, 'markComplete'])->name('complete');
        });
    });
