<?php

use App\Models\AuditLog;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

// ---------------------------------------------------------------------------
// Helpers — same pattern as other controller tests
// ---------------------------------------------------------------------------

/**
 * Create all roles and permissions per the permission matrix.
 *
 * @return array<string, Role>
 */
function setupRolesAndPermissionsForPermissionController(): array
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
function userWithRoleForPermissionController(string $roleName): User
{
    $user = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ]);
    $user->assignRole($roleName);

    return $user;
}

// ---------------------------------------------------------------------------
// Happy path — super_admin syncs permissions on a non-protected role
// ---------------------------------------------------------------------------

describe('update (sync permissions)', function () {
    beforeEach(fn () => setupRolesAndPermissionsForPermissionController());

    test('super_admin can sync permissions on a non-protected role and is redirected back', function () {
        $actor = userWithRoleForPermissionController('super_admin');
        $role = Role::create(['name' => 'custom_role', 'guard_name' => 'web']);

        $this->actingAs($actor)
            ->put(route('admin.roles.permissions.update', $role), [
                'permissions' => ['user.view', 'user.create'],
            ])
            ->assertRedirect();
    });

    test('permissions are actually updated on the role after sync', function () {
        $actor = userWithRoleForPermissionController('super_admin');
        $role = Role::create(['name' => 'custom_role', 'guard_name' => 'web']);

        $this->actingAs($actor)
            ->put(route('admin.roles.permissions.update', $role), [
                'permissions' => ['user.view', 'user.create'],
            ]);

        $role->refresh();
        $permissionNames = $role->permissions->pluck('name')->sort()->values()->toArray();

        expect($permissionNames)->toBe(['user.create', 'user.view']);
    });

    test('syncing with an empty array removes all permissions from the role', function () {
        $actor = userWithRoleForPermissionController('super_admin');
        $role = Role::create(['name' => 'custom_role', 'guard_name' => 'web']);
        $role->syncPermissions(['user.view', 'user.create']);

        $this->actingAs($actor)
            ->put(route('admin.roles.permissions.update', $role), [
                'permissions' => [],
            ]);

        $role->refresh();
        expect($role->permissions)->toHaveCount(0);
    });
});

// ---------------------------------------------------------------------------
// 403 case — attempt to modify super_admin role permissions
// ---------------------------------------------------------------------------

describe('403 — protected super_admin role', function () {
    beforeEach(fn () => setupRolesAndPermissionsForPermissionController());

    test('attempt to modify super_admin role permissions returns 403', function () {
        $actor = userWithRoleForPermissionController('super_admin');
        $superAdminRole = Role::where('name', 'super_admin')->first();

        $this->actingAs($actor)
            ->put(route('admin.roles.permissions.update', $superAdminRole), [
                'permissions' => ['user.view'],
            ])
            ->assertForbidden();
    });
});

// ---------------------------------------------------------------------------
// Audit log — one AuditLog entry with action 'permission.synced'
// ---------------------------------------------------------------------------

describe('audit log', function () {
    beforeEach(fn () => setupRolesAndPermissionsForPermissionController());

    test('syncing permissions creates one AuditLog entry with action permission.synced', function () {
        $actor = userWithRoleForPermissionController('super_admin');
        $role = Role::create(['name' => 'custom_role', 'guard_name' => 'web']);

        $this->actingAs($actor)
            ->put(route('admin.roles.permissions.update', $role), [
                'permissions' => ['user.view'],
            ]);

        expect(AuditLog::count())->toBe(1);

        $log = AuditLog::first();
        expect($log->user_id)->toBe($actor->id)
            ->and($log->action)->toBe('permission.synced');
    });

    test('audit log records old and new permission sets', function () {
        $actor = userWithRoleForPermissionController('super_admin');
        $role = Role::create(['name' => 'custom_role', 'guard_name' => 'web']);
        $role->syncPermissions(['user.view']);

        $this->actingAs($actor)
            ->put(route('admin.roles.permissions.update', $role), [
                'permissions' => ['user.view', 'user.create'],
            ]);

        $log = AuditLog::first();
        expect($log->old_values)->toBe(['permissions' => ['user.view']])
            ->and($log->new_values)->toBe(['permissions' => ['user.view', 'user.create']]);
    });
});
