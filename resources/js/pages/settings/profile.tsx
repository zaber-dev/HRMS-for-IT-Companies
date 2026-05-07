import { Form, Head, Link, usePage } from '@inertiajs/react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { store as assignSkill, destroy as removeSkill } from '@/actions/App/Http/Controllers/Skills/SkillAssignmentController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { type Auth, type Skill, type SkillAssignment, type SkillCategory } from '@/types';

type AssignedSkill = SkillAssignment & { skill: Skill & { skill_category: SkillCategory } };
type AvailableSkill = Skill & { skill_category: SkillCategory };

export default function Profile({
    mustVerifyEmail,
    status,
    assignedSkills,
    availableSkills,
    canManageSkills,
}: {
    mustVerifyEmail: boolean;
    status?: string;
    assignedSkills: AssignedSkill[];
    availableSkills: AvailableSkill[];
    canManageSkills: boolean;
}) {
    const { auth } = usePage<{ auth: Auth }>().props;

    const selfAssigned = assignedSkills.filter((a) => a.source === 'self');
    const privilegedAssigned = assignedSkills.filter((a) => a.source === 'privileged');

    return (
        <>
            <Head title="Profile settings" />

            <h1 className="sr-only">Profile settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Profile information"
                    description="Update your name and email address"
                />

                <Form
                    {...ProfileController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>

                                <Input
                                    id="name"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.name}
                                    name="name"
                                    required
                                    autoComplete="name"
                                    placeholder="Full name"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.name}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>

                                <Input
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.email}
                                    name="email"
                                    required
                                    autoComplete="username"
                                    placeholder="Email address"
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.email}
                                />
                            </div>

                            {mustVerifyEmail &&
                                auth.user.email_verified_at === null && (
                                    <div>
                                        <p className="-mt-4 text-sm text-muted-foreground">
                                            Your email address is unverified.{' '}
                                            <Link
                                                href={send()}
                                                as="button"
                                                className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                            >
                                                Click here to resend the
                                                verification email.
                                            </Link>
                                        </p>

                                        {status ===
                                            'verification-link-sent' && (
                                            <div className="mt-2 text-sm font-medium text-green-600">
                                                A new verification link has been
                                                sent to your email address.
                                            </div>
                                        )}
                                    </div>
                                )}

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-profile-button"
                                >
                                    Save
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>

            {/* Skills Section */}
            <div className="space-y-6 mt-8">
                <Heading
                    variant="small"
                    title="Skills"
                    description="Manage skills assigned to this profile"
                />

                {/* Self-assigned skills */}
                <div className="space-y-3">
                    <h3 className="text-sm font-semibold">Self-assigned</h3>
                    {selfAssigned.length > 0 ? (
                        <div className="space-y-2">
                            {selfAssigned.map((assignment) => (
                                <div key={assignment.id} className="flex items-center justify-between rounded-lg border border-border px-4 py-3">
                                    <div className="flex items-center gap-3">
                                        <span className="font-medium text-sm">{assignment.skill.name}</span>
                                        <Badge variant="secondary">{assignment.skill.skill_category.name}</Badge>
                                        <Badge className="bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                            Self-assigned
                                        </Badge>
                                    </div>
                                    <Form
                                        action={removeSkill.url({ user: auth.user.id, skill: assignment.skill_id })}
                                        method="delete"
                                        className="inline"
                                    >
                                        {({ processing }) => (
                                            <Button variant="outline" size="sm" type="submit" disabled={processing}>
                                                Remove
                                            </Button>
                                        )}
                                    </Form>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground">No self-assigned skills.</p>
                    )}
                </div>

                {/* Privileged-assigned skills */}
                <div className="space-y-3">
                    <h3 className="text-sm font-semibold">Assigned by HR</h3>
                    {privilegedAssigned.length > 0 ? (
                        <div className="space-y-2">
                            {privilegedAssigned.map((assignment) => (
                                <div key={assignment.id} className="flex items-center justify-between rounded-lg border border-border px-4 py-3">
                                    <div className="flex items-center gap-3">
                                        <span className="font-medium text-sm">{assignment.skill.name}</span>
                                        <Badge variant="secondary">{assignment.skill.skill_category.name}</Badge>
                                        <Badge className="bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">
                                            Assigned by HR
                                        </Badge>
                                    </div>
                                    {canManageSkills && (
                                        <Form
                                            action={removeSkill.url({ user: auth.user.id, skill: assignment.skill_id })}
                                            method="delete"
                                            className="inline"
                                        >
                                            {({ processing }) => (
                                                <Button variant="outline" size="sm" type="submit" disabled={processing}>
                                                    Remove
                                                </Button>
                                            )}
                                        </Form>
                                    )}
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground">No skills assigned by HR.</p>
                    )}
                </div>

                {/* Add Skill */}
                {availableSkills.length > 0 && (
                    <div className="space-y-3">
                        <h3 className="text-sm font-semibold">Add Skill</h3>
                        <Form
                            action={assignSkill.url(auth.user)}
                            method="post"
                            className="flex items-start gap-3"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-1">
                                        <select
                                            name="skill_id"
                                            className="flex h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                        >
                                            <option value="">Select a skill</option>
                                            {availableSkills.map((skill) => (
                                                <option key={skill.id} value={skill.id}>
                                                    {skill.name} ({skill.skill_category.name})
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.skill_id} />
                                    </div>
                                    <Button type="submit" disabled={processing}>
                                        Add
                                    </Button>
                                </>
                            )}
                        </Form>
                    </div>
                )}
            </div>

            <DeleteUser />
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile settings',
            href: edit(),
        },
    ],
};
