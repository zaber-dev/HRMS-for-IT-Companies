import { Link } from '@inertiajs/react';
import { show as projectShow } from '@/actions/App/Http/Controllers/Projects/ProjectController';
import { edit as userEdit } from '@/actions/App/Http/Controllers/Admin/UserController';
import type { DashboardProps } from '@/types/dashboard';

type Props = {
    alerts: NonNullable<DashboardProps['deadlineAlerts']>;
};

export function DeadlineAlertPanel({ alerts }: Props) {
    return (
        <div className="rounded-lg border border-border bg-card p-6">
            <h2 className="mb-4 text-lg font-semibold">Deadline Alerts</h2>

            {alerts.length === 0 ? (
                <p className="py-4 text-center text-sm text-muted-foreground">
                    No overdue tasks at this time.
                </p>
            ) : (
                <div className="overflow-hidden rounded-md border border-border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="px-4 py-2 text-left font-medium text-muted-foreground">Employee</th>
                                <th className="px-4 py-2 text-left font-medium text-muted-foreground">Project</th>
                                <th className="px-4 py-2 text-left font-medium text-muted-foreground">Task</th>
                                <th className="px-4 py-2 text-right font-medium text-muted-foreground">Deadline</th>
                                <th className="px-4 py-2 text-right font-medium text-muted-foreground">Days Overdue</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {alerts.map((alert, i) => (
                                <tr key={i} className="bg-background hover:bg-muted/30 transition-colors">
                                    <td className="px-4 py-2">
                                        <Link
                                            href={userEdit.url(alert.user_id)}
                                            className="font-medium hover:underline"
                                        >
                                            {alert.name}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-2">
                                        <Link
                                            href={projectShow.url(alert.project_id)}
                                            className="hover:underline"
                                        >
                                            {alert.project_name}
                                        </Link>
                                    </td>
                                    <td className="max-w-xs truncate px-4 py-2 text-muted-foreground">
                                        {alert.task_description}
                                    </td>
                                    <td className="px-4 py-2 text-right text-muted-foreground">
                                        {alert.task_deadline}
                                    </td>
                                    <td className="px-4 py-2 text-right font-semibold text-red-600 dark:text-red-400">
                                        {alert.days_overdue}d
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

export function DeadlineAlertPanelSkeleton() {
    return (
        <div className="rounded-lg border border-border bg-card p-6">
            <div className="mb-4 h-6 w-36 animate-pulse rounded bg-muted" />
            <div className="space-y-2">
                {[1, 2, 3].map((i) => (
                    <div key={i} className="h-10 animate-pulse rounded bg-muted" />
                ))}
            </div>
        </div>
    );
}
