import { Form, Head, Link } from '@inertiajs/react';
import { index, create, edit, destroy } from '@/actions/App/Http/Controllers/Admin/RoleController';
import { index as permissionsIndex } from '@/actions/App/Http/Controllers/Admin/PermissionController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Role } from '@/types/auth';

const BUILT_IN_ROLES = ['super_admin', 'admin', 'hr', 'employee'];

type Props = {
    roles: Role[];
};

export default function RolesIndex({ roles }: Props) {
    return (
        <>
            <Head title="Roles" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <Heading title="Roles" description="Manage roles and their permissions" />
                    <Button asChild>
                        <Link href={create.url()}>Create role</Link>
                    </Button>
                </div>

                <div className="overflow-hidden rounded-lg border border-border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">Name</th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">Permissions</th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">Users</th>
                                <th className="px-4 py-3 text-right font-medium text-muted-foreground">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {roles.map((role) => {
                                const isBuiltIn = BUILT_IN_ROLES.includes(role.name);
                                return (
                                    <tr key={role.id} className="bg-background hover:bg-muted/30 transition-colors">
                                        <td className="px-4 py-3 font-medium">
                                            {role.name}
                                            {isBuiltIn && (
                                                <span className="ml-2 text-xs text-muted-foreground">(built-in)</span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {role.permissions_count ?? 0}
                                        </td>
                                        <td className="px-4 py-3 text-muted-foreground">
                                            {role.users_count ?? 0}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex items-center justify-end gap-2">
                                                <Button variant="outline" size="sm" asChild>
                                                    <Link href={permissionsIndex.url(role)}>Manage Permissions</Link>
                                                </Button>
                                                {!isBuiltIn && (
                                                    <>
                                                        <Button variant="outline" size="sm" asChild>
                                                            <Link href={edit.url(role)}>Edit</Link>
                                                        </Button>
                                                        <Form {...destroy.form(role)}>
                                                            {({ processing }) => (
                                                                <Button
                                                                    type="submit"
                                                                    variant="destructive"
                                                                    size="sm"
                                                                    disabled={processing}
                                                                >
                                                                    Delete
                                                                </Button>
                                                            )}
                                                        </Form>
                                                    </>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                            {roles.length === 0 && (
                                <tr>
                                    <td colSpan={4} className="px-4 py-8 text-center text-muted-foreground">
                                        No roles found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

RolesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Roles',
            href: index.url(),
        },
    ],
};
