export type DashboardProps = {
    role: 'hr' | 'employee';
    // HR props
    workforceSummary?: {
        total_employees: number;
        on_bench: number;
        assigned: number;
        deactivated: number;
    };
    ptoAlerts?: Array<{
        user_id: number;
        name: string;
        total_leave_days: number;
        pending_requests_count: number;
    }>;
    deadlineAlerts?: Array<{
        user_id: number;
        name: string;
        project_id: number;
        project_name: string;
        task_description: string;
        task_deadline: string;
        days_overdue: number;
    }>;
    projectHealth?: {
        by_status: Record<string, number>;
        overdue: number;
    };
    leaveQueueCount?: number;
    skillCoverageProjects?: Array<{
        project_id: number;
        name: string;
        status: string;
        uncovered_skills: string[];
    }>;
    // Employee props
    personalStats?: {
        name: string;
        role: string | null;
        bench_status: 'on_bench' | 'assigned';
        skills_count: number;
        created_at: string;
    };
    myTasks?: Array<{
        assignment_id: number;
        project_id: number;
        project_name: string;
        task_description: string;
        task_deadline: string;
        days_remaining: number;
    }>;
    myProjects?: Array<{
        project_id: number;
        name: string;
        status: string;
        deadline: string | null;
        is_near_deadline: boolean;
    }>;
    myLeave?: {
        approved_days: number;
        pending_count: number;
        most_recent: {
            id: number;
            status: string;
            start_date: string;
            end_date: string;
        } | null;
    };
    mySkills?: Record<
        string,
        Array<{ id: number; name: string; source: 'self' | 'privileged' }>
    >;
};

export type DashboardDeferredProps = {
    ptoAlerts?: DashboardProps['ptoAlerts'];
    deadlineAlerts?: DashboardProps['deadlineAlerts'];
    skillCoverageProjects?: DashboardProps['skillCoverageProjects'];
};
