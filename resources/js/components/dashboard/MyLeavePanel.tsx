import { Link } from '@inertiajs/react';
import { index as leaveIndex } from '@/actions/App/Http/Controllers/Leave/LeaveRequestController';
import { Badge } from '@/components/ui/badge';
import { formatDateRange } from '@/lib/utils';
import type { DashboardProps } from '@/types/dashboard';
import type { LeaveStatus } from '@/types/leave';

type Props = NonNullable<DashboardProps['myLeave']>;

function statusBadgeClass(status: string): string {
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

    return map[status as LeaveStatus] ?? 'bg-gray-100 text-gray-800';
}

function formatStatus(status: string): string {
    return status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

export function MyLeavePanel({
    approved_days,
    pending_count,
    most_recent,
}: Props) {
    return (
        <div className="rounded-lg border border-border bg-card p-6">
            <h2 className="mb-4 text-lg font-semibold">My Leave</h2>

            <div className="mb-4 grid grid-cols-2 gap-3">
                <div className="rounded-md border border-border bg-background p-3 text-center">
                    <div className="text-2xl font-bold text-green-600 dark:text-green-400">
                        {approved_days}
                    </div>
                    <div className="mt-1 text-xs text-muted-foreground">
                        Approved Days (This Year)
                    </div>
                </div>
                <Link
                    href={leaveIndex.url()}
                    className="block rounded-md border border-border bg-background p-3 text-center transition-colors hover:bg-muted/30"
                >
                    <div className="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                        {pending_count}
                    </div>
                    <div className="mt-1 text-xs text-muted-foreground">
                        Pending Requests
                    </div>
                </Link>
            </div>

            {most_recent && (
                <div>
                    <p className="mb-2 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                        Most Recent Request
                    </p>
                    <Link
                        href={leaveIndex.url()}
                        className="flex items-center justify-between rounded-md border border-border bg-background p-3 transition-colors hover:bg-muted/30"
                    >
                        <span className="text-sm text-muted-foreground">
                            {formatDateRange(
                                most_recent.start_date,
                                most_recent.end_date,
                            )}
                        </span>
                        <Badge className={statusBadgeClass(most_recent.status)}>
                            {formatStatus(most_recent.status)}
                        </Badge>
                    </Link>
                </div>
            )}
        </div>
    );
}
