<?php

use App\Models\AuditLog;
use App\Models\User;
use App\Support\RoleHierarchy;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

// ---------------------------------------------------------------------------
// Shared setup helper — same pattern as other controller tests
// ---------------------------------------------------------------------------

/**
 * Create all roles and permissions per the permission matrix.
 *
 * @return array<string, Role>
 */
function setupRolesAndPermissionsForProperty(): array
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
function userWithRoleForProperty(string $roleName): User
{
    $user = User::factory()->create([
        'is_active' => true,
        'must_change_password' => false,
    ]);
    $user->assignRole($roleName);

    return $user;
}

// ---------------------------------------------------------------------------
// Property 1: Every user has exactly one role
// Feature: role-based-access-control, Property 1: every user has exactly one role
// ---------------------------------------------------------------------------

it('assigns exactly one role to every user created via UserController@store', function (string $role) {
    setupRolesAndPermissionsForProperty();

    $actor = userWithRoleForProperty('super_admin');

    $this->actingAs($actor)
        ->post(route('admin.users.store'), [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'role' => $role,
            'password' => 'password123',
        ])
        ->assertRedirect(route('admin.users.index'));

    $created = User::where('email', '!=', $actor->email)->latest()->first();

    expect($created->roles()->count())->toBe(1);
})->with([
    'admin',
    'hr',
    'employee',
]);
// Feature: role-based-access-control, Property 1: every user has exactly one role

// ---------------------------------------------------------------------------
// Property 2: Role change propagates permissions immediately
// Feature: role-based-access-control, Property 2: role change propagates permissions immediately
// ---------------------------------------------------------------------------

it('propagates the new role permissions immediately after a role change via UserController@update', function (string $roleA, string $roleB) {
    setupRolesAndPermissionsForProperty();

    $actor = userWithRoleForProperty('super_admin');
    $target = userWithRoleForProperty($roleA);

    $this->actingAs($actor)
        ->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'role' => $roleB,
        ])
        ->assertRedirect(route('admin.users.index'));

    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $target->refresh();

    $expectedPermissions = Role::findByName($roleB)->permissions->pluck('name')->sort()->values()->toArray();
    $actualPermissions = $target->getAllPermissions()->pluck('name')->sort()->values()->toArray();

    expect($actualPermissions)->toBe($expectedPermissions);
})->with([
    ['hr', 'employee'],
    ['admin', 'hr'],
    ['admin', 'employee'],
]);
// Feature: role-based-access-control, Property 2: role change propagates permissions immediately

// ---------------------------------------------------------------------------
// Property 3: Built-in roles cannot be deleted
// Feature: role-based-access-control, Property 3: built-in roles cannot be deleted
// ---------------------------------------------------------------------------

it('rejects deletion of built-in roles with a 422 response', function (string $builtInRole) {
    setupRolesAndPermissionsForProperty();

    $actor = userWithRoleForProperty('super_admin');
    $role = Role::where('name', $builtInRole)->first();

    $this->actingAs($actor)
        ->delete(route('admin.roles.destroy', $role))
        ->assertStatus(422)
        ->assertJson(['message' => 'Cannot delete a built-in role.']);
})->with([
    'super_admin',
    'admin',
    'hr',
    'employee',
]);
// Feature: role-based-access-control, Property 3: built-in roles cannot be deleted

// ---------------------------------------------------------------------------
// Property 4: Deactivated users are denied on all requests
// Feature: role-based-access-control, Property 4: deactivated users are denied on all requests
// ---------------------------------------------------------------------------

it('logs out and redirects deactivated users to login on every admin route request', function (string $routeName) {
    setupRolesAndPermissionsForProperty();

    $user = userWithRoleForProperty('super_admin');
    $user->update(['is_active' => false]);

    $this->actingAs($user)
        ->get(route($routeName))
        ->assertRedirect(route('login'));
})->with([
    'admin.users.index',
    'admin.roles.index',
    'admin.audit-logs.index',
]);
// Feature: role-based-access-control, Property 4: deactivated users are denied on all requests

// ---------------------------------------------------------------------------
// Property 5: Role hierarchy is enforced for user management
// Feature: role-based-access-control, Property 5: role hierarchy is enforced for user management
// ---------------------------------------------------------------------------

it('allows update only when actor role is strictly above target role', function (string $actorRole, string $targetRole) {
    setupRolesAndPermissionsForProperty();

    $actor = userWithRoleForProperty($actorRole);
    $target = userWithRoleForProperty($targetRole);

    $shouldAllow = RoleHierarchy::isAbove($actorRole, $targetRole);

    $response = $this->actingAs($actor)
        ->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'role' => $targetRole,
        ]);

    if ($shouldAllow) {
        $response->assertRedirect(route('admin.users.index'));
    } else {
        $response->assertForbidden();
    }
})->with(
    collect(RoleHierarchy::BUILT_IN_ROLES)
        ->crossJoin(RoleHierarchy::BUILT_IN_ROLES)
        ->filter(fn ($pair) => ! ($pair[0] === 'super_admin' && $pair[1] === 'super_admin'))
        ->map(fn ($pair) => [$pair[0], $pair[1]])
        ->values()
        ->toArray()
);
// Feature: role-based-access-control, Property 5: role hierarchy is enforced for user management

it('allows deactivation only when actor role is strictly above target role', function (string $actorRole, string $targetRole) {
    setupRolesAndPermissionsForProperty();

    $actor = userWithRoleForProperty($actorRole);
    $target = userWithRoleForProperty($targetRole);

    $shouldAllow = RoleHierarchy::isAbove($actorRole, $targetRole);

    $response = $this->actingAs($actor)
        ->delete(route('admin.users.destroy', $target));

    if ($shouldAllow) {
        $response->assertRedirect(route('admin.users.index'));
    } else {
        $response->assertForbidden();
    }
})->with(
    collect(RoleHierarchy::BUILT_IN_ROLES)
        ->crossJoin(RoleHierarchy::BUILT_IN_ROLES)
        ->filter(fn ($pair) => ! ($pair[0] === 'super_admin' && $pair[1] === 'super_admin'))
        ->map(fn ($pair) => [$pair[0], $pair[1]])
        ->values()
        ->toArray()
);
// Feature: role-based-access-control, Property 5: role hierarchy is enforced for user management

// ---------------------------------------------------------------------------
// Property 6: super_admin role permissions cannot be modified
// Feature: role-based-access-control, Property 6: super_admin role permissions cannot be modified
// ---------------------------------------------------------------------------

it('rejects any attempt to modify super_admin role permissions with 403', function (string $permission) {
    setupRolesAndPermissionsForProperty();

    $actor = userWithRoleForProperty('super_admin');
    $superAdminRole = Role::where('name', 'super_admin')->first();

    $this->actingAs($actor)
        ->put(route('admin.roles.permissions.update', $superAdminRole), [
            'permissions' => [$permission],
        ])
        ->assertForbidden();
})->with([
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
]);
// Feature: role-based-access-control, Property 6: super_admin role permissions cannot be modified

// ---------------------------------------------------------------------------
// Property 7: HR users cannot access role or permission management routes
// Feature: role-based-access-control, Property 7: HR users cannot access role or permission management routes
// ---------------------------------------------------------------------------

it('returns 403 for HR users on role management routes', function (string $routeName) {
    setupRolesAndPermissionsForProperty();

    $actor = userWithRoleForProperty('hr');

    $this->actingAs($actor)
        ->get(route($routeName))
        ->assertForbidden();
})->with([
    'admin.roles.index',
    'admin.roles.create',
]);
// Feature: role-based-access-control, Property 7: HR users cannot access role or permission management routes

it('returns 403 for HR users attempting to POST to role store', function () {
    setupRolesAndPermissionsForProperty();

    $actor = userWithRoleForProperty('hr');

    $this->actingAs($actor)
        ->post(route('admin.roles.store'), ['name' => 'some_role'])
        ->assertForbidden();
});
// Feature: role-based-access-control, Property 7: HR users cannot access role or permission management routes

// ---------------------------------------------------------------------------
// Property 8: Employee users cannot access any admin routes
// Feature: role-based-access-control, Property 8: employee users cannot access any admin routes
// ---------------------------------------------------------------------------

it('returns 403 for employee users on all admin routes', function (string $routeName) {
    setupRolesAndPermissionsForProperty();

    $actor = userWithRoleForProperty('employee');

    $this->actingAs($actor)
        ->get(route($routeName))
        ->assertForbidden();
})->with([
    'admin.users.index',
    'admin.roles.index',
    'admin.audit-logs.index',
]);
// Feature: role-based-access-control, Property 8: employee users cannot access any admin routes

// ---------------------------------------------------------------------------
// Property 9: Every management action produces an audit log entry
// Feature: role-based-access-control, Property 9: every management action produces an audit log entry
// ---------------------------------------------------------------------------

it('creates exactly one AuditLog entry for every management action', function (string $actionDescription, Closure $performAction) {
    setupRolesAndPermissionsForProperty();

    $actor = userWithRoleForProperty('super_admin');

    $performAction($this, $actor);

    expect(AuditLog::count())->toBe(1, "Expected exactly one AuditLog entry for action: {$actionDescription}");
})->with([
    'user create' => [
        'user create',
        function ($test, User $actor) {
            $test->actingAs($actor)
                ->post(route('admin.users.store'), [
                    'name' => fake()->name(),
                    'email' => fake()->unique()->safeEmail(),
                    'role' => 'hr',
                    'password' => 'password123',
                ]);
        },
    ],
    'user deactivate' => [
        'user deactivate',
        function ($test, User $actor) {
            $target = userWithRoleForProperty('employee');
            $test->actingAs($actor)
                ->delete(route('admin.users.destroy', $target));
        },
    ],
    'role create' => [
        'role create',
        function ($test, User $actor) {
            $test->actingAs($actor)
                ->post(route('admin.roles.store'), ['name' => 'new_custom_role']);
        },
    ],
    'role delete' => [
        'role delete',
        function ($test, User $actor) {
            $customRole = Role::create(['name' => 'deletable_role', 'guard_name' => 'web']);
            $test->actingAs($actor)
                ->delete(route('admin.roles.destroy', $customRole));
        },
    ],
    'permission sync' => [
        'permission sync',
        function ($test, User $actor) {
            $customRole = Role::create(['name' => 'syncable_role', 'guard_name' => 'web']);
            $test->actingAs($actor)
                ->put(route('admin.roles.permissions.update', $customRole), [
                    'permissions' => ['user.view'],
                ]);
        },
    ],
]);
// Feature: role-based-access-control, Property 9: every management action produces an audit log entry

// ---------------------------------------------------------------------------
// Property 10: New users always require a password change on first login
// Feature: role-based-access-control, Property 10: new users always require a password change on first login
// ---------------------------------------------------------------------------

it('sets must_change_password to true for every user created via UserController@store', function (string $role) {
    setupRolesAndPermissionsForProperty();

    $actor = userWithRoleForProperty('super_admin');
    $email = fake()->unique()->safeEmail();

    $this->actingAs($actor)
        ->post(route('admin.users.store'), [
            'name' => fake()->name(),
            'email' => $email,
            'role' => $role,
            'password' => 'password123',
        ])
        ->assertRedirect(route('admin.users.index'));

    $created = User::where('email', $email)->first();

    expect($created->must_change_password)->toBeTrue();
})->with([
    'admin',
    'hr',
    'employee',
]);
// Feature: role-based-access-control, Property 10: new users always require a password change on first login

it('redirects new users to settings.security on their first authenticated request', function (string $role) {
    setupRolesAndPermissionsForProperty();

    $actor = userWithRoleForProperty('super_admin');
    $email = fake()->unique()->safeEmail();

    $this->actingAs($actor)
        ->post(route('admin.users.store'), [
            'name' => fake()->name(),
            'email' => $email,
            'role' => $role,
            'password' => 'password123',
        ]);

    $created = User::where('email', $email)->first();

    $this->actingAs($created)
        ->get(route('admin.users.index'))
        ->assertRedirect(route('security.edit'));
})->with([
    'admin',
    'hr',
    'employee',
]);
// Feature: role-based-access-control, Property 10: new users always require a password change on first login

// ---------------------------------------------------------------------------
// Property 11: Role names are unique across the system
// Feature: role-based-access-control, Property 11: role names are unique across the system
// ---------------------------------------------------------------------------

it('rejects creation of a role with a duplicate name with a 422 validation error on the name field', function (string $existingRoleName) {
    setupRolesAndPermissionsForProperty();

    // Ensure the role exists (built-in roles are created by setup; custom_role needs creating)
    if (! Role::where('name', $existingRoleName)->exists()) {
        Role::create(['name' => $existingRoleName, 'guard_name' => 'web']);
    }

    $actor = userWithRoleForProperty('super_admin');

    $this->actingAs($actor)
        ->withHeaders(['Accept' => 'application/json'])
        ->post(route('admin.roles.store'), ['name' => $existingRoleName])
        ->assertStatus(422)
        ->assertInvalid(['name']);
})->with([
    'super_admin',
    'admin',
    'hr',
    'employee',
    'custom_role',
]);
// Feature: role-based-access-control, Property 11: role names are unique across the system
