import { Form, Head, Link, usePage } from '@inertiajs/react';
import { index as permissionsIndex } from '@/actions/App/Http/Controllers/Admin/PermissionController';
import {
    index,
    create,
    edit,
    destroy,
} from '@/actions/App/Http/Controllers/Admin/RoleController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import type { Role } from '@/types/auth';

// Role hierarchy levels (higher = more privilege)
const ROLE_LEVELS: Record<string, number> = {
    super_admin: 4,
    admin: 3,
    hr: 2,
    employee: 1,
};

function getRoleLevel(roleName: string): number {
    return ROLE_LEVELS[roleName] ?? 0;
}

type Props = {
    roles: Role[];
    userRoleLevel: number;
    builtInRoles: string[];
};

export default function RolesIndex({ roles, userRoleLevel, builtInRoles }: Props) {
    const canCreateRoles = userRoleLevel >= 3; // Admin and above can create roles

    return (
        <>
            <Head title="Roles" />

            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Roles"
                        description="Manage roles and their permissions"
                    />
                    {canCreateRoles && (
                        <Button asChild>
                            <Link href={create.url()}>Create role</Link>
                        </Button>
                    )}
                </div>

                <div className="overflow-hidden rounded-lg border border-border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Name
                                </th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Permissions
                                </th>
                                <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                    Users
                                </th>
                                <th className="px-4 py-3 text-right font-medium text-muted-foreground">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {roles.map((role) => {
                                const isBuiltIn = builtInRoles.includes(role.name);
                                const roleLevel = getRoleLevel(role.name);
                                const canManageRole = userRoleLevel >= roleLevel; // Can manage roles at or below own level
                                const canEditRole = canManageRole && !isBuiltIn; // Can edit if can manage and not built-in

                                return (
                                    <tr
                                        key={role.id}
                                        className="bg-background transition-colors hover:bg-muted/30"
                                    >
                                        <td className="px-4 py-3 font-medium">
                                            {role.name}
                                            {isBuiltIn && (
                                                <span className="ml-2 text-xs text-muted-foreground">
                                                    (built-in)
                                                </span>
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
                                                {/* Manage Permissions - disabled for built-in roles or roles above user's level */}
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                    disabled={isBuiltIn || !canManageRole}
                                                    title={
                                                        isBuiltIn
                                                            ? 'Built-in roles have fixed permissions'
                                                            : !canManageRole
                                                              ? 'You cannot manage permissions for roles above your level'
                                                              : undefined
                                                    }
                                                >
                                                    <Link
                                                        href={permissionsIndex.url(role)}
                                                        className={
                                                            isBuiltIn || !canManageRole
                                                                ? 'pointer-events-none'
                                                                : ''
                                                        }
                                                    >
                                                        Manage Permissions
                                                    </Link>
                                                </Button>
                                                {canEditRole && (
                                                    <>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            asChild
                                                        >
                                                            <Link href={edit.url(role)}>
                                                                Edit
                                                            </Link>
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
                                    <td
                                        colSpan={4}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
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
