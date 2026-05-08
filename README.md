# HRM System

HRM System is a role-aware HR platform for IT companies built on Laravel 13, Inertia v3, React 19, and Tailwind CSS v4. It centralizes PTO, projects, skills, and analytics with a strict role hierarchy and dedicated pages for every CRUD action.

## Highlights

- Role-aware dashboard with separate HR and Employee views.
- PTO leave management with multi-stage approvals and bypass support.
- Project management with skill-based assignment suggestions and bench tracking.
- Skills catalog with categories, activation toggles, and assignment sources.
- RBAC with audit trail and disabled public registration.

## Role Hierarchy

1. super_admin
2. admin
3. hr
4. employee

## Core Modules

- Dashboard analytics (workforce summary, PTO alerts, deadline alerts, project health, skill coverage).
- Leave requests and approvals (queues by role, self-approval prevention, approval history).
- Projects and assignments (required skills, task deadlines, completion status).
- Skills catalog and assignments (self vs privileged sources, categories, active status).
- Admin area (users, roles, permissions, audit logs).

## Tech Stack

- Backend: Laravel 13, PHP 8.3
- Frontend: Inertia v3, React 19, Tailwind CSS v4
- Auth: Fortify + Spatie Permission
- Tooling: Vite, ESLint, Prettier, Pint, Pest

## Requirements

- PHP 8.3+
- Node.js 20+
- Composer
- MySQL or SQLite

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
npm install
npm run build
```

## Development

```bash
composer run dev
```

## Linting and Formatting

```bash
npm run lint:check
npm run format:check
composer run lint:check
```

## Tests

```bash
php artisan test --compact
```

## Repository

https://github.com/zaber-dev/HRMS-for-IT-Companies
