import { Form, Head, Link } from '@inertiajs/react';
import {
    index,
    update,
} from '@/actions/App/Http/Controllers/Skills/SkillCategoryController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { SkillCategory } from '@/types';

type Props = {
    category: SkillCategory;
};

export default function SkillCategoryEdit({ category }: Props) {
    return (
        <>
            <Head title={`Edit Category: ${category.name}`} />

            <div className="space-y-6">
                <Heading
                    title="Edit Skill Category"
                    description="Rename this skill category"
                />

                <Form {...update.form(category)} className="max-w-lg space-y-6">
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    type="text"
                                    required
                                    defaultValue={category.name}
                                    placeholder="Category name"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button type="submit" disabled={processing}>
                                    Save Changes
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

SkillCategoryEdit.layout = {
    breadcrumbs: [
        {
            title: 'Skill Categories',
            href: index.url(),
        },
        {
            title: 'Edit Category',
            href: '#',
        },
    ],
};
