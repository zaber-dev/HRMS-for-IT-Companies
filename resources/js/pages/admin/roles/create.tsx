import { Form, Head, Link } from '@inertiajs/react';
import {
    index,
    create,
    store,
} from '@/actions/App/Http/Controllers/Admin/RoleController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function RolesCreate() {
    return (
        <>
            <Head title="Create Role" />

            <div className="space-y-6">
                <Heading
                    title="Create Role"
                    description="Add a new role to the system"
                />

                <Form {...store.form()} className="max-w-lg space-y-6">
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Role name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    type="text"
                                    placeholder="e.g. manager"
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button type="submit" disabled={processing}>
                                    Create role
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

RolesCreate.layout = {
    breadcrumbs: [
        {
            title: 'Roles',
            href: index.url(),
        },
        {
            title: 'Create Role',
            href: create.url(),
        },
    ],
};
