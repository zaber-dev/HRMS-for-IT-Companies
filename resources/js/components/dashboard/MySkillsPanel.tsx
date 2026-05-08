import { Link } from '@inertiajs/react';
import { show as skillShow } from '@/actions/App/Http/Controllers/Skills/SkillController';
import type { DashboardProps } from '@/types/dashboard';

type Props = {
    skillsByCategory: NonNullable<DashboardProps['mySkills']>;
};

export function MySkillsPanel({ skillsByCategory }: Props) {
    const categories = Object.keys(skillsByCategory);

    return (
        <div className="rounded-lg border border-border bg-card p-6">
            <h2 className="mb-4 text-lg font-semibold">My Skills</h2>

            {categories.length === 0 ? (
                <p className="py-4 text-center text-sm text-muted-foreground">
                    No skills have been assigned to your profile yet.
                </p>
            ) : (
                <div className="space-y-4">
                    {categories.map((category) => (
                        <div key={category}>
                            <h3 className="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                {category}
                            </h3>
                            <div className="flex flex-wrap gap-2">
                                {skillsByCategory[category].map((skill) => (
                                    <Link
                                        key={skill.id}
                                        href={skillShow.url(skill.id)}
                                        className="flex items-center gap-1.5 rounded-full border border-border bg-background px-3 py-1 text-sm transition-colors hover:bg-muted/30"
                                    >
                                        <span>{skill.name}</span>
                                        <span
                                            className={`rounded-full px-1.5 py-0.5 text-xs font-medium ${
                                                skill.source === 'self'
                                                    ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                                                    : 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'
                                            }`}
                                        >
                                            {skill.source === 'self'
                                                ? 'Self'
                                                : 'Assigned'}
                                        </span>
                                    </Link>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
