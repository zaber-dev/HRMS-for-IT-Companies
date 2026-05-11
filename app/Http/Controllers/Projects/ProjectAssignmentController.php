<?php

namespace App\Http\Controllers\Projects;

use App\Enums\BenchStatus;
use App\Enums\CompletionStatus;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\User;
use App\Services\BenchStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProjectAssignmentController extends Controller
{
    /**
     * Display the specified assignment.
     * Requirements: 5.1, 5.2
     */
    public function show(Project $project, ProjectAssignment $assignment): Response
    {
        $this->authorize('view', $assignment);

        $assignment->load('user');

        return Inertia::render('projects/assignments/show', [
            'project' => $project,
            'assignment' => $assignment,
        ]);
    }

    /**
     * Show the form for creating a new assignment, with employee suggestions.
     * Requirements: 5.1, 5.2, 9.1–9.5
     */
    public function create(Project $project): Response
    {
        $this->authorize('create', [ProjectAssignment::class, $project]);

        $project->load('skills');

        $projectSkillIds = $project->skills->pluck('id');

        $employees = User::role('employee')
            ->where('is_active', true)
            ->with('skills')
            ->get();

        $suggestions = $employees->filter(function (User $employee) use ($projectSkillIds): bool {
            if ($employee->bench_status !== BenchStatus::OnBench) {
                return false;
            }

            $employeeSkillIds = $employee->skills->pluck('id');

            return $projectSkillIds->intersect($employeeSkillIds)->isNotEmpty();
        })->values();

        return Inertia::render('projects/assignments/create', [
            'project' => $project,
            'suggestions' => $suggestions,
            'employees' => $employees,
        ]);
    }

    /**
     * Assign an employee to the project.
     * Requirements: 5.3–5.8, 6.2, 6.3, 6.4, 6.5
     */
    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('create', [ProjectAssignment::class, $project]);

        $validated = $request->validate([
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::unique('project_assignments')->where(fn ($query) => $query->where('project_id', $project->id)),
            ],
            'task_description' => ['required', 'string'],
            'task_deadline' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'before_or_equal:'.$project->deadline->toDateString(),
            ],
        ], [
            'user_id.unique' => 'This employee is already assigned to the project.',
        ]);

        $assignment = ProjectAssignment::create([
            'project_id' => $project->id,
            'user_id' => $validated['user_id'],
            'task_description' => $validated['task_description'],
            'task_deadline' => $validated['task_deadline'],
            'completion_status' => CompletionStatus::Pending,
        ]);

        $employee = User::find($assignment->user_id);

        app(BenchStatusService::class)->recalculate($employee);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Employee assigned to project successfully.']);

        return to_route('projects.show', $project);
    }

    /**
     * Show the form for editing an existing assignment.
     * Requirements: 11.1, 11.2, 11.3
     */
    public function edit(Project $project, ProjectAssignment $assignment): Response
    {
        $this->authorize('update', $assignment);

        $assignment->load('user');

        return Inertia::render('projects/assignments/edit', [
            'project' => $project,
            'assignment' => $assignment,
        ]);
    }

    /**
     * Update the task description and deadline for an assignment.
     * Requirements: 11.1–11.4
     */
    public function update(Request $request, Project $project, ProjectAssignment $assignment): RedirectResponse
    {
        $this->authorize('update', $assignment);

        $validated = $request->validate([
            'task_description' => ['required', 'string'],
            'task_deadline' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'before_or_equal:'.$project->deadline->toDateString(),
            ],
        ]);

        $assignment->update([
            'task_description' => $validated['task_description'],
            'task_deadline' => $validated['task_deadline'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Assignment updated successfully.']);

        return to_route('projects.show', $project);
    }

    /**
     * Remove an employee from the project and recalculate their bench status.
     * Requirements: 10.1–10.5
     */
    public function destroy(Project $project, ProjectAssignment $assignment): RedirectResponse
    {
        $this->authorize('delete', $assignment);

        $employee = User::find($assignment->user_id);

        $assignment->delete();

        if ($employee) {
            app(BenchStatusService::class)->recalculate($employee);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Employee removed from project successfully.']);

        return to_route('projects.show', $project);
    }
}
