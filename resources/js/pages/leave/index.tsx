import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    index as leaveIndex,
    create,
    cancel,
} from '@/actions/App/Http/Controllers/Leave/LeaveRequestController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatDateRange, formatDateTime, formatRelative } from '@/lib/utils';
import type { LeaveRequest, LeaveStatus, PaginatedData } from '@/types';

const statusOptions = [
    { value: 'all', label: 'All statuses' },
    { value: 'pending_hr', label: 'Pending HR' },
    { value: 'pending_admin', label: 'Pending Admin' },
    { value: 'pending_super_admin', label: 'Pending Super Admin' },
    { value: 'approved', label: 'Approved' },
    { value: 'rejected', label: 'Rejected' },
    { value: 'cancelled', label: 'Cancelled' },
];

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
    leaveRequests: PaginatedData<LeaveRequest>;
    filters: { status?: string };
    canCreate: boolean;
};

export default function LeaveRequestIndex({
    leaveRequests,
    filters,
    canCreate,
}: Props) {
    const [status, setStatus] = useState(filters.status ?? '');

    function handleStatusChange(value: string) {
        // Treat 'all' as clearing the filter
        const newStatus = value === 'all' ? '' : value;
        setStatus(newStatus);
        router.get(
            leaveIndex.url(),
            { status: newStatus || undefined },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="My Leave Requests" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <Heading
                        title="My Leave Requests"
                        description="View and manage your leave requests"
                    />
                    {canCreate && (
                        <Button asChild>
                            <Link href={create.url()}>New Leave Request</Link>
                        </Button>
                    )}
                </div>

                <div className="flex items-center gap-4">
                    <span className="text-sm font-medium text-muted-foreground">
                        Filter by status:
                    </span>
                    <Select value={status || undefined} onValueChange={handleStatusChange}>
                        <SelectTrigger className="w-56">
                            <SelectValue placeholder="All statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            {statusOptions.map((option) => (
                                <SelectItem key={option.value} value={option.value}>
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="overflow-hidden rounded-lg border border-border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Date Range
                                </th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Reason
                                </th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Status
                                </th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Submitted
                                </th>
                                <th className="px-4 py-3 text-right font-medium text-muted-foreground">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {leaveRequests.data.map((request) => (
                                <tr
                                    key={request.id}
                                    className="bg-background transition-colors hover:bg-muted/30"
                                >
                                    <td className="px-4 py-3 font-medium">
                                        {formatDateRange(
                                            request.start_date,
                                            request.end_date,
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {request.reason ? (
                                            request.reason.length > 50 ? (
                                                request.reason.slice(0, 50) +
                                                '…'
                                            ) : (
                                                request.reason
                                            )
                                        ) : (
                                            <span className="italic">
                                                No reason provided
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
                                    <td className="px-4 py-3 text-muted-foreground">
                                        <span title={formatDateTime(request.submitted_at)}>
                                            {formatRelative(request.submitted_at)}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <div className="flex items-center justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={`/leave-requests/${request.id}`}
                                                >
                                                    View
                                                </Link>
                                            </Button>
                                            {!TERMINAL_STATUSES.includes(
                                                request.status,
                                            ) && (
                                                <Button
                                                    variant="destructive"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={cancel.url(
                                                            request,
                                                        )}
                                                        method="delete"
                                                        as="button"
                                                    >
                                                        Cancel
                                                    </Link>
                                                </Button>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {leaveRequests.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        No leave requests found.
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

LeaveRequestIndex.layout = {
    breadcrumbs: [
        {
            title: 'My Leave Requests',
            href: leaveIndex.url(),
        },
    ],
};
