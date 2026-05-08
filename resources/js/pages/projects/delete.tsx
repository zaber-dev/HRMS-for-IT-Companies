import { Head, Link, useForm } from '@inertiajs/react';
import {
    index,
    show,
    destroy,
} from '@/actions/App/Http/Controllers/Projects/ProjectController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import type { Project } from '@/types/projects';

type Props = {
    project: Project & { assignments_count: number };
};

export default function ProjectDelete({ project }: Props) {
    const { delete: deleteProject, processing } = useForm({});

    function handleConfirm() {
        deleteProject(destroy.url(project));
    }

    return (
        <>
            <Head title={`Delete ${project.name}`} />

            <div className="space-y-6">
                <Heading
                    title="Delete Project"
                    description="This action cannot be undone."
                />

                <div className="max-w-lg space-y-4 rounded-lg border border-destructive/50 bg-destructive/5 p-6">
                    <p className="text-sm text-muted-foreground">
                        You are about to permanently delete the following
                        project:
                    </p>

                    <div className="space-y-2">
                        <div>
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Project Name
                            </p>
                            <p className="mt-1 text-sm font-semibold">
                                {project.name}
                            </p>
                        </div>

                        <div>
                            <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Active Assignments
                            </p>
                            <p className="mt-1 text-sm">
                                {project.assignments_count === 0
                                    ? 'No employees currently assigned.'
                                    : `${project.assignments_count} employee${project.assignments_count === 1 ? '' : 's'} will be unassigned.`}
                            </p>
                        </div>
                    </div>

                    <p className="text-sm text-muted-foreground">
                        All assignments will be removed and affected employees'
                        bench status will be recalculated.
                    </p>
                </div>

                <div className="flex items-center gap-4">
                    <Button
                        variant="destructive"
                        disabled={processing}
                        onClick={handleConfirm}
                    >
                        Confirm Delete
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href={show.url(project)}>Cancel</Link>
                    </Button>
                </div>
            </div>
        </>
    );
}

ProjectDelete.layout = ({ project }: Props) => ({
    breadcrumbs: [
        {
            title: 'Projects',
            href: index.url(),
        },
        {
            title: project.name,
            href: show.url(project),
        },
        {
            title: 'Delete',
        },
    ],
});
