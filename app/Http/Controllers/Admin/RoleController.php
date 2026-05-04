<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Support\RoleHierarchy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of all roles with permission and user counts.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::withCount(['permissions', 'users'])->get();

        return Inertia::render('admin/roles/index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Show the form for creating a new role.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', Role::class);

        return Inertia::render('admin/roles/create');
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        app(AuditLogger::class)->log($request->user(), 'role.created', $role, null, ['name' => $role->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role created successfully.')]);

        return to_route('admin.roles.index');
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Request $request, Role $role): Response
    {
        $this->authorize('update', $role);

        return Inertia::render('admin/roles/edit', [
            'role' => $role,
        ]);
    }

    /**
     * Update the specified role in storage.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', "unique:roles,name,{$role->id}"],
        ]);

        $oldName = $role->name;

        $role->update(['name' => $validated['name']]);

        app(AuditLogger::class)->log($request->user(), 'role.updated', $role, ['name' => $oldName], ['name' => $role->name]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role updated successfully.')]);

        return to_route('admin.roles.index');
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Request $request, Role $role): RedirectResponse|JsonResponse
    {
        if (in_array($role->name, RoleHierarchy::BUILT_IN_ROLES, true)) {
            return response()->json(['message' => 'Cannot delete a built-in role.'], 422);
        }

        $userCount = $role->users()->count();

        if ($userCount > 0) {
            return response()->json(['message' => "Cannot delete role with {$userCount} assigned user(s)."], 422);
        }

        $this->authorize('delete', $role);

        app(AuditLogger::class)->log($request->user(), 'role.deleted', $role, ['name' => $role->name], null);

        $role->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Role deleted successfully.')]);

        return to_route('admin.roles.index');
    }
}
