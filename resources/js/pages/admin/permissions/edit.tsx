import { Form, Head } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import {
    index as permissionsIndex,
    update,
} from '@/actions/App/Http/Controllers/Admin/PermissionController';
import { index as rolesIndex } from '@/actions/App/Http/Controllers/Admin/RoleController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import type { Permission, Role } from '@/types/auth';

type Props = {
    role: Role;
    permissions: Permission[];
};

export default function PermissionsEdit({ role, permissions }: Props) {
    return (
        <>
            <Head title={`${role.name} Permissions`} />

            <div className="space-y-6">
                <Heading
                    title={`${role.name} Permissions`}
                    description="Assign or revoke permissions for this role"
                />

                <Form {...update.form(role)} className="max-w-lg space-y-6">
                    {({ errors, processing }) => (
                        <>
                            <div className="space-y-3">
                                {permissions.map((permission) => (
                                    <div
                                        key={permission.id}
                                        className="flex items-center gap-3"
                                    >
                                        <Checkbox
                                            id={`permission-${permission.id}`}
                                            name="permissions[]"
                                            value={permission.name}
                                            defaultChecked={permission.assigned}
                                        />
                                        <Label
                                            htmlFor={`permission-${permission.id}`}
                                            className="cursor-pointer font-mono text-sm"
                                        >
                                            {permission.name}
                                        </Label>
                                    </div>
                                ))}
                                {permissions.length === 0 && (
                                    <p className="text-sm text-muted-foreground">
                                        No permissions available.
                                    </p>
                                )}
                            </div>

                            {errors.permissions && (
                                <p className="text-sm text-destructive">
                                    {errors.permissions}
                                </p>
                            )}

                            <div className="flex items-center gap-4">
                                <Button type="submit" disabled={processing}>
                                    Save permissions
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={rolesIndex.url()}>Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

PermissionsEdit.layout = ({ role }: Props) => ({
    breadcrumbs: [
        {
            title: 'Roles',
            href: rolesIndex.url(),
        },
        {
            title: `${role.name} Permissions`,
            href: permissionsIndex.url(role),
        },
    ],
});
