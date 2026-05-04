<?php

use App\Models\AuditLog;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

// ---------------------------------------------------------------------------
// Helpers — reuse the same pattern as UserControllerTest
// ---------------------------------------------------------------------------

/**
 * Create all roles and permissions per the permission matrix.
 *
 * @return array<string, Role>
 */
function setupRolesAndPermissionsForRoleController(): array
{
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
 * Create a user with the given role, marked active with no forced password change.
 */
function userWithRoleForRoleController(string $roleName): User
{
    $user = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ]);
    $user->assignRole($roleName);

    return $user;
}

// ---------------------------------------------------------------------------
// Happy path — super_admin creates a custom role
// ---------------------------------------------------------------------------

describe('store (create role)', function () {
    beforeEach(fn () => setupRolesAndPermissionsForRoleController());

    test('super_admin can create a custom role and is redirected to admin.roles.index', function () {
        $actor = userWithRoleForRoleController('super_admin');

        $this->actingAs($actor)
            ->post(route('admin.roles.store'), ['name' => 'custom_role'])
            ->assertRedirect(route('admin.roles.index'));

        $this->assertDatabaseHas('roles', ['name' => 'custom_role']);
    });

    test('store creates one AuditLog entry for role creation', function () {
        $actor = userWithRoleForRoleController('super_admin');

        $this->actingAs($actor)
            ->post(route('admin.roles.store'), ['name' => 'custom_role']);

        expect(AuditLog::count())->toBe(1);

        $log = AuditLog::first();
        expect($log->user_id)->toBe($actor->id)
            ->and($log->action)->toBe('role.created');
    });
});

// ---------------------------------------------------------------------------
// Happy path — super_admin updates a custom role
// ---------------------------------------------------------------------------

describe('update (edit role)', function () {
    beforeEach(fn () => setupRolesAndPermissionsForRoleController());

    test('super_admin can update a custom role and is redirected to admin.roles.index', function () {
        $actor = userWithRoleForRoleController('super_admin');
        $role = Role::create(['name' => 'old_name', 'guard_name' => 'web']);

        $this->actingAs($actor)
            ->put(route('admin.roles.update', $role), ['name' => 'new_name'])
            ->assertRedirect(route('admin.roles.index'));

        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'new_name']);
    });

    test('update creates one AuditLog entry for role update', function () {
        $actor = userWithRoleForRoleController('super_admin');
        $role = Role::create(['name' => 'old_name', 'guard_name' => 'web']);

        $this->actingAs($actor)
            ->put(route('admin.roles.update', $role), ['name' => 'new_name']);

        expect(AuditLog::count())->toBe(1);

        $log = AuditLog::first();
        expect($log->user_id)->toBe($actor->id)
            ->and($log->action)->toBe('role.updated')
            ->and($log->old_values)->toBe(['name' => 'old_name'])
            ->and($log->new_values)->toBe(['name' => 'new_name']);
    });
});

// ---------------------------------------------------------------------------
// Happy path — super_admin deletes a custom role with no users
// ---------------------------------------------------------------------------

describe('destroy (delete role)', function () {
    beforeEach(fn () => setupRolesAndPermissionsForRoleController());

    test('super_admin can delete a custom role with no users and is redirected to admin.roles.index', function () {
        $actor = userWithRoleForRoleController('super_admin');
        $role = Role::create(['name' => 'deletable_role', 'guard_name' => 'web']);

        $this->actingAs($actor)
            ->delete(route('admin.roles.destroy', $role))
            ->assertRedirect(route('admin.roles.index'));

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    });

    test('destroy creates one AuditLog entry for role deletion', function () {
        $actor = userWithRoleForRoleController('super_admin');
        $role = Role::create(['name' => 'deletable_role', 'guard_name' => 'web']);

        $this->actingAs($actor)
            ->delete(route('admin.roles.destroy', $role));

        expect(AuditLog::count())->toBe(1);

        $log = AuditLog::first();
        expect($log->user_id)->toBe($actor->id)
            ->and($log->action)->toBe('role.deleted');
    });
});

// ---------------------------------------------------------------------------
// 422 cases — built-in role and role with assigned users
// ---------------------------------------------------------------------------

describe('422 — delete restrictions', function () {
    beforeEach(fn () => setupRolesAndPermissionsForRoleController());

    test('attempt to delete a built-in role returns 422 JSON response', function (string $builtInRole) {
        $actor = userWithRoleForRoleController('super_admin');
        $role = Role::where('name', $builtInRole)->first();

        $this->actingAs($actor)
            ->delete(route('admin.roles.destroy', $role))
            ->assertStatus(422)
            ->assertJson(['message' => 'Cannot delete a built-in role.']);
    })->with(['super_admin', 'admin', 'hr', 'employee']);

    test('attempt to delete a role with assigned users returns 422 JSON response with user count', function () {
        $actor = userWithRoleForRoleController('super_admin');
        $role = Role::create(['name' => 'occupied_role', 'guard_name' => 'web']);

        // Assign two users to this role
        User::factory()->count(2)->create([
            'is_active' => true,
            'must_change_password' => false,
        ])->each(fn (User $u) => $u->assignRole($role));

        $this->actingAs($actor)
            ->delete(route('admin.roles.destroy', $role))
            ->assertStatus(422)
            ->assertJson(['message' => 'Cannot delete role with 2 assigned user(s).']);
    });
});

// ---------------------------------------------------------------------------
// Validation — duplicate role name
// ---------------------------------------------------------------------------

describe('validation', function () {
    beforeEach(fn () => setupRolesAndPermissionsForRoleController());

    test('duplicate role name returns 422 on store', function () {
        $actor = userWithRoleForRoleController('super_admin');
        Role::create(['name' => 'existing_role', 'guard_name' => 'web']);

        $this->actingAs($actor)
            ->withHeaders(['Accept' => 'application/json'])
            ->post(route('admin.roles.store'), ['name' => 'existing_role'])
            ->assertStatus(422)
            ->assertInvalid(['name']);
    });

    test('duplicate role name returns 422 on update', function () {
        $actor = userWithRoleForRoleController('super_admin');
        Role::create(['name' => 'taken_name', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'my_role', 'guard_name' => 'web']);

        $this->actingAs($actor)
            ->withHeaders(['Accept' => 'application/json'])
            ->put(route('admin.roles.update', $role), ['name' => 'taken_name'])
            ->assertStatus(422)
            ->assertInvalid(['name']);
    });
});
