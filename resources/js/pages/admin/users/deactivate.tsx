import { Form, Head, Link } from '@inertiajs/react';
import {
    index,
    deactivate,
    destroy,
} from '@/actions/App/Http/Controllers/Admin/UserController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import type { Role, User } from '@/types/auth';

type Props = {
    user: User & { roles: Role[] };
};

export default function UsersDeactivate({ user }: Props) {
    const roleName = user.roles[0]?.name ?? '—';

    return (
        <>
            <Head title="Deactivate User" />

            <div className="max-w-lg space-y-6">
                <Heading
                    title="Deactivate User"
                    description="Confirm user account deactivation"
                />

                <div className="space-y-3 rounded-lg border border-destructive/30 bg-destructive/5 p-4">
                    <p className="text-sm font-medium text-destructive">
                        Warning: This will immediately lock the user out of the
                        system.
                    </p>
                    <p className="text-sm text-muted-foreground">
                        The user will be logged out on their next request and
                        will not be able to log back in until reactivated.
                    </p>
                </div>

                <div className="space-y-2 rounded-lg border border-border bg-muted/30 p-4">
                    <div className="flex items-center gap-2 text-sm">
                        <span className="w-16 font-medium">Name:</span>
                        <span>{user.name}</span>
                    </div>
                    <div className="flex items-center gap-2 text-sm">
                        <span className="w-16 font-medium">Email:</span>
                        <span className="text-muted-foreground">
                            {user.email}
                        </span>
                    </div>
                    <div className="flex items-center gap-2 text-sm">
                        <span className="w-16 font-medium">Role:</span>
                        <span className="text-muted-foreground">
                            {roleName}
                        </span>
                    </div>
                </div>

                <Form {...destroy.form(user)}>
                    {({ processing }) => (
                        <div className="flex items-center gap-4">
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={processing}
                            >
                                Deactivate user
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href={index.url()}>Cancel</Link>
                            </Button>
                        </div>
                    )}
                </Form>
            </div>
        </>
    );
}

UsersDeactivate.layout = ({ user }: Props) => ({
    breadcrumbs: [
        {
            title: 'Users',
            href: index.url(),
        },
        {
            title: 'Deactivate User',
            href: deactivate.url(user),
        },
    ],
});
