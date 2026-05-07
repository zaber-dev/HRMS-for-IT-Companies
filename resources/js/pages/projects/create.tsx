import { Form, Head, Link } from '@inertiajs/react';
import { index, create, store } from '@/actions/App/Http/Controllers/Projects/ProjectController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type Skill } from '@/types/skills';

type Props = {
    skills: Skill[];
};

export default function ProjectCreate({ skills }: Props) {
    return (
        <>
            <Head title="New Project" />

            <div className="space-y-6">
                <Heading title="New Project" description="Create a new project and assign required skills" />

                <Form {...store.form()} className="space-y-6 max-w-lg">
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    type="text"
                                    required
                                    placeholder="Project name"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">
                                    Description <span className="text-muted-foreground">(optional)</span>
                                </Label>
                                <textarea
                                    id="description"
                                    name="description"
                                    rows={4}
                                    placeholder="Brief description of this project"
                                    className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="features_list">
                                    Features List <span className="text-muted-foreground">(optional)</span>
                                </Label>
                                <textarea
                                    id="features_list"
                                    name="features_list"
                                    rows={4}
                                    placeholder="List the key features or deliverables"
                                    className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                />
                                <InputError message={errors.features_list} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="status">Status</Label>
                                <select
                                    id="status"
                                    name="status"
                                    defaultValue="planning"
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                >
                                    <option value="planning">Planning</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="on_hold">On Hold</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                <InputError message={errors.status} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="deadline">Deadline</Label>
                                <Input
                                    id="deadline"
                                    name="deadline"
                                    type="date"
                                    required
                                />
                                <InputError message={errors.deadline} />
                            </div>

                            {skills.length > 0 && (
                                <div className="grid gap-2">
                                    <Label>Required Skills <span className="text-muted-foreground">(optional)</span></Label>
                                    <div className="rounded-md border border-input p-3 space-y-2 max-h-48 overflow-y-auto">
                                        {skills.map((skill) => (
                                            <label key={skill.id} className="flex items-center gap-2 cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    name="skill_ids[]"
                                                    value={skill.id}
                                                    className="h-4 w-4 rounded border-input"
                                                />
                                                <span className="text-sm">{skill.name}</span>
                                            </label>
                                        ))}
                                    </div>
                                    <InputError message={errors.skill_ids} />
                                </div>
                            )}

                            <div className="flex items-center gap-4">
                                <Button type="submit" disabled={processing}>
                                    Create Project
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={index.url()}>Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

ProjectCreate.layout = {
    breadcrumbs: [
        {
            title: 'Projects',
            href: index.url(),
        },
        {
            title: 'New Project',
            href: create.url(),
        },
    ],
};
