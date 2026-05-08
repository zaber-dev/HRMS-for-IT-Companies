# Requirements Document

## Introduction

This feature introduces a role-aware Dashboard Analytics system to the platform. The dashboard serves as the primary landing page after login and presents contextually relevant statistics based on the authenticated user's role.

For HR users and above (Admin, Super Admin), the dashboard provides company-wide analytics: workforce availability, PTO leave trends, employees with high leave usage, employees exceeding task deadlines, project health summaries, and skill coverage gaps. For Employee users, the dashboard presents a personal summary: their own profile stats, pending tasks, assigned projects, leave request history, and their skill profile.

The feature integrates data from four existing systems - Role-Based Access Control, Project Management, PTO/Leave Management, and Employee Skills Management - and enforces the same role hierarchy (`super_admin` -> `admin` -> `hr` -> `employee`) for all data visibility decisions. All data is read-only on the dashboard; no create, edit, or delete operations are performed from the dashboard itself.

---

## Glossary

- **Dashboard**: The primary landing page rendered after a successful login, displaying role-appropriate analytics and statistics.
- **Dashboard_System**: The component responsible for aggregating, computing, and rendering all dashboard data.
- **HR_Dashboard**: The dashboard view rendered for users with the `hr`, `admin`, or `super_admin` role, showing company-wide analytics.
- **Employee_Dashboard**: The dashboard view rendered for users with the `employee` role, showing personal statistics only.
- **PTO_Alert**: A highlighted indicator on the HR_Dashboard identifying an Employee whose approved or pending PTO leave days within the current calendar year meet or exceed the configured high-usage threshold.
- **Deadline_Alert**: A highlighted indicator on the HR_Dashboard identifying an Employee who has one or more Project_Assignments whose `task_deadline` has passed and whose `completion_status` is not `complete`.
- **Workforce_Summary**: An aggregate panel on the HR_Dashboard showing total employee count, count of employees currently `on_bench`, count of employees currently `assigned`, and count of deactivated accounts.
- **Project_Health_Summary**: An aggregate panel on the HR_Dashboard showing the count of Projects grouped by Project_Status (`planning`, `in_progress`, `on_hold`, `completed`, `cancelled`).
- **Leave_Queue_Summary**: An aggregate panel on the HR_Dashboard showing the count of Leave_Requests currently awaiting action at each approval stage relevant to the authenticated user's role.
- **Skill_Coverage_Summary**: An aggregate panel on the HR_Dashboard showing the count of active Projects that have at least one Required_Skill with no Employee currently possessing that skill.
- **Personal_Stats**: The panel on the Employee_Dashboard showing the authenticated employee's name, role, bench status, total assigned skills count, and account creation date.
- **My_Tasks_Panel**: The panel on the Employee_Dashboard listing all Project_Assignments for the authenticated employee with `completion_status` of `pending`, showing project name, task description, task deadline, and days remaining or overdue.
- **My_Projects_Panel**: The panel on the Employee_Dashboard listing all Projects the authenticated employee is currently assigned to, showing project name, status, and deadline.
- **My_Leave_Panel**: The panel on the Employee_Dashboard showing the authenticated employee's leave request summary: total approved days in the current calendar year, count of pending requests, and the most recent Leave_Request with its status.
- **My_Skills_Panel**: The panel on the Employee_Dashboard showing the authenticated employee's assigned skills grouped by Skill_Category.
- **High_PTO_Threshold**: A configurable integer value (default: 14) representing the number of approved or pending PTO leave days in the current calendar year at or above which an Employee is flagged as a PTO_Alert.
- **Privileged_User**: Any user with the `hr`, `admin`, or `super_admin` role, as defined by the RBAC system.
- **Employee**: A user with the `employee` role as defined by the RBAC system.
- **Access_Gate**: The middleware/policy layer from the RBAC system that enforces permission checks on every protected route or action.
- **Leave_Manager**: The PTO/Leave Management component, as defined in the PTO/Leave Management spec.
- **Project_Manager**: The Project Management component, as defined in the Project Management spec.
- **Skill_Manager**: The Employee Skills Management component, as defined in the Employee Skills Management spec.
- **RBAC_System**: The Role-Based Access Control component, as defined in the RBAC spec.

---

## Requirements

### Requirement 1: Role-Appropriate Dashboard Routing

**User Story:** As a user, I want to be directed to a dashboard that is relevant to my role upon login, so that I immediately see the information most useful to me without navigating elsewhere.

#### Acceptance Criteria

1. WHEN an authenticated user navigates to `/dashboard`, THE Dashboard_System SHALL render the HR_Dashboard for users with the `hr`, `admin`, or `super_admin` role.
2. WHEN an authenticated user navigates to `/dashboard`, THE Dashboard_System SHALL render the Employee_Dashboard for users with the `employee` role.
3. IF an unauthenticated user navigates to `/dashboard`, THEN THE Access_Gate SHALL redirect the request to the login page.
4. WHEN a user completes a successful login, THE Dashboard_System SHALL redirect the user to `/dashboard`.
5. THE Dashboard_System SHALL serve the dashboard exclusively at `/dashboard` and SHALL NOT expose separate routes for the HR_Dashboard or Employee_Dashboard.

---

### Requirement 2: HR Dashboard - Workforce Summary

**User Story:** As an HR user, I want to see a high-level summary of the workforce at a glance, so that I can quickly assess headcount and availability without running manual queries.

#### Acceptance Criteria

1. WHILE authenticated as a Privileged_User, THE Dashboard_System SHALL display a Workforce_Summary panel containing: total active employee count, count of employees with Bench_Status `on_bench`, count of employees with Bench_Status `assigned`, and count of deactivated user accounts.
2. THE Dashboard_System SHALL compute the Workforce_Summary using only users with the `employee` role; users with `hr`, `admin`, or `super_admin` roles SHALL NOT be included in the employee counts.
3. THE Dashboard_System SHALL display the Workforce_Summary counts as distinct, labelled metric cards within the panel.
4. WHEN a Privileged_User clicks on the `on_bench` or `assigned` count card, THE Dashboard_System SHALL navigate the user to the user management list page pre-filtered by the corresponding Bench_Status.

---

### Requirement 3: HR Dashboard - PTO Alerts

**User Story:** As an HR user, I want to see which employees have used a high number of PTO days, so that I can proactively manage leave balances and workforce planning.

#### Acceptance Criteria

1. WHILE authenticated as a Privileged_User, THE Dashboard_System SHALL display a PTO_Alert panel listing all Employees whose total approved or `pending_hr` / `pending_admin` / `pending_super_admin` leave days within the current calendar year meet or exceed the High_PTO_Threshold.
2. THE Dashboard_System SHALL compute each Employee's leave day count as the sum of calendar days (inclusive of start and end date) across all Leave_Requests for that Employee in the current calendar year with Leave_Status of `approved`, `pending_hr`, `pending_admin`, or `pending_super_admin`.
3. THE Dashboard_System SHALL display each PTO_Alert entry with the Employee's name, their total leave days in the current year, and the count of currently pending Leave_Requests.
4. THE Dashboard_System SHALL sort PTO_Alert entries in descending order by total leave days.
5. IF no Employees meet or exceed the High_PTO_Threshold, THEN THE Dashboard_System SHALL display an informational message within the PTO_Alert panel indicating no high-usage alerts are present.
6. WHEN a Privileged_User clicks on an Employee's name in the PTO_Alert panel, THE Dashboard_System SHALL navigate to that Employee's leave request listing page.
7. THE Dashboard_System SHALL use a High_PTO_Threshold default value of 14 days.

---

### Requirement 4: HR Dashboard - Deadline Alerts

**User Story:** As an HR user, I want to see which employees have overdue task deadlines, so that I can follow up and take corrective action before project delivery is impacted.

#### Acceptance Criteria

1. WHILE authenticated as a Privileged_User, THE Dashboard_System SHALL display a Deadline_Alert panel listing all Employees who have at least one Project_Assignment whose `task_deadline` is earlier than the current date and whose `completion_status` is `pending`.
2. THE Dashboard_System SHALL display each Deadline_Alert entry with the Employee's name, the Project name, the task description, the `task_deadline`, and the number of days the task is overdue (current date minus `task_deadline`).
3. THE Dashboard_System SHALL sort Deadline_Alert entries in descending order by number of days overdue.
4. IF an Employee has multiple overdue Project_Assignments, THE Dashboard_System SHALL display a separate Deadline_Alert entry for each overdue assignment.
5. IF no Employees have overdue tasks, THEN THE Dashboard_System SHALL display an informational message within the Deadline_Alert panel indicating no overdue tasks are present.
6. WHEN a Privileged_User clicks on a Project name in the Deadline_Alert panel, THE Dashboard_System SHALL navigate to that Project's show page.
7. WHEN a Privileged_User clicks on an Employee's name in the Deadline_Alert panel, THE Dashboard_System SHALL navigate to that Employee's profile page.

---

### Requirement 5: HR Dashboard - Project Health Summary

**User Story:** As an HR user, I want to see the overall health of all projects at a glance, so that I can identify stalled or at-risk initiatives without reviewing each project individually.

#### Acceptance Criteria

1. WHILE authenticated as a Privileged_User, THE Dashboard_System SHALL display a Project_Health_Summary panel showing the count of Projects grouped by each Project_Status value: `planning`, `in_progress`, `on_hold`, `completed`, and `cancelled`.
2. THE Dashboard_System SHALL display each Project_Status group as a labelled metric card with a visually distinct colour or indicator per status.
3. THE Dashboard_System SHALL display the count of Projects whose `deadline` is earlier than the current date and whose Project_Status is not `completed` or `cancelled` as a separate "Overdue Projects" metric within the Project_Health_Summary panel.
4. WHEN a Privileged_User clicks on a Project_Status count card, THE Dashboard_System SHALL navigate to the Project index page pre-filtered by that Project_Status.

---

### Requirement 6: HR Dashboard - Leave Queue Summary

**User Story:** As an HR user, I want to see how many leave requests are awaiting my action, so that I can prioritise my approval workload without visiting the approval queue page first.

#### Acceptance Criteria

1. WHILE authenticated as HR, THE Dashboard_System SHALL display a Leave_Queue_Summary panel showing the count of Leave_Requests with Leave_Status `pending_hr` that the HR user is authorised to act upon.
2. WHILE authenticated as Admin, THE Dashboard_System SHALL display a Leave_Queue_Summary panel showing the count of Leave_Requests with Leave_Status `pending_admin` that the Admin user is authorised to act upon, excluding Admin_Self_Requests submitted by other Admin users.
3. WHILE authenticated as Super_Admin, THE Dashboard_System SHALL display a Leave_Queue_Summary panel showing the count of Leave_Requests with Leave_Status `pending_super_admin`.
4. THE Dashboard_System SHALL display the Leave_Queue_Summary count as a labelled metric card.
5. WHEN a Privileged_User clicks on the Leave_Queue_Summary card, THE Dashboard_System SHALL navigate to the leave approval queue page.

---

### Requirement 7: HR Dashboard - Skill Coverage Summary

**User Story:** As an HR user, I want to see which active projects have skill gaps, so that I can identify hiring or training needs before they block project delivery.

#### Acceptance Criteria

1. WHILE authenticated as a Privileged_User, THE Dashboard_System SHALL display a Skill_Coverage_Summary panel showing the count of Projects with Project_Status `planning` or `in_progress` that have at least one Required_Skill for which no active Employee currently possesses that Skill.
2. THE Dashboard_System SHALL list each such Project in the Skill_Coverage_Summary panel, showing the Project name, its status, and the names of the Required_Skills with no matching Employee.
3. THE Dashboard_System SHALL consider only active Employees (non-deactivated accounts with the `employee` role) when evaluating skill coverage.
4. IF all active Projects have full skill coverage or have no Required_Skills, THEN THE Dashboard_System SHALL display an informational message within the Skill_Coverage_Summary panel indicating no skill gaps are detected.
5. WHEN a Privileged_User clicks on a Project name in the Skill_Coverage_Summary panel, THE Dashboard_System SHALL navigate to that Project's show page.

---

### Requirement 8: Employee Dashboard - Personal Stats Panel

**User Story:** As an employee, I want to see a summary of my own profile at a glance, so that I can quickly review my current status and key details without navigating to my profile page.

#### Acceptance Criteria

1. WHILE authenticated as Employee, THE Dashboard_System SHALL display a Personal_Stats panel containing: the Employee's full name, their assigned role label, their current Bench_Status, the total count of Skills assigned to their profile, and their account creation date.
2. THE Dashboard_System SHALL display the Bench_Status with a visually distinct indicator: a green label for `on_bench` and an amber label for `assigned`.
3. WHEN an Employee clicks on the skills count in the Personal_Stats panel, THE Dashboard_System SHALL navigate to the Employee's own profile page.

---

### Requirement 9: Employee Dashboard - My Tasks Panel

**User Story:** As an employee, I want to see all my pending tasks on the dashboard, so that I can immediately identify what work requires my attention and which deadlines are approaching or overdue.

#### Acceptance Criteria

1. WHILE authenticated as Employee, THE Dashboard_System SHALL display a My_Tasks_Panel listing all Project_Assignments for the authenticated Employee with `completion_status` of `pending`.
2. THE Dashboard_System SHALL display each task entry with: the Project name, the task description, the `task_deadline`, and a computed days-remaining value (positive if the deadline is in the future, negative if overdue).
3. THE Dashboard_System SHALL visually distinguish overdue tasks (negative days-remaining) from upcoming tasks using a distinct colour or label (e.g. a red "Overdue" badge).
4. THE Dashboard_System SHALL sort My_Tasks_Panel entries so that overdue tasks appear first (sorted by most overdue), followed by upcoming tasks sorted by nearest deadline.
5. IF the Employee has no pending tasks, THE Dashboard_System SHALL display an informational message within the My_Tasks_Panel indicating no pending tasks are assigned.
6. WHEN an Employee clicks on a Project name in the My_Tasks_Panel, THE Dashboard_System SHALL navigate to that Project's show page.

---

### Requirement 10: Employee Dashboard - My Projects Panel

**User Story:** As an employee, I want to see all the projects I am currently assigned to on the dashboard, so that I have a quick overview of my active project involvement.

#### Acceptance Criteria

1. WHILE authenticated as Employee, THE Dashboard_System SHALL display a My_Projects_Panel listing all Projects the authenticated Employee is currently assigned to, showing each Project's name, Project_Status, and `deadline`.
2. THE Dashboard_System SHALL display the Project_Status with a visually distinct label per status value.
3. THE Dashboard_System SHALL highlight Projects whose `deadline` is within 7 calendar days of the current date and whose Project_Status is not `completed` or `cancelled` with a visual warning indicator.
4. IF the Employee is not assigned to any Projects, THE Dashboard_System SHALL display an informational message within the My_Projects_Panel indicating no active project assignments exist.
5. WHEN an Employee clicks on a Project name in the My_Projects_Panel, THE Dashboard_System SHALL navigate to that Project's show page.

---

### Requirement 11: Employee Dashboard - My Leave Panel

**User Story:** As an employee, I want to see a summary of my leave usage and pending requests on the dashboard, so that I can track my PTO balance and request status without visiting the leave management pages.

#### Acceptance Criteria

1. WHILE authenticated as Employee, THE Dashboard_System SHALL display a My_Leave_Panel showing: the total count of approved leave days in the current calendar year, the count of currently pending Leave_Requests (any non-terminal, non-cancelled status), and the most recent Leave_Request with its Leave_Status and date range.
2. THE Dashboard_System SHALL compute approved leave days as the sum of calendar days (inclusive of start and end date) across all Leave_Requests for the Employee in the current calendar year with Leave_Status `approved`.
3. THE Dashboard_System SHALL display the Leave_Status of the most recent Leave_Request with a visually distinct label per status value.
4. WHEN an Employee clicks on the pending request count or the most recent Leave_Request entry, THE Dashboard_System SHALL navigate to the Employee's personal leave request index page.

---

### Requirement 12: Employee Dashboard - My Skills Panel

**User Story:** As an employee, I want to see my assigned skills on the dashboard, so that I can quickly review my competency profile without navigating to my profile page.

#### Acceptance Criteria

1. WHILE authenticated as Employee, THE Dashboard_System SHALL display a My_Skills_Panel listing all Skills assigned to the authenticated Employee, grouped by Skill_Category.
2. THE Dashboard_System SHALL display each Skill with its name and a visual indicator of its Assignment_Source (`self` or `privileged`), consistent with the labelling used on the Employee's profile page.
3. IF the Employee has no assigned Skills, THE Dashboard_System SHALL display an informational message within the My_Skills_Panel indicating no skills have been assigned yet.
4. WHEN an Employee clicks on a Skill name in the My_Skills_Panel, THE Dashboard_System SHALL navigate to that Skill's detail page in the Skill_Catalogue.

---

### Requirement 13: Dashboard Data Freshness

**User Story:** As a user, I want the dashboard data to reflect the current state of the system, so that the statistics I see are accurate and actionable.

#### Acceptance Criteria

1. THE Dashboard_System SHALL compute all dashboard statistics at the time of each page request; dashboard data SHALL NOT be served from a stale cache that persists across separate page loads.
2. WHEN a user navigates to `/dashboard`, THE Dashboard_System SHALL load all panels for the authenticated user's role in a single page response.
3. THE Dashboard_System SHALL use Inertia deferred props for panels whose data is computationally expensive (PTO_Alert, Deadline_Alert, Skill_Coverage_Summary), loading them asynchronously after the initial page render with a loading skeleton displayed in their place.

---

### Requirement 14: Access Control for Dashboard

**User Story:** As a platform administrator, I want dashboard access and data visibility to align with the existing role hierarchy, so that employees cannot view company-wide analytics and sensitive workforce data.

#### Acceptance Criteria

1. THE Access_Gate SHALL restrict the HR_Dashboard view (company-wide analytics) to users with the `hr`, `admin`, or `super_admin` role.
2. THE Access_Gate SHALL restrict the Employee_Dashboard view (personal stats) to users with the `employee` role.
3. IF an Employee attempts to access any HR_Dashboard data endpoint directly, THEN THE Access_Gate SHALL return an HTTP 403 response.
4. THE Dashboard_System SHALL NOT expose any API endpoint or Inertia prop that returns company-wide employee data to a user with the `employee` role.
5. WHILE authenticated as Super_Admin or Admin, THE Dashboard_System SHALL include HR users in the Workforce_Summary only as part of the total staff count and SHALL NOT include HR users in the employee-specific PTO_Alert or Deadline_Alert panels.
