import { Form, Head, Link } from '@inertiajs/react';
import { index, create, store } from '@/actions/App/Http/Controllers/Skills/SkillController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type SkillCategory } from '@/types';

type Props = {
    categories: SkillCategory[];
};

export default function SkillCreate({ categories }: Props) {
    return (
        <>
            <Head title="New Skill" />

            <div className="space-y-6">
                <Heading title="New Skill" description="Add a new skill to the catalogue" />

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
                                    placeholder="Skill name"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="skill_category_id">Category</Label>
                                <select
                                    id="skill_category_id"
                                    name="skill_category_id"
                                    required
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                >
                                    <option value="">Select a category</option>
                                    {categories.map((cat) => (
                                        <option key={cat.id} value={cat.id}>{cat.name}</option>
                                    ))}
                                </select>
                                <InputError message={errors.skill_category_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">
                                    Description <span className="text-muted-foreground">(optional)</span>
                                </Label>
                                <textarea
                                    id="description"
                                    name="description"
                                    rows={4}
                                    placeholder="Brief description of this skill"
                                    className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="flex items-center gap-2">
                                <input
                                    id="is_active"
                                    name="is_active"
                                    type="checkbox"
                                    defaultChecked
                                    value="1"
                                    className="h-4 w-4 rounded border-input"
                                />
                                <Label htmlFor="is_active">Active</Label>
                                <InputError message={errors.is_active} />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button type="submit" disabled={processing}>
                                    Create Skill
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

SkillCreate.layout = {
    breadcrumbs: [
        {
            title: 'Skills',
            href: index.url(),
        },
        {
            title: 'New Skill',
            href: create.url(),
        },
    ],
};
