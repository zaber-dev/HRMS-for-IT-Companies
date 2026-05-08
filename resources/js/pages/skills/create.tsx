import { Form, Head, Link } from '@inertiajs/react';
import {
    index,
    create,
    store,
} from '@/actions/App/Http/Controllers/Skills/SkillController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    FormSelect,
    SelectItem,
} from '@/components/ui/form-select';
import type { SkillCategory } from '@/types';

type Props = {
    categories: SkillCategory[];
};

export default function SkillCreate({ categories }: Props) {
    return (
        <>
            <Head title="New Skill" />

            <div className="space-y-6">
                <Heading
                    title="New Skill"
                    description="Add a new skill to the catalogue"
                />

                <Form {...store.form()} className="max-w-lg space-y-6">
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
                                <Label htmlFor="skill_category_id">
                                    Category
                                </Label>
                                <FormSelect
                                    id="skill_category_id"
                                    name="skill_category_id"
                                    placeholder="Select a category"
                                    className="w-full"
                                    required
                                >
                                    {categories.map((cat) => (
                                        <SelectItem key={cat.id} value={String(cat.id)}>
                                            {cat.name}
                                        </SelectItem>
                                    ))}
                                </FormSelect>
                                <InputError message={errors.skill_category_id} />
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
                                    placeholder="Brief description of this skill"
                                    className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
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
