<?php

namespace App\Http\Controllers\Skills;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Models\SkillCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SkillCategoryController extends Controller
{
    /**
     * Display a listing of all skill categories with their skills count.
     * Requirements: 10.2, 10.5
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Skill::class);

        $categories = SkillCategory::withCount('skills')->get();

        return Inertia::render('skills/categories/index', [
            'categories' => $categories,
            'canManageSkills' => auth()->user()->hasRole(['super_admin', 'admin', 'hr']),
        ]);
    }

    /**
     * Show the form for creating a new skill category.
     * Requirements: 10.2
     */
    public function create(): Response
    {
        $this->authorize('create', Skill::class);

        return Inertia::render('skills/categories/create');
    }

    /**
     * Store a newly created skill category.
     * Requirements: 10.2, 10.4, 10.7
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Skill::class);

        $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100', 'unique:skill_categories'],
        ]);

        SkillCategory::create($request->only('name'));

        return to_route('skill-categories.index')->with('success', 'Category created successfully.');
    }

    /**
     * Show the form for editing the specified skill category.
     * Requirements: 10.3
     */
    public function edit(SkillCategory $skillCategory): Response
    {
        $this->authorize('update', Skill::class);

        return Inertia::render('skills/categories/edit', [
            'category' => $skillCategory,
        ]);
    }

    /**
     * Update the specified skill category.
     * Requirements: 10.3, 10.4, 10.7
     */
    public function update(Request $request, SkillCategory $skillCategory): RedirectResponse
    {
        $this->authorize('update', Skill::class);

        $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100', "unique:skill_categories,name,{$skillCategory->id}"],
        ]);

        $skillCategory->update($request->only('name'));

        return to_route('skill-categories.index')->with('success', 'Category updated successfully.');
    }
}
