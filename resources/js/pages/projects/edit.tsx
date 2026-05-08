import { Form, Head, Link } from '@inertiajs/react';
import {
    index,
    show,
    update,
} from '@/actions/App/Http/Controllers/Projects/ProjectController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    FormSelect,
    SelectItem,
} from '@/components/ui/form-select';
import type { Project } from '@/types/projects';
import type { Skill } from '@/types/skills';

const statusOptions = [
    { value: 'planning', label: 'Planning' },
    { value: 'in_progress', label: 'In Progress' },
    { value: 'on_hold', label: 'On Hold' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' },
];

type Props = {
    project: Project & { skills: Skill[] };
    skills: Skill[];
};

export default function ProjectEdit({ project, skills }: Props) {
    const currentSkillIds = project.skills.map((skill) => skill.id);

    return (
        <>
            <Head title={`Edit ${project.name}`} />

            <div className="space-y-6">
                <Heading
                    title="Edit Project"
                    description="Update the project details and required skills"
                />

                <Form
                    {...update.form(project)}
                    className="max-w-lg space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    type="text"
                                    required
                                    defaultValue={project.name}
                                    placeholder="Project name"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">
                                    Description{' '}
                                    <span className="text-muted-foreground">
                                        (optional)
                                    </span>
                                </Label>
                                <textarea
                                    id="description"
                                    name="description"
                                    rows={4}
                                    defaultValue={project.description ?? ''}
                                    placeholder="Brief description of this project"
                                    className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="features_list">
                                    Features List{' '}
                                    <span className="text-muted-foreground">
                                        (optional)
                                    </span>
                                </Label>
                                <textarea
                                    id="features_list"
                                    name="features_list"
                                    rows={4}
                                    defaultValue={project.features_list ?? ''}
                                    placeholder="List the key features or deliverables"
                                    className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                                />
                                <InputError message={errors.features_list} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="status">Status</Label>
                                <FormSelect
                                    id="status"
                                    name="status"
                                    defaultValue={project.status}
                                    placeholder="Select a status"
                                    className="w-full"
                                >
                                    {statusOptions.map((option) => (
                                        <SelectItem key={option.value} value={option.value}>
                                            {option.label}
                                        </SelectItem>
                                    ))}
                                </FormSelect>
                                <InputError message={errors.status} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="deadline">Deadline</Label>
                                <Input
                                    id="deadline"
                                    name="deadline"
                                    type="date"
                                    required
                                    defaultValue={project.deadline}
                                />
                                <InputError message={errors.deadline} />
                            </div>

                            {skills.length > 0 && (
                                <div className="grid gap-2">
                                    <Label>
                                        Required Skills{' '}
                                        <span className="text-muted-foreground">
                                            (optional)
                                        </span>
                                    </Label>
                                    <div className="max-h-48 space-y-2 overflow-y-auto rounded-md border border-input p-3">
                                        {skills.map((skill) => (
                                            <label
                                                key={skill.id}
                                                className="flex cursor-pointer items-center gap-2"
                                            >
                                                <input
                                                    type="checkbox"
                                                    name="skill_ids[]"
                                                    value={skill.id}
                                                    defaultChecked={currentSkillIds.includes(
                                                        skill.id,
                                                    )}
                                                    className="h-4 w-4 rounded border-input"
                                                />
                                                <span className="text-sm">
                                                    {skill.name}
                                                </span>
                                            </label>
                                        ))}
                                    </div>
                                    <InputError message={errors.skill_ids} />
                                </div>
                            )}

                            <div className="flex items-center gap-4">
                                <Button type="submit" disabled={processing}>
                                    Save Changes
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={show.url(project)}>Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

ProjectEdit.layout = ({ project }: Props) => ({
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
            title: 'Edit',
        },
    ],
});
