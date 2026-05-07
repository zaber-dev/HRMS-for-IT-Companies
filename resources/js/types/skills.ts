import type { User } from '@/types/auth';

export type SkillCategory = {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
    skills_count?: number;
};

export type Skill = {
    id: number;
    skill_category_id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    skill_category?: SkillCategory;
    assignments_count?: number;
};

export type AssignmentSource = 'self' | 'privileged';

export type SkillAssignment = {
    id: number;
    user_id: number;
    skill_id: number;
    source: AssignmentSource;
    created_at: string;
    skill?: Skill;
    user?: Pick<User, 'id' | 'name' | 'email'>;
};
