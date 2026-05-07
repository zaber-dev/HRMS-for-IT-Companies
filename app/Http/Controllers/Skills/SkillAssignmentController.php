<?php

namespace App\Http\Controllers\Skills;

use App\Enums\AssignmentSource;
use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Models\SkillAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SkillAssignmentController extends Controller
{
    /**
     * Assign a skill to the given user.
     * Requirements: 5.1–5.6, 6.1–6.6
     */
    public function store(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('create', [SkillAssignment::class, $user]);

        $request->validate([
            'skill_id' => ['required', 'exists:skills,id'],
        ]);

        $skill = Skill::findOrFail($request->skill_id);

        if (! $skill->is_active) {
            return back()->withErrors(['skill_id' => 'This skill is not available for assignment.']);
        }

        if (SkillAssignment::where('user_id', $user->id)->where('skill_id', $skill->id)->exists()) {
            return back()->withErrors(['skill_id' => 'This skill is already assigned to this user.']);
        }

        $source = auth()->id() !== $user->id ? AssignmentSource::Privileged : AssignmentSource::Self;

        SkillAssignment::create([
            'user_id' => $user->id,
            'skill_id' => $skill->id,
            'source' => $source,
        ]);

        return to_route('profile.edit')->with('success', 'Skill assigned successfully.');
    }

    /**
     * Remove a skill assignment from the given user.
     * Requirements: 5.2, 6.2, 6.6, 7.5
     */
    public function destroy(User $user, Skill $skill): RedirectResponse
    {
        // Perform an early access check before attempting to find the assignment,
        // so that users without any permission receive 403 rather than 404.
        Gate::authorize('create', [SkillAssignment::class, $user]);

        $assignment = SkillAssignment::where('user_id', $user->id)
            ->where('skill_id', $skill->id)
            ->firstOrFail();

        $this->authorize('delete', [$assignment, $user]);

        $assignment->delete();

        return to_route('profile.edit')->with('success', 'Skill removed successfully.');
    }
}
