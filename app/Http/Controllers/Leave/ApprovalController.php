<?php

namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Services\LeaveApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalController extends Controller
{
    public function __construct(private readonly LeaveApprovalService $approvalService) {}

    /**
     * Display the approval queue for the authenticated approver.
     * Requirements: 2.1, 3.1, 6.1, 8.1, 10.3–10.5, 11.4
     */
    public function index(): Response
    {
        $this->authorize('viewApprovalQueue', LeaveRequest::class);

        $user = auth()->user();

        $leaveRequests = $this->approvalService
            ->queueFor(auth()->user())
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString();

        $leaveRequests->setCollection(
            $leaveRequests->getCollection()->map(function (LeaveRequest $leaveRequest) use ($user) {
                $leaveRequest->setAttribute('canApprove', $user->can('approve', $leaveRequest));
                $leaveRequest->setAttribute('canReject', $user->can('reject', $leaveRequest));

                return $leaveRequest;
            })
        );

        return Inertia::render('leave/approvals/index', [
            'leaveRequests' => $leaveRequests,
        ]);
    }

    /**
     * Show the approve confirmation page (GET).
     * Requirements: 2.2, 2.3, 3.2, 3.3, 4.1–4.5, 6.2, 6.3, 8.2, 8.3, 11.5, 11.7
     */
    public function showApprove(LeaveRequest $leaveRequest): Response
    {
        $this->authorize('approve', $leaveRequest);

        $leaveRequest->load('user', 'approvalActions.user');

        return Inertia::render('leave/approvals/approve', [
            'leaveRequest' => $leaveRequest,
        ]);
    }

    /**
     * Process the approval (POST).
     * Requirements: 2.2, 2.3, 3.2, 3.3, 4.1–4.5, 6.2, 6.3, 8.2, 8.3, 11.5, 11.7
     */
    public function approve(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        // Check terminal status before policy to return 422 instead of 403
        if ($leaveRequest->status->isTerminal()) {
            return back()->withErrors([
                'approve' => __('This leave request is already in a terminal state and cannot be approved.'),
            ])->setStatusCode(422);
        }

        $this->authorize('approve', $leaveRequest);

        $comment = $request->input('comment');

        $this->approvalService->approve(auth()->user(), $leaveRequest, $comment);

        return to_route('leave-requests.approvals.index');
    }

    /**
     * Show the reject form page (GET).
     * Requirements: 2.4, 2.5, 3.4, 3.5, 4.1–4.5, 6.4, 6.5, 8.4, 8.5, 11.5, 11.7
     */
    public function showReject(LeaveRequest $leaveRequest): Response
    {
        $this->authorize('reject', $leaveRequest);

        $leaveRequest->load('user', 'approvalActions.user');

        return Inertia::render('leave/approvals/reject', [
            'leaveRequest' => $leaveRequest,
        ]);
    }

    /**
     * Process the rejection (POST).
     * Requirements: 2.4, 2.5, 3.4, 3.5, 4.1–4.5, 6.4, 6.5, 8.4, 8.5, 11.5, 11.7
     */
    public function reject(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        // Check terminal status before policy to return 422 instead of 403
        if ($leaveRequest->status->isTerminal()) {
            return back()->withErrors([
                'reject' => __('This leave request is already in a terminal state and cannot be rejected.'),
            ])->setStatusCode(422);
        }

        $this->authorize('reject', $leaveRequest);

        $validated = $request->validate([
            'reason' => ['required', 'string'],
        ]);

        $comment = $request->input('comment');

        $this->approvalService->reject(auth()->user(), $leaveRequest, $validated['reason'], $comment);

        return to_route('leave-requests.approvals.index');
    }
}
