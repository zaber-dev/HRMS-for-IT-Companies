<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Dashboard\DeadlineAlertService;
use App\Services\Dashboard\EmployeeDashboardService;
use App\Services\Dashboard\LeaveQueueService;
use App\Services\Dashboard\ProjectHealthService;
use App\Services\Dashboard\PtoAlertService;
use App\Services\Dashboard\SkillCoverageService;
use App\Services\Dashboard\WorkforceSummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the role-appropriate dashboard for the authenticated user.
     *
     * Privileged users (hr, admin, super_admin) see the HR dashboard with company-wide analytics.
     * Employees see a personal dashboard with their own stats.
     * Users with no matching role receive a 403 response.
     *
     * Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 13.2, 13.3, 14.3
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $threshold = config('dashboard.high_pto_threshold', 14);

        if ($user->hasRole(['hr', 'admin', 'super_admin'])) {
            Gate::authorize('viewHr');

            $role = $user->getRoleNames()->first();

            return Inertia::render('dashboard/hr', [
                'workforceSummary' => app(WorkforceSummaryService::class)->get(),
                'projectHealth' => app(ProjectHealthService::class)->get(),
                'leaveQueueCount' => app(LeaveQueueService::class)->get($role),
                'ptoAlerts' => Inertia::defer(fn () => app(PtoAlertService::class)->get($threshold)),
                'deadlineAlerts' => Inertia::defer(fn () => app(DeadlineAlertService::class)->get()),
                'skillCoverageProjects' => Inertia::defer(fn () => app(SkillCoverageService::class)->get()),
            ]);
        }

        if ($user->hasRole('employee')) {
            Gate::authorize('viewEmployee');

            $data = app(EmployeeDashboardService::class)->get($user);

            return Inertia::render('dashboard/employee', [
                'personalStats' => $data['personal_stats'],
                'myTasks' => $data['my_tasks'],
                'myProjects' => $data['my_projects'],
                'myLeave' => $data['my_leave'],
                'mySkills' => $data['my_skills'],
            ]);
        }

        abort(403);
    }
}
