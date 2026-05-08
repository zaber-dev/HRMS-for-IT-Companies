# HRM System - Developer Notes

## Overview

HRM System is a Laravel 13 application with Inertia v3 and React 19. It uses Tailwind CSS v4, Wayfinder route helpers, and Pest for testing. The system is organized into modular route files with dedicated pages for each CRUD action.

## Architecture Map

- Backend: Laravel controllers + policies + services; models with enums for status/state.
- Frontend: Inertia pages in resources/js/pages with shared layouts and components.
- Auth + RBAC: Fortify plus Spatie Permission with strict role hierarchy.

## Route Files

- routes/admin.php: user, role, permission, and audit log admin pages.
- routes/leave.php: leave requests, approvals, and admin-wide leave listings.
- routes/projects.php: project CRUD, assignments, and my-projects.
- routes/skills.php: skills catalog, categories, and assignments.
- routes/dashboard.php: role-based dashboard entry.

## Core Services

- app/Services/LeaveApprovalService.php: leave status transitions, approvals, queues, and bypass logic.
- app/Services/BenchStatusService.php: recalculates user bench status based on active assignments.
- app/Services/AuditLogger.php: immutable audit log writer for RBAC mutations.
- app/Services/Dashboard/*: workforce summary, PTO alerts, deadline alerts, project health, skill coverage, and employee dashboard data.

## Policies

- UserPolicy and RolePolicy enforce role hierarchy restrictions.
- LeaveRequestPolicy delegates approval rules to LeaveApprovalService.
- ProjectPolicy and ProjectAssignmentPolicy enforce project visibility and assignment rules.
- SkillPolicy and SkillAssignmentPolicy enforce catalog and assignment permissions.

## Data Model Highlights

- users: is_active, must_change_password, bench_status.
- leave_requests: status, submitted_at, cancelled_at.
- approval_actions: decision, comment, is_bypass.
- projects: status, deadline, features_list.
- project_assignments: task_description, task_deadline, completion_status.
- skills: name, category, is_active.
- skill_assignments: source (self or privileged).

## Dashboard Behavior

- HR/Admin/Super Admin: workforce summary, PTO alerts, deadline alerts, leave queue, project health, skill coverage.
- Employee: personal stats, tasks, projects, leave summary, skills.
- Expensive panels use deferred props with skeleton placeholders.

## UI Conventions

- Dedicated pages for create/edit/delete and approvals; no modals.
- Prefer policy checks to drive UI visibility and Inertia props.
- Use Wayfinder helpers in frontend code for routes.

## Testing Notes

- Use Pest feature tests for controllers and policies.
- Prefer factories for model setup.
- Minimum run: php artisan test --compact.
- Run Pint after PHP changes.

## Useful Commands

```bash
npm run lint
npm run lint:check
npm run format
npm run types:check
php artisan test --compact
vendor/bin/pint --dirty --format agent
```
