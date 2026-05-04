<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    /**
     * Show all permissions with their current assignment state for the given role.
     */
    public function index(Request $request, Role $role): Response
    {
        $this->authorize('managePermissions', $role);

        $rolePermissionNames = $role->permissions->pluck('name')->toArray();

        $permissions = Permission::all()->map(fn (Permission $permission) => [
            'id' => $permission->id,
            'name' => $permission->name,
            'guard_name' => $permission->guard_name,
            'assigned' => in_array($permission->name, $rolePermissionNames, true),
        ]);

        return Inertia::render('admin/permissions/edit', [
            'role' => $role,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Sync the permission set for the given role.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('managePermissions', $role);

        $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $oldPermissions = $role->permissions->pluck('name')->toArray();

        $role->syncPermissions($request->input('permissions', []));

        app(AuditLogger::class)->log(
            $request->user(),
            'permission.synced',
            $role,
            ['permissions' => $oldPermissions],
            ['permissions' => $request->input('permissions', [])],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Permissions updated successfully.')]);

        return redirect()->back();
    }
}
