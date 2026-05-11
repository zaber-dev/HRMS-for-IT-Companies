import { Head, Link } from '@inertiajs/react';
import {
    index,
    showApprove,
    showReject,
} from '@/actions/App/Http/Controllers/Leave/ApprovalController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDateRange } from '@/lib/utils';
import type {
    LeaveRequest,
    LeaveStatus,
    PaginatedData,
    Role,
    User,
} from '@/types';

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

type UserWithRoles = User & { roles?: Role[] };
type LeaveRequestWithUser = LeaveRequest & {
    user: UserWithRoles;
    canApprove?: boolean;
    canReject?: boolean;
};

type Props = {
    leaveRequests: PaginatedData<LeaveRequestWithUser>;
};

export default function ApprovalsIndex({ leaveRequests }: Props) {
    return (
        <>
            <Head title="Approval Queue" />

            <div className="space-y-6">
                <Heading
                    title="Approval Queue"
                    description="Leave requests awaiting your action"
                />

                <div className="overflow-hidden rounded-lg border border-border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Submitter
                                </th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Role
                                </th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Date Range
                                </th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Reason
                                </th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Status
                                </th>
                                <th className="px-4 py-3 text-right font-medium text-muted-foreground">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {leaveRequests.data.map((request) => {
                                const roleName =
                                    request.user?.roles?.[0]?.name ?? '—';

                                return (
                                    <tr
                                        key={request.id}
                                        className="bg-background transition-colors hover:bg-muted/30"
                                    >
                                        <td className="px-4 py-3 font-medium">
                                            {request.user?.name ?? '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge variant="secondary">
                                                {roleName}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {formatDateRange(
                                                request.start_date,
                                                request.end_date,
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {request.reason ? (
                                                request.reason.length > 50 ? (
                                                    request.reason.slice(
                                                        0,
                                                        50,
                                                    ) + '…'
                                                ) : (
                                                    request.reason
                                                )
                                            ) : (
                                                <span className="italic">
                                                    —
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge
                                                className={statusBadgeClass(
                                                    request.status,
                                                )}
                                            >
                                                {formatStatus(request.status)}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                {request.canApprove && (
                                                    <Button
                                                        variant="default"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={showApprove.url(
                                                                request,
                                                            )}
                                                        >
                                                            Approve
                                                        </Link>
                                                    </Button>
                                                )}
                                                {request.canReject && (
                                                    <Button
                                                        variant="destructive"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={showReject.url(
                                                                request,
                                                            )}
                                                        >
                                                            Reject
                                                        </Link>
                                                    </Button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                            {leaveRequests.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        No leave requests pending your action.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {leaveRequests.links.length > 3 && (
                    <div className="flex items-center justify-center gap-1">
                        {leaveRequests.links.map((link, i) => (
                            <Button
                                key={i}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                asChild={!!link.url}
                            >
                                {link.url ? (
                                    <Link
                                        href={link.url}
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ) : (
                                    <span
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                )}
                            </Button>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

ApprovalsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Approval Queue',
            href: index.url(),
        },
    ],
};
