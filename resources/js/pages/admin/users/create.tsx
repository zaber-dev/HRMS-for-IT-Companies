import { Form, Head, Link } from '@inertiajs/react';
import {
    index,
    create,
    store,
} from '@/actions/App/Http/Controllers/Admin/UserController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Role } from '@/types/auth';

type Props = {
    roles: Role[];
};

export default function UsersCreate({ roles }: Props) {
    return (
        <>
            <Head title="Create User" />

            <div className="space-y-6">
                <Heading
                    title="Create User"
                    description="Add a new user account"
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
                                    autoComplete="name"
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
                                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
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

                            <div className="grid gap-2">
                                <Label htmlFor="password">
                                    Temporary password
                                </Label>
                                <Input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autoComplete="new-password"
                                    placeholder="Temporary password"
                                    required
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button type="submit" disabled={processing}>
                                    Create user
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

UsersCreate.layout = {
    breadcrumbs: [
        {
            title: 'Users',
            href: index.url(),
        },
        {
            title: 'Create User',
            href: create.url(),
        },
    ],
};
