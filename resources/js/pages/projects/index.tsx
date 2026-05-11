import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import {
    index,
    create,
    show,
    edit,
    deleteMethod,
} from '@/actions/App/Http/Controllers/Projects/ProjectController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { PaginatedData } from '@/types';
import type { Project, ProjectStatus } from '@/types/projects';

const statusOptions = [
    { value: 'all', label: 'All statuses' },
    { value: 'planning', label: 'Planning' },
    { value: 'in_progress', label: 'In Progress' },
    { value: 'on_hold', label: 'On Hold' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' },
];

type Props = {
    projects: PaginatedData<Project & { skills_count: number }>;
    filters: { status?: string };
    canManageProjects: boolean;
};

function statusBadgeClass(status: ProjectStatus): string {
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

    return map[status] ?? '';
}

function formatStatus(status: ProjectStatus): string {
    return status.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

export default function ProjectsIndex({ projects, filters, canManageProjects }: Props) {
    const [status, setStatus] = useState(filters.status ?? '');

    function handleStatusChange(value: string) {
        // Treat 'all' as clearing the filter
        const newStatus = value === 'all' ? '' : value;
        setStatus(newStatus);
        router.get(
            index.url(),
            { status: newStatus || undefined },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Projects" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Projects"
                        description="Manage projects and team assignments"
                    />
                    {canManageProjects && (
                        <Button asChild>
                            <Link href={create.url()}>New Project</Link>
                        </Button>
                    )}
                </div>

                <div className="flex items-center gap-4">
                    <span className="text-sm font-medium text-muted-foreground">
                        Filter by status:
                    </span>
                    <Select value={status || undefined} onValueChange={handleStatusChange}>
                        <SelectTrigger className="w-50">
                            <SelectValue placeholder="All statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            {statusOptions.map((option) => (
                                <SelectItem key={option.value} value={option.value}>
                                    {option.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <div className="overflow-hidden rounded-lg border border-border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Name
                                </th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Status
                                </th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Deadline
                                </th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Skills
                                </th>
                                <th className="px-4 py-3 text-right font-medium text-muted-foreground">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {projects.data.map((project) => (
                                <tr
                                    key={project.id}
                                    className="bg-background transition-colors hover:bg-muted/30"
                                >
                                    <td className="px-4 py-3 font-medium">
                                        <Link
                                            href={show.url(project)}
                                            className="hover:underline"
                                        >
                                            {project.name}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge
                                            className={statusBadgeClass(
                                                project.status,
                                            )}
                                        >
                                            {formatStatus(project.status)}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {new Date(
                                            project.deadline,
                                        ).toLocaleDateString()}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {project.skills_count}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <div className="flex items-center justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link href={show.url(project)}>
                                                    View
                                                </Link>
                                            </Button>
                                            {canManageProjects && (
                                                <>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link href={edit.url(project)}>
                                                            Edit
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        variant="destructive"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={deleteMethod.url(
                                                                project,
                                                            )}
                                                        >
                                                            Delete
                                                        </Link>
                                                    </Button>
                                                </>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {projects.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        No projects found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {projects.links.length > 3 && (
                    <div className="flex items-center justify-center gap-1">
                        {projects.links.map((link, i) => (
                            <Button
                                key={i}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                asChild={!!link.url}
                            >
                                {link.url ? (
                                    <Link
                                        href={link.url}
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ) : (
                                    <span
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                )}
                            </Button>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

ProjectsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Projects',
            href: index.url(),
        },
    ],
};
