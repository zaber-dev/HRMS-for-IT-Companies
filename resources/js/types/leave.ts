import type { User } from '@/types/auth';

export type LeaveStatus =
    | 'pending_hr'
    | 'pending_admin'
    | 'pending_super_admin'
    | 'approved'
    | 'rejected'
    | 'cancelled';

export type ApprovalDecision = 'approved' | 'rejected';

export type LeaveRequest = {
    id: number;
    user_id: number;
    start_date: string; // ISO date string
    end_date: string;
    reason: string | null;
    status: LeaveStatus;
    submitted_at: string;
    cancelled_at: string | null;
    created_at: string;
    updated_at: string;
    user?: Pick<User, 'id' | 'name' | 'email'>;
    approval_actions?: ApprovalAction[];
};

export type ApprovalAction = {
    id: number;
    leave_request_id: number;
    user_id: number;
    decision: ApprovalDecision;
    comment: string | null;
    is_bypass: boolean;
    created_at: string;
    user?: Pick<User, 'id' | 'name' | 'email'>;
};

export type PaginatedData<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
};
