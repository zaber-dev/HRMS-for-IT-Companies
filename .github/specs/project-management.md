# Requirements Document

## Introduction

This feature introduces a Project Management system to the platform. Projects represent work initiatives that require specific skills to complete and are assigned to one or more employees. Privileged Users (HR, Admin, Super Admin) can create, edit, delete, and manage projects through dedicated CRUD pages. Each project carries a name, description, features list, status, and deadline. Projects have a many-to-many relationship with Skills (from the Employee Skills Management system), expressing which competencies are needed to deliver the project.

From a project's dedicated show page, Privileged Users can assign the project to employees. The system suggests employees who both possess the required skills and are currently on bench. Each assignment carries a per-employee task description and task deadline. An employee's bench status is automatically set to `assigned` when they are added to a project, and automatically reverted to `on_bench` when they mark their task as complete. Employees can view the projects they are assigned to in read-only mode.

The feature integrates with the existing RBAC system (super_admin -> admin -> hr -> employee hierarchy) and the Employee Skills Management system. All CRUD operations use dedicated pages - no modal dialogs or inline forms.

## Glossary

- **Project**: A named work initiative with a `name`, `description`, `features_list`, `status`, `deadline`, and a set of required Skills. Managed by the Project_Manager.
- **Project_Manager**: The component responsible for creating, updating, deleting, listing, and displaying Projects, as well as managing Project_Assignments from the project show page.
- **Project_Status**: An enumerated value representing the current state of a Project. Valid values: `planning`, `in_progress`, `on_hold`, `completed`, `cancelled`.
- **Project_Assignment**: The pivot record linking a Project to an Employee. Each Project_Assignment stores a `task_description`, a `task_deadline`, and a `completion_status` (pending / complete).
- **Required_Skills**: The set of Skills associated with a Project via a many-to-many relationship, representing the competencies needed to deliver the project.
- **Bench_Status**: A per-user attribute on the User model indicating whether the employee is currently available (`on_bench`) or engaged on a project (`assigned`).
- **Assignment_Suggestion**: A candidate Employee surfaced by the system when assigning a project - one who possesses at least one of the Project's Required_Skills and whose Bench_Status is `on_bench`.
- **Employee**: A user with the `employee` role as defined by the RBAC system.
- **HR**: A user with the `hr` role as defined by the RBAC system.
- **Admin**: A user with the `admin` role as defined by the RBAC system.
- **Super_Admin**: A user with the `super_admin` role as defined by the RBAC system.
- **Privileged_User**: Any user with the `hr`, `admin`, or `super_admin` role.
- **Skill**: A named competency as defined by the Employee Skills Management system.
- **Access_Gate**: The middleware/policy layer from the RBAC system that enforces permission checks on every protected route or action.

---

## Requirements

### Requirement 1: Project CRUD - Create and Edit

**User Story:** As an HR user, I want to create and edit projects, so that the organisation's active and planned work initiatives are accurately recorded in the system.

#### Acceptance Criteria

1. WHILE authenticated as a Privileged_User, THE Project_Manager SHALL allow creating a new Project with a unique `name`, a `description`, a `features_list`, a `status` (defaulting to `planning`), a `deadline`, and zero or more Required_Skills.
2. WHILE authenticated as a Privileged_User, THE Project_Manager SHALL allow updating the `name`, `description`, `features_list`, `status`, `deadline`, and Required_Skills of any existing Project.
3. IF a Project is created or updated with a `name` that already exists, THEN THE Project_Manager SHALL reject the request and return a validation error identifying the duplicate name.
4. THE Project_Manager SHALL enforce that `name` is between 2 and 150 characters in length.
5. THE Project_Manager SHALL enforce that `status` is one of the defined Project_Status values: `planning`, `in_progress`, `on_hold`, `completed`, `cancelled`.
6. THE Project_Manager SHALL enforce that `deadline` is a valid future or present date when creating a Project.
7. IF an Employee attempts to create or edit a Project, THEN THE Access_Gate SHALL return an HTTP 403 response.

---

### Requirement 2: Project CRUD - Delete

**User Story:** As an HR user, I want to remove projects that are no longer relevant, so that the project list stays accurate and uncluttered.

#### Acceptance Criteria

1. WHILE authenticated as a Privileged_User, THE Project_Manager SHALL allow deleting a Project.
2. WHEN a Project is deleted, THE Project_Manager SHALL remove all Project_Assignments associated with that Project.
3. WHEN a Project is deleted and its removal causes an Employee to have no remaining active Project_Assignments, THE Project_Manager SHALL automatically set that Employee's Bench_Status to `on_bench`.
4. IF an Employee attempts to delete a Project, THEN THE Access_Gate SHALL return an HTTP 403 response.

---

### Requirement 3: Project CRUD - Dedicated Pages

**User Story:** As an HR user, I want each project management action to have its own dedicated page, so that the interface is clear and consistent with the rest of the application.

#### Acceptance Criteria

1. THE Project_Manager SHALL provide a dedicated index page at `/projects` that displays a paginated list of all Projects, showing each Project's `name`, `status`, `deadline`, and Required_Skills count.
2. THE Project_Manager SHALL provide a dedicated create page at `/projects/create`, accessible via a link from the index page.
3. THE Project_Manager SHALL provide a dedicated edit page at `/projects/{project}/edit`, accessible via a link from the index and show pages.
4. THE Project_Manager SHALL provide a dedicated show page at `/projects/{project}` that displays all Project fields, the Required_Skills list, and the list of assigned Employees with their task details.
5. THE Project_Manager SHALL provide a dedicated delete confirmation page at `/projects/{project}/delete`, accessible via a link from the index and show pages.
6. THE Project_Manager SHALL NOT use modal dialogs or inline forms for any Project create, edit, or delete operation.
7. WHEN a Project creation or edit is completed successfully, THE Project_Manager SHALL redirect the acting user to the Project show page for that Project.
8. WHEN a Project is deleted successfully, THE Project_Manager SHALL redirect the acting user to the Project index page.
9. IF an unauthenticated user attempts to access any Project page, THEN THE Access_Gate SHALL redirect the request to the login page.

---

### Requirement 4: Project-Skill Relationship

**User Story:** As an HR user, I want to associate required skills with a project, so that the system knows which competencies are needed and can suggest suitable employees.

#### Acceptance Criteria

1. THE Project_Manager SHALL allow associating zero or more Skills from the Skill_Catalogue with a Project as Required_Skills via a many-to-many relationship.
2. WHILE authenticated as a Privileged_User, THE Project_Manager SHALL allow adding or removing Required_Skills on a Project from the Project edit page.
3. THE Project_Manager SHALL display the full list of Required_Skills for a Project on the Project show page.
4. WHEN a Skill is deleted from the Skill_Catalogue, THE Project_Manager SHALL remove that Skill from the Required_Skills of all Projects.
5. THE Project_Manager SHALL allow a Project to exist with zero Required_Skills.

---

### Requirement 5: Employee Assignment to Projects

**User Story:** As an HR user, I want to assign employees to a project from the project show page, so that I can staff projects without leaving the project context.

#### Acceptance Criteria

1. WHILE authenticated as a Privileged_User, THE Project_Manager SHALL provide a dedicated assignment page at `/projects/{project}/assignments/create`, accessible via a link from the Project show page, for assigning an Employee to the Project.
2. THE Project_Manager SHALL display Assignment_Suggestions on the assignment page - Employees who possess at least one of the Project's Required_Skills and whose Bench_Status is `on_bench` - listed before other available employees.
3. WHILE authenticated as a Privileged_User, THE Project_Manager SHALL require a `task_description` and a `task_deadline` to be provided when assigning an Employee to a Project.
4. THE Project_Manager SHALL enforce that `task_deadline` is a valid date not later than the Project's own `deadline`.
5. IF a Privileged_User attempts to assign an Employee who is already assigned to the Project, THEN THE Project_Manager SHALL reject the request and return a validation error indicating the Employee is already assigned.
6. WHEN an Employee is successfully assigned to a Project, THE Project_Manager SHALL automatically set that Employee's Bench_Status to `assigned`.
7. WHEN an Employee is successfully assigned to a Project, THE Project_Manager SHALL redirect the acting user to the Project show page.
8. IF an Employee attempts to assign any user to a Project, THEN THE Access_Gate SHALL return an HTTP 403 response.

---

### Requirement 6: Bench Status Management

**User Story:** As an HR user, I want the system to automatically track whether an employee is available or engaged, so that I always have an accurate picture of workforce availability without manual updates.

#### Acceptance Criteria

1. THE Project_Manager SHALL maintain a `bench_status` attribute on each User with two possible values: `on_bench` and `assigned`.
2. WHEN an Employee is assigned to a Project, THE Project_Manager SHALL set that Employee's Bench_Status to `assigned`.
3. WHEN an Employee marks their task as complete on a Project, THE Project_Manager SHALL evaluate whether the Employee has any remaining active (non-complete) Project_Assignments.
4. WHEN an Employee marks their task as complete and has no remaining active Project_Assignments, THE Project_Manager SHALL automatically set that Employee's Bench_Status to `on_bench`.
5. WHEN an Employee marks their task as complete but still has one or more active Project_Assignments, THE Project_Manager SHALL retain that Employee's Bench_Status as `assigned`.
6. THE Project_Manager SHALL default the Bench_Status of a newly created User to `on_bench`.
7. WHILE authenticated as a Privileged_User, THE Project_Manager SHALL display each Employee's current Bench_Status on the assignment page and on the Employee list within the Project show page.

---

### Requirement 7: Task Completion by Employee

**User Story:** As an employee, I want to mark my task on a project as complete, so that the system reflects my availability and my contribution is recorded.

#### Acceptance Criteria

1. WHILE authenticated as Employee, THE Project_Manager SHALL allow the Employee to mark their own Project_Assignment task as complete from their assigned projects page.
2. WHEN an Employee marks a task as complete, THE Project_Manager SHALL set the `completion_status` of that Project_Assignment to `complete`.
3. IF an Employee attempts to mark a task as complete that is already marked complete, THEN THE Project_Manager SHALL reject the request and return a validation error indicating the task is already complete.
4. IF an Employee attempts to mark another Employee's task as complete, THEN THE Access_Gate SHALL return an HTTP 403 response.
5. WHEN a task is marked as complete, THE Project_Manager SHALL re-evaluate and update the Employee's Bench_Status according to Requirement 6.

---

### Requirement 8: Employee View of Assigned Projects

**User Story:** As an employee, I want to see the projects I am assigned to, so that I can review my task details and deadlines.

#### Acceptance Criteria

1. WHILE authenticated as Employee, THE Project_Manager SHALL provide a dedicated page at `/my-projects` that lists all Projects the Employee is currently assigned to, showing each Project's `name`, `status`, `deadline`, the Employee's `task_description`, `task_deadline`, and `completion_status`.
2. WHILE authenticated as Employee, THE Project_Manager SHALL allow the Employee to view the full details of any Project they are assigned to on the Project show page, in read-only mode.
3. WHILE authenticated as Employee, THE Project_Manager SHALL NOT display create, edit, delete, or assignment controls on any Project page.
4. IF an Employee attempts to access the show page of a Project they are not assigned to, THEN THE Access_Gate SHALL return an HTTP 403 response.
5. THE Project_Manager SHALL display the Employee's current Bench_Status on their assigned projects page.

---

### Requirement 9: Assignment Suggestions Based on Skills and Availability

**User Story:** As an HR user, I want the system to suggest suitable employees when assigning a project, so that I can quickly identify the best candidates without manually cross-referencing skills and availability.

#### Acceptance Criteria

1. THE Project_Manager SHALL compute Assignment_Suggestions as the set of Employees who satisfy both conditions: (a) the Employee has at least one Skill that matches a Required_Skill of the Project, and (b) the Employee's Bench_Status is `on_bench`.
2. THE Project_Manager SHALL display Assignment_Suggestions in a visually distinct section at the top of the assignment page, above the full employee list.
3. THE Project_Manager SHALL display each suggested Employee's name, their matching Skills (those overlapping with the Project's Required_Skills), and their Bench_Status on the assignment page.
4. IF the Project has no Required_Skills, THEN THE Project_Manager SHALL display all Employees with Bench_Status `on_bench` as candidates, without a separate suggestions section.
5. IF no Employees satisfy the Assignment_Suggestion criteria, THEN THE Project_Manager SHALL display an informational message indicating no matching available employees were found, and SHALL still display the full employee list so the Privileged_User can assign any employee.

---

### Requirement 10: Project Assignment Removal

**User Story:** As an HR user, I want to remove an employee from a project, so that I can correct assignment mistakes or reflect staffing changes.

#### Acceptance Criteria

1. WHILE authenticated as a Privileged_User, THE Project_Manager SHALL allow removing an Employee from a Project from the Project show page.
2. WHEN an Employee is removed from a Project, THE Project_Manager SHALL delete the corresponding Project_Assignment record.
3. WHEN an Employee is removed from a Project and has no remaining active Project_Assignments, THE Project_Manager SHALL automatically set that Employee's Bench_Status to `on_bench`.
4. WHEN an Employee is removed from a Project but still has one or more active Project_Assignments, THE Project_Manager SHALL retain that Employee's Bench_Status as `assigned`.
5. IF an Employee attempts to remove any Project_Assignment, THEN THE Access_Gate SHALL return an HTTP 403 response.

---

### Requirement 11: Task Detail Management by Privileged Users

**User Story:** As an HR user, I want to update the task description and task deadline for an employee's assignment, so that I can keep task details accurate as the project evolves.

#### Acceptance Criteria

1. WHILE authenticated as a Privileged_User, THE Project_Manager SHALL provide a dedicated edit page at `/projects/{project}/assignments/{assignment}/edit` for updating the `task_description` and `task_deadline` of a Project_Assignment.
2. THE Project_Manager SHALL enforce that the updated `task_deadline` is a valid date not later than the Project's own `deadline`.
3. WHEN a Project_Assignment is updated successfully, THE Project_Manager SHALL redirect the acting user to the Project show page.
4. IF an Employee attempts to edit a Project_Assignment's task details, THEN THE Access_Gate SHALL return an HTTP 403 response.

---

### Requirement 12: Access Control for Project Management

**User Story:** As a platform administrator, I want project management permissions to align with the existing role hierarchy, so that access is consistently enforced across the application.

#### Acceptance Criteria

1. THE Access_Gate SHALL restrict Project creation, editing, and deletion to users with the `hr`, `admin`, or `super_admin` role.
2. THE Access_Gate SHALL restrict Employee assignment and removal on Projects to users with the `hr`, `admin`, or `super_admin` role.
3. THE Access_Gate SHALL allow users with the `employee` role to view only the Projects they are assigned to, in read-only mode.
4. IF an Employee attempts to access the Project index page (`/projects`), THEN THE Access_Gate SHALL return an HTTP 403 response.
5. IF a user without any of the four defined roles attempts to access any Project route, THEN THE Access_Gate SHALL return an HTTP 403 response.
6. WHILE authenticated as a Privileged_User, THE Project_Manager SHALL display the full Project index with all projects, regardless of assignment.
