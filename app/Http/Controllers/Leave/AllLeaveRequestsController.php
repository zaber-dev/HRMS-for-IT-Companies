<?php

namespace App\Http\Controllers\Leave;

use App\Enums\LeaveStatus;
use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AllLeaveRequestsController extends Controller
{
    /**
     * Display all leave requests across all users (Admin / Super Admin only).
     * Requirements: 10.6, 12.1
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAll', LeaveRequest::class);

        $query = LeaveRequest::with('user')->latest('submitted_at');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by submitter role (spatie/laravel-permission provides the role() scope)
        if ($request->filled('role')) {
            $query->whereHas('user', fn ($q) => $q->role($request->input('role')));
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('start_date', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->where('end_date', '<=', $request->input('end_date'));
        }

        $leaveRequests = $query->paginate(15)->withQueryString();

        return Inertia::render('leave/all', [
            'leaveRequests' => $leaveRequests,
            'filters' => $request->only(['status', 'role', 'start_date', 'end_date']),
            'statuses' => array_column(LeaveStatus::cases(), 'value'),
        ]);
    }
}
