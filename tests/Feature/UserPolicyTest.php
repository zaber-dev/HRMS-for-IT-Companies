<?php

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Create all roles and permissions per the permission matrix, then return
 * a map of role name → Role model so tests can assign roles easily.
 *
 * @return array<string, Role>
 */
function setupRolesAndPermissions(): array
{
    // Create all permissions
    $permissions = [
        'user.view', 'user.create', 'user.update', 'user.delete',
        'role.view', 'role.create', 'role.update', 'role.delete',
        'permission.manage', 'audit-log.view',
    ];

    foreach ($permissions as $name) {
        Permission::create(['name' => $name]);
    }

    // Create roles and assign permissions per the matrix
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
function userWithRole(string $roleName): User
{
    $user = User::factory()->create();
    $user->assignRole($roleName);

    return $user;
}

// ---------------------------------------------------------------------------
// viewAny
// ---------------------------------------------------------------------------

describe('viewAny', function () {
    beforeEach(fn () => setupRolesAndPermissions());

    test('super_admin can view any users', function () {
        $actor = userWithRole('super_admin');

        expect((new UserPolicy)->viewAny($actor))->toBeTrue();
    });

    test('admin can view any users', function () {
        $actor = userWithRole('admin');

        expect((new UserPolicy)->viewAny($actor))->toBeTrue();
    });

    test('hr can view any users', function () {
        $actor = userWithRole('hr');

        expect((new UserPolicy)->viewAny($actor))->toBeTrue();
    });

    test('employee cannot view any users', function () {
        $actor = userWithRole('employee');

        expect((new UserPolicy)->viewAny($actor))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// create
// ---------------------------------------------------------------------------

describe('create', function () {
    beforeEach(fn () => setupRolesAndPermissions());

    test('super_admin can create users', function () {
        $actor = userWithRole('super_admin');

        expect((new UserPolicy)->create($actor))->toBeTrue();
    });

    test('admin can create users', function () {
        $actor = userWithRole('admin');

        expect((new UserPolicy)->create($actor))->toBeTrue();
    });

    test('hr can create users', function () {
        $actor = userWithRole('hr');

        expect((new UserPolicy)->create($actor))->toBeTrue();
    });

    test('employee cannot create users', function () {
        $actor = userWithRole('employee');

        expect((new UserPolicy)->create($actor))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// update
// ---------------------------------------------------------------------------

describe('update', function () {
    beforeEach(fn () => setupRolesAndPermissions());

    // super_admin can update admin, hr, employee
    test('super_admin can update admin', function () {
        $actor = userWithRole('super_admin');
        $target = userWithRole('admin');

        expect((new UserPolicy)->update($actor, $target))->toBeTrue();
    });

    test('super_admin can update hr', function () {
        $actor = userWithRole('super_admin');
        $target = userWithRole('hr');

        expect((new UserPolicy)->update($actor, $target))->toBeTrue();
    });

    test('super_admin can update employee', function () {
        $actor = userWithRole('super_admin');
        $target = userWithRole('employee');

        expect((new UserPolicy)->update($actor, $target))->toBeTrue();
    });

    // super_admin cannot update another super_admin (same level)
    test('super_admin cannot update another super_admin', function () {
        $actor = userWithRole('super_admin');
        $target = userWithRole('super_admin');

        expect((new UserPolicy)->update($actor, $target))->toBeFalse();
    });

    // admin can update hr, employee but NOT admin or super_admin
    test('admin can update hr', function () {
        $actor = userWithRole('admin');
        $target = userWithRole('hr');

        expect((new UserPolicy)->update($actor, $target))->toBeTrue();
    });

    test('admin can update employee', function () {
        $actor = userWithRole('admin');
        $target = userWithRole('employee');

        expect((new UserPolicy)->update($actor, $target))->toBeTrue();
    });

    test('admin cannot update another admin', function () {
        $actor = userWithRole('admin');
        $target = userWithRole('admin');

        expect((new UserPolicy)->update($actor, $target))->toBeFalse();
    });

    test('admin cannot update super_admin', function () {
        $actor = userWithRole('admin');
        $target = userWithRole('super_admin');

        expect((new UserPolicy)->update($actor, $target))->toBeFalse();
    });

    // hr can update employee but NOT hr, admin, or super_admin
    test('hr can update employee', function () {
        $actor = userWithRole('hr');
        $target = userWithRole('employee');

        expect((new UserPolicy)->update($actor, $target))->toBeTrue();
    });

    test('hr cannot update another hr', function () {
        $actor = userWithRole('hr');
        $target = userWithRole('hr');

        expect((new UserPolicy)->update($actor, $target))->toBeFalse();
    });

    test('hr cannot update admin', function () {
        $actor = userWithRole('hr');
        $target = userWithRole('admin');

        expect((new UserPolicy)->update($actor, $target))->toBeFalse();
    });

    test('hr cannot update super_admin', function () {
        $actor = userWithRole('hr');
        $target = userWithRole('super_admin');

        expect((new UserPolicy)->update($actor, $target))->toBeFalse();
    });

    // employee cannot update anyone
    test('employee cannot update employee', function () {
        $actor = userWithRole('employee');
        $target = userWithRole('employee');

        expect((new UserPolicy)->update($actor, $target))->toBeFalse();
    });

    test('employee cannot update hr', function () {
        $actor = userWithRole('employee');
        $target = userWithRole('hr');

        expect((new UserPolicy)->update($actor, $target))->toBeFalse();
    });

    test('employee cannot update admin', function () {
        $actor = userWithRole('employee');
        $target = userWithRole('admin');

        expect((new UserPolicy)->update($actor, $target))->toBeFalse();
    });

    test('employee cannot update super_admin', function () {
        $actor = userWithRole('employee');
        $target = userWithRole('super_admin');

        expect((new UserPolicy)->update($actor, $target))->toBeFalse();
    });
});

// ---------------------------------------------------------------------------
// delete
// ---------------------------------------------------------------------------

describe('delete', function () {
    beforeEach(fn () => setupRolesAndPermissions());

    // super_admin can delete admin, hr, employee
    test('super_admin can delete admin', function () {
        $actor = userWithRole('super_admin');
        $target = userWithRole('admin');

        expect((new UserPolicy)->delete($actor, $target))->toBeTrue();
    });

    test('super_admin can delete hr', function () {
        $actor = userWithRole('super_admin');
        $target = userWithRole('hr');

        expect((new UserPolicy)->delete($actor, $target))->toBeTrue();
    });

    test('super_admin can delete employee', function () {
        $actor = userWithRole('super_admin');
        $target = userWithRole('employee');

        expect((new UserPolicy)->delete($actor, $target))->toBeTrue();
    });

    // super_admin cannot delete another super_admin (target has super_admin role)
    test('super_admin cannot delete another super_admin', function () {
        $actor = userWithRole('super_admin');
        $target = userWithRole('super_admin');

        expect((new UserPolicy)->delete($actor, $target))->toBeFalse();
    });

    // super_admin self-deactivation is rejected
    test('super_admin cannot deactivate themselves', function () {
        $actor = userWithRole('super_admin');

        expect((new UserPolicy)->delete($actor, $actor))->toBeFalse();
    });

    // admin can delete hr, employee but NOT admin or super_admin
    test('admin can delete hr', function () {
        $actor = userWithRole('admin');
        $target = userWithRole('hr');

        expect((new UserPolicy)->delete($actor, $target))->toBeTrue();
    });

    test('admin can delete employee', function () {
        $actor = userWithRole('admin');
        $target = userWithRole('employee');

        expect((new UserPolicy)->delete($actor, $target))->toBeTrue();
    });

    test('admin cannot delete another admin', function () {
        $actor = userWithRole('admin');
        $target = userWithRole('admin');

        expect((new UserPolicy)->delete($actor, $target))->toBeFalse();
    });

    test('admin cannot delete super_admin', function () {
        $actor = userWithRole('admin');
        $target = userWithRole('super_admin');

        expect((new UserPolicy)->delete($actor, $target))->toBeFalse();
    });

    // hr can delete employee but NOT hr, admin, or super_admin
    test('hr can delete employee', function () {
        $actor = userWithRole('hr');
        $target = userWithRole('employee');

        expect((new UserPolicy)->delete($actor, $target))->toBeTrue();
    });

    test('hr cannot delete another hr', function () {
        $actor = userWithRole('hr');
        $target = userWithRole('hr');

        expect((new UserPolicy)->delete($actor, $target))->toBeFalse();
    });

    test('hr cannot delete admin', function () {
        $actor = userWithRole('hr');
        $target = userWithRole('admin');

        expect((new UserPolicy)->delete($actor, $target))->toBeFalse();
    });

    test('hr cannot delete super_admin', function () {
        $actor = userWithRole('hr');
        $target = userWithRole('super_admin');

        expect((new UserPolicy)->delete($actor, $target))->toBeFalse();
    });

    // employee cannot delete anyone
    test('employee cannot delete employee', function () {
        $actor = userWithRole('employee');
        $target = userWithRole('employee');

        expect((new UserPolicy)->delete($actor, $target))->toBeFalse();
    });

    test('employee cannot delete hr', function () {
        $actor = userWithRole('employee');
        $target = userWithRole('hr');

        expect((new UserPolicy)->delete($actor, $target))->toBeFalse();
    });

    test('employee cannot delete admin', function () {
        $actor = userWithRole('employee');
        $target = userWithRole('admin');

        expect((new UserPolicy)->delete($actor, $target))->toBeFalse();
    });

    test('employee cannot delete super_admin', function () {
        $actor = userWithRole('employee');
        $target = userWithRole('super_admin');

        expect((new UserPolicy)->delete($actor, $target))->toBeFalse();
    });
});
