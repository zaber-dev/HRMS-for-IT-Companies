<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated employees can visit the dashboard', function () {
    $user = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
        'bench_status' => 'on_bench',
    ])->assignRole('employee');

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->component('dashboard/employee'));
});

test('authenticated hr users can visit the dashboard', function () {
    $user = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ])->assignRole('hr');

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->component('dashboard/hr'));
});

test('users with no role receive a 403', function () {
    $user = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertForbidden();
});
