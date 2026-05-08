import { Form, Head, Link } from '@inertiajs/react';
import {
    index,
    edit,
    update,
} from '@/actions/App/Http/Controllers/Admin/UserController';
import {
    store as assignSkill,
    destroy as removeSkill,
} from '@/actions/App/Http/Controllers/Skills/SkillAssignmentController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Role, User } from '@/types/auth';
import type { Skill, SkillAssignment, SkillCategory } from '@/types/skills';

type AssignedSkill = SkillAssignment & {
    skill: Skill & { skill_category: SkillCategory };
};
type AvailableSkill = Skill & { skill_category: SkillCategory };

type Props = {
    user: User & { roles: Role[] };
    roles: Role[];
    assignedSkills: AssignedSkill[];
    availableSkills: AvailableSkill[];
};

export default function UsersEdit({
    user,
    roles,
    assignedSkills = [],
    availableSkills = [],
}: Props) {
    const currentRole = user.roles[0]?.name ?? '';

    return (
        <>
            <Head title="Edit User" />

            <div className="space-y-8">
                <Heading
                    title="Edit User"
                    description="Update user account details and skills"
                />

                {/* Account details form */}
                <div className="space-y-6">
                    <h2 className="text-base font-semibold">Account Details</h2>
                    <Form {...update.form(user)} className="max-w-lg space-y-6">
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
                                        className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                                        required
                                    >
                                        <option value="">Select a role</option>
                                        {roles.map((role) => (
                                            <option
                                                key={role.id}
                                                value={role.name}
                                            >
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

                {/* Skills management */}
                <div className="space-y-4 border-t border-border pt-6">
                    <h2 className="text-base font-semibold">Skills</h2>
                    <p className="text-sm text-muted-foreground">
                        Manage skills assigned to this user.
                    </p>

                    {/* Assigned skills */}
                    {assignedSkills.length > 0 ? (
                        <div className="space-y-2">
                            {assignedSkills.map((assignment) => (
                                <div
                                    key={assignment.id}
                                    className="flex items-center justify-between rounded-lg border border-border px-4 py-3"
                                >
                                    <div className="flex items-center gap-3">
                                        <span className="text-sm font-medium">
                                            {assignment.skill.name}
                                        </span>
                                        <Badge variant="secondary">
                                            {
                                                assignment.skill.skill_category
                                                    .name
                                            }
                                        </Badge>
                                        <Badge
                                            className={
                                                assignment.source === 'self'
                                                    ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'
                                                    : 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400'
                                            }
                                        >
                                            {assignment.source === 'self'
                                                ? 'Self-assigned'
                                                : 'Assigned by HR'}
                                        </Badge>
                                    </div>
                                    <Form
                                        action={removeSkill.url({
                                            user: user.id,
                                            skill: assignment.skill_id,
                                        })}
                                        method="delete"
                                        className="inline"
                                    >
                                        {({ processing }) => (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                type="submit"
                                                disabled={processing}
                                            >
                                                Remove
                                            </Button>
                                        )}
                                    </Form>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            No skills assigned yet.
                        </p>
                    )}

                    {/* Add skill */}
                    {availableSkills.length > 0 && (
                        <div className="space-y-2">
                            <h3 className="text-sm font-semibold">Add Skill</h3>
                            <Form
                                action={assignSkill.url(user)}
                                method="post"
                                className="flex items-start gap-3"
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <div className="grid gap-1">
                                            <select
                                                name="skill_id"
                                                className="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                                            >
                                                <option value="">
                                                    Select a skill
                                                </option>
                                                {availableSkills.map(
                                                    (skill) => (
                                                        <option
                                                            key={skill.id}
                                                            value={skill.id}
                                                        >
                                                            {skill.name} (
                                                            {
                                                                skill
                                                                    .skill_category
                                                                    .name
                                                            }
                                                            )
                                                        </option>
                                                    ),
                                                )}
                                            </select>
                                            <InputError
                                                message={errors.skill_id}
                                            />
                                        </div>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            Assign
                                        </Button>
                                    </>
                                )}
                            </Form>
                        </div>
                    )}
                </div>
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
