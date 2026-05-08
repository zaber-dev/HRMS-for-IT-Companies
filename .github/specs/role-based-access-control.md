# Requirements Document

## Introduction

This feature introduces Role-Based Access Control (RBAC) to the platform. The system defines four roles - Super Admin, Admin, HR, and Employee - arranged in a strict hierarchy: Super Admin -> Admin -> HR -> Employee. A single Super Admin account governs the entire permission model: creating and managing users of any role, defining roles, and assigning permissions to those roles. Admins can manage HR and Employee users. HR users can manage Employee users only. Employees have no user management capabilities.

The implementation builds on the existing Laravel Fortify authentication layer and extends the `User` model with role and permission relationships using the `spatie/laravel-permission` package. Public self-registration is disabled; all user accounts are created exclusively by authorised administrators through the administrative interface. Each User CRUD operation (create, view, edit, deactivate) is served by a dedicated page - no modal or inline forms are used for user management.

## Glossary

- **RBAC_System**: The role-based access control subsystem responsible for enforcing permissions across the platform.
- **Super_Admin**: The single highest-privilege user who can manage all roles, permissions, and users.
- **Admin**: A user role with elevated privileges; can manage HR and Employee users. Managed exclusively by Super_Admin.
- **HR**: A user role for human resources personnel; can manage Employee users only. Managed by Super_Admin or Admin.
- **Employee**: A standard user role with the lowest default privilege level. Managed by Super_Admin, Admin, or HR.
- **Role**: A named grouping of permissions that can be assigned to users.
- **Permission**: A named capability that grants access to a specific action or resource.
- **User**: An authenticated account in the system with exactly one assigned Role.
- **Role_Manager**: The component responsible for creating, updating, and deleting roles.
- **Permission_Manager**: The component responsible for assigning and revoking permissions on roles.
- **User_Manager**: The component responsible for creating, updating, deactivating, and assigning roles to users.
- **Access_Gate**: The middleware/policy layer that enforces permission checks on every protected route or action.
- **Audit_Log**: The persistent record of all role, permission, and user-management actions, including the acting user, action type, affected entity, and timestamp.
- **Spatie_Permission**: The `spatie/laravel-permission` package that provides the underlying Role and Permission model infrastructure used by the RBAC_System.

---

## Requirements

### Requirement 1: Role Assignment

**User Story:** As a platform administrator, I want every user account to have exactly one role, so that the system can consistently determine what each user is allowed to do.

#### Acceptance Criteria

1. THE RBAC_System SHALL assign exactly one Role to every User account.
2. WHEN a new User is created, THE User_Manager SHALL require a Role to be specified before the account is saved.
3. IF a User is created without a Role, THEN THE User_Manager SHALL return a validation error identifying the missing role.
4. WHEN a User's Role is changed, THE RBAC_System SHALL apply the new Role's permissions immediately upon the next authenticated request.

---

### Requirement 2: Role Hierarchy and Seeding

**User Story:** As a developer, I want the four platform roles to be seeded automatically, so that the system is ready to use after initial setup without manual configuration.

#### Acceptance Criteria

1. THE RBAC_System SHALL define four built-in roles: `super_admin`, `admin`, `hr`, and `employee`, in descending order of privilege.
2. THE RBAC_System SHALL seed exactly one Super_Admin user account during initial database setup.
3. WHEN the database is seeded, THE RBAC_System SHALL ensure the `super_admin` role exists before any user is created.
4. IF a built-in role is deleted, THEN THE Role_Manager SHALL prevent the deletion and return an error indicating the role is protected.

---

### Requirement 3: Super Admin - User Management

**User Story:** As the Super Admin, I want to create, update, and deactivate user accounts of any role, so that I have full control over who can access the platform.

#### Acceptance Criteria

1. WHILE authenticated as Super_Admin, THE User_Manager SHALL allow creating users with any of the four roles.
2. WHILE authenticated as Super_Admin, THE User_Manager SHALL allow updating the name, email, and role of any User.
3. WHILE authenticated as Super_Admin, THE User_Manager SHALL allow deactivating any User account that is not the Super_Admin account itself.
4. IF the Super_Admin attempts to deactivate their own account, THEN THE User_Manager SHALL reject the request and return an error.
5. WHEN a User account is deactivated, THE Access_Gate SHALL deny all authenticated requests from that User.
6. WHILE authenticated as Super_Admin, THE User_Manager SHALL display a paginated list of all users with their assigned roles.

---

### Requirement 4: Super Admin - Role Management

**User Story:** As the Super Admin, I want to create and manage custom roles beyond the four built-in ones, so that I can tailor access levels to the organisation's needs.

#### Acceptance Criteria

1. WHILE authenticated as Super_Admin, THE Role_Manager SHALL allow creating a new Role with a unique name.
2. WHILE authenticated as Super_Admin, THE Role_Manager SHALL allow updating the name of any non-protected Role.
3. WHILE authenticated as Super_Admin, THE Role_Manager SHALL allow deleting any non-protected Role that has no users assigned to it.
4. IF a Role deletion is attempted while users are assigned to it, THEN THE Role_Manager SHALL reject the deletion and return an error identifying the number of affected users.
5. THE Role_Manager SHALL enforce that Role names are unique across the system.

---

### Requirement 5: Super Admin - Permission Management

**User Story:** As the Super Admin, I want to assign and revoke permissions on roles, so that I can precisely control what each role can do on the platform.

#### Acceptance Criteria

1. WHILE authenticated as Super_Admin, THE Permission_Manager SHALL allow assigning one or more Permissions to a Role.
2. WHILE authenticated as Super_Admin, THE Permission_Manager SHALL allow revoking one or more Permissions from a Role.
3. WHEN a Permission is assigned to or revoked from a Role, THE RBAC_System SHALL apply the change to all Users holding that Role on their next authenticated request.
4. THE Permission_Manager SHALL display the full list of available Permissions alongside the current assignments for a given Role.
5. IF the Super_Admin attempts to modify permissions on the `super_admin` role, THEN THE Permission_Manager SHALL reject the request and return an error indicating the role is protected.

---

### Requirement 6: Access Control Enforcement

**User Story:** As a platform user, I want the system to enforce my role's permissions on every action I attempt, so that I cannot access resources or perform actions outside my authorised scope.

#### Acceptance Criteria

1. WHEN an authenticated User requests a protected route, THE Access_Gate SHALL verify the User's Role has the required Permission before allowing access.
2. IF an authenticated User's Role lacks the required Permission, THEN THE Access_Gate SHALL return an HTTP 403 response.
3. IF an unauthenticated request is made to a protected route, THEN THE Access_Gate SHALL redirect the request to the login page.
4. THE Access_Gate SHALL re-evaluate permissions on every request and SHALL NOT cache permission results beyond the lifetime of a single request.
5. WHILE a User's account is deactivated, THE Access_Gate SHALL deny all requests regardless of the User's Role or Permissions.

---

### Requirement 7: Admin Role Capabilities

**User Story:** As an Admin, I want to manage HR and Employee users and their roles, so that I can handle day-to-day user administration without requiring Super Admin involvement.

#### Acceptance Criteria

1. WHILE authenticated as Admin, THE User_Manager SHALL allow creating users with the `hr` or `employee` role only.
2. WHILE authenticated as Admin, THE User_Manager SHALL allow updating the name, email, and role of users with the `hr` or `employee` role.
3. WHILE authenticated as Admin, THE User_Manager SHALL allow deactivating users with the `hr` or `employee` role.
4. IF an Admin attempts to create, update, deactivate, or change the role of a user with the `super_admin` or `admin` role, THEN THE User_Manager SHALL reject the request and return an HTTP 403 response.
5. WHILE authenticated as Admin, THE User_Manager SHALL display a paginated list of users with the `hr` and `employee` roles only.
6. THE User_Manager SHALL reserve the ability to create, update, deactivate, or change the role of any `admin` account exclusively for the Super_Admin.
7. WHILE authenticated as Admin, THE Role_Manager SHALL allow creating, updating, and deleting non-protected roles that are below the `admin` level in the hierarchy.
8. IF an Admin attempts to create, update, or delete a role at or above the `admin` level, THEN THE Role_Manager SHALL reject the request and return an HTTP 403 response.

---

### Requirement 8: HR Role Capabilities

**User Story:** As an HR user, I want to create, edit, and deactivate Employee accounts, so that I can manage the employee user base without requiring Admin involvement.

#### Acceptance Criteria

1. WHILE authenticated as HR, THE User_Manager SHALL allow creating users with the `employee` role only.
2. WHILE authenticated as HR, THE User_Manager SHALL allow updating the name and email of users with the `employee` role.
3. WHILE authenticated as HR, THE User_Manager SHALL allow deactivating users with the `employee` role.
4. IF an HR user attempts to create, update, or deactivate a user with the `super_admin`, `admin`, or `hr` role, THEN THE User_Manager SHALL reject the request and return an HTTP 403 response.
5. WHILE authenticated as HR, THE User_Manager SHALL display a paginated list of users with the `employee` role only.
6. WHILE authenticated as HR, THE Access_Gate SHALL deny access to all role management and permission management routes.
7. THE RBAC_System SHALL allow HR users to view and update their own profile information.
8. IF an HR user attempts to access a role management or permission management route, THEN THE Access_Gate SHALL return an HTTP 403 response.

---

### Requirement 9: Employee Role Restrictions

**User Story:** As an Employee, I want the system to restrict my access to only the features my role permits, so that sensitive administrative functions remain protected.

#### Acceptance Criteria

1. WHILE authenticated as Employee, THE Access_Gate SHALL deny access to all user management, role management, and permission management routes.
2. THE RBAC_System SHALL allow Employee users to view and update their own profile information.
3. IF an Employee attempts to access any administrative route, THEN THE Access_Gate SHALL return an HTTP 403 response.

---

### Requirement 10: Dedicated Pages for User CRUD Operations

**User Story:** As an administrator, I want each user management action to have its own dedicated page, so that the interface is clear, navigable, and consistent with the rest of the application.

#### Acceptance Criteria

1. THE User_Manager SHALL provide a dedicated page for listing users, separate from the create, edit, and deactivate flows.
2. THE User_Manager SHALL provide a dedicated page for creating a new user, accessible via a link from the user list page.
3. THE User_Manager SHALL provide a dedicated page for editing an existing user, accessible via a link from the user list page.
4. THE User_Manager SHALL provide a dedicated page for reviewing and confirming a user deactivation, accessible via a link from the user list page.
5. THE User_Manager SHALL NOT use modal dialogs or inline forms for any user create, edit, or deactivate operation.
6. WHEN a user creation, edit, or deactivation is completed successfully, THE User_Manager SHALL redirect the acting user to the user list page.

---

### Requirement 11: Audit Trail

**User Story:** As the Super Admin, I want all role, permission, and user-management changes to be logged, so that I can review who made what changes and when.

#### Acceptance Criteria

1. WHEN a Role is created, updated, or deleted, THE RBAC_System SHALL record an Audit_Log entry containing the acting User's ID, the action performed, the affected Role, and a timestamp.
2. WHEN a Permission is assigned to or revoked from a Role, THE RBAC_System SHALL record an Audit_Log entry containing the acting User's ID, the action performed, the affected Role, the affected Permission, and a timestamp.
3. WHEN a User's Role is changed, THE RBAC_System SHALL record an Audit_Log entry containing the acting User's ID, the target User's ID, the previous Role, the new Role, and a timestamp.
4. WHEN a User account is created or deactivated, THE RBAC_System SHALL record an Audit_Log entry containing the acting User's ID, the action performed, the target User's ID, and a timestamp.
5. WHILE authenticated as Super_Admin, THE RBAC_System SHALL provide a paginated view of Audit_Log entries, ordered by timestamp descending.

---

### Requirement 12: Disabled Public Registration

**User Story:** As the Super Admin, I want public self-registration to be disabled, so that only authorised administrators can create user accounts and the user base remains fully controlled.

#### Acceptance Criteria

1. THE RBAC_System SHALL disable the Fortify `registration` feature so that no public registration route is accessible.
2. IF a request is made to the registration route, THEN THE RBAC_System SHALL return an HTTP 404 response.
3. THE User_Manager SHALL allow only users with the `super_admin`, `admin`, or `hr` role to create new user accounts, subject to the hierarchy restrictions defined in Requirements 3, 7, and 8.
4. WHEN a Super_Admin, Admin, or HR user creates a new user account, THE User_Manager SHALL set a temporary password and require the new user to change it on first login.
5. THE RBAC_System SHALL remove any registration link or UI element from the login page.
