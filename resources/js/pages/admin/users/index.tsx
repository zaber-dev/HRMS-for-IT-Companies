import { Head, Link } from '@inertiajs/react';
import { index, create, edit, deactivate } from '@/actions/App/Http/Controllers/Admin/UserController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Role, User } from '@/types/auth';

type UserWithRoles = User & { roles: Role[] };

type PaginatedUsers = {
    data: UserWithRoles[];
    links: { url: string | null; label: string; active: boolean }[];
    meta: {
        current_page: number;
        last_page: number;
        total: number;
        per_page: number;
    };
};

type Props = {
    users: PaginatedUsers;
};

export default function UsersIndex({ users }: Props) {
    return (
        <>
            <Head title="Users" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <Heading title="Users" description="Manage user accounts and roles" />
                    <Button asChild>
                        <Link href={create.url()}>Create user</Link>
                    </Button>
                </div>

                <div className="overflow-hidden rounded-lg border border-border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">Name</th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">Email</th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">Role</th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">Status</th>
                                <th className="px-4 py-3 text-right font-medium text-muted-foreground">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {users.data.map((user) => {
                                const roleName = user.roles[0]?.name ?? '—';
                                return (
                                    <tr key={user.id} className="bg-background hover:bg-muted/30 transition-colors">
                                        <td className="px-4 py-3 font-medium">{user.name}</td>
                                        <td className="px-4 py-3 text-muted-foreground">{user.email}</td>
                                        <td className="px-4 py-3">
                                            <Badge variant="secondary">{roleName}</Badge>
                                        </td>
                                        <td className="px-4 py-3">
                                            {user.is_active !== false ? (
                                                <Badge variant="default" className="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                    Active
                                                </Badge>
                                            ) : (
                                                <Badge variant="destructive">Inactive</Badge>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link href={edit.url(user)}>Edit</Link>
                                                </Button>
                                                {user.is_active !== false && (
                                                    <Button variant="destructive" size="sm" asChild>
                                                        <Link href={deactivate.url(user)}>Deactivate</Link>
                                                    </Button>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                            {users.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-4 py-8 text-center text-muted-foreground">
                                        No users found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {users.links.length > 3 && (
                    <div className="flex items-center justify-center gap-1">
                        {users.links.map((link, i) => (
                            <Button
                                key={i}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                asChild={!!link.url}
                            >
                                {link.url ? (
                                    <Link href={link.url} dangerouslySetInnerHTML={{ __html: link.label }} />
                                ) : (
                                    <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                )}
                            </Button>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Users',
            href: index.url(),
        },
    ],
};
