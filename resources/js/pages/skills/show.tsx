import { Form, Head, Link, usePage } from '@inertiajs/react';
import {
    index,
    edit,
    destroy,
    toggle,
} from '@/actions/App/Http/Controllers/Skills/SkillController';
import {
    store as assignSkill,
    destroy as removeSkill,
} from '@/actions/App/Http/Controllers/Skills/SkillAssignmentController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Auth, Skill, SkillAssignment, SkillCategory, User } from '@/types';

type Props = {
    skill: Skill & {
        skill_category: SkillCategory;
        assignments: (SkillAssignment & {
            user: Pick<User, 'id' | 'name' | 'email'>;
        })[];
    };
    canManageSkills: boolean;
};

export default function SkillShow({ skill, canManageSkills }: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const canSelfAssign = !canManageSkills;
    const currentAssignment = skill.assignments.find(
        (assignment) => assignment.user_id === auth.user.id,
    );
    const isPrivilegedAssignment =
        currentAssignment?.source === 'privileged';

    return (
        <>
            <Head title={skill.name} />

            <div className="max-w-2xl space-y-8">
                <Heading
                    title={skill.name}
                    description="Skill details and assigned employees"
                />

                {/* Skill Details */}
                <div className="space-y-4 rounded-lg border border-border p-6">
                    <h2 className="text-base font-semibold">
                        Skill Information
                    </h2>

                    <dl className="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                        <div>
                            <dt className="text-muted-foreground">Name</dt>
                            <dd className="mt-1 font-medium">{skill.name}</dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Category</dt>
                            <dd className="mt-1">
                                <Badge variant="secondary">
                                    {skill.skill_category.name}
                                </Badge>
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Status</dt>
                            <dd className="mt-1">
                                {skill.is_active ? (
                                    <Badge className="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                        Active
                                    </Badge>
                                ) : (
                                    <Badge className="bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400">
                                        Inactive
                                    </Badge>
                                )}
                            </dd>
                        </div>
                        <div className="col-span-2">
                            <dt className="text-muted-foreground">
                                Description
                            </dt>
                            <dd className="mt-1">
                                {skill.description ?? (
                                    <span className="text-muted-foreground italic">
                                        No description provided
                                    </span>
                                )}
                            </dd>
                        </div>
                    </dl>
                </div>

                {/* Action Buttons */}
                {canManageSkills && (
                    <div className="flex items-center gap-3">
                        <Button asChild>
                            <Link href={edit.url(skill)}>Edit</Link>
                        </Button>
                        <Form
                            action={toggle.url(skill)}
                            method="patch"
                            className="inline"
                        >
                            {({ processing }) => (
                                <Button
                                    variant="outline"
                                    type="submit"
                                    disabled={processing}
                                >
                                    {skill.is_active
                                        ? 'Deactivate'
                                        : 'Activate'}
                                </Button>
                            )}
                        </Form>
                        <Form
                            action={destroy.url(skill)}
                            method="delete"
                            className="inline"
                            onBefore={() =>
                                window.confirm(
                                    'Are you sure you want to delete this skill?',
                                )
                            }
                        >
                            {({ processing }) => (
                                <Button
                                    variant="destructive"
                                    type="submit"
                                    disabled={processing}
                                >
                                    Delete
                                </Button>
                            )}
                        </Form>
                        <Button variant="outline" asChild>
                            <Link href={index.url()}>Back to Skills</Link>
                        </Button>
                    </div>
                )}

                {!canManageSkills && (
                    <div>
                        <Button variant="outline" asChild>
                            <Link href={index.url()}>Back to Skills</Link>
                        </Button>
                    </div>
                )}

                {canSelfAssign && (
                    <div className="flex items-center gap-3">
                        <span className="text-sm font-medium">
                            My Skill
                        </span>
                        {isPrivilegedAssignment && (
                            <Badge className="bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">
                                Assigned by HR
                            </Badge>
                        )}
                        {!isPrivilegedAssignment && currentAssignment && (
                            <Form
                                action={removeSkill.url({
                                    user: auth.user.id,
                                    skill: skill.id,
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
                        )}
                        {!isPrivilegedAssignment && !currentAssignment && (
                            <Form
                                action={assignSkill.url(auth.user.id)}
                                method="post"
                                className="inline"
                            >
                                {({ processing }) => (
                                    <>
                                        <input
                                            type="hidden"
                                            name="skill_id"
                                            value={skill.id}
                                        />
                                        <Button
                                            size="sm"
                                            type="submit"
                                            disabled={processing || !skill.is_active}
                                        >
                                            Add
                                        </Button>
                                    </>
                                )}
                            </Form>
                        )}
                    </div>
                )}

                {/* Assigned Employees */}
                <div className="space-y-4">
                    <h2 className="text-base font-semibold">
                        Assigned Employees ({skill.assignments.length})
                    </h2>

                    {skill.assignments.length > 0 ? (
                        <div className="overflow-hidden rounded-lg border border-border">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/50">
                                    <tr>
                                        <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                            Name
                                        </th>
                                        <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                            Email
                                        </th>
                                        <th className="px-4 py-3 text-left font-medium text-muted-foreground">
                                            Source
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-border">
                                    {skill.assignments.map((assignment) => (
                                        <tr
                                            key={assignment.id}
                                            className="bg-background transition-colors hover:bg-muted/30"
                                        >
                                            <td className="px-4 py-3 font-medium">
                                                {assignment.user.name}
                                            </td>
                                            <td className="px-4 py-3 text-muted-foreground">
                                                {assignment.user.email}
                                            </td>
                                            <td className="px-4 py-3">
                                                {assignment.source ===
                                                'self' ? (
                                                    <Badge className="bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                                        Self-assigned
                                                    </Badge>
                                                ) : (
                                                    <Badge className="bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">
                                                        Assigned by HR
                                                    </Badge>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            No employees assigned to this skill yet.
                        </p>
                    )}
                </div>
            </div>
        </>
    );
}

SkillShow.layout = {
    breadcrumbs: [
        {
            title: 'Skills',
            href: index.url(),
        },
        {
            title: 'Skill Details',
            href: '#',
        },
    ],
};
