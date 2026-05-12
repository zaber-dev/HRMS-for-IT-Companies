import { Head } from '@inertiajs/react';
import { Deferred } from '@inertiajs/react';
import {
    DeadlineAlertPanel,
    DeadlineAlertPanelSkeleton,
} from '@/components/dashboard/DeadlineAlertPanel';
import { LeaveQueuePanel } from '@/components/dashboard/LeaveQueuePanel';
import { ProjectHealthPanel } from '@/components/dashboard/ProjectHealthPanel';
import {
    PtoAlertPanel,
    PtoAlertPanelSkeleton,
} from '@/components/dashboard/PtoAlertPanel';
import {
    SkillCoveragePanel,
    SkillCoveragePanelSkeleton,
} from '@/components/dashboard/SkillCoveragePanel';
import { WorkforceSummaryPanel } from '@/components/dashboard/WorkforceSummaryPanel';
import { dashboard } from '@/routes';
import type { DashboardProps } from '@/types/dashboard';

type Props = Required<
    Pick<
        DashboardProps,
        | 'workforceSummary'
        | 'projectHealth'
        | 'leaveQueueCount'
        | 'ptoAlerts'
        | 'deadlineAlerts'
        | 'skillCoverageProjects'
    >
>;

export default function HrDashboard({
    workforceSummary,
    projectHealth,
    leaveQueueCount,
    ptoAlerts,
    deadlineAlerts,
    skillCoverageProjects,
}: Props) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="space-y-6">
                {/* Eager panels */}
                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <WorkforceSummaryPanel
                            totalEmployees={workforceSummary.total_employees}
                            onBench={workforceSummary.on_bench}
                            assigned={workforceSummary.assigned}
                            deactivated={workforceSummary.deactivated}
                        />
                    </div>
                    <LeaveQueuePanel count={leaveQueueCount} />
                </div>

                <ProjectHealthPanel
                    byStatus={projectHealth.by_status}
                    overdue={projectHealth.overdue}
                />

                {/* Deferred panels */}
                <Deferred data="ptoAlerts" fallback={<PtoAlertPanelSkeleton />}>
                    <PtoAlertPanel
                        alerts={Array.isArray(ptoAlerts) ? ptoAlerts : []}
                        threshold={14}
                    />
                </Deferred>

                <Deferred
                    data="deadlineAlerts"
                    fallback={<DeadlineAlertPanelSkeleton />}
                >
                    <DeadlineAlertPanel
                        alerts={
                            Array.isArray(deadlineAlerts) ? deadlineAlerts : []
                        }
                    />
                </Deferred>

                <Deferred
                    data="skillCoverageProjects"
                    fallback={<SkillCoveragePanelSkeleton />}
                >
                    <SkillCoveragePanel
                        projects={
                            Array.isArray(skillCoverageProjects)
                                ? skillCoverageProjects
                                : []
                        }
                    />
                </Deferred>
            </div>
        </>
    );
}

HrDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
