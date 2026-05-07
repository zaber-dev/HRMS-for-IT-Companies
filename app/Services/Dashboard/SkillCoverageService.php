<?php

namespace App\Services\Dashboard;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\SkillAssignment;
use App\Models\User;

class SkillCoverageService
{
    /**
     * Get projects with status `planning` or `in_progress` that have at least one
     * required skill with no active employee possessing it.
     *
     * Only active employees (`is_active = true`, `employee` role) are considered
     * when evaluating skill coverage.
     *
     * @return array<int, array{project_id: int, name: string, status: string, uncovered_skills: string[]}>
     */
    public function get(): array
    {
        $activeEmployeeIds = User::role('employee')
            ->where('is_active', true)
            ->pluck('id');

        $coveredSkillIds = SkillAssignment::whereIn('user_id', $activeEmployeeIds)
            ->pluck('skill_id')
            ->unique();

        $projects = Project::with('skills')
            ->whereIn('status', [ProjectStatus::Planning, ProjectStatus::InProgress])
            ->get();

        $result = [];

        foreach ($projects as $project) {
            if ($project->skills->isEmpty()) {
                continue;
            }

            $uncoveredSkills = $project->skills
                ->filter(fn ($skill) => ! $coveredSkillIds->contains($skill->id))
                ->pluck('name')
                ->values()
                ->all();

            if (empty($uncoveredSkills)) {
                continue;
            }

            $result[] = [
                'project_id' => $project->id,
                'name' => $project->name,
                'status' => $project->status->value,
                'uncovered_skills' => $uncoveredSkills,
            ];
        }

        return $result;
    }
}
