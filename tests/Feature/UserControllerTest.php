<?php

use App\Models\AuditLog;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

// ---------------------------------------------------------------------------
// Helpers (reuse the same pattern as UserPolicyTest)
// ---------------------------------------------------------------------------

/**
 * Create all roles and permissions per the permission matrix.
 *
 * @return array<string, Role>
 */
function setupRolesAndPermissionsForController(): array
{
    // Flush Spatie's in-memory permission cache so each test starts clean.
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $permissions = [
        'user.view', 'user.create', 'user.update', 'user.delete',
        'role.view', 'role.create', 'role.update', 'role.delete',
        'permission.manage', 'audit-log.view',
    ];

    foreach ($permissions as $name) {
        Permission::firstOrCreate(['name' => $name]);
    }

    $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
    $superAdmin->syncPermissions($permissions);

    $admin = Role::firstOrCreate(['name' => 'admin']);
    $admin->syncPermissions([
        'user.view', 'user.create', 'user.update', 'user.delete',
        'role.view', 'role.create', 'role.update', 'role.delete',
    ]);

    $hr = Role::firstOrCreate(['name' => 'hr']);
    $hr->syncPermissions([
        'user.view', 'user.create', 'user.update', 'user.delete',
    ]);

    $employee = Role::firstOrCreate(['name' => 'employee']);
    $employee->syncPermissions([]);

    return [
        'super_admin' => $superAdmin,
        'admin' => $admin,
        'hr' => $hr,
        'employee' => $employee,
    ];
}

/**
 * Create a user with the given role and mark them as active with no forced password change.
 */
function userWithRoleForController(string $roleName): User
{
    $user = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ]);
    $user->assignRole($roleName);

    return $user;
}

// ---------------------------------------------------------------------------
// Happy path — super_admin can create a user
// ---------------------------------------------------------------------------

describe('store (create user)', function () {
    beforeEach(fn () => setupRolesAndPermissionsForController());

    test('super_admin can create a user and is redirected to admin.users.index', function () {
        $actor = userWithRoleForController('super_admin');

        $this->actingAs($actor)
            ->post(route('admin.users.store'), [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'role' => 'admin',
                'password' => 'password123',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'is_active' => true,
            'must_change_password' => true,
        ]);
    });

    test('created user has the assigned role', function () {
        $actor = userWithRoleForController('super_admin');

        $this->actingAs($actor)
            ->post(route('admin.users.store'), [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'role' => 'hr',
                'password' => 'password123',
            ]);

        $user = User::where('email', 'jane@example.com')->first();
        expect($user->hasRole('hr'))->toBeTrue();
    });

    test('store creates one AuditLog entry for user creation', function () {
        $actor = userWithRoleForController('super_admin');

        $this->actingAs($actor)
            ->post(route('admin.users.store'), [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'role' => 'hr',
                'password' => 'password123',
            ]);

        expect(AuditLog::count())->toBe(1);

        $log = AuditLog::first();
        expect($log->user_id)->toBe($actor->id)
            ->and($log->action)->toBe('user.created');
    });
});

// ---------------------------------------------------------------------------
// Happy path — super_admin can edit a user
// ---------------------------------------------------------------------------

describe('update (edit user)', function () {
    beforeEach(fn () => setupRolesAndPermissionsForController());

    test('super_admin can update a user and is redirected to admin.users.index', function () {
        $actor = userWithRoleForController('super_admin');
        $target = userWithRoleForController('hr');

        $this->actingAs($actor)
            ->put(route('admin.users.update', $target), [
                'name' => 'Updated Name',
                'email' => $target->email,
                'role' => 'hr',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'name' => 'Updated Name',
        ]);
    });

    test('update creates one AuditLog entry when role changes', function () {
        $actor = userWithRoleForController('super_admin');
        $target = userWithRoleForController('hr');

        $this->actingAs($actor)
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'role' => 'employee',
            ]);

        expect(AuditLog::count())->toBe(1);

        $log = AuditLog::first();
        expect($log->user_id)->toBe($actor->id)
            ->and($log->action)->toBe('role.assigned')
            ->and($log->old_values)->toBe(['role' => 'hr'])
            ->and($log->new_values)->toBe(['role' => 'employee']);
    });

    test('update does not create an AuditLog entry when role is unchanged', function () {
        $actor = userWithRoleForController('super_admin');
        $target = userWithRoleForController('hr');

        $this->actingAs($actor)
            ->put(route('admin.users.update', $target), [
                'name' => 'New Name',
                'email' => $target->email,
                'role' => 'hr',
            ]);

        expect(AuditLog::count())->toBe(0);
    });
});

// ---------------------------------------------------------------------------
// Happy path — super_admin can deactivate a user
// ---------------------------------------------------------------------------

describe('destroy (deactivate user)', function () {
    beforeEach(fn () => setupRolesAndPermissionsForController());

    test('super_admin can deactivate a user and is redirected to admin.users.index', function () {
        $actor = userWithRoleForController('super_admin');
        $target = userWithRoleForController('hr');

        $this->actingAs($actor)
            ->delete(route('admin.users.destroy', $target))
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'is_active' => false,
        ]);
    });

    test('destroy creates one AuditLog entry for deactivation', function () {
        $actor = userWithRoleForController('super_admin');
        $target = userWithRoleForController('employee');

        $this->actingAs($actor)
            ->delete(route('admin.users.destroy', $target));

        expect(AuditLog::count())->toBe(1);

        $log = AuditLog::first();
        expect($log->user_id)->toBe($actor->id)
            ->and($log->action)->toBe('user.deactivated')
            ->and($log->old_values)->toBe(['is_active' => true])
            ->and($log->new_values)->toBe(['is_active' => false]);
    });
});

// ---------------------------------------------------------------------------
// 403 cases — hierarchy enforcement
// ---------------------------------------------------------------------------

describe('403 — hierarchy enforcement', function () {
    beforeEach(fn () => setupRolesAndPermissionsForController());

    test('admin attempting to manage another admin returns 403 on update', function () {
        $actor = userWithRoleForController('admin');
        $target = userWithRoleForController('admin');

        $this->actingAs($actor)
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'role' => 'hr',
            ])
            ->assertForbidden();
    });

    test('admin attempting to deactivate another admin returns 403', function () {
        $actor = userWithRoleForController('admin');
        $target = userWithRoleForController('admin');

        $this->actingAs($actor)
            ->delete(route('admin.users.destroy', $target))
            ->assertForbidden();
    });

    test('HR attempting to manage an HR user returns 403 on update', function () {
        $actor = userWithRoleForController('hr');
        $target = userWithRoleForController('hr');

        $this->actingAs($actor)
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'role' => 'employee',
            ])
            ->assertForbidden();
    });

    test('HR attempting to deactivate an HR user returns 403', function () {
        $actor = userWithRoleForController('hr');
        $target = userWithRoleForController('hr');

        $this->actingAs($actor)
            ->delete(route('admin.users.destroy', $target))
            ->assertForbidden();
    });
});

// ---------------------------------------------------------------------------
// Validation — missing role and duplicate email
// ---------------------------------------------------------------------------

describe('validation', function () {
    beforeEach(fn () => setupRolesAndPermissionsForController());

    test('missing role returns 422 on store', function () {
        $actor = userWithRoleForController('super_admin');

        $this->actingAs($actor)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('admin.users.store'), [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'password' => 'password123',
                // 'role' intentionally omitted
            ])
            ->assertStatus(422)
            ->assertInvalid(['role']);
    });

    test('duplicate email returns 422 on store', function () {
        $actor = userWithRoleForController('super_admin');
        $existing = userWithRoleForController('employee');

        $this->actingAs($actor)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('admin.users.store'), [
                'name' => 'Jane Doe',
                'email' => $existing->email,
                'role' => 'employee',
                'password' => 'password123',
            ])
            ->assertStatus(422)
            ->assertInvalid(['email']);
    });

    test('missing role returns 422 on update', function () {
        $actor = userWithRoleForController('super_admin');
        $target = userWithRoleForController('hr');

        $this->actingAs($actor)
            ->withHeaders(['Accept' => 'application/json'])
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                // 'role' intentionally omitted
            ])
            ->assertStatus(422)
            ->assertInvalid(['role']);
    });

    test('duplicate email returns 422 on update', function () {
        $actor = userWithRoleForController('super_admin');
        $target = userWithRoleForController('hr');
        $other = userWithRoleForController('employee');

        $this->actingAs($actor)
            ->withHeaders(['Accept' => 'application/json'])
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $other->email,
                'role' => 'hr',
            ])
            ->assertStatus(422)
            ->assertInvalid(['email']);
    });
});
