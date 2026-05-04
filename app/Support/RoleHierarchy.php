<?php

namespace App\Support;

class RoleHierarchy
{
    /**
     * The four built-in protected role names.
     *
     * @var array<int, string>
     */
    public const BUILT_IN_ROLES = ['super_admin', 'admin', 'hr', 'employee'];

    /**
     * Hierarchy levels for each built-in role.
     * Higher number = higher privilege.
     *
     * @var array<string, int>
     */
    private const LEVELS = [
        'super_admin' => 4,
        'admin' => 3,
        'hr' => 2,
        'employee' => 1,
    ];

    /**
     * Return the hierarchy level for the given role name.
     * Returns 0 for unknown (custom) roles.
     */
    public static function level(string $roleName): int
    {
        return self::LEVELS[$roleName] ?? 0;
    }

    /**
     * Return true if the actor's role is strictly above the target's role
     * in the hierarchy.
     */
    public static function isAbove(string $actorRole, string $targetRole): bool
    {
        return self::level($actorRole) > self::level($targetRole);
    }
}
