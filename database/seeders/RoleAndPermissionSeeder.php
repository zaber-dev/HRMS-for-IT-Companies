<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all 10 permissions
        $permissions = [
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

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create four built-in roles and assign permissions per the Role → Permission Matrix

        // super_admin: all 10 permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->syncPermissions($permissions);

        // admin: user.view, user.create, user.update, user.delete, role.view, role.create, role.update, role.delete
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions([
            'user.view',
            'user.create',
            'user.update',
            'user.delete',
            'role.view',
            'role.create',
            'role.update',
            'role.delete',
        ]);

        // hr: user.view, user.create, user.update, user.delete
        $hr = Role::firstOrCreate(['name' => 'hr']);
        $hr->syncPermissions([
            'user.view',
            'user.create',
            'user.update',
            'user.delete',
        ]);

        // employee: no permissions
        $employee = Role::firstOrCreate(['name' => 'employee']);
        $employee->syncPermissions([]);
    }
}
