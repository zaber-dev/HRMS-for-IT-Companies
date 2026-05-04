<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Register a test route protected by the middleware
    Route::middleware(['web', 'auth', EnsureUserIsActive::class])
        ->get('/_test/active-check', fn () => response('ok'))
        ->name('test.active-check');
});

test('inactive user is logged out and redirected to login', function () {
    $user = User::factory()->create(['is_active' => false]);

    $response = $this->actingAs($user)->get('/_test/active-check');

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});

test('inactive user session is invalidated after redirect', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->actingAs($user)->get('/_test/active-check');

    // User should no longer be authenticated
    $this->assertGuest();
});

test('active user passes through the middleware without interruption', function () {
    $user = User::factory()->create(['is_active' => true]);

    $response = $this->actingAs($user)->get('/_test/active-check');

    $response->assertOk();
    $response->assertSeeText('ok');
});

test('active user remains authenticated after passing through middleware', function () {
    $user = User::factory()->create(['is_active' => true]);

    $this->actingAs($user)->get('/_test/active-check');

    $this->assertAuthenticated();
});

test('inactive user redirect includes a flash error message', function () {
    $user = User::factory()->create(['is_active' => false]);

    $response = $this->actingAs($user)->get('/_test/active-check');

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error', 'Your account has been deactivated.');
});

test('unauthenticated request is redirected to login without triggering middleware logout', function () {
    $response = $this->get('/_test/active-check');

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});
