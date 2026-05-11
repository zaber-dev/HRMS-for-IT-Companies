import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    edit,
    destroy,
} from '@/actions/App/Http/Controllers/Projects/ProjectAssignmentController';
import {
    show as projectShow,
} from '@/actions/App/Http/Controllers/Projects/ProjectController';
import { markComplete } from '@/actions/App/Http/Controllers/Projects/MyProjectController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Auth } from '@/types/auth';
import type {
    Project,
    ProjectAssignment,
    BenchStatus,
    CompletionStatus,
} from '@/types/projects';

type AssignmentWithUser = ProjectAssignment & {
    user: {
        id: number;
        name: string;
        email: string;
        bench_status: BenchStatus;
    };
};

type Props = {
    project: Project;
    assignment: AssignmentWithUser;
};

function benchStatusBadgeClass(status: BenchStatus): string {
    return status === 'on_bench'
        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
        : 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400';
}

function formatBenchStatus(status: BenchStatus): string {
    return status === 'on_bench' ? 'On Bench' : 'Assigned';
}

function completionStatusBadgeClass(status: CompletionStatus): string {
    return status === 'complete'
        ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'
        : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
}

function MarkCompleteButton({ assignment }: { assignment: AssignmentWithUser }) {
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

export default function AssignmentShow({ project, assignment }: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const role = auth.user.role;
    const isPrivileged =
        role === 'hr' || role === 'admin' || role === 'super_admin';
    const isAssignedEmployee = auth.user.id === assignment.user.id;

    return (
        <>
            <Head title={`Task - ${project.name}`} />

            <div className="space-y-8">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <p className="text-sm text-muted-foreground mb-1">
                            <Link
                                href={projectShow.url(project)}
                                className="hover:underline"
                            >
                                {project.name}
                            </Link>{' '}
                            / Assignment
                        </p>
                        <Heading
                            title="Task Details"
                            description="View task information and status"
                        />
                    </div>

                    <div className="flex shrink-0 items-center gap-2">
                        {isPrivileged && (
                            <>
                                <Button variant="outline" asChild>
                                    <Link
                                        href={edit({
                                            project: project.id,
                                            assignment: assignment.id,
                                        })}
                                    >
                                        Edit
                                    </Link>
                                </Button>
                                <Button variant="destructive" asChild>
                                    <Link
                                        href={destroy({
                                            project: project.id,
                                            assignment: assignment.id,
                                        }).url}
                                    >
                                        Remove
                                    </Link>
                                </Button>
                            </>
                        )}
                        {isAssignedEmployee && !isPrivileged && (
                            <MarkCompleteButton assignment={assignment} />
                        )}
                    </div>
                </div>

                {/* Assignment Details */}
                <div className="space-y-4 rounded-lg border border-border p-6">
                    <h2 className="text-base font-semibold">
                        Assignment Information
                    </h2>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Assigned Employee
                            </p>
                            <p className="mt-1 font-medium">
                                {assignment.user.name}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {assignment.user.email}
                            </p>
                        </div>

                        <div>
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Bench Status
                            </p>
                            <div className="mt-1">
                                <Badge
                                    className={benchStatusBadgeClass(
                                        assignment.user.bench_status,
                                    )}
                                >
                                    {formatBenchStatus(
                                        assignment.user.bench_status,
                                    )}
                                </Badge>
                            </div>
                        </div>

                        <div className="sm:col-span-2">
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Task Description
                            </p>
                            <p className="mt-1 text-sm whitespace-pre-wrap">
                                {assignment.task_description}
                            </p>
                        </div>

                        <div>
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Task Deadline
                            </p>
                            <p className="mt-1 text-sm">
                                {new Date(
                                    assignment.task_deadline,
                                ).toLocaleDateString()}
                            </p>
                        </div>

                        <div>
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Completion Status
                            </p>
                            <div className="mt-1">
                                <Badge
                                    className={completionStatusBadgeClass(
                                        assignment.completion_status,
                                    )}
                                >
                                    {assignment.completion_status === 'complete'
                                        ? 'Complete'
                                        : 'Pending'}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Project Info Card */}
                <div className="space-y-4 rounded-lg border border-border p-6">
                    <h2 className="text-base font-semibold">Project</h2>
                    <div className="flex items-center justify-between">
                        <div>
                            <p className="font-medium">{project.name}</p>
                            <p className="text-sm text-muted-foreground">
                                Deadline:{' '}
                                {new Date(project.deadline).toLocaleDateString()}
                            </p>
                        </div>
                        <Button variant="outline" size="sm" asChild>
                            <Link href={projectShow.url(project)}>
                                View Project
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="flex items-center gap-3">
                    <Button variant="outline" asChild>
                        <Link href={projectShow.url(project)}>
                            Back to Project
                        </Link>
                    </Button>
                </div>
            </div>
        </>
    );
}

AssignmentShow.layout = (props: Props) => ({
    breadcrumbs: [
        {
            title: 'Projects',
            href: projectShow.url(props.project),
        },
        {
            title: props.project.name,
            href: projectShow.url(props.project),
        },
        {
            title: 'Assignment',
            href: '#',
        },
    ],
});
