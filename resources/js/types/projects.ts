import type { User } from '@/types/auth';
import type { Skill } from '@/types/skills';

export type ProjectStatus =
    | 'planning'
    | 'in_progress'
    | 'on_hold'
    | 'completed'
    | 'cancelled';
export type CompletionStatus = 'pending' | 'complete';
export type BenchStatus = 'on_bench' | 'assigned';

export type Project = {
    id: number;
    name: string;
    description: string | null;
    features_list: string | null;
    status: ProjectStatus;
    deadline: string;
    created_at: string;
    updated_at: string;
    skills?: Skill[];
    skills_count?: number;
    assignments?: ProjectAssignment[];
    assignments_count?: number;
};

export type ProjectAssignment = {
    id: number;
    project_id: number;
    user_id: number;
    task_description: string;
    task_deadline: string;
    completion_status: CompletionStatus;
    created_at: string;
    updated_at: string;
    project?: Project;
    user?: Pick<User, 'id' | 'name' | 'email'> & { bench_status: BenchStatus };
};
