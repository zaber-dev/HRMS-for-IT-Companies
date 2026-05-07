import { Head, Link, useForm } from '@inertiajs/react';
import { index, show } from '@/actions/App/Http/Controllers/Projects/ProjectController';
import { update } from '@/actions/App/Http/Controllers/Projects/ProjectAssignmentController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type Project, type ProjectAssignment } from '@/types/projects';
import { type User } from '@/types/auth';

type Props = {
    project: Project;
    assignment: ProjectAssignment & { user: Pick<User, 'id' | 'name' | 'email'> };
};

export default function AssignmentEdit({ project, assignment }: Props) {
    const { data, setData, put, processing, errors } = useForm({
        task_description: assignment.task_description,
        task_deadline: assignment.task_deadline,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        put(update.url({ project, assignment }));
    }

    return (
        <>
            <Head title={`Edit Assignment — ${assignment.user.name}`} />

            <div className="space-y-6">
                <Heading
                    title="Edit Assignment"
                    description={`Update task details for ${assignment.user.name} on ${project.name}`}
                />

                <form onSubmit={handleSubmit} className="max-w-lg space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="task_description">Task Description</Label>
                        <textarea
                            id="task_description"
                            value={data.task_description}
                            onChange={(e) => setData('task_description', e.target.value)}
                            rows={4}
                            placeholder="Describe the employee's task on this project"
                            className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        />
                        <InputError message={errors.task_description} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="task_deadline">
                            Task Deadline{' '}
                            <span className="text-xs text-muted-foreground">
                                (must be on or before {new Date(project.deadline).toLocaleDateString()})
                            </span>
                        </Label>
                        <Input
                            id="task_deadline"
                            type="date"
                            value={data.task_deadline}
                            max={project.deadline}
                            onChange={(e) => setData('task_deadline', e.target.value)}
                        />
                        <InputError message={errors.task_deadline} />
                    </div>

                    <div className="flex items-center gap-4">
                        <Button type="submit" disabled={processing}>
                            Save Changes
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={show.url(project)}>Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

AssignmentEdit.layout = ({ project }: Props) => ({
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
            title: 'Edit Assignment',
        },
    ],
});
