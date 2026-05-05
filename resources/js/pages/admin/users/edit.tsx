import { Form, Head, Link } from '@inertiajs/react';
import { index, edit, update } from '@/actions/App/Http/Controllers/Admin/UserController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Role, User } from '@/types/auth';

type Props = {
    user: User & { roles: Role[] };
    roles: Role[];
};

export default function UsersEdit({ user, roles }: Props) {
    const currentRole = user.roles[0]?.name ?? '';

    return (
        <>
            <Head title="Edit User" />

            <div className="space-y-6">
                <Heading title="Edit User" description="Update user account details" />

                <Form {...update.form(user)} className="space-y-6 max-w-lg">
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    type="text"
                                    autoComplete="name"
                                    defaultValue={user.name}
                                    placeholder="Full name"
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    autoComplete="email"
                                    defaultValue={user.email}
                                    placeholder="Email address"
                                    required
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="role">Role</Label>
                                <select
                                    id="role"
                                    name="role"
                                    defaultValue={currentRole}
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                    required
                                >
                                    <option value="">Select a role</option>
                                    {roles.map((role) => (
                                        <option key={role.id} value={role.name}>
                                            {role.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.role} />
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

UsersEdit.layout = ({ user }: Props) => ({
    breadcrumbs: [
        {
            title: 'Users',
            href: index.url(),
        },
        {
            title: 'Edit User',
            href: edit.url(user),
        },
    ],
});
