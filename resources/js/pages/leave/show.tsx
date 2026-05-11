import { Form, Head, Link, usePage } from '@inertiajs/react';
import {
    showApprove,
    showReject,
} from '@/actions/App/Http/Controllers/Leave/ApprovalController';
import {
    index,
    cancel,
} from '@/actions/App/Http/Controllers/Leave/LeaveRequestController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    formatDate,
    formatDateTime,
    formatRelative,
} from '@/lib/utils';
import type { ApprovalAction, LeaveRequest, LeaveStatus, User } from '@/types';

const TERMINAL_STATUSES: LeaveStatus[] = ['approved', 'rejected', 'cancelled'];

function statusBadgeClass(status: LeaveStatus): string {
    const map: Record<LeaveStatus, string> = {
        pending_hr:
            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        pending_admin:
            'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
        pending_super_admin:
            'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        approved:
            'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        rejected:
            'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        cancelled:
            'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
    };

    return map[status] ?? '';
}

function formatStatus(status: LeaveStatus): string {
    return status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

type Props = {
    leaveRequest: LeaveRequest & {
        user: User;
        approval_actions: ApprovalAction[];
    };
    canApprove: boolean;
    canReject: boolean;
    canSuperApprove: boolean;
};

export default function LeaveRequestShow({
    leaveRequest,
    canApprove,
    canReject,
    canSuperApprove,
}: Props) {
    const { auth } = usePage<{ auth: { user: User } }>().props;
    const isOwner = auth.user.id === leaveRequest.user_id;
    const isTerminal = TERMINAL_STATUSES.includes(leaveRequest.status);

    return (
        <>
            <Head title="Leave Request Details" />

            <div className="max-w-2xl space-y-8">
                <Heading
                    title="Leave Request Details"
                    description="Full details and approval history"
                />

                {/* Request Details */}
                <div className="space-y-4 rounded-lg border border-border p-6">
                    <h2 className="text-base font-semibold">
                        Request Information
                    </h2>

                    <dl className="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                        <div>
                            <dt className="text-muted-foreground">
                                Submitted by
                            </dt>
                            <dd className="mt-1 font-medium">
                                {leaveRequest.user?.name ?? '—'}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Status</dt>
                            <dd className="mt-1">
                                <Badge
                                    className={statusBadgeClass(
                                        leaveRequest.status,
                                    )}
                                >
                                    {formatStatus(leaveRequest.status)}
                                </Badge>
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">
                                Start Date
                            </dt>
                            <dd className="mt-1 font-medium">
                                {formatDate(leaveRequest.start_date)}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">End Date</dt>
                            <dd className="mt-1 font-medium">
                                {formatDate(leaveRequest.end_date)}
                            </dd>
                        </div>
                        <div className="col-span-2">
                            <dt className="text-muted-foreground">Reason</dt>
                            <dd className="mt-1">
                                {leaveRequest.reason ?? (
                                    <span className="text-muted-foreground italic">
                                        No reason provided
                                    </span>
                                )}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">
                                Submitted At
                            </dt>
                            <dd className="mt-1 font-medium">
                                <span title={formatDateTime(leaveRequest.submitted_at)}>
                                    {formatRelative(leaveRequest.submitted_at)}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>

                {/* Actions */}
                <div className="flex items-center gap-3">
                    {canApprove && !canSuperApprove && (
                        <Button asChild>
                            <Link href={showApprove.url(leaveRequest)}>
                                Approve
                            </Link>
                        </Button>
                    )}
                    {canSuperApprove && (
                        <Button asChild>
                            <Link href={showApprove.url(leaveRequest)}>
                                Super Admin Approve
                            </Link>
                        </Button>
                    )}
                    {canReject && (
                        <Button variant="destructive" asChild>
                            <Link href={showReject.url(leaveRequest)}>
                                Reject
                            </Link>
                        </Button>
                    )}
                    {isOwner && !isTerminal && (
                        <Form
                            action={cancel.url(leaveRequest)}
                            method="delete"
                            className="inline"
                        >
                            {({ processing }) => (
                                <Button
                                    variant="outline"
                                    type="submit"
                                    disabled={processing}
                                >
                                    Cancel Request
                                </Button>
                            )}
                        </Form>
                    )}
                    <Button variant="outline" asChild>
                        <Link href={index.url()}>Back to My Requests</Link>
                    </Button>
                </div>

                {/* Approval History */}
                {leaveRequest.approval_actions &&
                    leaveRequest.approval_actions.length > 0 && (
                        <div className="space-y-4">
                            <h2 className="text-base font-semibold">
                                Approval History
                            </h2>
                            <div className="space-y-3">
                                {leaveRequest.approval_actions.map((action) => (
                                    <div
                                        key={action.id}
                                        className="space-y-2 rounded-lg border border-border p-4"
                                    >
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <span className="text-sm font-medium">
                                                    {action.user?.name ??
                                                        `User #${action.user_id}`}
                                                </span>
                                                <Badge
                                                    className={
                                                        action.decision ===
                                                        'approved'
                                                            ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
                                                            : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                                                    }
                                                >
                                                    {action.decision ===
                                                    'approved'
                                                        ? 'Approved'
                                                        : 'Rejected'}
                                                </Badge>
                                                {action.is_bypass && (
                                                    <Badge className="bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">
                                                        Bypass
                                                    </Badge>
                                                )}
                                            </div>
                                            <span className="text-xs text-muted-foreground">
                                                <span title={formatDateTime(action.created_at)}>
                                                    {formatRelative(action.created_at)}
                                                </span>
                                            </span>
                                        </div>
                                        {action.comment && (
                                            <p className="text-sm text-muted-foreground">
                                                {action.comment}
                                            </p>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
            </div>
        </>
    );
}

LeaveRequestShow.layout = {
    breadcrumbs: [
        {
            title: 'My Leave Requests',
            href: index.url(),
        },
        {
            title: 'Leave Request Details',
            href: '#',
        },
    ],
};
