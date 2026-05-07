import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { index, markComplete } from '@/actions/App/Http/Controllers/Projects/MyProjectController';
import { show } from '@/actions/App/Http/Controllers/Projects/ProjectController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { type Auth } from '@/types/auth';
import { type BenchStatus, type CompletionStatus, type ProjectAssignment, type ProjectStatus } from '@/types/projects';

type AssignmentWithProject = ProjectAssignment & {
    project: {
        id: number;
        name: string;
        status: ProjectStatus;
        deadline: string;
    };
};

type Props = {
    assignments: AssignmentWithProject[];
};

function statusBadgeClass(status: ProjectStatus): string {
    const map: Record<ProjectStatus, string> = {
        planning: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        in_progress: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        on_hold: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
        completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        cancelled: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
    };
    return map[status] ?? '';
}

function formatStatus(status: ProjectStatus): string {
    return status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

function completionStatusBadgeClass(status: CompletionStatus): string {
    return status === 'complete'
        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
        : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
}

function benchStatusBadgeClass(status: BenchStatus): string {
    return status === 'on_bench'
        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
        : 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400';
}

function formatBenchStatus(status: BenchStatus): string {
    return status === 'on_bench' ? 'On Bench' : 'Assigned';
}

function MarkCompleteButton({ assignment }: { assignment: AssignmentWithProject }) {
    const { patch, processing } = useForm({});
    const isComplete = assignment.completion_status === 'complete';

    function handleMarkComplete() {
        patch(markComplete.url(assignment));
    }

    return (
        <Button
            size="sm"
            disabled={isComplete || processing}
            onClick={handleMarkComplete}
            variant={isComplete ? 'outline' : 'default'}
        >
            {isComplete ? 'Completed' : 'Mark Complete'}
        </Button>
    );
}

export default function MyProjectsIndex({ assignments }: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const benchStatus = (auth.user.bench_status ?? 'on_bench') as BenchStatus;

    return (
        <>
            <Head title="My Projects" />

            <div className="space-y-6">
                <div className="flex items-start justify-between gap-4">
                    <Heading title="My Projects" description="Projects you are currently assigned to" />

                    <div className="shrink-0">
                        <Badge className={benchStatusBadgeClass(benchStatus)}>
                            {formatBenchStatus(benchStatus)}
                        </Badge>
                    </div>
                </div>

                <div className="overflow-hidden rounded-lg border border-border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">Project</th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">Status</th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">Project Deadline</th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">Task Description</th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">Task Deadline</th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">Completion</th>
                                <th className="px-4 py-3 text-right font-medium text-muted-foreground">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {assignments.map((assignment) => (
                                <tr key={assignment.id} className="bg-background hover:bg-muted/30 transition-colors">
                                    <td className="px-4 py-3 font-medium">
                                        <Link href={show.url(assignment.project)} className="hover:underline">
                                            {assignment.project.name}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge className={statusBadgeClass(assignment.project.status)}>
                                            {formatStatus(assignment.project.status)}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {new Date(assignment.project.deadline).toLocaleDateString()}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground max-w-xs truncate">
                                        {assignment.task_description}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {new Date(assignment.task_deadline).toLocaleDateString()}
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge className={completionStatusBadgeClass(assignment.completion_status)}>
                                            {assignment.completion_status === 'complete' ? 'Complete' : 'Pending'}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <MarkCompleteButton assignment={assignment} />
                                    </td>
                                </tr>
                            ))}
                            {assignments.length === 0 && (
                                <tr>
                                    <td colSpan={7} className="px-4 py-8 text-center text-muted-foreground">
                                        You are not assigned to any projects.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

MyProjectsIndex.layout = {
    breadcrumbs: [
        {
            title: 'My Projects',
            href: index.url(),
        },
    ],
};
