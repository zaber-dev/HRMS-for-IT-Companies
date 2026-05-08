<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\LeaveApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveRequestController extends Controller
{
    public function __construct(private readonly LeaveApprovalService $approvalService) {}

    /**
     * Display the authenticated user's leave requests with optional status filter.
     * Requirements: 10.1, 10.7, 11.3
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', LeaveRequest::class);

        $query = LeaveRequest::where('user_id', auth()->id())
            ->latest('submitted_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $leaveRequests = $query->paginate(15)->withQueryString();

        return Inertia::render('leave/index', [
            'leaveRequests' => $leaveRequests,
            'filters' => $request->only('status'),
            'canCreate' => $request->user()->can('create', LeaveRequest::class),
        ]);
    }

    /**
     * Show the form for creating a new leave request.
     * Requirements: 1.1, 11.1
     */
    public function create(): Response
    {
        $this->authorize('create', LeaveRequest::class);

        return Inertia::render('leave/create');
    }

    /**
     * Store a newly created leave request.
     * Requirements: 1.1–1.6
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', LeaveRequest::class);

        $validated = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string'],
        ]);

        // Overlap detection: reject if any non-rejected, non-cancelled request overlaps
        $overlap = LeaveRequest::where('user_id', auth()->id())
            ->whereNotIn('status', ['rejected', 'cancelled'])
            ->where('start_date', '<=', $validated['end_date'])
            ->where('end_date', '>=', $validated['start_date'])
            ->first();

        if ($overlap) {
            return back()->withErrors([
                'start_date' => __('This date range overlaps with an existing leave request (ID: :id).', ['id' => $overlap->id]),
            ])->withInput();
        }

        $user = $request->user();
        $status = $this->approvalService->initialStatus($user);

        LeaveRequest::create([
            'user_id' => $user->id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'reason' => $validated['reason'] ?? null,
            'status' => $status,
            'submitted_at' => now(),
        ]);

        return to_route('leave-requests.index');
    }

    /**
     * Display the specified leave request with approval history.
     * Requirements: 10.2, 11.2
     */
    public function show(Request $request, LeaveRequest $leaveRequest): Response
    {
        $this->authorize('view', $leaveRequest);

        $leaveRequest->load('approvalActions.user');

        $user = $request->user();

        return Inertia::render('leave/show', [
            'leaveRequest' => $leaveRequest,
            'canApprove' => $user->can('approve', $leaveRequest),
            'canReject' => $user->can('reject', $leaveRequest),
            'canSuperApprove' => $user->hasRole('super_admin')
                && $user->id !== $leaveRequest->user_id
                && ! $leaveRequest->status->isTerminal(),
        ]);
    }

    /**
     * Cancel the specified leave request.
     * Requirements: 9.1–9.4
     */
    public function cancel(LeaveRequest $leaveRequest): RedirectResponse
    {
        $this->authorize('cancel', $leaveRequest);

        if ($leaveRequest->status->isTerminal()) {
            return back()->withErrors([
                'cancel' => __('This leave request cannot be cancelled in its current state.'),
            ]);
        }

        $this->approvalService->cancel($leaveRequest);

        return to_route('leave-requests.index');
    }
}
