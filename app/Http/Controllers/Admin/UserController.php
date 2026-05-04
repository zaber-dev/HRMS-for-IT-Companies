<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\RoleHierarchy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a paginated list of users filtered by the acting user's hierarchy.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $actorRole = $request->user()->getRoleNames()->first();
        $actorLevel = RoleHierarchy::level($actorRole);

        $users = User::with('roles')
            ->get()
            ->filter(fn (User $user) => RoleHierarchy::level($user->getRoleNames()->first()) < $actorLevel)
            ->values();

        $users = new LengthAwarePaginator(
            $users->forPage(Paginator::resolveCurrentPage(), 15),
            $users->count(),
            15,
            Paginator::resolveCurrentPage(),
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return Inertia::render('admin/users/index', [
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create', User::class);

        $roles = $this->availableRoles($request->user());

        return Inertia::render('admin/users/create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => true,
            'must_change_password' => true,
        ]);

        $user->assignRole($validated['role']);

        app(AuditLogger::class)->log(
            $request->user(),
            'user.created',
            $user,
            null,
            ['name' => $user->name, 'email' => $user->email, 'role' => $validated['role']],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created successfully.')]);

        return to_route('admin.users.index');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(Request $request, User $user): Response
    {
        $this->authorize('update', $user);

        $roles = $this->availableRoles($request->user());

        return Inertia::render('admin/users/edit', [
            'user' => $user->load('roles'),
            'roles' => $roles,
        ]);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', "unique:users,email,{$user->id}"],
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $oldRole = $user->getRoleNames()->first();

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($oldRole !== $validated['role']) {
            $user->syncRoles([$validated['role']]);

            app(AuditLogger::class)->log(
                $request->user(),
                'role.assigned',
                $user,
                ['role' => $oldRole],
                ['role' => $validated['role']],
            );
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated successfully.')]);

        return to_route('admin.users.index');
    }

    /**
     * Show the deactivation confirmation page for the specified user.
     */
    public function deactivate(Request $request, User $user): Response
    {
        $this->authorize('delete', $user);

        return Inertia::render('admin/users/deactivate', [
            'user' => $user->load('roles'),
        ]);
    }

    /**
     * Deactivate the specified user (set is_active to false).
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        app(AuditLogger::class)->log(
            $request->user(),
            'user.deactivated',
            $user,
            ['is_active' => true],
            ['is_active' => false],
        );

        $user->update(['is_active' => false]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deactivated successfully.')]);

        return to_route('admin.users.index');
    }

    /**
     * Get roles available to the actor based on their hierarchy level.
     */
    private function availableRoles(User $actor): Collection
    {
        $actorLevel = RoleHierarchy::level($actor->getRoleNames()->first());

        return Role::all()
            ->filter(fn ($role) => RoleHierarchy::level($role->name) < $actorLevel)
            ->values();
    }
}
