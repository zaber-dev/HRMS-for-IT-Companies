<?php

use App\Http\Middleware\EnsurePasswordChanged;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Register a test route protected by the middleware
    Route::middleware(['web', 'auth', EnsurePasswordChanged::class])
        ->get('/_test/password-check', fn () => response('ok'))
        ->name('test.password-check');

    // Register a test route that simulates the password change page (exempt from redirect)
    Route::middleware(['web', 'auth', EnsurePasswordChanged::class])
        ->get('/_test/security', fn () => response('security page'));
});

test('user with must_change_password true is redirected to settings.security', function () {
    $user = User::factory()->create(['must_change_password' => true]);

    $response = $this->actingAs($user)->get('/_test/password-check');

    $response->assertRedirect(route('security.edit'));
});

test('user already on settings.security is not redirected to avoid infinite loop', function () {
    $user = User::factory()->create(['must_change_password' => true]);

    // Visiting the actual security.edit URL; EnsurePasswordChanged exempts it via routeIs check
    // The response may be redirected by password.confirm middleware, but NOT by EnsurePasswordChanged
    $response = $this->actingAs($user)->get('/settings/security');

    // Assert the redirect is NOT back to security.edit (which would be an infinite loop)
    expect($response->headers->get('Location'))->not->toBe(route('security.edit'));
});

test('user with must_change_password false passes through normally', function () {
    $user = User::factory()->create(['must_change_password' => false]);

    $response = $this->actingAs($user)->get('/_test/password-check');

    $response->assertOk();
    $response->assertSeeText('ok');
});

test('user with must_change_password true remains authenticated after redirect', function () {
    $user = User::factory()->create(['must_change_password' => true]);

    $this->actingAs($user)->get('/_test/password-check');

    // Unlike EnsureUserIsActive, this middleware does NOT log the user out
    $this->assertAuthenticated();
});

test('unauthenticated request is redirected to login without triggering middleware', function () {
    $response = $this->get('/_test/password-check');

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});
