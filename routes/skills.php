<?php

use App\Http\Controllers\Skills\SkillAssignmentController;
use App\Http\Controllers\Skills\SkillCategoryController;
use App\Http\Controllers\Skills\SkillController;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureUserIsActive;

Route::middleware(['auth', 'verified', EnsureUserIsActive::class, EnsurePasswordChanged::class])
    ->group(function () {

        // Skill Catalogue
        Route::prefix('skills')->name('skills.')->group(function () {
            Route::get('/', [SkillController::class, 'index'])->name('index');
            Route::get('/create', [SkillController::class, 'create'])->name('create');
            Route::post('/', [SkillController::class, 'store'])->name('store');
            Route::get('/{skill}', [SkillController::class, 'show'])->name('show');
            Route::get('/{skill}/edit', [SkillController::class, 'edit'])->name('edit');
            Route::put('/{skill}', [SkillController::class, 'update'])->name('update');
            Route::delete('/{skill}', [SkillController::class, 'destroy'])->name('destroy');
            Route::patch('/{skill}/toggle', [SkillController::class, 'toggle'])->name('toggle');
        });

        // Skill Categories
        Route::prefix('skill-categories')->name('skill-categories.')->group(function () {
            Route::get('/', [SkillCategoryController::class, 'index'])->name('index');
            Route::get('/create', [SkillCategoryController::class, 'create'])->name('create');
            Route::post('/', [SkillCategoryController::class, 'store'])->name('store');
            Route::get('/{skillCategory}/edit', [SkillCategoryController::class, 'edit'])->name('edit');
            Route::put('/{skillCategory}', [SkillCategoryController::class, 'update'])->name('update');
        });

        // Skill Assignments (scoped to a user)
        Route::prefix('users/{user}/skills')->name('users.skills.')->group(function () {
            Route::post('/', [SkillAssignmentController::class, 'store'])->name('store');
            Route::delete('/{skill}', [SkillAssignmentController::class, 'destroy'])->name('destroy');
        });
    });
