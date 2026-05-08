# Requirements Document

## Introduction

This feature introduces an Employee Skills Management system to the platform. Skills represent competencies, qualifications, or capabilities that can be associated with employee user accounts. Each Skill belongs to a Category and carries an active/inactive status. HR personnel and higher-privileged roles (Admin, Super Admin) can define, edit, deactivate, and remove skills from the system catalogue. Employees can self-assign skills from the catalogue to their own profile. HR and above can assign or remove skills on behalf of any employee.

The feature integrates with the existing RBAC system (super_admin -> admin -> hr -> employee hierarchy) and follows the established pattern of dedicated pages for all CRUD operations - no modal dialogs or inline forms. Skill catalogue management (create, edit, delete, list) lives on dedicated `/skills` pages. Skill assignment to employees is managed directly from the employee's profile page.

## Glossary

- **Skill**: A named competency, qualification, or capability that can be associated with one or more Employee users. Each Skill has a `name`, an optional `description`, a `category`, and an `is_active` flag.
- **Skill_Category**: A grouping label used to organise related Skills within the Skill_Catalogue (e.g. "Engineering", "Leadership", "Communication").
- **Skill_Catalogue**: The system-wide collection of all defined Skills available for assignment.
- **Skill_Manager**: The component responsible for creating, updating, deactivating, deleting, and listing Skills in the Skill_Catalogue, as well as managing Skill_Assignments from the employee profile page.
- **Skill_Assignment**: The association between a User and a Skill, representing that the user possesses that skill. Each Skill_Assignment records whether it was created by the Employee themselves (`self_assigned`) or by a Privileged_User (`privileged_assigned`).
- **Assignment_Source**: The origin of a Skill_Assignment - either `self` (added by the Employee) or `privileged` (added by a Privileged_User).
- **Employee**: A user with the `employee` role as defined by the RBAC system.
- **HR**: A user with the `hr` role as defined by the RBAC system.
- **Admin**: A user with the `admin` role as defined by the RBAC system.
- **Super_Admin**: A user with the `super_admin` role as defined by the RBAC system.
- **Privileged_User**: Any user with the `hr`, `admin`, or `super_admin` role.
- **Access_Gate**: The middleware/policy layer from the RBAC system that enforces permission checks on every protected route or action.

---

## Requirements

### Requirement 1: Skill Catalogue Management - Create and Edit

**User Story:** As an HR user, I want to create and edit skills in the catalogue, so that the organisation's competency library stays accurate and up to date.

#### Acceptance Criteria

1. WHILE authenticated as a Privileged_User, THE Skill_Manager SHALL allow creating a new Skill with a unique name, a required Skill_Category, an optional description, and an `is_active` flag that defaults to `true`.
2. WHILE authenticated as a Privileged_User, THE Skill_Manager SHALL allow updating the name, Skill_Category, description, and `is_active` flag of any existing Skill.
3. IF a Skill is created or updated with a name that already exists in the Skill_Catalogue, THEN THE Skill_Manager SHALL reject the request and return a validation error identifying the duplicate name.
4. THE Skill_Manager SHALL enforce that Skill names are between 2 and 100 characters in length.
5. THE Skill_Manager SHALL enforce that a Skill_Category is provided when creating or updating a Skill.
6. IF an Employee attempts to create or edit a Skill, THEN THE Access_Gate SHALL return an HTTP 403 response.

---

### Requirement 2: Skill Catalogue Management - Delete

**User Story:** As an HR user, I want to remove obsolete skills from the catalogue, so that employees are not assigned to skills that are no longer relevant.

#### Acceptance Criteria

1. WHILE authenticated as a Privileged_User, THE Skill_Manager SHALL allow deleting a Skill from the Skill_Catalogue.
2. WHEN a Skill is deleted, THE Skill_Manager SHALL remove all Skill_Assignments associated with that Skill.
3. IF an Employee attempts to delete a Skill, THEN THE Access_Gate SHALL return an HTTP 403 response.

---

### Requirement 3: Skill Catalogue - Listing and Detail Pages

**User Story:** As an HR user, I want to browse the full skill catalogue and view individual skill details, so that I can understand what skills are available and how they are used.

#### Acceptance Criteria

1. THE Skill_Manager SHALL provide a dedicated index page at `/skills` that displays a paginated list of all Skills in the Skill_Catalogue, showing each Skill's name, Skill_Category, description, and `is_active` status.
2. THE Skill_Manager SHALL provide a dedicated detail (show) page for each Skill that displays the Skill's name, Skill_Category, description, `is_active` status, and the list of Employees currently assigned that Skill.
3. WHILE authenticated as a Privileged_User, THE Skill_Manager SHALL display links to create, edit, toggle active status, and delete Skills from the index and detail pages.
4. WHILE authenticated as Employee, THE Skill_Manager SHALL display the index and detail pages in read-only mode, without create, edit, or delete controls.
5. IF an unauthenticated user attempts to access any Skill page, THEN THE Access_Gate SHALL redirect the request to the login page.
6. THE Skill_Manager SHALL allow filtering the Skills index by Skill_Category, returning only Skills belonging to the selected Skill_Category.
7. THE Skill_Manager SHALL allow filtering the Skills index by `is_active` status, returning only active or only inactive Skills as selected.

---

### Requirement 4: Dedicated Pages for Skill CRUD Operations

**User Story:** As an HR user, I want each skill management action to have its own dedicated page, so that the interface is clear and consistent with the rest of the application.

#### Acceptance Criteria

1. THE Skill_Manager SHALL provide a dedicated page for listing all Skills (index) at `/skills`, separate from the create, edit, and detail flows.
2. THE Skill_Manager SHALL provide a dedicated page for creating a new Skill at `/skills/create`, accessible via a link from the index page.
3. THE Skill_Manager SHALL provide a dedicated page for editing an existing Skill at `/skills/{skill}/edit`, accessible via a link from the index and detail pages.
4. THE Skill_Manager SHALL provide a dedicated detail page for viewing a Skill at `/skills/{skill}`, accessible via a link from the index page.
5. THE Skill_Manager SHALL NOT use modal dialogs or inline forms for any Skill create or edit operation.
6. WHEN a Skill creation or edit is completed successfully, THE Skill_Manager SHALL redirect the acting user to the Skill index page.
7. THE Skill_Manager SHALL manage Skill_Assignments (assigning and removing Skills for an Employee) from the employee's profile page, not from the `/skills` pages.

---

### Requirement 5: Employee Self-Assignment of Skills

**User Story:** As an employee, I want to add skills from the catalogue to my own profile, so that my competencies are accurately reflected in the system.

#### Acceptance Criteria

1. WHILE authenticated as Employee, THE Skill_Manager SHALL allow the Employee to add any active Skill from the Skill_Catalogue to their own profile.
2. WHILE authenticated as Employee, THE Skill_Manager SHALL allow the Employee to remove any Skill with Assignment_Source `self` from their own profile.
3. IF an Employee attempts to add a Skill that is already assigned to their profile, THEN THE Skill_Manager SHALL reject the request and return a validation error indicating the Skill is already assigned.
4. IF an Employee attempts to add a Skill whose `is_active` flag is `false`, THEN THE Skill_Manager SHALL reject the request and return a validation error indicating the Skill is not available for assignment.
5. THE Skill_Manager SHALL display the Employee's currently assigned Skills on their profile page, visually distinguishing Skills with Assignment_Source `self` from Skills with Assignment_Source `privileged` using a distinct label or badge for each source.
6. WHEN an Employee adds or removes a Skill from their own profile, THE Skill_Manager SHALL reflect the change immediately on the Employee's profile page.

---

### Requirement 6: Privileged User Assignment of Skills to Employees

**User Story:** As an HR user, I want to assign and remove skills on behalf of any employee, so that I can maintain accurate skill records for the workforce.

#### Acceptance Criteria

1. WHILE authenticated as a Privileged_User, THE Skill_Manager SHALL allow assigning one or more Skills from the Skill_Catalogue to any Employee from that Employee's profile page.
2. WHILE authenticated as a Privileged_User, THE Skill_Manager SHALL allow removing one or more Skills from any Employee's profile from that Employee's profile page.
3. IF a Privileged_User attempts to assign a Skill that is already assigned to the target Employee, THEN THE Skill_Manager SHALL reject the request and return a validation error indicating the Skill is already assigned.
4. WHEN a Privileged_User assigns a Skill to an Employee, THE Skill_Manager SHALL record the Assignment_Source as `privileged`.
5. WHEN a Privileged_User assigns or removes a Skill from an Employee's profile, THE Skill_Manager SHALL reflect the change immediately on the Employee's profile page.
6. IF an Employee attempts to assign or remove a Skill from another user's profile, THEN THE Access_Gate SHALL return an HTTP 403 response.

---

### Requirement 7: Skill Visibility on Employee Profiles

**User Story:** As an HR user, I want to see all skills assigned to an employee on their profile, so that I can quickly assess their competencies.

#### Acceptance Criteria

1. THE Skill_Manager SHALL display all Skills assigned to an Employee on that Employee's profile page, grouped or labelled by Assignment_Source.
2. THE Skill_Manager SHALL render Skills with Assignment_Source `self` with a visual indicator (e.g. a "Self-assigned" badge) that is distinct from the visual indicator used for Skills with Assignment_Source `privileged` (e.g. an "Assigned by HR" badge).
3. WHILE authenticated as a Privileged_User, THE Skill_Manager SHALL display controls to assign additional Skills and remove existing Skills from the Employee's profile page.
4. WHILE authenticated as Employee, THE Skill_Manager SHALL allow the Employee to view their own assigned Skills and manage them via their own profile page only.
5. IF an Employee attempts to view another Employee's skill assignments via a direct URL, THEN THE Access_Gate SHALL return an HTTP 403 response.
6. WHILE authenticated as a Privileged_User, THE Skill_Manager SHALL allow viewing the skill assignments of any Employee on that Employee's profile page.

---

### Requirement 8: Access Control for Skill Management

**User Story:** As a platform administrator, I want skill management permissions to align with the existing role hierarchy, so that access is consistently enforced across the application.

#### Acceptance Criteria

1. THE Access_Gate SHALL restrict Skill creation, editing, deactivation, and deletion to users with the `hr`, `admin`, or `super_admin` role.
2. THE Access_Gate SHALL allow users with the `employee` role to read the Skill_Catalogue (index and detail pages) but not modify it.
3. THE Access_Gate SHALL allow users with the `employee` role to manage Skill_Assignments on their own profile only.
4. THE Access_Gate SHALL allow Privileged_Users to manage Skill_Assignments on any Employee's profile.
5. IF a user without any of the four defined roles attempts to access any Skill route, THEN THE Access_Gate SHALL return an HTTP 403 response.

---

### Requirement 9: Skill Active Status Management

**User Story:** As an HR user, I want to deactivate skills that are temporarily unavailable without deleting them, so that historical assignments are preserved while preventing new assignments to those skills.

#### Acceptance Criteria

1. WHILE authenticated as a Privileged_User, THE Skill_Manager SHALL allow toggling the `is_active` flag of any Skill between `true` and `false`.
2. WHEN a Skill's `is_active` flag is set to `false`, THE Skill_Manager SHALL retain all existing Skill_Assignments for that Skill unchanged.
3. WHEN a Skill's `is_active` flag is set to `false`, THE Skill_Manager SHALL prevent that Skill from appearing in the assignable Skills list on the employee profile page.
4. THE Skill_Manager SHALL visually distinguish inactive Skills from active Skills on the Skill_Catalogue index page (e.g. greyed-out row or "Inactive" badge).
5. IF an Employee attempts to self-assign a Skill whose `is_active` flag is `false`, THEN THE Skill_Manager SHALL reject the request and return a validation error indicating the Skill is not available for assignment.
6. IF a Privileged_User attempts to assign a Skill whose `is_active` flag is `false` to an Employee, THEN THE Skill_Manager SHALL reject the request and return a validation error indicating the Skill is not available for assignment.

---

### Requirement 10: Skill Category Management

**User Story:** As an HR user, I want to organise skills into categories, so that employees and HR can quickly find relevant skills and filter the catalogue by domain.

#### Acceptance Criteria

1. THE Skill_Manager SHALL require every Skill to belong to exactly one Skill_Category at the time of creation.
2. WHILE authenticated as a Privileged_User, THE Skill_Manager SHALL allow creating a new Skill_Category with a unique name of between 2 and 100 characters.
3. WHILE authenticated as a Privileged_User, THE Skill_Manager SHALL allow renaming an existing Skill_Category, with the rename reflected on all Skills belonging to that Skill_Category.
4. IF a Skill_Category is created or renamed to a name that already exists, THEN THE Skill_Manager SHALL reject the request and return a validation error identifying the duplicate name.
5. THE Skill_Manager SHALL display the Skill_Category on each Skill's detail page and on each row of the Skill_Catalogue index page.
6. THE Skill_Manager SHALL provide a filter control on the Skill_Catalogue index page that allows any authenticated user to filter Skills by Skill_Category.
7. IF an Employee attempts to create or rename a Skill_Category, THEN THE Access_Gate SHALL return an HTTP 403 response.
