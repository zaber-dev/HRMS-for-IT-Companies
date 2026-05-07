import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { index, edit, deleteMethod } from '@/actions/App/Http/Controllers/Projects/ProjectController';
import { create as assignmentCreate, edit as assignmentEdit, destroy as assignmentDestroy } from '@/actions/App/Http/Controllers/Projects/ProjectAssignmentController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { type Auth } from '@/types/auth';
import { type Project, type ProjectAssignment, type ProjectStatus, type BenchStatus, type CompletionStatus } from '@/types/projects';
import { type Skill } from '@/types/skills';

type AssignmentWithUser = Omit<ProjectAssignment, 'user'> & {
    user: {
        id: number;
        name: string;
        email: string;
        bench_status: BenchStatus;
    };
};

type ProjectWithDetails = Omit<Project, 'assignments'> & {
    skills: Skill[];
    assignments: AssignmentWithUser[];
};

type Props = {
    project: ProjectWithDetails;
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

function RemoveAssignmentButton({ project, assignment }: { project: ProjectWithDetails; assignment: AssignmentWithUser }) {
    const { delete: destroy, processing } = useForm({});

    function handleRemove() {
        destroy(assignmentDestroy({ project: project.id, assignment: assignment.id }).url, {
            preserveScroll: true,
        });
    }

    return (
        <Button variant="destructive" size="sm" disabled={processing} onClick={handleRemove}>
            Remove
        </Button>
    );
}

export default function ProjectShow({ project }: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const role = auth.user.role;
    const isPrivileged = role === 'hr' || role === 'admin' || role === 'super_admin';

    return (
        <>
            <Head title={project.name} />

            <div className="space-y-8">
                <div className="flex items-start justify-between gap-4">
                    <Heading title={project.name} description="Project details and team assignments" />

                    {isPrivileged && (
                        <div className="flex items-center gap-2 shrink-0">
                            <Button variant="outline" asChild>
                                <Link href={edit.url(project)}>Edit</Link>
                            </Button>
                            <Button variant="destructive" asChild>
                                <Link href={deleteMethod.url(project)}>Delete</Link>
                            </Button>
                        </div>
                    )}
                </div>

                {/* Project Details */}
                <div className="rounded-lg border border-border p-6 space-y-4">
                    <h2 className="text-base font-semibold">Project Details</h2>

                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">Status</p>
                            <div className="mt-1">
                                <Badge className={statusBadgeClass(project.status)}>
                                    {formatStatus(project.status)}
                                </Badge>
                            </div>
                        </div>

                        <div>
                            <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">Deadline</p>
                            <p className="mt-1 text-sm">{new Date(project.deadline).toLocaleDateString()}</p>
                        </div>

                        {project.description && (
                            <div className="sm:col-span-2">
                                <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">Description</p>
                                <p className="mt-1 text-sm whitespace-pre-wrap">{project.description}</p>
                            </div>
                        )}

                        {project.features_list && (
                            <div className="sm:col-span-2">
                                <p className="text-xs font-medium text-muted-foreground uppercase tracking-wide">Features List</p>
                                <p className="mt-1 text-sm whitespace-pre-wrap">{project.features_list}</p>
                            </div>
                        )}
                    </div>
                </div>

                {/* Required Skills */}
                <div className="rounded-lg border border-border p-6 space-y-4">
                    <h2 className="text-base font-semibold">Required Skills</h2>

                    {project.skills.length > 0 ? (
                        <div className="flex flex-wrap gap-2">
                            {project.skills.map((skill) => (
                                <Badge key={skill.id} variant="secondary">
                                    {skill.name}
                                </Badge>
                            ))}
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground">No required skills specified.</p>
                    )}
                </div>

                {/* Assignments */}
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h2 className="text-base font-semibold">Assignments</h2>

                        {isPrivileged && (
                            <Button asChild>
                                <Link href={assignmentCreate.url(project)}>Assign Employee</Link>
                            </Button>
                        )}
                    </div>

                    <div className="overflow-hidden rounded-lg border border-border">
                        <table className="w-full text-sm">
                            <thead className="bg-muted/50">
                                <tr>
                                    <th className="px-4 py-3 text-left font-medium text-muted-foreground">Employee</th>
                                    <th className="px-4 py-3 text-left font-medium text-muted-foreground">Bench Status</th>
                                    <th className="px-4 py-3 text-left font-medium text-muted-foreground">Task Description</th>
                                    <th className="px-4 py-3 text-left font-medium text-muted-foreground">Task Deadline</th>
                                    <th className="px-4 py-3 text-left font-medium text-muted-foreground">Completion</th>
                                    {isPrivileged && (
                                        <th className="px-4 py-3 text-right font-medium text-muted-foreground">Actions</th>
                                    )}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-border">
                                {project.assignments.map((assignment) => (
                                    <tr key={assignment.id} className="bg-background hover:bg-muted/30 transition-colors">
                                        <td className="px-4 py-3 font-medium">{assignment.user.name}</td>
                                        <td className="px-4 py-3">
                                            <Badge className={benchStatusBadgeClass(assignment.user.bench_status)}>
                                                {formatBenchStatus(assignment.user.bench_status)}
                                            </Badge>
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
                                        {isPrivileged && (
                                            <td className="px-4 py-3 text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    <Button variant="outline" size="sm" asChild>
                                                        <Link href={assignmentEdit.url({ project: project.id, assignment: assignment.id })}>
                                                            Edit
                                                        </Link>
                                                    </Button>
                                                    <RemoveAssignmentButton project={project} assignment={assignment} />
                                                </div>
                                            </td>
                                        )}
                                    </tr>
                                ))}
                                {project.assignments.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={isPrivileged ? 6 : 5}
                                            className="px-4 py-8 text-center text-muted-foreground"
                                        >
                                            No employees assigned yet.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </>
    );
}

ProjectShow.layout = ({ project }: Props) => ({
    breadcrumbs: [
        {
            title: 'Projects',
            href: index.url(),
        },
        {
            title: project.name,
        },
    ],
});
