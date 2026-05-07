import { Head, Link, router } from '@inertiajs/react';
import { index, create, show, edit, deleteMethod } from '@/actions/App/Http/Controllers/Projects/ProjectController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { type PaginatedData } from '@/types';
import { type Project, type ProjectStatus } from '@/types/projects';

type Props = {
    projects: PaginatedData<Project & { skills_count: number }>;
    filters: { status?: string };
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

export default function ProjectsIndex({ projects, filters }: Props) {
    function handleStatusChange(e: React.ChangeEvent<HTMLSelectElement>) {
        router.get(index.url(), { status: e.target.value || undefined }, { preserveState: true, replace: true });
    }

    return (
        <>
            <Head title="Projects" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <Heading title="Projects" description="Manage projects and team assignments" />
                    <Button asChild>
                        <Link href={create.url()}>New Project</Link>
                    </Button>
                </div>

                <div className="flex items-center gap-4">
                    <label htmlFor="status-filter" className="text-sm font-medium text-muted-foreground">
                        Filter by status:
                    </label>
                    <select
                        id="status-filter"
                        value={filters.status ?? ''}
                        onChange={handleStatusChange}
                        className="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                    >
                        <option value="">All statuses</option>
                        <option value="planning">Planning</option>
                        <option value="in_progress">In Progress</option>
                        <option value="on_hold">On Hold</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <div className="overflow-hidden rounded-lg border border-border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">Name</th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">Status</th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">Deadline</th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">Skills</th>
                                <th className="px-4 py-3 text-right font-medium text-muted-foreground">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {projects.data.map((project) => (
                                <tr key={project.id} className="bg-background hover:bg-muted/30 transition-colors">
                                    <td className="px-4 py-3 font-medium">
                                        <Link href={show.url(project)} className="hover:underline">
                                            {project.name}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge className={statusBadgeClass(project.status)}>
                                            {formatStatus(project.status)}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {new Date(project.deadline).toLocaleDateString()}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {project.skills_count}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <div className="flex items-center justify-end gap-2">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={show.url(project)}>View</Link>
                                            </Button>
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={edit.url(project)}>Edit</Link>
                                            </Button>
                                            <Button variant="destructive" size="sm" asChild>
                                                <Link href={deleteMethod.url(project)}>Delete</Link>
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {projects.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">
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
                                    <Link href={link.url} dangerouslySetInnerHTML={{ __html: link.label }} />
                                ) : (
                                    <span dangerouslySetInnerHTML={{ __html: link.label }} />
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
