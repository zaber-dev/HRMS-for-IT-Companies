<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// ---------------------------------------------------------------------------
// Seeder integration tests
// ---------------------------------------------------------------------------

describe('DatabaseSeeder', function () {
    test('creates exactly 4 roles', function () {
        $this->seed(DatabaseSeeder::class);

        expect(Role::count())->toBe(4);
    });

    test('creates the four expected built-in roles', function () {
        $this->seed(DatabaseSeeder::class);

        foreach (['super_admin', 'admin', 'hr', 'employee'] as $roleName) {
            expect(Role::where('name', $roleName)->exists())->toBeTrue("Role '{$roleName}' should exist");
        }
    });

    test('creates exactly 10 permissions', function () {
        $this->seed(DatabaseSeeder::class);

        expect(Permission::count())->toBe(10);
    });

    test('creates all 10 expected permissions', function () {
        $this->seed(DatabaseSeeder::class);

        $expected = [
            'user.view',
            'user.create',
            'user.update',
            'user.delete',
            'role.view',
            'role.create',
            'role.update',
            'role.delete',
            'permission.manage',
            'audit-log.view',
        ];

        foreach ($expected as $permissionName) {
            expect(Permission::where('name', $permissionName)->exists())
                ->toBeTrue("Permission '{$permissionName}' should exist");
        }
    });

    test('creates exactly one super_admin user', function () {
        $this->seed(DatabaseSeeder::class);

        $superAdminRole = Role::where('name', 'super_admin')->first();

        expect($superAdminRole->users()->count())->toBe(1);
    });

    test('creates super_admin user with correct email and attributes', function () {
        $this->seed(DatabaseSeeder::class);

        $user = User::where('email', 'superadmin@example.com')->first();

        expect($user)->not->toBeNull()
            ->and($user->name)->toBe('Super Admin')
            ->and($user->is_active)->toBeTrue()
            ->and($user->must_change_password)->toBeFalse();
    });

    test('super_admin user has the super_admin role assigned', function () {
        $this->seed(DatabaseSeeder::class);

        $user = User::where('email', 'superadmin@example.com')->first();

        expect($user->hasRole('super_admin'))->toBeTrue();
    });
});

// ---------------------------------------------------------------------------
// Registration disabled tests
// ---------------------------------------------------------------------------

describe('registration disabled', function () {
    test('GET /register returns 404', function () {
        $this->get('/register')->assertNotFound();
    });

    test('POST /register returns 404', function () {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertNotFound();
    });
});
