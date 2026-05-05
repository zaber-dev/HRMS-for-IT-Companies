export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    role?: string;
    is_active?: boolean;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type Role = {
    id: number;
    name: string;
    guard_name: string;
    permissions_count?: number;
    users_count?: number;
    created_at: string;
    updated_at: string;
};

export type Permission = {
    id: number;
    name: string;
    guard_name: string;
    assigned: boolean; // whether this permission is currently on the role being edited
};

export type AuditLog = {
    id: number;
    user_id: number | null;
    action: string;
    auditable_type: string;
    auditable_id: number;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    created_at: string;
    actor?: Pick<User, 'id' | 'name' | 'email'>;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
