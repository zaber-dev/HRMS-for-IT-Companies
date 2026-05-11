import { Link } from '@inertiajs/react';
import { show as assignmentShow } from '@/actions/App/Http/Controllers/Projects/ProjectAssignmentController';
import type { DashboardProps } from '@/types/dashboard';

type Props = {
    tasks: NonNullable<DashboardProps['myTasks']>;
};

export function MyTasksPanel({ tasks }: Props) {
    return (
        <div className="rounded-lg border border-border bg-card p-6">
            <h2 className="mb-4 text-lg font-semibold">My Tasks</h2>

            {tasks.length === 0 ? (
                <p className="py-4 text-center text-sm text-muted-foreground">
                    No pending tasks assigned to you.
                </p>
            ) : (
                <div className="space-y-2">
                    {tasks.map((task, i) => {
                        const isOverdue = task.days_remaining < 0;

                        return (
                            <div
                                key={i}
                                className="flex items-start justify-between gap-4 rounded-md border border-border bg-background p-3"
                            >
                                <div className="min-w-0 flex-1">
                                    <Link
                                        href={assignmentShow({
                                            project: task.project_id,
                                            assignment: task.assignment_id,
                                        })}
                                        className="text-sm font-medium hover:underline"
                                    >
                                        {task.project_name}
                                    </Link>
                                    <p className="mt-0.5 truncate text-xs text-muted-foreground">
                                        {task.task_description}
                                    </p>
                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                        Due: {task.task_deadline}
                                    </p>
                                </div>
                                <div className="shrink-0">
                                    {isOverdue ? (
                                        <span className="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                            Overdue{' '}
                                            {Math.abs(task.days_remaining)}d
                                        </span>
                                    ) : (
                                        <span className="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                            {task.days_remaining}d left
                                        </span>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
