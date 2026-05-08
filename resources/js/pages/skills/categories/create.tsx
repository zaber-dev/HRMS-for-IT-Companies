import { Form, Head, Link } from '@inertiajs/react';
import {
    index,
    create,
    store,
} from '@/actions/App/Http/Controllers/Skills/SkillCategoryController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function SkillCategoryCreate() {
    return (
        <>
            <Head title="New Skill Category" />

            <div className="space-y-6">
                <Heading
                    title="New Skill Category"
                    description="Add a new category to organise skills"
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
                                    placeholder="Category name"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button type="submit" disabled={processing}>
                                    Create Category
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

SkillCategoryCreate.layout = {
    breadcrumbs: [
        {
            title: 'Skill Categories',
            href: index.url(),
        },
        {
            title: 'New Category',
            href: create.url(),
        },
    ],
};
