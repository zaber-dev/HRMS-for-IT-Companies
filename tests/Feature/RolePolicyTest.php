<?php

use App\Models\User;
use App\Policies\RolePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers (reuse pattern from UserPolicyTest)
// ---------------------------------------------------------------------------

/**
 * Create all roles and permissions per the permission matrix, then return
 * a map of role name → Role model so tests can assign roles easily.
 *
 * @return array<string, Role>
 */
function setupRolesAndPermissionsForRolePolicy(): array
{
    $permissions = [
        'user.view', 'user.create', 'user.update', 'user.delete',
        'role.view', 'role.create', 'role.update', 'role.delete',
        'permission.manage', 'audit-log.view',
    ];

    foreach ($permissions as $name) {
        Permission::create(['name' => $name]);
    }

    $superAdmin = Role::create(['name' => 'super_admin']);
    $superAdmin->givePermissionTo($permissions);

    $admin = Role::create(['name' => 'admin']);
    $admin->givePermissionTo([
        'user.view', 'user.create', 'user.update', 'user.delete',
        'role.view', 'role.create', 'role.update', 'role.delete',
    ]);

    $hr = Role::create(['name' => 'hr']);
    $hr->givePermissionTo([
        'user.view', 'user.create', 'user.update', 'user.delete',
    ]);

    $employee = Role::create(['name' => 'employee']);
    // employee has no permissions

    return [
        'super_admin' => $superAdmin,
        'admin' => $admin,
        'hr' => $hr,
        'employee' => $employee,
    ];
}

/**
 * Create a user and assign the given role.
 */
function userWithRoleForRolePolicy(string $roleName): User
{
    $user = User::factory()->create();
    $user->assignRole($roleName);

    return $user;
}

// ---------------------------------------------------------------------------
// update — built-in roles are protected
// ---------------------------------------------------------------------------

describe('update', function () {
    beforeEach(fn () => setupRolesAndPermissionsForRolePolicy());

    test('super_admin cannot update the super_admin built-in role', function () {
        $actor = userWithRoleForRolePolicy('super_admin');
        $role = Role::findByName('super_admin');

        expect((new RolePolicy)->update($actor, $role))->toBeFalse();
    });

    test('super_admin cannot update the admin built-in role', function () {
        $actor = userWithRoleForRolePolicy('super_admin');
        $role = Role::findByName('admin');

        expect((new RolePolicy)->update($actor, $role))->toBeFalse();
    });

    test('super_admin cannot update the hr built-in role', function () {
        $actor = userWithRoleForRolePolicy('super_admin');
        $role = Role::findByName('hr');

        expect((new RolePolicy)->update($actor, $role))->toBeFalse();
    });

    test('super_admin cannot update the employee built-in role', function () {
        $actor = userWithRoleForRolePolicy('super_admin');
        $role = Role::findByName('employee');

        expect((new RolePolicy)->update($actor, $role))->toBeFalse();
    });

    test('super_admin can update a custom non-built-in role', function () {
        $actor = userWithRoleForRolePolicy('super_admin');
        $customRole = Role::create(['name' => 'custom-role']);

        expect((new RolePolicy)->update($actor, $customRole))->toBeTrue();
    });

    test('admin cannot update the super_admin built-in role', function () {
        $actor = userWithRoleForRolePolicy('admin');
        $role = Role::findByName('super_admin');

        expect((new RolePolicy)->update($actor, $role))->toBeFalse();
    });

    test('admin cannot update the admin built-in role', function () {
        $actor = userWithRoleForRolePolicy('admin');
        $role = Role::findByName('admin');

        expect((new RolePolicy)->update($actor, $role))->toBeFalse();
    });

    test('admin cannot update the hr built-in role', function () {
        $actor = userWithRoleForRolePolicy('admin');
        $role = Role::findByName('hr');

        expect((new RolePolicy)->update($actor, $role))->toBeFalse();
    });

    test('admin cannot update the employee built-in role', function () {
        $actor = userWithRoleForRolePolicy('admin');
        $role = Role::findByName('employee');

        expect((new RolePolicy)->update($actor, $role))->toBeFalse();
    });

    test('hr cannot update any built-in role', function () {
        $actor = userWithRoleForRolePolicy('hr');

        foreach (['super_admin', 'admin', 'hr', 'employee'] as $roleName) {
            $role = Role::findByName($roleName);
            expect((new RolePolicy)->update($actor, $role))->toBeFalse("hr should not update {$roleName}");
        }
    });

    test('employee cannot update any built-in role', function () {
        $actor = userWithRoleForRolePolicy('employee');

        foreach (['super_admin', 'admin', 'hr', 'employee'] as $roleName) {
            $role = Role::findByName($roleName);
            expect((new RolePolicy)->update($actor, $role))->toBeFalse("employee should not update {$roleName}");
        }
    });
});

// ---------------------------------------------------------------------------
// delete — built-in roles are protected
// ---------------------------------------------------------------------------

describe('delete', function () {
    beforeEach(fn () => setupRolesAndPermissionsForRolePolicy());

    test('super_admin cannot delete the super_admin built-in role', function () {
        $actor = userWithRoleForRolePolicy('super_admin');
        $role = Role::findByName('super_admin');

        expect((new RolePolicy)->delete($actor, $role))->toBeFalse();
    });

    test('super_admin cannot delete the admin built-in role', function () {
        $actor = userWithRoleForRolePolicy('super_admin');
        $role = Role::findByName('admin');

        expect((new RolePolicy)->delete($actor, $role))->toBeFalse();
    });

    test('super_admin cannot delete the hr built-in role', function () {
        $actor = userWithRoleForRolePolicy('super_admin');
        $role = Role::findByName('hr');

        expect((new RolePolicy)->delete($actor, $role))->toBeFalse();
    });

    test('super_admin cannot delete the employee built-in role', function () {
        $actor = userWithRoleForRolePolicy('super_admin');
        $role = Role::findByName('employee');

        expect((new RolePolicy)->delete($actor, $role))->toBeFalse();
    });

    test('super_admin can delete a custom role with no assigned users', function () {
        $actor = userWithRoleForRolePolicy('super_admin');
        $customRole = Role::create(['name' => 'custom-role']);

        expect((new RolePolicy)->delete($actor, $customRole))->toBeTrue();
    });

    test('delete is rejected when users are assigned to the custom role', function () {
        $actor = userWithRoleForRolePolicy('super_admin');
        $customRole = Role::create(['name' => 'custom-role']);

        // Assign a user to the custom role
        $assignedUser = User::factory()->create();
        $assignedUser->assignRole($customRole);

        expect((new RolePolicy)->delete($actor, $customRole))->toBeFalse();
    });

    test('admin cannot delete any built-in role', function () {
        $actor = userWithRoleForRolePolicy('admin');

        foreach (['super_admin', 'admin', 'hr', 'employee'] as $roleName) {
            $role = Role::findByName($roleName);
            expect((new RolePolicy)->delete($actor, $role))->toBeFalse("admin should not delete {$roleName}");
        }
    });

    test('hr cannot delete any built-in role', function () {
        $actor = userWithRoleForRolePolicy('hr');

        foreach (['super_admin', 'admin', 'hr', 'employee'] as $roleName) {
            $role = Role::findByName($roleName);
            expect((new RolePolicy)->delete($actor, $role))->toBeFalse("hr should not delete {$roleName}");
        }
    });

    test('employee cannot delete any built-in role', function () {
        $actor = userWithRoleForRolePolicy('employee');

        foreach (['super_admin', 'admin', 'hr', 'employee'] as $roleName) {
            $role = Role::findByName($roleName);
            expect((new RolePolicy)->delete($actor, $role))->toBeFalse("employee should not delete {$roleName}");
        }
    });
});

// ---------------------------------------------------------------------------
// managePermissions — super_admin role is protected
// ---------------------------------------------------------------------------

describe('managePermissions', function () {
    beforeEach(fn () => setupRolesAndPermissionsForRolePolicy());

    test('super_admin actor cannot manage permissions on the super_admin role', function () {
        $actor = userWithRoleForRolePolicy('super_admin');
        $superAdminRole = Role::findByName('super_admin');

        expect((new RolePolicy)->managePermissions($actor, $superAdminRole))->toBeFalse();
    });

    test('super_admin can manage permissions on a non-super_admin built-in role', function () {
        $actor = userWithRoleForRolePolicy('super_admin');
        $adminRole = Role::findByName('admin');

        expect((new RolePolicy)->managePermissions($actor, $adminRole))->toBeTrue();
    });

    test('super_admin can manage permissions on a custom role', function () {
        $actor = userWithRoleForRolePolicy('super_admin');
        $customRole = Role::create(['name' => 'custom-role']);

        expect((new RolePolicy)->managePermissions($actor, $customRole))->toBeTrue();
    });

    test('admin cannot manage permissions on any role (lacks permission.manage)', function () {
        $actor = userWithRoleForRolePolicy('admin');
        $customRole = Role::create(['name' => 'custom-role']);

        expect((new RolePolicy)->managePermissions($actor, $customRole))->toBeFalse();
    });

    test('hr cannot manage permissions on any role', function () {
        $actor = userWithRoleForRolePolicy('hr');
        $customRole = Role::create(['name' => 'custom-role']);

        expect((new RolePolicy)->managePermissions($actor, $customRole))->toBeFalse();
    });

    test('employee cannot manage permissions on any role', function () {
        $actor = userWithRoleForRolePolicy('employee');
        $customRole = Role::create(['name' => 'custom-role']);

        expect((new RolePolicy)->managePermissions($actor, $customRole))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// viewAny and create — basic permission checks
// ---------------------------------------------------------------------------

describe('viewAny', function () {
    beforeEach(fn () => setupRolesAndPermissionsForRolePolicy());

    test('super_admin can view any roles', function () {
        $actor = userWithRoleForRolePolicy('super_admin');

        expect((new RolePolicy)->viewAny($actor))->toBeTrue();
    });

    test('admin can view any roles', function () {
        $actor = userWithRoleForRolePolicy('admin');

        expect((new RolePolicy)->viewAny($actor))->toBeTrue();
    });

    test('hr cannot view roles', function () {
        $actor = userWithRoleForRolePolicy('hr');

        expect((new RolePolicy)->viewAny($actor))->toBeFalse();
    });

    test('employee cannot view roles', function () {
        $actor = userWithRoleForRolePolicy('employee');

        expect((new RolePolicy)->viewAny($actor))->toBeFalse();
    });
});

describe('create', function () {
    beforeEach(fn () => setupRolesAndPermissionsForRolePolicy());

    test('super_admin can create roles', function () {
        $actor = userWithRoleForRolePolicy('super_admin');

        expect((new RolePolicy)->create($actor))->toBeTrue();
    });

    test('admin can create roles', function () {
        $actor = userWithRoleForRolePolicy('admin');

        expect((new RolePolicy)->create($actor))->toBeTrue();
    });

    test('hr cannot create roles', function () {
        $actor = userWithRoleForRolePolicy('hr');

        expect((new RolePolicy)->create($actor))->toBeFalse();
    });

    test('employee cannot create roles', function () {
        $actor = userWithRoleForRolePolicy('employee');

        expect((new RolePolicy)->create($actor))->toBeFalse();
    });
});
