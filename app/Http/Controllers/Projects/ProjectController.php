<?php

namespace App\Http\Controllers\Projects;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use App\Services\BenchStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Display a paginated listing of projects with optional status filter.
     * Requirements: 1.1, 3.1, 3.2, 3.3, 3.5, 12.4, 12.6
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Project::class);

        $query = Project::withCount('skills');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $projects = $query->paginate(15)->withQueryString();

        return Inertia::render('projects/index', [
            'projects' => $projects,
            'filters' => [
                'status' => $request->input('status'),
            ],
            'canManageProjects' => auth()->user()->hasRole(['super_admin', 'admin', 'hr']),
        ]);
    }

    /**
     * Show the form for creating a new project.
     * Requirements: 1.1, 3.2, 4.1, 4.2
     */
    public function create(): Response
    {
        $this->authorize('create', Project::class);

        $skills = Skill::where('is_active', true)->get();

        return Inertia::render('projects/create', [
            'skills' => $skills,
        ]);
    }

    /**
     * Store a newly created project in storage.
     * Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 3.7, 4.1
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Project::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:150', 'unique:projects'],
            'description' => ['nullable', 'string'],
            'features_list' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(array_column(ProjectStatus::cases(), 'value'))],
            'deadline' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'skill_ids' => ['nullable', 'array'],
            'skill_ids.*' => ['integer', 'exists:skills,id'],
        ]);

        $project = Project::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'features_list' => $validated['features_list'] ?? null,
            'status' => $validated['status'],
            'deadline' => $validated['deadline'],
        ]);

        $project->skills()->sync($validated['skill_ids'] ?? []);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Project created successfully.']);

        return to_route('projects.show', $project);
    }

    /**
     * Display the specified project with its skills and assignments.
     * Requirements: 3.4, 4.3, 5.1, 6.7, 8.2, 8.3
     */
    public function show(Project $project): Response
    {
        $this->authorize('view', $project);

        $project->load(['skills', 'assignments.user']);

        return Inertia::render('projects/show', [
            'project' => $project,
        ]);
    }

    /**
     * Show the form for editing the specified project.
     * Requirements: 1.2, 3.3, 3.6, 4.2
     */
    public function edit(Project $project): Response
    {
        $this->authorize('update', $project);

        $project->load('skills');

        $skills = Skill::where('is_active', true)->get();

        return Inertia::render('projects/edit', [
            'project' => $project,
            'skills' => $skills,
        ]);
    }

    /**
     * Update the specified project in storage.
     * Requirements: 1.2, 1.3, 1.4, 1.5, 2.4, 3.7, 4.2
     */
    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:150', Rule::unique('projects')->ignore($project->id)],
            'description' => ['nullable', 'string'],
            'features_list' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(array_column(ProjectStatus::cases(), 'value'))],
            'deadline' => ['required', 'date', 'date_format:Y-m-d'],
            'skill_ids' => ['nullable', 'array'],
            'skill_ids.*' => ['integer', 'exists:skills,id'],
        ]);

        $project->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'features_list' => $validated['features_list'] ?? null,
            'status' => $validated['status'],
            'deadline' => $validated['deadline'],
        ]);

        $project->skills()->sync($validated['skill_ids'] ?? []);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Project updated successfully.']);

        return to_route('projects.show', $project);
    }

    /**
     * Show the delete confirmation page for the specified project.
     * Requirements: 2.1, 3.5, 3.6
     */
    public function delete(Project $project): Response
    {
        $this->authorize('delete', $project);

        $project->loadCount('assignments');

        return Inertia::render('projects/delete', [
            'project' => $project,
        ]);
    }

    /**
     * Remove the specified project from storage, cascading assignments and recalculating bench status.
     * Requirements: 2.1, 2.2, 2.3, 2.4, 3.8
     */
    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $affectedUserIds = $project->assignments()->pluck('user_id')->unique()->all();

        $project->delete();

        $benchStatusService = app(BenchStatusService::class);

        foreach ($affectedUserIds as $userId) {
            $employee = User::find($userId);

            if ($employee) {
                $benchStatusService->recalculate($employee);
            }
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Project deleted successfully.']);

        return to_route('projects.index');
    }
}
