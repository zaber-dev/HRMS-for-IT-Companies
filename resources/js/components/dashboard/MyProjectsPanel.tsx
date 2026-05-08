import { Link } from '@inertiajs/react';
import { show as projectShow } from '@/actions/App/Http/Controllers/Projects/ProjectController';
import { Badge } from '@/components/ui/badge';
import type { DashboardProps } from '@/types/dashboard';
import type { ProjectStatus } from '@/types/projects';

type Props = {
    projects: NonNullable<DashboardProps['myProjects']>;
};

function statusBadgeClass(status: string): string {
    const map: Record<ProjectStatus, string> = {
        planning:
            'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        in_progress:
            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        on_hold:
            'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
        completed:
            'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        cancelled:
            'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
    };

    return map[status as ProjectStatus] ?? 'bg-gray-100 text-gray-800';
}

function formatStatus(status: string): string {
    return status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

export function MyProjectsPanel({ projects }: Props) {
    return (
        <div className="rounded-lg border border-border bg-card p-6">
            <h2 className="mb-4 text-lg font-semibold">My Projects</h2>

            {projects.length === 0 ? (
                <p className="py-4 text-center text-sm text-muted-foreground">
                    You are not assigned to any projects.
                </p>
            ) : (
                <div className="space-y-2">
                    {projects.map((project) => (
                        <div
                            key={project.project_id}
                            className="flex items-center justify-between gap-4 rounded-md border border-border bg-background p-3"
                        >
                            <div className="min-w-0 flex-1">
                                <div className="flex items-center gap-2">
                                    <Link
                                        href={projectShow.url(
                                            project.project_id,
                                        )}
                                        className="text-sm font-medium hover:underline"
                                    >
                                        {project.name}
                                    </Link>
                                    {project.is_near_deadline && (
                                        <span
                                            className="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"
                                            title="Deadline approaching"
                                        >
                                            ⚠ Soon
                                        </span>
                                    )}
                                </div>
                                {project.deadline && (
                                    <p className="mt-0.5 text-xs text-muted-foreground">
                                        Due: {project.deadline}
                                    </p>
                                )}
                            </div>
                            <Badge className={statusBadgeClass(project.status)}>
                                {formatStatus(project.status)}
                            </Badge>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
