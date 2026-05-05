<?php

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
function setupRolesAndPermissionsForAuditLogController(): array
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
function userWithRoleForAuditLogController(string $roleName): User
{
    $user = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ]);
    $user->assignRole($roleName);

    return $user;
}

// ---------------------------------------------------------------------------
// Happy path — super_admin views the paginated audit log
// ---------------------------------------------------------------------------

describe('index (view audit log)', function () {
    beforeEach(fn () => setupRolesAndPermissionsForAuditLogController());

    test('super_admin can view the paginated audit log and receives the correct Inertia component', function () {
        $actor = userWithRoleForAuditLogController('super_admin');

        $this->withoutVite()
            ->actingAs($actor)
            ->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/audit-logs/index'));
    });
});

// ---------------------------------------------------------------------------
// 403 cases — admin and HR cannot access the audit log
// ---------------------------------------------------------------------------

describe('403 — roles without audit-log.view permission', function () {
    beforeEach(fn () => setupRolesAndPermissionsForAuditLogController());

    test('admin attempting to access audit log returns 403', function () {
        $actor = userWithRoleForAuditLogController('admin');

        $this->actingAs($actor)
            ->get(route('admin.audit-logs.index'))
            ->assertForbidden();
    });

    test('HR attempting to access audit log returns 403', function () {
        $actor = userWithRoleForAuditLogController('hr');

        $this->actingAs($actor)
            ->get(route('admin.audit-logs.index'))
            ->assertForbidden();
    });
});
