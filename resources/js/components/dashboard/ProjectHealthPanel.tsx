import { Link } from '@inertiajs/react';
import { index as projectsIndex } from '@/actions/App/Http/Controllers/Projects/ProjectController';
import type { DashboardProps } from '@/types/dashboard';
import type { ProjectStatus } from '@/types/projects';

type Props = {
    byStatus: NonNullable<DashboardProps['projectHealth']>['by_status'];
    overdue: number;
};

const STATUS_CONFIG: Record<
    ProjectStatus,
    { label: string; colorClass: string }
> = {
    planning: {
        label: 'Planning',
        colorClass: 'text-blue-600 dark:text-blue-400',
    },
    in_progress: {
        label: 'In Progress',
        colorClass: 'text-yellow-600 dark:text-yellow-400',
    },
    on_hold: {
        label: 'On Hold',
        colorClass: 'text-orange-600 dark:text-orange-400',
    },
    completed: {
        label: 'Completed',
        colorClass: 'text-green-600 dark:text-green-400',
    },
    cancelled: {
        label: 'Cancelled',
        colorClass: 'text-gray-500 dark:text-gray-400',
    },
};

const STATUS_ORDER: ProjectStatus[] = [
    'planning',
    'in_progress',
    'on_hold',
    'completed',
    'cancelled',
];

export function ProjectHealthPanel({ byStatus, overdue }: Props) {
    return (
        <div className="rounded-lg border border-border bg-card p-6">
            <h2 className="mb-4 text-lg font-semibold">Project Health</h2>
            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                {STATUS_ORDER.map((status) => {
                    const config = STATUS_CONFIG[status];
                    const count = byStatus[status] ?? 0;

                    return (
                        <Link
                            key={status}
                            href={projectsIndex.url({ query: { status } })}
                            className="rounded-md border border-border bg-background p-3 text-center transition-colors hover:bg-muted/30"
                        >
                            <div
                                className={`text-2xl font-bold ${config.colorClass}`}
                            >
                                {count}
                            </div>
                            <div className="mt-1 text-xs text-muted-foreground">
                                {config.label}
                            </div>
                        </Link>
                    );
                })}
                <div className="rounded-md border border-red-200 bg-red-50 p-3 text-center dark:border-red-900/30 dark:bg-red-900/10">
                    <div className="text-2xl font-bold text-red-600 dark:text-red-400">
                        {overdue}
                    </div>
                    <div className="mt-1 text-xs text-muted-foreground">
                        Overdue
                    </div>
                </div>
            </div>
        </div>
    );
}
