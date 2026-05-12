import { Link } from '@inertiajs/react';
import { indexForUser as leaveIndexForUser } from '@/actions/App/Http/Controllers/Leave/LeaveRequestController';
import type { DashboardProps } from '@/types/dashboard';

type Props = {
    alerts: NonNullable<DashboardProps['ptoAlerts']>;
    threshold: number;
};

export function PtoAlertPanel({ alerts, threshold }: Props) {
    return (
        <div className="rounded-lg border border-border bg-card p-6">
            <h2 className="mb-1 text-lg font-semibold">PTO Alerts</h2>
            <p className="mb-4 text-sm text-muted-foreground">
                Employees with {threshold}+ leave days this year
            </p>

            {alerts.length === 0 ? (
                <p className="py-4 text-center text-sm text-muted-foreground">
                    No high-usage PTO alerts at this time.
                </p>
            ) : (
                <div className="overflow-hidden rounded-md border border-border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="px-4 py-2 text-left font-medium text-muted-foreground">
                                    Employee
                                </th>
                                <th className="px-4 py-2 text-right font-medium text-muted-foreground">
                                    Total Days
                                </th>
                                <th className="px-4 py-2 text-right font-medium text-muted-foreground">
                                    Pending
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {alerts.map((alert) => (
                                <tr
                                    key={alert.user_id}
                                    className="bg-background transition-colors hover:bg-muted/30"
                                >
                                    <td className="px-4 py-2">
                                        <Link
                                            href={leaveIndexForUser.url({
                                                query: {
                                                    user: alert.user_id,
                                                },
                                            })}
                                            className="font-medium hover:underline"
                                        >
                                            {alert.name}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-2 text-right font-semibold text-orange-600 dark:text-orange-400">
                                        {alert.total_leave_days}
                                    </td>
                                    <td className="px-4 py-2 text-right text-muted-foreground">
                                        {alert.pending_requests_count}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}

export function PtoAlertPanelSkeleton() {
    return (
        <div className="rounded-lg border border-border bg-card p-6">
            <div className="mb-4 h-6 w-32 animate-pulse rounded bg-muted" />
            <div className="space-y-2">
                {[1, 2, 3].map((i) => (
                    <div
                        key={i}
                        className="h-10 animate-pulse rounded bg-muted"
                    />
                ))}
            </div>
        </div>
    );
}
