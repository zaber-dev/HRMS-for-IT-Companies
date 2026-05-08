import { Link } from '@inertiajs/react';
import { show as projectShow } from '@/actions/App/Http/Controllers/Projects/ProjectController';
import { Badge } from '@/components/ui/badge';
import type { DashboardProps } from '@/types/dashboard';

type Props = {
    projects: NonNullable<DashboardProps['skillCoverageProjects']>;
};

function statusLabel(status: string): string {
    return status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

export function SkillCoveragePanel({ projects }: Props) {
    return (
        <div className="rounded-lg border border-border bg-card p-6">
            <h2 className="mb-4 text-lg font-semibold">Skill Coverage Gaps</h2>

            {projects.length === 0 ? (
                <p className="py-4 text-center text-sm text-muted-foreground">
                    All active projects have full skill coverage.
                </p>
            ) : (
                <div className="space-y-3">
                    {projects.map((project) => (
                        <div
                            key={project.project_id}
                            className="rounded-md border border-border bg-background p-4"
                        >
                            <div className="flex items-center justify-between gap-2">
                                <Link
                                    href={projectShow.url(project.project_id)}
                                    className="font-medium hover:underline"
                                >
                                    {project.name}
                                </Link>
                                <Badge className="bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                    {statusLabel(project.status)}
                                </Badge>
                            </div>
                            <div className="mt-2 flex flex-wrap gap-1">
                                {project.uncovered_skills.map((skill) => (
                                    <span
                                        key={skill}
                                        className="rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-700 dark:bg-red-900/30 dark:text-red-400"
                                    >
                                        {skill}
                                    </span>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

export function SkillCoveragePanelSkeleton() {
    return (
        <div className="rounded-lg border border-border bg-card p-6">
            <div className="mb-4 h-6 w-40 animate-pulse rounded bg-muted" />
            <div className="space-y-3">
                {[1, 2, 3].map((i) => (
                    <div key={i} className="h-16 animate-pulse rounded bg-muted" />
                ))}
            </div>
        </div>
    );
}
