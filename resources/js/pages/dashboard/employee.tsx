import { Head, usePage } from '@inertiajs/react';
import { MyLeavePanel } from '@/components/dashboard/MyLeavePanel';
import { MyProjectsPanel } from '@/components/dashboard/MyProjectsPanel';
import { MySkillsPanel } from '@/components/dashboard/MySkillsPanel';
import { MyTasksPanel } from '@/components/dashboard/MyTasksPanel';
import { PersonalStatsPanel } from '@/components/dashboard/PersonalStatsPanel';
import { dashboard } from '@/routes';
import type { Auth } from '@/types';
import type { DashboardProps } from '@/types/dashboard';

type Props = Required<
    Pick<
        DashboardProps,
        'personalStats' | 'myTasks' | 'myProjects' | 'myLeave' | 'mySkills'
    >
>;

export default function EmployeeDashboard({
    personalStats,
    myTasks,
    myProjects,
    myLeave,
    mySkills,
}: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;

    return (
        <>
            <Head title="Dashboard" />

            <div className="grid gap-6 lg:grid-cols-3">
                {/* Left column */}
                <div className="space-y-6">
                    <PersonalStatsPanel
                        userId={auth.user.id}
                        name={personalStats.name}
                        role={personalStats.role}
                        bench_status={personalStats.bench_status}
                        skills_count={personalStats.skills_count}
                        created_at={personalStats.created_at}
                    />
                    <MyLeavePanel
                        approved_days={myLeave.approved_days}
                        pending_count={myLeave.pending_count}
                        most_recent={myLeave.most_recent}
                    />
                    <MySkillsPanel skillsByCategory={mySkills} />
                </div>

                {/* Right column */}
                <div className="space-y-6 lg:col-span-2">
                    <MyTasksPanel tasks={myTasks} />
                    <MyProjectsPanel projects={myProjects} />
                </div>
            </div>
        </>
    );
}

EmployeeDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
