import { Form, Head, Link } from '@inertiajs/react';
import {
    index,
    edit,
    update,
} from '@/actions/App/Http/Controllers/Admin/RoleController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Role } from '@/types/auth';

type Props = {
    role: Role;
};

export default function RolesEdit({ role }: Props) {
    return (
        <>
            <Head title="Edit Role" />

            <div className="space-y-6">
                <Heading title="Edit Role" description="Update the role name" />

                <Form {...update.form(role)} className="max-w-lg space-y-6">
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Role name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    type="text"
                                    defaultValue={role.name}
                                    placeholder="e.g. manager"
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button type="submit" disabled={processing}>
                                    Save changes
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

RolesEdit.layout = ({ role }: Props) => ({
    breadcrumbs: [
        {
            title: 'Roles',
            href: index.url(),
        },
        {
            title: 'Edit Role',
            href: edit.url(role),
        },
    ],
});
