<?php

namespace App\Http\Controllers\Skills;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Models\SkillCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SkillController extends Controller
{
    /**
     * Display a paginated listing of skills with optional filters.
     * Requirements: 3.1, 3.6, 3.7, 4.1
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Skill::class);

        $user = $request->user();

        $query = Skill::with([
            'skillCategory',
            'assignments' => fn ($assignmentQuery) => $assignmentQuery->where('user_id', $user->id),
        ]);

        if ($request->filled('category')) {
            $query->where('skill_category_id', $request->input('category'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $skills = $query->paginate(15)->withQueryString();
        $categories = SkillCategory::all();

        return Inertia::render('skills/index', [
            'skills' => $skills,
            'categories' => $categories,
            'filters' => [
                'category' => $request->input('category'),
                'is_active' => $request->input('is_active'),
            ],
            'canManageSkills' => auth()->user()->hasRole(['super_admin', 'admin', 'hr']),
        ]);
    }

    /**
     * Show the form for creating a new skill.
     * Requirements: 1.1, 4.2
     */
    public function create(): Response
    {
        $this->authorize('create', Skill::class);

        $categories = SkillCategory::all();

        return Inertia::render('skills/create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created skill in the catalogue.
     * Requirements: 1.1–1.6
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Skill::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100', 'unique:skills'],
            'skill_category_id' => ['required', 'exists:skill_categories,id'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        Skill::create($validated);

        return to_route('skills.index')->with('success', 'Skill created successfully.');
    }

    /**
     * Display the specified skill with its assigned employees.
     * Requirements: 3.2, 3.3, 3.4, 4.4
     */
    public function show(Skill $skill): Response
    {
        $this->authorize('view', $skill);

        $skill->load(['skillCategory', 'assignments.user']);

        return Inertia::render('skills/show', [
            'skill' => $skill,
            'canManageSkills' => auth()->user()->hasRole(['super_admin', 'admin', 'hr']),
        ]);
    }

    /**
     * Show the form for editing the specified skill.
     * Requirements: 1.2, 4.3
     */
    public function edit(Skill $skill): Response
    {
        $this->authorize('update', $skill);

        $categories = SkillCategory::all();

        return Inertia::render('skills/edit', [
            'skill' => $skill,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified skill in the catalogue.
     * Requirements: 1.2–1.6
     */
    public function update(Request $request, Skill $skill): RedirectResponse
    {
        $this->authorize('update', $skill);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100', "unique:skills,name,{$skill->id}"],
            'skill_category_id' => ['required', 'exists:skill_categories,id'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $skill->update($validated);

        return to_route('skills.index')->with('success', 'Skill updated successfully.');
    }

    /**
     * Remove the specified skill from the catalogue (cascade removes assignments).
     * Requirements: 2.1, 2.2, 2.3
     */
    public function destroy(Skill $skill): RedirectResponse
    {
        $this->authorize('delete', $skill);

        $skill->delete();

        return to_route('skills.index')->with('success', 'Skill deleted successfully.');
    }

    /**
     * Toggle the is_active flag of the specified skill.
     * Requirements: 9.1–9.4
     */
    public function toggle(Skill $skill): RedirectResponse
    {
        $this->authorize('toggle', $skill);

        $skill->update(['is_active' => ! $skill->is_active]);

        return back()->with('success', 'Skill status updated.');
    }
}
