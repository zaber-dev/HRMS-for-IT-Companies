<?php

namespace App\Http\Controllers\Projects;

use App\Enums\CompletionStatus;
use App\Http\Controllers\Controller;
use App\Models\ProjectAssignment;
use App\Services\BenchStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MyProjectController extends Controller
{
    /**
     * Display the authenticated employee's project assignments.
     * Requirements: 7.1, 8.1, 8.2, 8.3
     */
    public function index(Request $request): Response
    {
        $assignments = ProjectAssignment::where('user_id', auth()->id())
            ->with('project')
            ->get();

        return Inertia::render('projects/my-projects/index', [
            'assignments' => $assignments,
        ]);
    }

    /**
     * Mark the authenticated employee's assignment as complete.
     * Requirements: 7.2, 7.3, 7.4, 7.5, 8.4, 8.5
     */
    public function markComplete(ProjectAssignment $assignment): RedirectResponse
    {
        $this->authorize('markComplete', $assignment);

        if ($assignment->completion_status === CompletionStatus::Complete) {
            throw ValidationException::withMessages([
                'completion_status' => 'This task has already been marked as complete.',
            ]);
        }

        $assignment->completion_status = CompletionStatus::Complete;
        $assignment->save();

        app(BenchStatusService::class)->recalculate(auth()->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Task marked as complete successfully.']);

        return to_route('my-projects.index');
    }
}
