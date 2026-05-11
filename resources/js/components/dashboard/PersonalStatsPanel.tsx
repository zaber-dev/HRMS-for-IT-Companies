import { Link } from '@inertiajs/react';
import { edit as userEdit } from '@/actions/App/Http/Controllers/Admin/UserController';
import type { DashboardProps } from '@/types/dashboard';

type Props = NonNullable<DashboardProps['personalStats']> & { userId: number };

function formatRole(role: string | null): string {
    if (!role) {
        return 'Unknown';
    }

    return role.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}

export function PersonalStatsPanel({
    userId,
    name,
    role,
    bench_status,
    skills_count,
    created_at,
}: Props) {
    const isOnBench = bench_status === 'on_bench';

    return (
        <div className="rounded-lg border border-border bg-card p-6">
            <h2 className="mb-4 text-lg font-semibold">My Profile</h2>
            <div className="space-y-3">
                <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">Name</span>
                    <span className="font-medium">{name}</span>
                </div>
                <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">Role</span>
                    <span className="font-medium">{formatRole(role)}</span>
                </div>
                <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">
                        Status
                    </span>
                    <span
                        className={`rounded-full px-2 py-0.5 text-xs font-medium ${
                            isOnBench
                                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
                        }`}
                    >
                        {isOnBench ? 'On Bench' : 'Assigned'}
                    </span>
                </div>
                <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">
                        Skills
                    </span>
                    <span
                        className="font-medium text-primary"
                    >
                        {skills_count} skill{skills_count !== 1 ? 's' : ''}
                    </span>
                </div>
                <div className="flex items-center justify-between">
                    <span className="text-sm text-muted-foreground">
                        Member since
                    </span>
                    <span className="text-sm text-muted-foreground">
                        {new Date(created_at).toLocaleDateString()}
                    </span>
                </div>
            </div>
        </div>
    );
}
